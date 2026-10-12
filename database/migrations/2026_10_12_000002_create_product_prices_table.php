<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One price per (product, product type), synced from AutoCount. Product types
 * double as the pricing category: a line price resolves as the customer's
 * product type -> that product's price for the type, with products.price as the
 * fallback.
 *
 * Guarded with hasTable because this project's live schema has drifted from the
 * migration history before (see snoodle-web legacy base tables).
 */
class CreateProductPricesTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('product_prices')) {
            return;
        }

        Schema::create('product_prices', function (Blueprint $table) {
            $table->bigInteger('id', true);
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('product_type_id');
            $table->float('price', 10, 2);
            $table->timestamps();

            // At most one price per product per type; also the key the sync
            // upsert and the pricing join look rows up by.
            $table->unique(['product_id', 'product_type_id'], 'product_prices_product_type_uq');
        });
    }

    public function down()
    {
        Schema::dropIfExists('product_prices');
    }
}
