<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\SalesOrder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

/**
 * Covers Portal\OrderController::removeOffDay() — the customer clearing the
 * "off day" hold on one of their own orders. Removal is explicit and only
 * allowed while the order is still open (status != 2, no delivery order, no
 * invoice). Removal clears off_day and re-dates the order to the release day so
 * it behaves like a fresh order for the driver.
 *
 * Authenticates on the 'customer' session guard; the {order} path segment is
 * Crypt::encrypt($order->id), matching OrderController::findOwnedOrder().
 * Uses DatabaseTransactions so the configured dev DB (`snoodle`) is rolled
 * back after each test, never rebuilt.
 */
class OffDayRemovePortalTest extends TestCase
{
    use DatabaseTransactions;

    private function makeCustomer(): Customer
    {
        return Customer::create([
            'code'        => 'OFFDAY-RM-' . uniqid(),
            'company'     => 'Off Day Remove Customer',
            'paymentterm' => 0,
            'status'      => 1,
        ]);
    }

    /** An off-day sales order for $customer dated in the past, to prove re-dating. */
    private function makeOffDayOrder(Customer $customer, Carbon $date): SalesOrder
    {
        $order = new SalesOrder();
        $order->forceFill([
            'sono'            => 'OFFDAY-RM-' . uniqid(),
            'date'            => $date,
            'customer_id'     => $customer->id,
            'status'          => 0,
            'is_portal_order' => true,
            'off_day'         => true,
        ]);
        $order->save();

        return $order;
    }

    /** The stored (raw, un-cast) order date as a plain Y-m-d day string. */
    private function storedDay(SalesOrder $order): string
    {
        return Carbon::parse($order->fresh()->getRawOriginal('date'))->toDateString();
    }

    /** @test */
    public function removing_the_hold_on_an_open_order_clears_off_day_and_redates_to_today(): void
    {
        $customer = $this->makeCustomer();
        $order = $this->makeOffDayOrder($customer, now()->subDays(3)->startOfDay());

        $response = $this->actingAs($customer, 'customer')
            ->post(route('portal.orders.remove-off-day', Crypt::encrypt($order->id)));

        $response->assertStatus(302);
        $this->assertStringContainsString('/portal/my-orders/', $response->headers->get('Location'));
        $response->assertSessionHas('status');

        $this->assertFalse($order->fresh()->off_day, 'off_day must be cleared');
        $this->assertSame(
            now()->toDateString(),
            $this->storedDay($order),
            'the freed order must be re-dated to the release day'
        );
    }

    /** @test */
    public function an_invoiced_order_is_left_on_hold(): void
    {
        $customer = $this->makeCustomer();
        $invoice = Invoice::create([
            'invoiceno'   => 'OFFDAY-RM-IV-' . uniqid(),
            'date'        => now(),
            'customer_id' => $customer->id,
            'paymentterm' => 0,
            'status'      => 0,
        ]);
        $order = $this->makeOffDayOrder($customer, now()->subDays(2)->startOfDay());
        $order->forceFill(['invoice_id' => $invoice->id])->save();

        $this->actingAs($customer, 'customer')
            ->post(route('portal.orders.remove-off-day', Crypt::encrypt($order->id)));

        $this->assertTrue($order->fresh()->off_day, 'an invoiced order must stay on hold');
    }

    /** @test */
    public function a_cancelled_order_is_left_on_hold(): void
    {
        $customer = $this->makeCustomer();
        $order = $this->makeOffDayOrder($customer, now()->subDays(2)->startOfDay());
        $order->forceFill(['status' => 2])->save();

        $this->actingAs($customer, 'customer')
            ->post(route('portal.orders.remove-off-day', Crypt::encrypt($order->id)));

        $this->assertTrue($order->fresh()->off_day, 'a cancelled order must stay on hold');
    }

    /** @test */
    public function another_customers_order_is_404_and_untouched(): void
    {
        $owner = $this->makeCustomer();
        $other = $this->makeCustomer();
        $order = $this->makeOffDayOrder($owner, now()->subDays(1)->startOfDay());

        $response = $this->actingAs($other, 'customer')
            ->post(route('portal.orders.remove-off-day', Crypt::encrypt($order->id)));

        $response->assertStatus(404);
        $this->assertTrue($order->fresh()->off_day, "another customer's order must be untouched");
    }
}
