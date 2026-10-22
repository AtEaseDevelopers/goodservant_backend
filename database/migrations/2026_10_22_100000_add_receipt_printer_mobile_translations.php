<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Messages for printing an invoice straight to the paired Bluetooth printer.
 */
class AddReceiptPrinterMobileTranslations extends Migration
{
    private $translations = [
        'app.pdfview.text.selectprinter' => [
            'en' => 'Select printer',
            'ms' => 'Pilih pencetak',
            'zh' => '选择打印机',
        ],
        'app.pdfview.toast.printed' => [
            'en' => 'Printed',
            'ms' => 'Telah dicetak',
            'zh' => '已打印',
        ],
        'app.pdfview.toast.noprinter' => [
            'en' => 'Pair the printer in Bluetooth settings, then try again',
            'ms' => 'Pasangkan pencetak di tetapan Bluetooth, kemudian cuba lagi',
            'zh' => '请先在蓝牙设置中配对打印机，然后再试',
        ],
        'app.pdfview.toast.connectfailed' => [
            'en' => 'Could not connect to the printer. Turn it on and try again',
            'ms' => 'Tidak dapat sambung ke pencetak. Hidupkan dan cuba lagi',
            'zh' => '无法连接打印机。请开机后再试',
        ],
        'app.pdfview.toast.permission' => [
            'en' => 'Allow Bluetooth so the app can print',
            'ms' => 'Benarkan Bluetooth supaya apl boleh mencetak',
            'zh' => '请允许蓝牙以便打印',
        ],
        'app.pdfview.toast.failed' => [
            'en' => 'Could not print',
            'ms' => 'Tidak dapat mencetak',
            'zh' => '无法打印',
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
