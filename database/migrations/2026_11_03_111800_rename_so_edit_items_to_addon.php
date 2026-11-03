<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Sales-order detail button: "Edit items" is now "Add On".
 */
class RenameSoEditItemsToAddon extends Migration
{
    private $translations = [
        'app.sodetails.button.edititems' => [
            'en' => 'Add On',
            'ms' => 'Tambahan',
            'zh' => '追加',
        ],
    ];

    private $previous = [
        'en' => 'Edit items',
        'ms' => 'Edit item',
        'zh' => '编辑货品',
    ];

    public function up()
    {
        $this->write($this->translations);
    }

    public function down()
    {
        $restored = [];
        foreach ($this->previous as $code => $value) {
            $restored['app.sodetails.button.edititems'][$code] = $value;
        }
        $this->write($restored);
    }

    private function write(array $translations)
    {
        $now = now();
        foreach (['en', 'ms', 'zh'] as $code) {
            $languageId = DB::table('languages')->where('code', $code)->value('id');
            if (!$languageId) {
                continue;
            }
            foreach ($translations as $key => $values) {
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
}
