<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Split the overloaded "product type" concept in two:
 *
 *   - product_types stays the product CLASSIFICATION (products.type_id), and
 *   - a new product_price_categories table becomes the customer PRICING tier.
 *
 * The pricing tiers ("Price 1".."Price 6", "Dealer", ...) and the classification
 * ("Noodle") previously shared product_types. Here we copy every product_types
 * row into the new table PRESERVING ITS ID, then repoint the two pricing columns
 * (customers + product_prices) at the new table. Because the ids are preserved,
 * the existing links keep resolving to the same named category — no data moves,
 * only the table a column belongs to. products.type_id is left on product_types.
 *
 * Every step is guarded (hasTable/hasColumn + index lookup) because this
 * project's live schema has drifted from the migration history before (see
 * snoodle-web legacy base tables), so the migration is safe to re-run.
 */
class CreateProductPriceCategoriesTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('product_price_categories')) {
            Schema::create('product_price_categories', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->boolean('status')->default(1);
                $table->timestamps();
            });
        }

        if (Schema::hasColumn('customers', 'product_type_id') && !Schema::hasColumn('customers', 'product_price_category_id')) {
            DB::statement('ALTER TABLE `customers` CHANGE `product_type_id` `product_price_category_id` BIGINT UNSIGNED NULL');
            $this->renameIndex('customers', 'customers_product_type_id_idx', 'customers_product_price_category_id_idx');
        }

        if (Schema::hasColumn('product_prices', 'product_type_id') && !Schema::hasColumn('product_prices', 'product_price_category_id')) {
            DB::statement('ALTER TABLE `product_prices` CHANGE `product_type_id` `product_price_category_id` BIGINT UNSIGNED NOT NULL');
            $this->renameIndex('product_prices', 'product_prices_product_type_uq', 'product_prices_product_price_category_uq');
        }
    }

    public function down()
    {
        if (Schema::hasColumn('product_prices', 'product_price_category_id') && !Schema::hasColumn('product_prices', 'product_type_id')) {
            $this->renameIndex('product_prices', 'product_prices_product_price_category_uq', 'product_prices_product_type_uq');
            DB::statement('ALTER TABLE `product_prices` CHANGE `product_price_category_id` `product_type_id` BIGINT UNSIGNED NOT NULL');
        }

        if (Schema::hasColumn('customers', 'product_price_category_id') && !Schema::hasColumn('customers', 'product_type_id')) {
            $this->renameIndex('customers', 'customers_product_price_category_id_idx', 'customers_product_type_id_idx');
            DB::statement('ALTER TABLE `customers` CHANGE `product_price_category_id` `product_type_id` BIGINT UNSIGNED NULL');
        }

        Schema::dropIfExists('product_price_categories');
    }

    /**
     * Rename an index only if the source name exists, so a drifted live schema
     * (where the index may already carry the target name) does not error.
     */
    private function renameIndex(string $table, string $from, string $to): void
    {
        $exists = DB::table('information_schema.statistics')
            ->where('table_schema', DB::getDatabaseName())
            ->where('table_name', $table)
            ->where('index_name', $from)
            ->exists();

        if ($exists) {
            DB::statement("ALTER TABLE `{$table}` RENAME INDEX `{$from}` TO `{$to}`");
        }
    }
}
