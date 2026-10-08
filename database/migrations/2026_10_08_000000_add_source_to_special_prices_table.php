<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lets the AutoCount plugin own a subset of special_prices without touching
 * hand-entered overrides:
 *   - source: 'manual' (default, existing rows) vs 'autocount' (plugin-synced).
 *   - sync_token: the id of the sync run that last wrote an 'autocount' row, so
 *     a run can prune its own stale rows (customer moved price category / price
 *     no longer special) without deleting manual overrides.
 *
 * Guarded with hasColumn because this project's live schema has drifted from
 * the migration history before (see snoodle-web legacy base tables).
 */
class AddSourceToSpecialPricesTable extends Migration
{
    public function up()
    {
        Schema::table('special_prices', function (Blueprint $table) {
            if (!Schema::hasColumn('special_prices', 'source')) {
                $table->string('source')->default('manual')->after('status');
            }
            if (!Schema::hasColumn('special_prices', 'sync_token')) {
                $table->string('sync_token')->nullable()->after('source');
            }
        });

        // Index the columns the reconciliation sweep filters on. Added separately
        // so a re-run after a partial apply does not fail on a missing column.
        Schema::table('special_prices', function (Blueprint $table) {
            if (Schema::hasColumn('special_prices', 'source')) {
                $table->index(['source', 'sync_token'], 'special_prices_source_token_idx');
            }
        });
    }

    public function down()
    {
        Schema::table('special_prices', function (Blueprint $table) {
            if (Schema::hasColumn('special_prices', 'source')) {
                $table->dropIndex('special_prices_source_token_idx');
                $table->dropColumn('source');
            }
            if (Schema::hasColumn('special_prices', 'sync_token')) {
                $table->dropColumn('sync_token');
            }
        });
    }
}
