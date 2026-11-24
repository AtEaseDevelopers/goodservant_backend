<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Products are now soft deleted (the record stays, deleted_at is set), so old
 * invoices, orders and stock never lose their product again.
 *
 * - A product code only has to be unique among products that are not
 *   deleted: a deleted "Kolo" must not stop the office adding "Kolo" again.
 *   The unique index moves to a generated column that is the code while the
 *   product is live and NULL once it is deleted. The code itself is kept, so
 *   old invoices still send the right item code to AutoCount.
 * - The ten products hard-deleted on 18 Nov 2026 come back as deleted
 *   records with their old ids and codes (taken from what the driver app
 *   last received for them). Their documents were already moved to the new
 *   products by 2026_11_24_170000; this makes any leftover reference (e.g. a
 *   phone that still holds the old product list) resolve to a name.
 */
class SoftDeleteProducts extends Migration
{
    private $restore = [
        ['id' => 1, 'code' => 'Kolo', 'name' => 'MEE KOLO - 1KG', 'price' => 5.5, 'type_id' => 15, 'image_path' => '/product-images/1792816187_00002.png'],
        ['id' => 2, 'code' => 'M.Telur', 'name' => 'MEE TELUR - 1KG', 'price' => 5.5, 'type_id' => 15, 'image_path' => '/product-images/1792816207_00003.png'],
        ['id' => 4, 'code' => 'Lurus', 'name' => 'MEE KAMPUA LURUS - 1KG', 'price' => 4.8, 'type_id' => 15, 'image_path' => '/product-images/1792816240_00005.png'],
        ['id' => 5, 'code' => 'Pok', 'name' => 'MEE POK - 1KG', 'price' => 4.8, 'type_id' => 15, 'image_path' => '/product-images/1792816260_00006.png'],
        ['id' => 6, 'code' => 'KT(K)', 'name' => 'KUEH TEOW - 1KG', 'price' => 3.5, 'type_id' => 15, 'image_path' => '/product-images/1792816273_00007.png'],
        ['id' => 7, 'code' => 'KC', 'name' => 'KUEH CHAP - 1KG', 'price' => 3.5, 'type_id' => 15, 'image_path' => '/product-images/1792816303_00008.png'],
        ['id' => 10, 'code' => 'Inactive', 'name' => 'KULIT WANTAN - 400G', 'price' => 2.8, 'type_id' => 15, 'image_path' => '/product-images/1792816419_00011.png'],
        ['id' => 11, 'code' => 'Kolo (P)', 'name' => 'MEE KOLO PREMIUM - 1KG', 'price' => 6, 'type_id' => 15, 'image_path' => '/product-images/1792816158_00001.png'],
        ['id' => 25, 'code' => '00030', 'name' => 'WHEAT FLOUR BAG', 'price' => 0.3, 'type_id' => 14, 'image_path' => null],
        ['id' => 58, 'code' => 'K.WT', 'name' => 'KULIT WANTAN - 500G', 'price' => 3.5, 'type_id' => 15, 'image_path' => '/product-images/1793864443_kwt.png'],
    ];

    public function up()
    {
        if (!Schema::hasColumn('products', 'live_code')) {
            Schema::table('products', function (Blueprint $table) {
                $table->string('live_code')->nullable()->storedAs('IF(`deleted_at` IS NULL, `code`, NULL)');
            });
            Schema::table('products', function (Blueprint $table) {
                $table->dropUnique('products_code_unique');
                $table->index('code');
                $table->unique('live_code', 'products_live_code_unique');
            });
        }

        // Only on the database those products were deleted from (production:
        // the replacement MEE KOLO is id 104), and only into a free id.
        if (DB::table('products')->where('id', 104)->value('code') !== 'Kolo') {
            return;
        }
        $now = now();
        foreach ($this->restore as $row) {
            if (DB::table('products')->where('id', $row['id'])->exists()) {
                continue;
            }
            DB::table('products')->insert($row + [
                'status' => 0,
                'created_at' => '2026-10-14 18:35:38',
                'updated_at' => $now,
                'deleted_at' => '2026-11-18 17:05:00',
            ]);
        }
    }

    public function down()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique('products_live_code_unique');
            $table->dropIndex(['code']);
            $table->dropColumn('live_code');
        });
    }
}
