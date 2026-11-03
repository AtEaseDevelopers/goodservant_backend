<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Inventory page: Change Printer, and the receipt prompt that points there.
 */
class AddChangePrinterMobileTranslations extends Migration
{
    private $translations = [
        'app.inventory.button.changeprinter' => [
            'en' => 'Change Printer',
            'ms' => 'Tukar pencetak',
            'zh' => '更换打印机',
        ],
        'app.inventory.text.noprinter' => [
            'en' => 'No printer selected',
            'ms' => 'Tiada pencetak dipilih',
            'zh' => '未选择打印机',
        ],
        'app.inventory.toast.printersaved' => [
            'en' => 'Printer saved',
            'ms' => 'Pencetak disimpan',
            'zh' => '打印机已保存',
        ],
        'app.pdfview.toast.chooseprinter' => [
            'en' => 'Choose a printer on the Inventory page',
            'ms' => 'Pilih pencetak di halaman Inventori',
            'zh' => '请在库存页面选择打印机',
        ],
    ];

    public function up()
    {
        $this->write($this->translations);
    }

    public function down()
    {
        DB::table('mobile_translations')->whereIn('key', array_keys($this->translations))->delete();
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
