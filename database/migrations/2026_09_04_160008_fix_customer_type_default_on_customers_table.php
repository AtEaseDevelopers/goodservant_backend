<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pre-existing bug fix: `customers.customer_type` was NOT NULL with no default
 * and nothing in CustomerController/CreateCustomerRequest/customers.fields ever
 * set it, so creating any NEW customer via the admin UI threw a SQL error
 * ("Field 'customer_type' doesn't have a default value"). Giving it a default
 * empty string unblocks customer creation without touching existing data.
 */
class FixCustomerTypeDefaultOnCustomersTable extends Migration
{
    public function up()
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('customer_type', 255)->default('')->change();
        });
    }

    public function down()
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('customer_type', 255)->change();
        });
    }
}
