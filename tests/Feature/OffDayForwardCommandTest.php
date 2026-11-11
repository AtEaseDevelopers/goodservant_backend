<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\DeliveryOrder;
use App\Models\Invoice;
use App\Models\SalesOrder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

/**
 * Covers the `offday:forward` console command (App\Console\Commands\ForwardOffDayOrders).
 *
 * An "off day" sales order is placed by the customer but held back from today's
 * delivery. The daily command converts every still-open off-day order dated
 * BEFORE today into a normal (off_day=false) order dated for the new day.
 * Orders that are cancelled (status 2) or already turned into a delivery
 * order / invoice must be left untouched, and an off-day order dated TODAY must
 * not be forwarded yet.
 *
 * Fully self-contained: creates sales_orders rows directly (no customer/driver
 * rows needed). Uses DatabaseTransactions so the configured dev DB (`snoodle`)
 * is rolled back after each test, never rebuilt.
 */
class OffDayForwardCommandTest extends TestCase
{
    use DatabaseTransactions;

    private ?Customer $customer = null;

    /** A real customer row, satisfying the sales_orders FK (created lazily, once). */
    private function customer(): Customer
    {
        if ($this->customer === null) {
            $this->customer = Customer::create([
                'code'        => 'OFFDAY-CUST-' . uniqid(),
                'company'     => 'Off Day Test Customer',
                'paymentterm' => 0,
                'status'      => 1,
            ]);
        }

        return $this->customer;
    }

    /**
     * Insert a sales_orders row directly with explicit columns. `sono` is unique
     * per run so parallel/leftover data can't collide. Returns the fresh model.
     */
    private function makeSalesOrder(array $overrides = []): SalesOrder
    {
        $attributes = array_merge([
            'sono'             => 'OFFDAY-' . uniqid(),
            'date'             => now(),
            'customer_id'      => $this->customer()->id,
            'status'           => 0,
            'is_portal_order'  => true,
            'off_day'          => true,
            'deliveryorder_id' => null,
            'invoice_id'       => null,
        ], $overrides);

        $order = new SalesOrder();
        $order->forceFill($attributes);
        $order->save();

        return $order;
    }

    /** The stored DB date, as a Y-m-d day string (bypasses the d-m-Y accessor). */
    private function storedDay(SalesOrder $order): string
    {
        return Carbon::parse($order->getRawOriginal('date'))->toDateString();
    }

    /** @test */
    public function a_yesterday_dated_open_off_day_order_is_forwarded_to_today(): void
    {
        $order = $this->makeSalesOrder([
            'date'    => now()->subDay(),
            'off_day' => true,
        ]);

        Artisan::call('offday:forward');

        $order->refresh();

        // Flag cleared: it is now a normal, deliverable order.
        $this->assertFalse($order->off_day, 'off_day should be cleared after forwarding');

        // Re-dated to today (date cast is d-m-Y H:i:s, so compare the day only).
        $this->assertSame(now()->toDateString(), $this->storedDay($order));
    }

    /** @test */
    public function an_order_missed_for_several_days_is_still_forwarded(): void
    {
        // "before today" (not strictly yesterday) also recovers a missed run.
        $order = $this->makeSalesOrder([
            'date'    => now()->subDays(5),
            'off_day' => true,
        ]);

        Artisan::call('offday:forward');

        $order->refresh();

        $this->assertFalse($order->off_day);
        $this->assertSame(now()->toDateString(), $this->storedDay($order));
    }

    /** @test */
    public function a_today_dated_off_day_order_is_left_untouched(): void
    {
        // Boundary: whereDate('date', '<', today) must NOT match today's order.
        $order = $this->makeSalesOrder([
            'date'    => now(),
            'off_day' => true,
        ]);

        Artisan::call('offday:forward');

        $order->refresh();

        $this->assertTrue($order->off_day, "today's off-day order must not be forwarded yet");
    }

    /** @test */
    public function a_cancelled_off_day_order_is_left_untouched(): void
    {
        $order = $this->makeSalesOrder([
            'date'    => now()->subDay(),
            'off_day' => true,
            'status'  => 2, // cancelled
        ]);

        Artisan::call('offday:forward');

        $order->refresh();

        $this->assertTrue($order->off_day, 'cancelled off-day order must not be forwarded');
        // Date must be unchanged (still yesterday).
        $this->assertSame(now()->subDay()->toDateString(), $this->storedDay($order));
    }

    /** @test */
    public function an_off_day_order_already_turned_into_a_delivery_order_is_left_untouched(): void
    {
        $do = DeliveryOrder::create([
            'dono'        => 'OFFDAY-DO-' . uniqid(),
            'date'        => now(),
            'customer_id' => $this->customer()->id,
            'status'      => 0,
        ]);

        $order = $this->makeSalesOrder([
            'date'             => now()->subDay(),
            'off_day'          => true,
            'deliveryorder_id' => $do->id,
        ]);

        Artisan::call('offday:forward');

        $order->refresh();

        $this->assertTrue($order->off_day, 'DO-converted off-day order must not be forwarded');
    }

    /** @test */
    public function an_off_day_order_already_invoiced_is_left_untouched(): void
    {
        $invoice = Invoice::create([
            'invoiceno'   => 'OFFDAY-IV-' . uniqid(),
            'date'        => now(),
            'customer_id' => $this->customer()->id,
            'paymentterm' => 0,
            'status'      => 0,
        ]);

        $order = $this->makeSalesOrder([
            'date'       => now()->subDay(),
            'off_day'    => true,
            'invoice_id' => $invoice->id,
        ]);

        Artisan::call('offday:forward');

        $order->refresh();

        $this->assertTrue($order->off_day, 'invoiced off-day order must not be forwarded');
    }

    /** @test */
    public function a_yesterday_dated_normal_order_is_not_affected(): void
    {
        // A non-off-day order dated yesterday must stay off_day=false and NOT be
        // re-dated to today (the command only touches off_day=true rows).
        $order = $this->makeSalesOrder([
            'date'    => now()->subDay(),
            'off_day' => false,
        ]);

        Artisan::call('offday:forward');

        $order->refresh();

        $this->assertFalse($order->off_day);
        $this->assertSame(
            now()->subDay()->toDateString(),
            $this->storedDay($order),
            'a normal yesterday order must keep its original date'
        );
    }
}
