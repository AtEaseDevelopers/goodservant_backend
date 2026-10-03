<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Translations for the cancelled-status rollout and the driver UX batch:
 * already-cancelled API errors, the share button on A4 (DO-converted)
 * invoice PDFs, the View SO filters, and the pay-credit invoice picker.
 */
class SyncMobileTranslationsForCancelStatusAndDriverUx extends Migration
{
    private function translations()
    {
        return [
            'api.message.invoice_already_cancelled' => [
                'en' => 'Invoice has already been cancelled',
                'ms' => 'Invois telah pun dibatalkan',
                'zh' => '发票已被取消',
            ],
            'api.message.sales_order_already_cancelled' => [
                'en' => 'Sales Order has already been cancelled',
                'ms' => 'Pesanan Jualan telah pun dibatalkan',
                'zh' => '销售订单已被取消',
            ],
            'api.message.delivery_order_already_cancelled' => [
                'en' => 'Delivery Order has already been cancelled',
                'ms' => 'Pesanan Penghantaran telah pun dibatalkan',
                'zh' => '送货单已被取消',
            ],
            'app.pdfview.button.share' => [
                'en' => 'Share',
                'ms' => 'Kongsi',
                'zh' => '分享',
            ],
            'app.solist.filter.alldates' => [
                'en' => 'All dates',
                'ms' => 'Semua tarikh',
                'zh' => '全部日期',
            ],
            'app.solist.filter.notconverted' => [
                'en' => 'Not converted',
                'ms' => 'Belum ditukar',
                'zh' => '未转换',
            ],
            'app.paycredit.text.payforinvoice' => [
                'en' => 'Pay for invoice',
                'ms' => 'Bayar untuk invois',
                'zh' => '支付发票',
            ],
            'app.paycredit.text.selectinvoice' => [
                'en' => 'Select invoice (optional)',
                'ms' => 'Pilih invois (pilihan)',
                'zh' => '选择发票（可选）',
            ],
        ];
    }

    public function up()
    {
        $now = now();

        foreach (['en', 'ms', 'zh'] as $code) {
            $languageId = DB::table('languages')->where('code', $code)->value('id');
            if (!$languageId) {
                continue;
            }
            foreach ($this->translations() as $key => $values) {
                DB::table('mobile_translations')->updateOrInsert(
                    ['language_id' => $languageId, 'key' => $key],
                    ['value' => $values[$code], 'updated_at' => $now, 'created_at' => $now]
                );
            }
            DB::table('mobile_translation_versions')->updateOrInsert(
                ['language_id' => $languageId],
                ['version' => 14, 'updated_at' => $now, 'created_at' => $now]
            );
        }
    }

    public function down()
    {
        DB::table('mobile_translations')->whereIn('key', array_keys($this->translations()))->delete();
    }
}
