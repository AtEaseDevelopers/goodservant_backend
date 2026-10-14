<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Distinguishes how an `assigns` row was created so the debtor-type automation
 * can maintain its own rows without clobbering hand-made assignments:
 *   - 'manual'      (default, existing rows + admin Assign screen)
 *   - 'debtor_type' (created/removed by App\Services\DebtorTypeAssignmentService
 *                    when a driver's debtor_types or a customer's debtor_type
 *                    changes).
 * The automation only ever touches 'debtor_type' rows.
 *
 * Guarded with hasColumn because this project's live schema has drifted from the
 * migration history (see snoodle-web legacy base tables).
 */
class AddSourceToAssignsTable extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('assigns', 'source')) {
            Schema::table('assigns', function (Blueprint $table) {
                $table->string('source')->default('manual');
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('assigns', 'source')) {
            Schema::table('assigns', function (Blueprint $table) {
                $table->dropColumn('source');
            });
        }
    }
}
