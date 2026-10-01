<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * `products.code` is the key the AutoCount sync upserts on (ItemCode), but the
 * original table never enforced uniqueness — only the model validation rule did,
 * which the sync bypasses. A unique index makes the DB the source of truth so
 * two concurrent syncs can't create duplicate rows for the same item.
 *
 * NOTE: this fails if the table already holds duplicate codes. Resolve those
 * first (the guard below logs them) before re-running.
 */
class AddUniqueIndexToProductsCode extends Migration
{
    public function up()
    {
        $duplicates = DB::table('products')
            ->select('code', DB::raw('COUNT(*) as total'))
            ->groupBy('code')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('total', 'code');

        if ($duplicates->isNotEmpty()) {
            throw new \RuntimeException(
                'Cannot add unique index: products has duplicate codes -> '
                . $duplicates->keys()->implode(', ')
                . '. Merge/remove the duplicates, then re-run the migration.'
            );
        }

        Schema::table('products', function (Blueprint $table) {
            $table->unique('code');
        });
    }

    public function down()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique(['code']);
        });
    }
}
