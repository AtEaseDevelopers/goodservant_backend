<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Invoice discount: creates the hidden system product the discount line is
 * stored on, and adds the app labels for the "Round down discount" switch.
 */
class AddInvoiceDiscountProductAndTranslations extends Migration
{
    private $translations = [
        'app.createinvoice.text.rounddown' => [
            'en' => 'Round down discount',
            'ms' => 'Diskaun bundar ke bawah',
            'zh' => '抹零折扣',
        ],
        'app.createinvoice.text.subtotal' => [
            'en' => 'Subtotal',
            'ms' => 'Jumlah kecil',
            'zh' => '小计',
        ],
        'app.createinvoice.text.discount' => [
            'en' => 'Discount',
            'ms' => 'Diskaun',
            'zh' => '折扣',
        ],
    ];

    public function up()
    {
        \App\Support\InvoiceDiscount::productId();

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
            DB::table('mobile_translation_versions')->updateOrInsert(
                ['language_id' => $languageId],
                ['version' => 18, 'updated_at' => $now, 'created_at' => $now]
            );
        }
    }

    public function down()
    {
        DB::table('mobile_translations')->whereIn('key', array_keys($this->translations))->delete();
    }
}
