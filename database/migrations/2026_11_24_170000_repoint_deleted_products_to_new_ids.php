<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * On 18 Nov 2026 ten products were deleted from the admin Products page and
 * added again with new ids. Invoices, DOs, SOs, stock and trip balances kept
 * the old ids, so the driver app showed "Unknown" or a blank name (e.g. the
 * End Trip Summary). This points every row at the new product.
 *
 * Old -> new was matched on the product code the app last received for the
 * old id (api_logs, 18 Nov 11:16) and the new product's code/name.
 *
 * Only runs where the old product no longer exists and the new one has the
 * expected code, so other databases are left alone.
 */
class RepointDeletedProductsToNewIds extends Migration
{
    private $map = [
        1 => [104, 'Kolo'],      // MEE KOLO - 1KG
        2 => [103, 'M.Telur'],   // MEE TELUR - 1KG
        4 => [99, 'Lurus'],      // MEE KAMPUA LURUS - 1KG
        5 => [102, 'Pok'],       // MEE POK - 1KG
        6 => [101, 'KT(K)'],     // KUEH TEOW - 1KG
        7 => [100, 'KC'],        // KUEH CHAP - 1KG
        10 => [94, '00011'],     // KULIT WANTAN - 400G
        11 => [105, 'Kolo (P)'], // MEE KOLO PREMIUM - 1KG
        25 => [96, '00030'],     // WHEAT FLOUR BAG
        58 => [98, 'K.WT'],      // KULIT WANTAN - 500G
    ];

    /** Plain references: the product id is simply replaced. */
    private $columns = [
        ['invoice_details', 'product_id'],
        ['deliveryorder_details', 'product_id'],
        ['sales_order_details', 'product_id'],
        ['inventory_transactions', 'product_id'],
        ['inventory_transfers', 'product_id'],
        ['cart_items', 'product_id'],
        ['focs', 'product_id'],
        ['focs', 'free_product_id'],
        ['special_prices', 'product_id'],
    ];

    public function up()
    {
        DB::transaction(function () {
            foreach ($this->map as $old => [$new, $code]) {
                if (DB::table('products')->where('id', $old)->exists()
                    || DB::table('products')->where('id', $new)->value('code') !== $code) {
                    continue;
                }

                foreach ($this->columns as [$table, $column]) {
                    if (Schema::hasTable($table) && Schema::hasColumn($table, $column)) {
                        DB::table($table)->where($column, $old)->update([$column => $new]);
                    }
                }

                // Lorry stock: one row per lorry and product - add the old
                // quantity to the new product's row when there is one.
                foreach (DB::table('inventory_balances')->where('product_id', $old)->get() as $row) {
                    $target = DB::table('inventory_balances')->where('lorry_id', $row->lorry_id)->where('product_id', $new)->first();
                    if ($target) {
                        DB::table('inventory_balances')->where('id', $target->id)->update(['quantity' => $target->quantity + $row->quantity]);
                        DB::table('inventory_balances')->where('id', $row->id)->delete();
                    } else {
                        DB::table('inventory_balances')->where('id', $row->id)->update(['product_id' => $new]);
                    }
                }

                // Trip start/end stock: one row per trip, type, lorry and product.
                foreach (DB::table('trip_inventory_balances')->where('product_id', $old)->get() as $row) {
                    $target = DB::table('trip_inventory_balances')->where('trip_id', $row->trip_id)->where('type', $row->type)
                        ->where('lorry_id', $row->lorry_id)->where('product_id', $new)->first();
                    if ($target) {
                        DB::table('trip_inventory_balances')->where('id', $target->id)->update(['quantity' => $target->quantity + $row->quantity]);
                        DB::table('trip_inventory_balances')->where('id', $row->id)->delete();
                    } else {
                        DB::table('trip_inventory_balances')->where('id', $row->id)->update(['product_id' => $new]);
                    }
                }

                // Prices and types: the new product's own setting wins.
                foreach (DB::table('product_prices')->where('product_id', $old)->get() as $row) {
                    $taken = DB::table('product_prices')->where('product_id', $new)
                        ->where('product_price_category_id', $row->product_price_category_id)->exists();
                    if ($taken) {
                        DB::table('product_prices')->where('id', $row->id)->delete();
                    } else {
                        DB::table('product_prices')->where('id', $row->id)->update(['product_id' => $new]);
                    }
                }
                foreach (DB::table('product_product_type')->where('product_id', $old)->pluck('product_type_id') as $typeId) {
                    DB::table('product_product_type')->insertOrIgnore(['product_id' => $new, 'product_type_id' => $typeId]);
                }
                DB::table('product_product_type')->where('product_id', $old)->delete();
            }
        });
    }

    public function down()
    {
        // Not reversible: the old products no longer exist.
    }
}
