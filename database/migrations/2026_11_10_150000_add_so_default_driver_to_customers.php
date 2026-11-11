<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSoDefaultDriverToCustomers extends Migration
{
    /**
     * Optional default driver for a customer. When a Sales Order is created for
     * the customer without a driver, it falls back to this one (see
     * SalesOrder::booted).
     */
    public function up()
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->unsignedBigInteger('so_default_driver_id')->nullable()->after('supervisor_id');
        });
    }

    public function down()
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('so_default_driver_id');
        });
    }
}
