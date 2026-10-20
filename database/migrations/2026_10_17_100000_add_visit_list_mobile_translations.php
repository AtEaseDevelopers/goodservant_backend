<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Wording for unconverted sales orders, and the "use another driver's
 * list for a day" action on the delivery screen.
 */
class AddVisitListMobileTranslations extends Migration
{
    private $translations = [
        'app.customerinfo.text.unconvertedso' => [
            'en' => 'Unconverted sales orders',
            'ms' => 'Pesanan jualan yang belum ditukar',
            'zh' => '未转换销售订单',
        ],
        'app.customerinfo.text.nounconvertedso' => [
            'en' => 'No unconverted sales orders',
            'ms' => 'Tiada pesanan jualan yang belum ditukar',
            'zh' => '没有未转换的销售订单',
        ],
        'app.delivery.button.replacelist' => [
            'en' => "Use another driver's list",
            'ms' => 'Guna senarai pemandu lain',
            'zh' => '使用其他司机的名单',
        ],
        'app.replacelist.text.title' => [
            'en' => "Replace today's list",
            'ms' => 'Ganti senarai hari ini',
            'zh' => '替换今天的名单',
        ],
        'app.replacelist.text.date' => [
            'en' => 'Take the list from this day',
            'ms' => 'Ambil senarai dari hari yang dipilih',
            'zh' => '取这一天的名单',
        ],
        'app.replacelist.toast.done' => [
            'en' => "Today's customer list was replaced",
            'ms' => 'Senarai pelanggan hari ini telah diganti',
            'zh' => '今天的客户名单已替换',
        ],
        'app.replacelist.toast.nodriver' => [
            'en' => 'Select a driver',
            'ms' => 'Pilih pemandu',
            'zh' => '请选择司机',
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
