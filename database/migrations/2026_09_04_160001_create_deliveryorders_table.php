<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDeliveryordersTable extends Migration
{
    public function up()
    {
        Schema::create('deliveryorders', function (Blueprint $table) {
            $table->bigInteger('id', true);
            $table->string('dono', 255);
            $table->datetime('date');
            $table->integer('customer_id');
            $table->integer('driver_id')->nullable();
            $table->integer('kelindan_id')->nullable();
            $table->integer('agent_id')->nullable();
            $table->integer('supervisor_id')->nullable();
            $table->integer('paymentterm')->nullable();
            $table->bigInteger('status');
            $table->string('remark', 255)->nullable();
            $table->string('chequeno', 20)->nullable();
            $table->bigInteger('invoice_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('invoice_id')->references('id')->on('invoices')->nullOnDelete();
        });
    }

    public function down()
    {
        Schema::drop('deliveryorders');
    }
}
