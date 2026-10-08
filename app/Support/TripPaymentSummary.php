<?php

namespace App\Support;

use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\Trip;

/**
 * Money figures of one trip for the driver app's End Trip screen: what was
 * sold per payment term, and what was actually collected per payment method.
 */
class TripPaymentSummary
{
    /**
     * @param Trip $startTrip the type=1 row of the trip
     * @param string|null $until end of the window (the end trip's date); null = now
     */
    public static function build(Trip $startTrip, $until = null)
    {
        $from = $startTrip->getRawOriginal('date');
        $until = $until ?: date('Y-m-d H:i:s');

        // Same rule as the admin Daily Sales Report: invoices made by converting a
        // DO are paperwork for an earlier delivery, not a sale of this trip.
        $invoices = Invoice::where('trip_id', $startTrip->id)
            ->where('status', 1)
            ->whereDoesntHave('invoicedetail', function ($q) {
                $q->whereNotNull('deliveryorder_id');
            })
            ->with('invoicedetail:id,invoice_id,totalprice')
            ->get();

        $sales = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
        foreach ($invoices as $invoice) {
            $term = (int) $invoice->paymentterm;
            if (array_key_exists($term, $sales)) {
                $sales[$term] += $invoice->invoicedetail->sum('totalprice');
            }
        }

        // Credit that customers paid off during the trip, by how they paid.
        $creditPaid = InvoicePayment::where('driver_id', $startTrip->driver_id)
            ->where('status', 1)
            ->where('created_at', '>=', $from)
            ->where('created_at', '<=', $until)
            ->whereHas('invoice', function ($q) {
                $q->where('paymentterm', 2);
            })
            ->get(['type', 'amount']);

        // Collected = sales settled on the spot (every term except Credit)
        // plus the credit payments received.
        $collected = [1 => 0, 3 => 0, 4 => 0, 5 => 0];
        foreach ($collected as $method => $amount) {
            $collected[$method] = $sales[$method] + $creditPaid->where('type', $method)->sum('amount');
        }

        return [
            'sales' => [
                'cash' => round($sales[1], 2),
                'credit' => round($sales[2], 2),
                'online_banking' => round($sales[3], 2),
                'ewallet' => round($sales[4], 2),
                'cheque' => round($sales[5], 2),
                'total' => round(array_sum($sales), 2),
            ],
            'collected' => [
                'cash' => round($collected[1], 2),
                'online_banking' => round($collected[3], 2),
                'ewallet' => round($collected[4], 2),
                'cheque' => round($collected[5], 2),
                'total' => round(array_sum($collected), 2),
            ],
            'credit_collected' => round($creditPaid->sum('amount'), 2),
            'invoice_count' => $invoices->count(),
        ];
    }
}
