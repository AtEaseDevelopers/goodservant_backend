<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddConvertedTagMobileTranslation extends Migration
{
    public function up()
    {
        $values = [
            'en' => 'Converted',
            'ms' => 'Telah Ditukar',
            'zh' => '已转换',
        ];
        $now = now();

        foreach ($values as $code => $value) {
            $languageId = DB::table('languages')->where('code', $code)->value('id');
            if (!$languageId) {
                continue;
            }
            DB::table('mobile_translations')->updateOrInsert(
                ['language_id' => $languageId, 'key' => 'app.common.text.converted'],
                ['value' => $value, 'updated_at' => $now, 'created_at' => $now]
            );
            DB::table('mobile_translation_versions')->updateOrInsert(
                ['language_id' => $languageId],
                ['version' => 13, 'updated_at' => $now, 'created_at' => $now]
            );
        }
    }

    public function down()
    {
        DB::table('mobile_translations')->where('key', 'app.common.text.converted')->delete();
    }
}
