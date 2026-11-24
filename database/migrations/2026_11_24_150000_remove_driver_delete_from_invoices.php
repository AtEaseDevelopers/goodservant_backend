<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The client dropped "Delete invoice": the driver app keeps only Cancel
 * Invoice (in the invoice page's menu). Takes out what
 * 2026_11_24_120000_add_driver_delete_to_invoices added. No invoice had been
 * deleted on production when this was written.
 */
class RemoveDriverDeleteFromInvoices extends Migration
{
    private $keys = [
        'app.invoicedetails.text.deleteinvoice',
        'app.invoicedetails.dialog.deleteconfirm',
        'app.invoicedetails.text.confirmdelete',
        'app.invoicedetails.toast.deleted',
        'api.message.invoice_deleted_successfully',
        'api.message.invoice_not_current_trip',
    ];

    public function up()
    {
        if (Schema::hasColumn('invoices', 'driver_deleted_at')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->dropColumn(['driver_deleted_at', 'driver_deleted_by']);
            });
        }

        DB::table('mobile_translations')->whereIn('key', $this->keys)->delete();
        $now = now();
        foreach (DB::table('languages')->whereIn('code', ['en', 'ms', 'zh'])->pluck('id') as $languageId) {
            $current = (int) DB::table('mobile_translation_versions')->where('language_id', $languageId)->value('version');
            DB::table('mobile_translation_versions')->updateOrInsert(
                ['language_id' => $languageId],
                ['version' => $current + 1, 'updated_at' => $now, 'created_at' => $now]
            );
        }
    }

    public function down()
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dateTime('driver_deleted_at')->nullable()->after('status');
            $table->unsignedBigInteger('driver_deleted_by')->nullable()->after('driver_deleted_at');
        });
    }
}
