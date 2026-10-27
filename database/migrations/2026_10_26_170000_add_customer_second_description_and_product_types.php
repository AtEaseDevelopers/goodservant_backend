<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Customers gain a second description shown beside the customer name.
 * Products can belong to more than one type. The original type_id stays
 * as the first type so existing screens and AutoCount keep working.
 */
class AddCustomerSecondDescriptionAndProductTypes extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('customers', 'second_description')) {
            $after = Schema::hasColumn('customers', 'company') ? 'company' : 'name';
            Schema::table('customers', function (Blueprint $table) use ($after) {
                $table->string('second_description', 255)->nullable()->after($after);
            });
        }

        if (!Schema::hasTable('product_product_type')) {
            Schema::create('product_product_type', function (Blueprint $table) {
                $table->unsignedBigInteger('product_id');
                $table->unsignedBigInteger('product_type_id');
                $table->primary(['product_id', 'product_type_id']);
            });

            DB::statement('INSERT INTO product_product_type (product_id, product_type_id) SELECT id, type_id FROM products WHERE type_id IS NOT NULL');
        }
    }

    public function down()
    {
        Schema::dropIfExists('product_product_type');

        if (Schema::hasColumn('customers', 'second_description')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->dropColumn('second_description');
            });
        }
    }
}
