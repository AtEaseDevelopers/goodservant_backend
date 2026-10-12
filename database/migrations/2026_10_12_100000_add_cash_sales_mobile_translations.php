<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * App labels for the "Create Cash Sales" shortcut (an invoice paid on the spot).
 */
class AddCashSalesMobileTranslations extends Migration
{
    private $translations = [
        'app.createinvoice.text.cashsales' => [
            'en' => 'Create Cash Sales',
            'ms' => 'Buat Jualan Tunai',
            'zh' => '创建现金销售',
        ],
        'app.home.button.cashsales' => [
            'en' => 'Cash Sales',
            'ms' => 'Jualan Tunai',
            'zh' => '现金销售',
        ],
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
                ['version' => max($current + 1, 19), 'updated_at' => $now, 'created_at' => $now]
            );
        }
    }

    public function down()
    {
        DB::table('mobile_translations')->whereIn('key', array_keys($this->translations))->delete();
    }
}
