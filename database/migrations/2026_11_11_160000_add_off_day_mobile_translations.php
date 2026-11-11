<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Off Day: swipe button on customer lists, Off Day / Normal SO tabs and
 * their convert dialogs, and the note on Create SO.
 */
class AddOffDayMobileTranslations extends Migration
{
    private $translations = [
        'app.offday.button.set' => [
            'en' => "Set Off Day",
            'ms' => "Tetapkan Cuti",
            'zh' => "设为休息日",
        ],
        'app.offday.button.unset' => [
            'en' => "Set Normal",
            'ms' => "Tetapkan Biasa",
            'zh' => "设为正常",
        ],
        'app.offday.toast.set' => [
            'en' => "Marked as Off Day",
            'ms' => "Ditanda sebagai Cuti",
            'zh' => "已设为休息日",
        ],
        'app.offday.toast.unset' => [
            'en' => "Back to Normal",
            'ms' => "Kembali ke Biasa",
            'zh' => "已恢复正常",
        ],
        'app.offday.text.customer' => [
            'en' => "Off Day customer: this SO will be saved as an Off Day SO.",
            'ms' => "Pelanggan Cuti: SO ini akan disimpan sebagai SO Cuti.",
            'zh' => "休息日客户：此SO将保存为休息日SO。",
        ],
        'app.solist.tab.normal' => [
            'en' => "Normal SO",
            'ms' => "SO Biasa",
            'zh' => "正常SO",
        ],
        'app.solist.tab.offday' => [
            'en' => "Off Day SO",
            'ms' => "SO Cuti",
            'zh' => "休息日SO",
        ],
        'app.solist.text.nooffday' => [
            'en' => "No Off Day SO",
            'ms' => "Tiada SO Cuti",
            'zh' => "没有休息日SO",
        ],
        'app.solist.dialog.convertto' => [
            'en' => "Convert the ticked SO to:",
            'ms' => "Tukar SO yang ditanda kepada:",
            'zh' => "将已勾选的SO转换为：",
        ],
        'app.solist.dialog.confirmnormal' => [
            'en' => "Move the ticked Off Day SO to Normal SO?",
            'ms' => "Pindahkan SO Cuti yang ditanda ke SO Biasa?",
            'zh' => "将已勾选的休息日SO转为正常SO？",
        ],
        'app.solist.dialog.confirminvoice' => [
            'en' => "Convert this Off Day SO to an invoice?",
            'ms' => "Tukar SO Cuti ini kepada invois?",
            'zh' => "将此休息日SO转换为发票？",
        ],
        'app.solist.text.invoiceoneonly' => [
            'en' => "To convert to an invoice, tick only 1 SO.",
            'ms' => "Untuk tukar kepada invois, tanda 1 SO sahaja.",
            'zh' => "转换为发票时，只能勾选1张SO。",
        ],
        'app.solist.toast.movednormal' => [
            'en' => "Moved to Normal SO",
            'ms' => "Dipindahkan ke SO Biasa",
            'zh' => "已转为正常SO",
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
