<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Master-data field on each driver: the AutoCount Debtor Types (Debtor.DebtorType
 * values, e.g. "FOOD SERVICE" / "WHOLESALER") this driver is responsible for.
 * Stored as a JSON array because the values are free-form strings. Selecting a
 * type auto-assigns every customer of that type to the driver via the `assigns`
 * table (see App\Services\DebtorTypeAssignmentService).
 *
 * Guarded with hasColumn because this project's live schema has drifted from the
 * migration history (see snoodle-web legacy base tables).
 */
class AddDebtorTypesToDriversTable extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('drivers', 'debtor_types')) {
            Schema::table('drivers', function (Blueprint $table) {
                $table->json('debtor_types')->nullable();
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('drivers', 'debtor_types')) {
            Schema::table('drivers', function (Blueprint $table) {
                $table->dropColumn('debtor_types');
            });
        }
    }
}
