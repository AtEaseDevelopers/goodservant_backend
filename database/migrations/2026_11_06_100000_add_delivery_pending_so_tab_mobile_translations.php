<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Delivery screen tabs: all customers / customers with an unconverted
 * sales order.
 */
class AddDeliveryPendingSoTabMobileTranslations extends Migration
{
    private $translations = [
        'app.delivery.tab.all' => [
            'en' => 'All',
            'ms' => 'Semua',
            'zh' => '全部',
        ],
        'app.delivery.tab.pendingso' => [
            'en' => 'Unconverted SO',
            'ms' => 'SO belum ditukar',
            'zh' => '未转换SO',
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
