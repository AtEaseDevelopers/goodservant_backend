<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The `codes` table is a legacy key/value lookup (AutoCount-style, with the
 * generic STR_UDF and INT_UDF extension columns) that the whole app depends on:
 * running numbers (sorunningnumber, dorunningnumber, ...), customer_group
 * options, lorry commission rates, the mobile app version, etc. It pre-existed
 * in the original database but was never captured as a migration, so a fresh
 * `snoodle` database is missing it and Code::nextRunningNumber() crashes with
 * "Base table or view not found: ... Table 'snoodle.codes' doesn't exist".
 *
 * This creates it (guarded, so it is a no-op where the legacy table already
 * exists) and must sort before 2026_09_04_160007_seed_so_do_cashsales_running_numbers,
 * which seeds the running-number rows into it.
 */
class CreateCodesTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('codes')) {
            return;
        }

        Schema::create('codes', function (Blueprint $table) {
            $table->id();
            $table->string('code')->nullable();
            $table->string('description')->nullable();
            $table->string('value')->nullable();
            $table->integer('sequence')->nullable();
            $table->string('STR_UDF1')->nullable();
            $table->string('STR_UDF2')->nullable();
            $table->string('STR_UDF3')->nullable();
            $table->integer('INT_UDF1')->nullable();
            $table->integer('INT_UDF2')->nullable();
            $table->integer('INT_UDF3')->nullable();
            $table->timestamps();

            // Rows are looked up by `code` throughout the app (and more than one
            // row shares a code, e.g. customer_group), so index it but do not
            // make it unique.
            $table->index('code');
        });
    }

    public function down()
    {
        Schema::dropIfExists('codes');
    }
}
