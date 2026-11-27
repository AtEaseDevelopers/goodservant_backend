<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Nightly safety net: flag every invoice not yet synced to AutoCount as pending
 * so the plugin picks it up on its next poll. Runs at the admin-configured time
 * (see App\Console\Kernel + the Settings page). Invoices mid-sync (SYNCING) or
 * already synced (SYNCED) are left untouched.
 */
class SyncPendingInvoicesToAutoCount extends Command
{
    protected $signature = 'CronJob:autocountnightlysync {--dry-run : Only count the invoices that would be queued}';

    protected $description = 'Flag all unsynced invoices (none/failed) as pending for AutoCount sync';

    public function handle()
    {
        $eligible = Invoice::whereIn('sync_status', [Invoice::SYNC_NONE, Invoice::SYNC_FAILED]);

        if ($this->option('dry-run')) {
            $count = $eligible->count();
            $this->info("Dry run: {$count} unsynced invoice(s) would be queued for AutoCount sync.");
            return 0;
        }

        $count = $eligible->update([
            'sync_status' => Invoice::SYNC_PENDING,
            'sync_error' => null,
        ]);

        $message = "Nightly AutoCount sync: queued {$count} unsynced invoice(s) as pending.";
        $this->info($message);
        Log::channel('autocount')->info($message);

        return 0;
    }
}
