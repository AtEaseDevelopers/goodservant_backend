<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSequenceToAssignsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Column already exists on databases where it was added out-of-band
        // (never previously tracked by a migration) - guard so this is safe
        // to run everywhere.
        if (!Schema::hasColumn('assigns', 'sequence')) {
            Schema::table('assigns', function (Blueprint $table) {
                $table->unsignedBigInteger('sequence')->default(0)->after('customer_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasColumn('assigns', 'sequence')) {
            Schema::table('assigns', function (Blueprint $table) {
                $table->dropColumn('sequence');
            });
        }
    }
}
