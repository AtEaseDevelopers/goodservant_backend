<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Off Day customer: the shop is closed, so nothing is delivered to it.
 * Stays on until the admin (Edit Customer) or a driver (swipe in the app)
 * turns it off. Sales orders made while it is on become Off Day SOs.
 */
class AddOffDayToCustomers extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('customers', 'off_day')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->boolean('off_day')->default(false)->after('is_do_customer');
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('customers', 'off_day')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->dropColumn('off_day');
            });
        }
    }
}
