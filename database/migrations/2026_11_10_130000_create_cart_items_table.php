<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCartItemsTable extends Migration
{
    /**
     * Server-side ordering-portal cart: one row per (customer, product). The
     * cart persists across devices/sessions instead of living in localStorage.
     */
    public function up()
    {
        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedInteger('quantity')->default(1);
            $table->timestamps();

            $table->unique(['customer_id', 'product_id']);
            $table->index('customer_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('cart_items');
    }
}
