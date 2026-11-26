<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/** Keep this month and last month of the portal activity log (same as api_logs). */
class PruneActivityLogs extends Command
{
    protected $signature = 'CronJob:pruneactivitylogs {--dry-run : Only count the rows that would be deleted}';

    protected $description = 'Delete activity_logs rows older than the start of last month (keeps this month and last month)';

    public function handle()
    {
        // startOfMonth() first so the 31st never overflows into the next month.
        $cutoff = now()->startOfMonth()->subMonth();

        $query = DB::table('activity_logs')->where('created_at', '<', $cutoff);
        if ($this->option('dry-run')) {
            $this->info('Dry run: ' . $query->count() . ' activity_logs rows before ' . $cutoff->toDateTimeString() . ' would be deleted.');
            return 0;
        }

        $deleted = 0;
        do {
            $n = DB::table('activity_logs')->where('created_at', '<', $cutoff)->limit(5000)->delete();
            $deleted += $n;
        } while ($n > 0);

        $this->info("Deleted {$deleted} activity_logs rows before {$cutoff->toDateTimeString()}.");
        return 0;
    }
}
