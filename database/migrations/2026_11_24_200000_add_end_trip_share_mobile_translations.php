<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Driver app: after End Trip a pop-up asks whether to share the end trip
 * report (Share opens the report and shares it, No goes back to Home).
 */
class AddEndTripShareMobileTranslations extends Migration
{
    private $translations = [
        'app.endtrip.dialog.share.message' => ['en' => 'Share the end trip report?', 'ms' => 'Kongsi laporan tamat perjalanan?', 'zh' => '要分享结束行程报告吗？'],
        'app.endtrip.dialog.share.yes' => ['en' => 'Share', 'ms' => 'Kongsi', 'zh' => '分享'],
        'app.endtrip.dialog.share.no' => ['en' => 'No', 'ms' => 'Tidak', 'zh' => '不要'],
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
