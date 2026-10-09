<?php

namespace App\Support;

use App\Models\Invoice;
use App\Models\InvoiceDetail;
use App\Models\Product;
use App\Models\ProductType;

/**
 * Invoice discount (e.g. RM 15.40 rounded down to RM 15.00).
 *
 * A discount is stored as one invoice line on a hidden system product with a
 * negative price. Every existing total - credit, payments, reports, the trip
 * summary - is a sum of invoice lines, so they all come out net of the
 * discount without each of them having to know about it.
 */
class InvoiceDiscount
{
    const PRODUCT_CODE = 'DISCOUNT';

    private static $productId = null;

    /** Id of the hidden discount product; created on first use. */
    public static function productId()
    {
        if (self::$productId === null) {
            $product = Product::where('code', self::PRODUCT_CODE)->first();
            if (empty($product)) {
                $product = new Product();
                $product->code = self::PRODUCT_CODE;
                $product->name = 'Discount';
                $product->price = 0;
                // Inactive: never offered in the app's or the admin's product pickers.
                $product->status = 0;
                $product->type_id = (int) ProductType::min('id');
                $product->save();
            }
            self::$productId = (int) $product->id;
        }

        return self::$productId;
    }

    public static function isDiscountProduct($productId)
    {
        return (int) $productId === self::productId();
    }

    /** The discount on an invoice as a positive amount (0 when there is none). */
    public static function amount($invoiceId)
    {
        return round(abs((float) InvoiceDetail::where('invoice_id', $invoiceId)
            ->where('product_id', self::productId())
            ->sum('totalprice')), 2);
    }

    /** Sum of the invoice's lines without the discount. */
    public static function subtotal($invoiceId)
    {
        return round((float) InvoiceDetail::where('invoice_id', $invoiceId)
            ->where('product_id', '!=', self::productId())
            ->sum('totalprice'), 2);
    }

    /**
     * Set (replace) the discount of an invoice. An amount of 0 removes it.
     * The discount can never exceed the invoice's subtotal.
     *
     * @return float the discount now on the invoice
     */
    public static function set($invoiceId, $amount)
    {
        $amount = round((float) $amount, 2);
        InvoiceDetail::where('invoice_id', $invoiceId)->where('product_id', self::productId())->delete();

        if ($amount <= 0) {
            return 0.0;
        }
        $amount = min($amount, self::subtotal($invoiceId));
        if ($amount <= 0) {
            return 0.0;
        }

        $line = new InvoiceDetail();
        $line->invoice_id = $invoiceId;
        $line->product_id = self::productId();
        $line->quantity = 1;
        $line->price = -$amount;
        $line->totalprice = -$amount;
        $line->remark = 'Discount';
        $line->save();

        return $amount;
    }
}
