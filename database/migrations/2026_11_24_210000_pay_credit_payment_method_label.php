<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Driver app Pay Credit page: the "Payment Details" heading reads
 * "Payment Method" (owner request).
 */
class PayCreditPaymentMethodLabel extends Migration
{
    private $translations = [
        'app.paycredit.text.paymentdetails' => ['en' => 'Payment Method', 'ms' => 'Kaedah Pembayaran', 'zh' => '付款方式'],
    ];

    public function up()
    {
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
        // The old texts were "Payment Details" / "Butiran Pembayaran" / "付款详情".
    }
}
