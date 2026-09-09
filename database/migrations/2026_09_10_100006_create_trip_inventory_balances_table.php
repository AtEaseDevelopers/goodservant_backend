<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTripInventoryBalancesTable extends Migration
{
    public function up()
    {
        Schema::create('trip_inventory_balances', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('trip_id')->nullable();
            $table->integer('driver_id');
            $table->integer('lorry_id');
            $table->integer('product_id');
            $table->integer('quantity');
            $table->tinyInteger('type'); // 1 = start of trip snapshot, 2 = end of trip snapshot
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('trip_inventory_balances');
    }
}
