<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Groups the payment rows created by one multi-invoice payment from the
 * driver app, so the receipt PDF can print them as a single combined receipt.
 */
class AddBatchIdToInvoicePayments extends Migration
{
    public function up()
    {
        Schema::table('invoice_payments', function (Blueprint $table) {
            $table->string('batch_id', 36)->nullable()->after('cash_received')->index();
        });
    }

    public function down()
    {
        Schema::table('invoice_payments', function (Blueprint $table) {
            $table->dropColumn('batch_id');
        });
    }
}
