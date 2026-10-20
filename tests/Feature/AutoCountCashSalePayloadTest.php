<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceDetail;
use App\Models\InvoicePayment;
use App\Models\Product;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Covers the two cash-sale fields AutoCountController::buildInvoicePayload()
 * now adds to each invoice in the POST /api/v1/autocount/invoices/pending
 * response:
 *
 *   - is_cash_sale : true for a `CS…` invoiceno, false for `IV…`
 *     (paymentterm 1 fallback only when no recognised prefix).
 *   - paid_amount  : SUM of APPROVED payments (InvoicePayment.status = 1)
 *     for that invoice, 2dp. Non-approved payments are excluded.
 *
 * The plugin needs these so a CS invoice posts as an AutoCount Cash Sale that
 * is already fully knocked off, while a credit IV invoice posts with its
 * outstanding balance.
 *
 * Uses DatabaseTransactions (not RefreshDatabase): the suite runs against the
 * configured dev DB (`snoodle`) and every change is rolled back, never
 * rebuilt (see snoodle-web CLAUDE.md + the "fresh local migrate misses prod
 * cols" note). Mirrors the field set of InvoiceMarkPendingSyncTest, which is
 * known to insert cleanly against this schema.
 */
class AutoCountCashSalePayloadTest extends TestCase
{
    use DatabaseTransactions;

    private function makeCustomer(int $paymentterm): Customer
    {
        $customer = new Customer();
        // `name` is a legacy NOT NULL column the Customer model does not mark
        // fillable (the controller sets it directly); set it explicitly so the
        // insert does not die on the reconstructed-but-prod-aligned schema.
        $customer->name = 'AC Cash Sale Customer';
        $customer->forceFill([
            'code' => 'CSCUST-' . uniqid(),
            'company' => 'AC Cash Sale Customer',
            'paymentterm' => $paymentterm,
            'agent_id' => 0,
            'supervisor_id' => 0,
            'status' => 1,
        ]);
        $customer->save();

        return $customer;
    }

    private function makeProduct(): Product
    {
        return Product::create([
            'code' => 'CSPROD-' . uniqid(),
            'name' => 'AC Cash Sale Product',
            'price' => 100.00,
            'status' => 1,
            'type' => 0,
        ]);
    }

    private function makeInvoice(Customer $customer, string $invoiceno, int $paymentterm): Invoice
    {
        $invoice = new Invoice();
        // Required legacy NOT NULL columns with no AutoCount/test source.
        $invoice->driver_id = 0;
        $invoice->kelindan_id = 0;
        $invoice->agent_id = 0;
        $invoice->supervisor_id = 0;
        $invoice->remark = '';
        $invoice->forceFill([
            'invoiceno' => $invoiceno,
            'date' => now()->toDateString(),
            'customer_id' => $customer->id,
            'paymentterm' => $paymentterm,
            'status' => 0,
            'sync_status' => Invoice::SYNC_PENDING,
        ]);
        $invoice->save();

        return $invoice;
    }

    private function makeLine(Invoice $invoice, Product $product, float $qty, float $price): InvoiceDetail
    {
        return InvoiceDetail::create([
            'invoice_id' => $invoice->id,
            'product_id' => $product->id,
            'quantity' => $qty,
            'price' => $price,
            'totalprice' => $qty * $price,
            'remark' => '',
        ]);
    }

    private function makePayment(Invoice $invoice, Customer $customer, float $amount, int $status): InvoicePayment
    {
        $payment = new InvoicePayment();
        // Required legacy NOT NULL columns on the reconstructed schema.
        $payment->attachment = '';
        $payment->approve_by = '';
        $payment->approve_at = now();
        $payment->remark = '';
        $payment->forceFill([
            'invoice_id' => $invoice->id,
            'customer_id' => $customer->id,
            'type' => 1,
            'amount' => $amount,
            'status' => $status,
        ]);
        $payment->save();

        return $payment;
    }

    private function pullPending(): \Illuminate\Testing\TestResponse
    {
        return $this->withHeader('X-AutoCount-Token', config('services.autocount.token'))
            ->postJson('/api/v1/autocount/invoices/pending', []);
    }

    /**
     * Index the returned `invoices` array by the `id` field so assertions do
     * not depend on claim order.
     */
    private function byId(array $invoices): array
    {
        $out = [];
        foreach ($invoices as $inv) {
            $out[$inv['id']] = $inv;
        }

        return $out;
    }

    /** @test */
    public function cash_sale_is_flagged_and_sums_only_approved_payments_while_credit_is_not()
    {
        $customer = $this->makeCustomer(1);
        $product = $this->makeProduct();

        // CASH SALE: invoiceno CS…, paymentterm 1, one RM100 line.
        $cash = $this->makeInvoice($customer, 'CS-' . strtoupper(uniqid()), 1);
        $this->makeLine($cash, $product, 1, 100.00);
        // Two approved payments (status 1) summing to the full RM100 total...
        $this->makePayment($cash, $customer, 60.00, 1);
        $this->makePayment($cash, $customer, 40.00, 1);
        // ...plus a NON-approved payment (status 0) that must be excluded. If
        // paid_amount were a blind SUM it would read 150, not 100.
        $this->makePayment($cash, $customer, 50.00, 0);

        // CREDIT: invoiceno IV…, paymentterm 2, a line, NO approved payment.
        $credit = $this->makeInvoice($customer, 'IV-' . strtoupper(uniqid()), 2);
        $this->makeLine($credit, $product, 1, 100.00);

        $response = $this->pullPending();

        $response->assertOk();
        $response->assertJson(['result' => true]);

        $invoices = $this->byId($response->json('invoices'));

        $this->assertArrayHasKey($cash->id, $invoices, 'Cash sale invoice missing from claimed payload.');
        $this->assertArrayHasKey($credit->id, $invoices, 'Credit invoice missing from claimed payload.');

        // Cash sale: flagged, and only the two approved payments are summed.
        // is_cash_sale must be a strict boolean true; paid_amount is compared
        // loosely because JSON serialises 100.0 as 100 (decoded as int).
        $this->assertSame(true, $invoices[$cash->id]['is_cash_sale'], 'CS invoice should be a cash sale (strict bool true).');
        $this->assertEqualsWithDelta(100.0, $invoices[$cash->id]['paid_amount'], 0.001, 'paid_amount must sum only status=1 payments (100), not 150.');

        // Credit: not a cash sale, nothing collected.
        $this->assertSame(false, $invoices[$credit->id]['is_cash_sale'], 'IV invoice should not be a cash sale (strict bool false).');
        $this->assertEqualsWithDelta(0.0, $invoices[$credit->id]['paid_amount'], 0.001, 'Credit invoice with no approved payment must report 0.');
    }
}
