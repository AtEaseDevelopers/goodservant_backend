<?php

namespace Tests\Feature;

use App\Models\Customer;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * The AutoCount customer push carries the debtor's credit term (e.g. "30 DAYS",
 * "C.O.D."). The OMS stores it verbatim in `credit_term` and a parsed
 * `credit_term_days`. It is AutoCount-owned: present reflects it (blank clears),
 * absent leaves the stored value alone.
 *
 * The OMS payment-method enum `paymentterm` is DERIVED from the term: a zero-day
 * term (C.O.D. / CASH) is Cash (1); any credit window ("Net 30 days") is
 * Credit (2). A blank/absent term leaves the stored payment method untouched.
 *
 * Uses DatabaseTransactions so the dev DB is rolled back, never rebuilt.
 */
class AutoCountCreditTermSyncTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.autocount.token' => 'test-token', 'services.autocount.dry_run' => false]);
    }

    private function headers(): array
    {
        return ['X-AutoCount-Token' => 'test-token'];
    }

    private function sync(array $customer): void
    {
        $this->postJson('/api/v1/autocount/customers', [
            'customers' => [$customer],
        ], $this->headers())->assertOk()->assertJson(['result' => true]);
    }

    /** @test */
    public function it_stores_the_raw_credit_term_and_parses_the_day_count()
    {
        $code = 'CT-' . uniqid();

        $this->sync(['code' => $code, 'company' => 'Net30 Co', 'credit_term' => '30 DAYS']);

        $customer = Customer::where('code', $code)->firstOrFail();
        $this->assertSame('30 DAYS', $customer->credit_term);
        $this->assertSame(30, $customer->credit_term_days);
        // A credit window maps the payment method to Credit (2).
        $this->assertSame(2, (int) $customer->paymentterm);
    }

    /** @test */
    public function cash_on_delivery_terms_parse_to_zero_days_and_set_cash_payment()
    {
        foreach (['C.O.D.', 'COD', 'CASH'] as $term) {
            $code = 'CT-' . uniqid();

            $this->sync(['code' => $code, 'company' => 'Cash Co', 'credit_term' => $term]);

            $customer = Customer::where('code', $code)->firstOrFail();
            $this->assertSame($term, $customer->credit_term, "raw kept for {$term}");
            $this->assertSame(0, $customer->credit_term_days, "zero days for {$term}");
            // A zero-day term maps the payment method to Cash (1).
            $this->assertSame(1, (int) $customer->paymentterm, "cash payment for {$term}");
        }
    }

    /** @test */
    public function a_non_numeric_term_is_kept_raw_with_null_days_and_credit_payment()
    {
        $code = 'CT-' . uniqid();

        $this->sync(['code' => $code, 'company' => 'Weird Co', 'credit_term' => 'ON ACCOUNT']);

        $customer = Customer::where('code', $code)->firstOrFail();
        $this->assertSame('ON ACCOUNT', $customer->credit_term);
        $this->assertNull($customer->credit_term_days);
        // Not a cash term, so it maps to Credit (2).
        $this->assertSame(2, (int) $customer->paymentterm);
    }

    /** @test */
    public function the_term_flips_the_payment_method_between_cash_and_credit_on_resync()
    {
        $code = 'CT-' . uniqid();

        // Starts as a cash (C.O.D.) customer.
        $this->sync(['code' => $code, 'company' => 'Flip Co', 'credit_term' => 'C.O.D.']);
        $this->assertSame(1, (int) Customer::where('code', $code)->firstOrFail()->paymentterm);

        // Re-synced onto a credit term -> flips to Credit.
        $this->sync(['code' => $code, 'company' => 'Flip Co', 'credit_term' => 'Net 30 days']);
        $this->assertSame(2, (int) Customer::where('code', $code)->firstOrFail()->paymentterm);
    }

    /** @test */
    public function an_absent_credit_term_leaves_the_stored_value_untouched()
    {
        $code = 'CT-' . uniqid();

        $this->sync(['code' => $code, 'company' => 'Keep Co', 'credit_term' => '60 DAYS']);
        // Re-sync without the key at all.
        $this->sync(['code' => $code, 'company' => 'Keep Co']);

        $customer = Customer::where('code', $code)->firstOrFail();
        $this->assertSame('60 DAYS', $customer->credit_term);
        $this->assertSame(60, $customer->credit_term_days);
    }

    /** @test */
    public function a_blank_credit_term_clears_both_columns()
    {
        $code = 'CT-' . uniqid();

        // Start on a cash term so paymentterm is 1, distinct from the default.
        $this->sync(['code' => $code, 'company' => 'Clear Co', 'credit_term' => 'C.O.D.']);
        $this->sync(['code' => $code, 'company' => 'Clear Co', 'credit_term' => '']);

        $customer = Customer::where('code', $code)->firstOrFail();
        $this->assertNull($customer->credit_term);
        $this->assertNull($customer->credit_term_days);
        // A blank term has no mapping, so the payment method is left untouched.
        $this->assertSame(1, (int) $customer->paymentterm);
    }
}
