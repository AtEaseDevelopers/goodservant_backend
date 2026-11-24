<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Invoices the office made from delivery orders (Invoice from DOs) or from a
 * sales order were saved as New (status 0), and the driver app showed them as
 * PENDING although the goods had already gone out. They are complete.
 * Invoices waiting for a driver (tasks) stay New.
 */
class CompleteInvoicesMadeFromOrders extends Migration
{
    public function up()
    {
        DB::table('invoices')
            ->where('status', 0)
            ->where(function ($q) {
                $q->whereExists(function ($s) {
                    $s->from('deliveryorders')->whereColumn('deliveryorders.invoice_id', 'invoices.id');
                })->orWhereExists(function ($s) {
                    $s->from('sales_orders')->whereColumn('sales_orders.invoice_id', 'invoices.id');
                });
            })
            ->update(['status' => 1, 'updated_at' => now()]);
    }

    public function down()
    {
    }
}
