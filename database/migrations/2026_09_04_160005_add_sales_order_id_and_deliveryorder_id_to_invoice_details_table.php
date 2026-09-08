<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSalesOrderIdAndDeliveryorderIdToInvoiceDetailsTable extends Migration
{
    public function up()
    {
        Schema::table('invoice_details', function (Blueprint $table) {
            $table->integer('sales_order_id')->nullable()->after('product_id');
            $table->integer('deliveryorder_id')->nullable()->after('sales_order_id');
        });
    }

    public function down()
    {
        Schema::table('invoice_details', function (Blueprint $table) {
            $table->dropColumn(['sales_order_id', 'deliveryorder_id']);
        });
    }
}
