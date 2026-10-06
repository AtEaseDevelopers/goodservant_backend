<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Translations for the second feedback round: AutoCount invoice lock,
 * multi-invoice credit payments, conversion add-item, and app labels for
 * the invoice change-amount section and pay-credit multi-select.
 */
class SyncMobileTranslationsForMeetingFeedbackRound2 extends Migration
{
    private function translations()
    {
        return [
            'api.message.invoice_synced_locked' => [
                'en' => 'Invoice is locked after syncing to AutoCount and can no longer be changed',
                'ms' => 'Invois telah disegerakkan ke AutoCount dan tidak boleh diubah lagi',
                'zh' => '发票已同步至 AutoCount，无法再更改',
            ],
            'api.message.invalid_invoice_selection' => [
                'en' => 'One or more selected invoices are invalid',
                'ms' => 'Satu atau lebih invois yang dipilih tidak sah',
                'zh' => '所选发票中有无效项',
            ],
            'api.message.no_outstanding_invoices' => [
                'en' => 'The selected invoices have no outstanding amount',
                'ms' => 'Invois yang dipilih tiada baki tertunggak',
                'zh' => '所选发票没有未结余额',
            ],
            'api.message.product_not_found' => [
                'en' => 'Product not found',
                'ms' => 'Produk tidak dijumpai',
                'zh' => '找不到产品',
            ],
            'app.invoicedetails.text.total' => [
                'en' => 'Total',
                'ms' => 'Jumlah',
                'zh' => '总计',
            ],
            'app.invoicedetails.text.cashreceived' => [
                'en' => 'Cash Received',
                'ms' => 'Tunai Diterima',
                'zh' => '收到现金',
            ],
            'app.invoicedetails.text.change' => [
                'en' => 'Change',
                'ms' => 'Baki Pulangan',
                'zh' => '找零',
            ],
            'app.paycredit.text.selectinvoices' => [
                'en' => 'Select invoices (optional)',
                'ms' => 'Pilih invois (pilihan)',
                'zh' => '选择发票（可选）',
            ],
            'app.paycredit.text.invoicesselected' => [
                'en' => '{{1}} invoice(s) selected',
                'ms' => '{{1}} invois dipilih',
                'zh' => '已选择 {{1}} 张发票',
            ],
            'app.paycredit.text.outstanding' => [
                'en' => 'Outstanding',
                'ms' => 'Tertunggak',
                'zh' => '未结余额',
            ],
            'app.convertso.button.additem' => [
                'en' => 'Add Item',
                'ms' => 'Tambah Item',
                'zh' => '添加项目',
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
                ['version' => 15, 'updated_at' => $now, 'created_at' => $now]
            );
        }
    }

    public function down()
    {
        DB::table('mobile_translations')->whereIn('key', array_keys($this->translations()))->delete();
    }
}
