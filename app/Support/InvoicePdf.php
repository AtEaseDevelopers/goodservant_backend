<?php

namespace App\Support;

use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use Illuminate\Support\Facades\DB;

/**
 * Builds an invoice PDF with the correct layout for how the invoice was made,
 * shared by the admin panel and the driver mobile API so both always render
 * the same document:
 *  - converted from Delivery Order(s) -> full A4 business invoice
 *    (invoices.print_converted) referencing the source D/O numbers;
 *  - created directly or converted from a Sales Order -> narrow receipt
 *    (invoices.print).
 */
class InvoicePdf
{
    public static function render($invoiceId)
    {
        $invoice = Invoice::where('id', $invoiceId)
            ->with('customer')
            ->with('driver')
            ->with('invoicedetail.product')
            ->with('invoicedetail.deliveryorder')
            ->first();

        if (empty($invoice)) {
            return null;
        }

        try {
            $credit = DB::select('call ice_spGetCustomerCreditByDate("'.$invoice->updated_at.'",'.$invoice->customer_id.');');
            $invoice->newcredit = $credit ? round($credit[0]->credit, 2) : 0;
        } catch (Exception $ex) {
            $invoice->newcredit = 0;
        }

        $invoice->customer->groupcompany = DB::table('companies')
            ->where('companies.group_id', explode(',', $invoice->customer->group)[0])
            ->select('companies.*')
            ->first() ?? null;

        $isConvertedFromDo = $invoice->invoicedetail->whereNotNull('deliveryorder_id')->isNotEmpty();
        $options = ['isPhpEnabled' => true, 'isRemoteEnabled' => true];

        if ($isConvertedFromDo) {
            // "Our D/O No." only makes sense when every line traces back to the
            // same source Delivery Order - a combined invoice spans several DOs,
            // so it's left blank instead.
            $sourceDoNumbers = $invoice->invoicedetail->pluck('deliveryorder.dono')->filter()->unique();
            $sourceDoNo = $sourceDoNumbers->count() === 1 ? $sourceDoNumbers->first() : null;

            $pageUsableHeightPt = 565;
            $tableHeaderHeightPt = 20;
            $rowHeightPt = 16;
            $footerHeightPt = 140;
            $contentHeightPt = $tableHeaderHeightPt + (count($invoice['invoicedetail']) * $rowHeightPt) + $footerHeightPt;
            $totalPages = max(1, (int) ceil($contentHeightPt / $pageUsableHeightPt));

            return Pdf::loadView('invoices.print_converted', [
                'invoice' => $invoice,
                'sourceDoNo' => $sourceDoNo,
                'totalPages' => $totalPages,
            ])->setPaper('a4', 'portrait')->setOptions($options);
        }

        $height = (count($invoice['invoicedetail']) * 23) + 450;
        if (!empty($invoice['remark'])) {
            // Room for the "Remark : ..." line at the bottom of the receipt.
            $height += 40;
        }

        return Pdf::loadView('invoices.print', ['invoice' => $invoice])
            ->setPaper([0, 0, 300, $height], 'portrait')
            ->setOptions($options);
    }
}
