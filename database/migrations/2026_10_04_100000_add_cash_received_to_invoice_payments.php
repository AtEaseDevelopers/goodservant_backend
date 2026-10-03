<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cash the customer physically handed over for a cash sale. When it is more
 * than the invoice total, the receipt prints it together with the change the
 * driver has to give back. Null for non-cash payments and old rows.
 */
class AddCashReceivedToInvoicePayments extends Migration
{
    public function up()
    {
        Schema::table('invoice_payments', function (Blueprint $table) {
            $table->decimal('cash_received', 10, 2)->nullable()->after('amount');
        });
    }

    public function down()
    {
        Schema::table('invoice_payments', function (Blueprint $table) {
            $table->dropColumn('cash_received');
        });
    }
}
