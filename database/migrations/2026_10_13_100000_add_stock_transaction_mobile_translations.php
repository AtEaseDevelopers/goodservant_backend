<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * App labels for the reworked Stock Transaction screen (per-product summary
 * and movements listed by invoice).
 */
class AddStockTransactionMobileTranslations extends Migration
{
    private $translations = [
        'app.stocktransaction.text.summary' => [
            'en' => 'Stock Summary',
            'ms' => 'Ringkasan Stok',
            'zh' => '库存汇总',
        ],
        'app.stocktransaction.text.movements' => [
            'en' => 'Movements',
            'ms' => 'Pergerakan',
            'zh' => '库存变动',
        ],
        'app.stocktransaction.text.sold' => [
            'en' => 'Sold',
            'ms' => 'Jual',
            'zh' => '售出',
        ],
        'app.stocktransaction.text.balance' => [
            'en' => 'Balance',
            'ms' => 'Baki',
            'zh' => '结余',
        ],
        'app.stocktransaction.text.nomovement' => [
            'en' => 'No stock movement on this trip yet',
            'ms' => 'Belum ada pergerakan stok dalam perjalanan ini',
            'zh' => '本次行程暂无库存变动',
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
