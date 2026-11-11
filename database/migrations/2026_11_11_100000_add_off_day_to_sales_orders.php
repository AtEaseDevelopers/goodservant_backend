<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOffDayToSalesOrders extends Migration
{
    /**
     * Marks a Sales Order as an "off day" order: the customer placed it but does
     * not want delivery today. The driver sees no order for that customer today;
     * a daily job (offday:forward) converts it to a normal order the next day.
     */
    public function up()
    {
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->boolean('off_day')->default(false)->after('is_portal_order');
        });
    }

    public function down()
    {
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->dropColumn('off_day');
        });
    }
}
