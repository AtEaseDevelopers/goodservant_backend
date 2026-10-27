<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Payment Collection rename, and the sales-order detail buttons.
 */
class UpdateDriverAppMobileTranslations extends Migration
{
    private $translations = [
        'app.customerlist.text.title' => [
            'en' => 'Payment Collection',
            'ms' => 'Kutipan Bayaran',
            'zh' => '收款',
        ],
        'app.sodetails.button.createnew' => [
            'en' => 'Create New SO',
            'ms' => 'Cipta SO baharu',
            'zh' => '新建销售订单',
        ],
        'app.sodetails.button.edititems' => [
            'en' => 'Edit items',
            'ms' => 'Edit item',
            'zh' => '编辑货品',
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
        DB::table('mobile_translations')->whereIn('key', [
            'app.sodetails.button.createnew',
            'app.sodetails.button.edititems',
        ])->delete();
    }
}
