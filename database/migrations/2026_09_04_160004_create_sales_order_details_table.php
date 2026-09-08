<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSalesOrderDetailsTable extends Migration
{
    public function up()
    {
        Schema::create('sales_order_details', function (Blueprint $table) {
            $table->bigInteger('id', true);
            $table->integer('sales_order_id');
            $table->integer('product_id');
            $table->integer('quantity');
            $table->float('price', 10, 2);
            $table->float('totalprice', 10, 2)->default(0);
            $table->string('remark', 255)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::drop('sales_order_details');
    }
}
