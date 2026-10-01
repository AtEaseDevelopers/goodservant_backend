<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * `customers.code` is the key the AutoCount customer sync upserts on (Debtor
 * AccNo). Like products.code it was never unique at the DB level, so a unique
 * index makes concurrent syncs safe from creating duplicate rows.
 *
 * NOTE: fails if the table already holds duplicate codes — the guard lists them
 * so they can be resolved before re-running.
 */
class AddUniqueIndexToCustomersCode extends Migration
{
    public function up()
    {
        $duplicates = DB::table('customers')
            ->select('code', DB::raw('COUNT(*) as total'))
            ->groupBy('code')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('total', 'code');

        if ($duplicates->isNotEmpty()) {
            throw new \RuntimeException(
                'Cannot add unique index: customers has duplicate codes -> '
                . $duplicates->keys()->implode(', ')
                . '. Merge/remove the duplicates, then re-run the migration.'
            );
        }

        Schema::table('customers', function (Blueprint $table) {
            $table->unique('code');
        });
    }

    public function down()
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropUnique(['code']);
        });
    }
}
