<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Success message for driver credit payments (previously reused the
 * invoice-created message).
 */
class AddPaymentRecordedMobileTranslation extends Migration
{
    public function up()
    {
        $values = [
            'en' => 'Payment recorded successfully',
            'ms' => 'Pembayaran berjaya direkodkan',
            'zh' => '付款已成功记录',
        ];
        $now = now();

        foreach ($values as $code => $value) {
            $languageId = DB::table('languages')->where('code', $code)->value('id');
            if (!$languageId) {
                continue;
            }
            DB::table('mobile_translations')->updateOrInsert(
                ['language_id' => $languageId, 'key' => 'api.message.payment_recorded_successfully'],
                ['value' => $value, 'updated_at' => $now, 'created_at' => $now]
            );
            DB::table('mobile_translation_versions')->updateOrInsert(
                ['language_id' => $languageId],
                ['version' => 16, 'updated_at' => $now, 'created_at' => $now]
            );
        }
    }

    public function down()
    {
        DB::table('mobile_translations')->where('key', 'api.message.payment_recorded_successfully')->delete();
    }
}
