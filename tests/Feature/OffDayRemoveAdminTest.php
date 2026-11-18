<?php

namespace Tests\Feature;

use App\Http\Controllers\SalesOrderController;
use App\Models\Customer;
use App\Models\DeliveryOrder;
use App\Models\Invoice;
use App\Models\SalesOrder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

/**
 * Covers SalesOrderController::removeOffDay() — an admin clearing the "off day"
 * hold on a Sales Order. The HTTP route sits behind web-session + permission
 * middleware which is heavy to stand up here; the business rule under test is
 * the state transition itself, so we invoke the controller method directly
 * (resolved through the container so its SalesOrderRepository dependency is
 * injected).
 *
 * The rule: clear off_day only while the order is still open (status != 2, no
 * delivery order, no invoice); the freed order is re-dated to the release day.
 * Uses DatabaseTransactions so the configured dev DB (`snoodle`) is rolled
 * back after each test.
 */
class OffDayRemoveAdminTest extends TestCase
{
    use DatabaseTransactions;

    private function controller(): SalesOrderController
    {
        return $this->app->make(SalesOrderController::class);
    }

    private function makeCustomer(): Customer
    {
        return Customer::create([
            'code'        => 'OFFDAY-ADM-' . uniqid(),
            'company'     => 'Off Day Admin Customer',
            'paymentterm' => 0,
            'status'      => 1,
        ]);
    }

    private function makeOffDayOrder(Customer $customer, Carbon $date): SalesOrder
    {
        $order = new SalesOrder();
        $order->forceFill([
            'sono'        => 'OFFDAY-ADM-' . uniqid(),
            'date'        => $date,
            'customer_id' => $customer->id,
            'status'      => 0,
            'off_day'     => true,
        ]);
        $order->save();

        return $order;
    }

    private function storedDay(SalesOrder $order): string
    {
        return Carbon::parse($order->fresh()->getRawOriginal('date'))->toDateString();
    }

    /** @test */
    public function removing_the_hold_on_an_open_order_clears_off_day_and_redates_to_today(): void
    {
        $customer = $this->makeCustomer();
        $order = $this->makeOffDayOrder($customer, now()->subDays(3)->startOfDay());

        $this->controller()->removeOffDay(Crypt::encrypt($order->id));

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
            'invoiceno'   => 'OFFDAY-ADM-IV-' . uniqid(),
            'date'        => now(),
            'customer_id' => $customer->id,
            'paymentterm' => 0,
            'status'      => 0,
        ]);
        $order = $this->makeOffDayOrder($customer, now()->subDays(2)->startOfDay());
        $order->forceFill(['invoice_id' => $invoice->id])->save();

        $this->controller()->removeOffDay(Crypt::encrypt($order->id));

        $this->assertTrue($order->fresh()->off_day, 'an invoiced order must stay on hold');
    }

    /** @test */
    public function a_cancelled_order_is_left_on_hold(): void
    {
        $customer = $this->makeCustomer();
        $order = $this->makeOffDayOrder($customer, now()->subDays(2)->startOfDay());
        $order->forceFill(['status' => 2])->save();

        $this->controller()->removeOffDay(Crypt::encrypt($order->id));

        $this->assertTrue($order->fresh()->off_day, 'a cancelled order must stay on hold');
    }

    /** @test */
    public function a_delivery_order_converted_order_is_left_on_hold(): void
    {
        $customer = $this->makeCustomer();
        $do = DeliveryOrder::create([
            'dono'        => 'OFFDAY-ADM-DO-' . uniqid(),
            'date'        => now(),
            'customer_id' => $customer->id,
            'status'      => 0,
        ]);
        $order = $this->makeOffDayOrder($customer, now()->subDays(2)->startOfDay());
        $order->forceFill(['deliveryorder_id' => $do->id])->save();

        $this->controller()->removeOffDay(Crypt::encrypt($order->id));

        $this->assertTrue($order->fresh()->off_day, 'a DO-converted order must stay on hold');
    }
}
