<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('products', 'sequence')) {
            Schema::table('products', function (Blueprint $table) {
                $table->unsignedInteger('sequence')->default(0)->index();
            });
        }

        // Start from the order products are shown in today (by id).
        $sequence = 0;
        foreach (DB::table('products')->orderBy('id')->pluck('id') as $id) {
            DB::table('products')->where('id', $id)->update(['sequence' => ++$sequence]);
        }
    }

    public function down()
    {
        if (Schema::hasColumn('products', 'sequence')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('sequence');
            });
        }
    }
};
