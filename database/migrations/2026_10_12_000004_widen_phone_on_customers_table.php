<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Widen customers.phone. AutoCount sometimes stores two numbers in one phone
 * field (e.g. "011-10969552, 019-8190174"), which overflowed the old
 * varchar(20) and failed the whole customer-sync upsert with a 1406.
 *
 * Guarded with hasColumn because this project's live schema has drifted from
 * the migration history (see snoodle-web legacy base tables).
 */
class WidenPhoneOnCustomersTable extends Migration
{
    public function up()
    {
        if (Schema::hasColumn('customers', 'phone')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->string('phone', 50)->nullable()->change();
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('customers', 'phone')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->string('phone', 20)->nullable()->change();
            });
        }
    }
}
