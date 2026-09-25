<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInventoryCountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('inventory_counts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('driver_id');
            $table->json('items');
            $table->string('status', 50)->default('pending');
            $table->string('remarks')->nullable();
            $table->string('rejection_reason')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->unsignedBigInteger('trip_id')->nullable();
            $table->unsignedBigInteger('rejected_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('inventory_counts');
    }
}
