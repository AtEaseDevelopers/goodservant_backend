<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class SeedSoDoCashsalesRunningNumbers extends Migration
{
    public function up()
    {
        foreach (['sorunningnumber', 'dorunningnumber', 'cashsalesrunningnumber'] as $code) {
            if (!DB::table('codes')->where('code', $code)->exists()) {
                DB::table('codes')->insert([
                    'code' => $code,
                    'description' => $code,
                    'value' => 0,
                    'sequence' => 1,
                ]);
            }
        }
    }

    public function down()
    {
        DB::table('codes')->whereIn('code', ['sorunningnumber', 'dorunningnumber', 'cashsalesrunningnumber'])->delete();
    }
}
