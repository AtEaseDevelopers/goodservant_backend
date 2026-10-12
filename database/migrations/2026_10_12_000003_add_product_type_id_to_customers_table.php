<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ties a customer to one product type, which acts as their pricing category.
 * Nullable: a customer with no type (or an unknown one) falls back to the
 * product's base price.
 *
 * Guarded with hasColumn because this project's live schema has drifted from the
 * migration history before (see snoodle-web legacy base tables).
 */
class AddProductTypeIdToCustomersTable extends Migration
{
    public function up()
    {
        Schema::table('customers', function (Blueprint $table) {
            if (!Schema::hasColumn('customers', 'product_type_id')) {
                $table->unsignedBigInteger('product_type_id')->nullable()->after('status');
                $table->index('product_type_id', 'customers_product_type_id_idx');
            }
        });
    }

    public function down()
    {
        Schema::table('customers', function (Blueprint $table) {
            if (Schema::hasColumn('customers', 'product_type_id')) {
                $table->dropIndex('customers_product_type_id_idx');
                $table->dropColumn('product_type_id');
            }
        });
    }
}
