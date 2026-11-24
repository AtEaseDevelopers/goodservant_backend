<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * "Delete invoice" in the driver app. A deleted invoice is handled like a
 * cancelled one (status 2: stock back to the lorry, payments voided, left out
 * of every report and the credit), and is also marked as deleted by the
 * driver: the app no longer lists it, the admin portal shows it as Deleted.
 */
class AddDriverDeleteToInvoices extends Migration
{
    private $translations = [
        'app.invoicedetails.text.deleteinvoice' => ['en' => 'Delete Invoice', 'ms' => 'Padam Invois', 'zh' => '删除发票'],
        'app.invoicedetails.dialog.deleteconfirm' => ['en' => 'Delete this invoice? The stock goes back to the lorry and the invoice no longer shows in the app. The office can still see it as Deleted.', 'ms' => 'Padam invois ini? Stok dikembalikan ke lori dan invois tidak lagi dipaparkan dalam aplikasi. Pejabat masih boleh melihatnya sebagai Dipadam.', 'zh' => '确定删除这张发票？库存会退回货车，发票不再显示在应用中。办公室仍可看到它（已删除）。'],
        'app.invoicedetails.text.confirmdelete' => ['en' => 'Delete', 'ms' => 'Padam', 'zh' => '删除'],
        'app.invoicedetails.toast.deleted' => ['en' => 'Invoice deleted successfully', 'ms' => 'Invois berjaya dipadam', 'zh' => '发票已成功删除'],
        'api.message.invoice_deleted_successfully' => ['en' => 'Invoice deleted successfully', 'ms' => 'Invois berjaya dipadam', 'zh' => '发票已成功删除'],
        'api.message.invoice_cancelled_successfully' => ['en' => 'Invoice cancelled successfully', 'ms' => 'Invois berjaya dibatalkan', 'zh' => '发票已成功取消'],
        'api.message.invoice_not_current_trip' => ['en' => 'Only invoices of your current trip can be deleted.', 'ms' => 'Hanya invois perjalanan semasa anda boleh dipadam.', 'zh' => '只能删除您当前行程的发票。'],
    ];

    public function up()
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dateTime('driver_deleted_at')->nullable()->after('status');
            $table->unsignedBigInteger('driver_deleted_by')->nullable()->after('driver_deleted_at');
        });

        $now = now();
        foreach (['en', 'ms', 'zh'] as $code) {
            $languageId = DB::table('languages')->where('code', $code)->value('id');
            if (!$languageId) {
                continue;
            }
            foreach ($this->translations as $key => $values) {
                DB::table('mobile_translations')->updateOrInsert(
                    ['language_id' => $languageId, 'key' => $key],
                    ['value' => $values[$code], 'updated_at' => $now, 'created_at' => $now]
                );
            }
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
            $table->dropColumn(['driver_deleted_at', 'driver_deleted_by']);
        });
        DB::table('mobile_translations')->whereIn('key', array_keys($this->translations))->delete();
    }
}
