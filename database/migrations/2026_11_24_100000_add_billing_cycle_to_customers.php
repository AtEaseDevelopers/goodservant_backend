<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * How a DO customer is billed: null (each DO converted on its own, as
 * before), "weekly" or "monthly". Delivery Orders -> Billing Convert turns
 * all pending DOs of the weekly or monthly customers into one invoice each.
 */
class AddBillingCycleToCustomers extends Migration
{
    public function up()
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('billing_cycle', 10)->nullable()->after('is_do_customer');
        });
    }

    public function down()
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('billing_cycle');
        });
    }
}
