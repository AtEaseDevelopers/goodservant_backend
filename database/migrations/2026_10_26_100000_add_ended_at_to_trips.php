<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A trip is one row. date is when it started; ended_at is when it finished.
 * Older trips still use a second type=2 row and leave ended_at empty.
 */
class AddEndedAtToTrips extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('trips', 'ended_at')) {
            Schema::table('trips', function (Blueprint $table) {
                $table->dateTime('ended_at')->nullable()->after('date');
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('trips', 'ended_at')) {
            Schema::table('trips', function (Blueprint $table) {
                $table->dropColumn('ended_at');
            });
        }
    }
}
