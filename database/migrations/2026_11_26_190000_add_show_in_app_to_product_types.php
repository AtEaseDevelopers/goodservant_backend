<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Product Types: "Show in driver app". A type that is switched off stays on
 * its products in the admin portal but is not sent to the driver app, so it
 * is not a filter tab there. "2ND HAND" starts switched off (owner request).
 */
class AddShowInAppToProductTypes extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('product_types', 'show_in_app')) {
            Schema::table('product_types', function (Blueprint $table) {
                $table->boolean('show_in_app')->default(true)->after('status');
            });
        }
        DB::table('product_types')->whereRaw('UPPER(TRIM(name)) = ?', ['2ND HAND'])->update(['show_in_app' => false]);
    }

    public function down()
    {
        Schema::table('product_types', function (Blueprint $table) {
            $table->dropColumn('show_in_app');
        });
    }
}
