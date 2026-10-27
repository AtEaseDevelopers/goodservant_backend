<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Connected / not connected line on the invoice print page.
 */
class AddPrinterStatusMobileTranslations extends Migration
{
    private $translations = [
        'app.pdfview.text.connected' => [
            'en' => 'Printer connected',
            'ms' => 'Pencetak disambung',
            'zh' => '打印机已连接',
        ],
        'app.pdfview.text.notconnected' => [
            'en' => 'Printer not connected',
            'ms' => 'Pencetak tidak disambung',
            'zh' => '打印机未连接',
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
                ['version' => $current + 1, 'updated_at' => $now, 'created_at' => $now]
            );
        }
    }

    public function down()
    {
        DB::table('mobile_translations')->whereIn('key', array_keys($this->translations))->delete();
    }
}
