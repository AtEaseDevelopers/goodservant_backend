<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * AutoCount invoice sync tracking.
 *
 * Flow: a user marks an invoice "pending sync" (sync_status = 1); the AutoCount
 * plugin polls every 30s, claims pending rows (-> 2 syncing) so a slow poll
 * can't double-create, builds a DRAFT Sales Invoice, and writes the AutoCount
 * doc no back (-> 3 synced) or the error (-> 4 failed).
 *
 * sync_status: 0 none | 1 pending | 2 syncing | 3 synced | 4 failed
 */
class AddAutoCountSyncToInvoicesTable extends Migration
{
    public function up()
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->tinyInteger('sync_status')->default(0)->after('status');
            $table->string('api_invoice_id')->nullable()->after('sync_status'); // AutoCount DocNo
            $table->text('sync_error')->nullable()->after('api_invoice_id');
            $table->timestamp('synced_at')->nullable()->after('sync_error');

            $table->index('sync_status');
        });
    }

    public function down()
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex(['sync_status']);
            $table->dropColumn(['sync_status', 'api_invoice_id', 'sync_error', 'synced_at']);
        });
    }
}
