<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTripIdToDeliveryordersTable extends Migration
{
    public function up()
    {
        Schema::table('deliveryorders', function (Blueprint $table) {
            $table->integer('trip_id')->nullable()->after('driver_id');
        });
    }

    public function down()
    {
        Schema::table('deliveryorders', function (Blueprint $table) {
            $table->dropColumn('trip_id');
        });
    }
}
