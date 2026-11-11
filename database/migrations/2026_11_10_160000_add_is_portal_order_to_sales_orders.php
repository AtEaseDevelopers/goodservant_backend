<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsPortalOrderToSalesOrders extends Migration
{
    /**
     * Marks Sales Orders placed by the customer themselves through the ordering
     * portal (as opposed to admin/mobile-created orders).
     */
    public function up()
    {
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->boolean('is_portal_order')->default(false)->after('status');
        });
    }

    public function down()
    {
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->dropColumn('is_portal_order');
        });
    }
}
