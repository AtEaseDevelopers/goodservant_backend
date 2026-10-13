<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Store AutoCount's Debtor Type (the Debtor.DebtorType classification) on each
 * customer. Pushed from the AutoCount plugin via POST /api/v1/autocount/customers
 * and shown read-only in the admin. AutoCount-owned.
 *
 * Guarded with hasColumn because this project's live schema has drifted from the
 * migration history (see snoodle-web legacy base tables).
 */
class AddDebtorTypeToCustomersTable extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('customers', 'debtor_type')) {
            Schema::table('customers', function (Blueprint $table) {
                // No ->after(): the e-invoice columns it would anchor to are
                // prod-only drift and may not exist on a fresh local DB.
                $table->string('debtor_type', 255)->nullable();
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('customers', 'debtor_type')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->dropColumn('debtor_type');
            });
        }
    }
}
