<?php

namespace Tests\Feature;

use App\Models\Customer;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * The AutoCount customer push carries the debtor's credit term (e.g. "30 DAYS",
 * "C.O.D."). The OMS stores it verbatim in `credit_term` and a parsed
 * `credit_term_days` for any date-driven use later. It is AutoCount-owned:
 * present reflects it (blank clears), absent leaves the stored value alone.
 *
 * It is distinct from `paymentterm` (a Cash/Credit payment-method enum), which
 * stays at its synced default and is never touched by the credit term.
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
        // The payment-method enum is untouched by the credit term.
        $this->assertSame(2, (int) $customer->paymentterm);
    }

    /** @test */
    public function cash_on_delivery_terms_parse_to_zero_days()
    {
        foreach (['C.O.D.', 'COD', 'CASH'] as $term) {
            $code = 'CT-' . uniqid();

            $this->sync(['code' => $code, 'company' => 'Cash Co', 'credit_term' => $term]);

            $customer = Customer::where('code', $code)->firstOrFail();
            $this->assertSame($term, $customer->credit_term, "raw kept for {$term}");
            $this->assertSame(0, $customer->credit_term_days, "zero days for {$term}");
        }
    }

    /** @test */
    public function a_non_numeric_term_is_kept_raw_with_null_days()
    {
        $code = 'CT-' . uniqid();

        $this->sync(['code' => $code, 'company' => 'Weird Co', 'credit_term' => 'ON ACCOUNT']);

        $customer = Customer::where('code', $code)->firstOrFail();
        $this->assertSame('ON ACCOUNT', $customer->credit_term);
        $this->assertNull($customer->credit_term_days);
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

        $this->sync(['code' => $code, 'company' => 'Clear Co', 'credit_term' => '90 DAYS']);
        $this->sync(['code' => $code, 'company' => 'Clear Co', 'credit_term' => '']);

        $customer = Customer::where('code', $code)->firstOrFail();
        $this->assertNull($customer->credit_term);
        $this->assertNull($customer->credit_term_days);
    }
}
