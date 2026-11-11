<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Driver;
use App\Models\SalesOrder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Covers DriverController::getsalesorder() with unconverted=1 — the driver
 * "packing list" — and its whereSalesOrderStillOpen() helper.
 *
 * Off Day SOs stay on the packing list (the PDF prints them as "No Order")
 * and in the full listing, until the driver converts them to a normal SO or
 * an invoice. Also covers the driver Off Day actions.
 *
 * Exercises the real HTTP endpoint (GET /api/v1/driver/salesOrder) with a
 * driver session header. Uses DatabaseTransactions so the configured dev DB
 * (`snoodle`) is rolled back after each test, never rebuilt.
 */
class OffDayPackingListTest extends TestCase
{
    use DatabaseTransactions;

    private Driver $driver;
    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->driver = Driver::create([
            'employeeid' => 'OD' . substr(uniqid(), -8),
            'password'   => bcrypt('secret'),
            'name'       => 'Off Day Driver',
            'session'    => 'offday-session-' . uniqid(),
            'status'     => 1,
        ]);

        $this->customer = Customer::create([
            'code'        => 'OFFDAY-PL-' . uniqid(),
            'company'     => 'Off Day Packing Customer',
            'paymentterm' => 0,
            'status'      => 1,
        ]);
    }

    private function makeOrder(bool $offDay): SalesOrder
    {
        $order = new SalesOrder();
        $order->forceFill([
            'sono'        => 'OFFDAY-PL-' . uniqid(),
            'date'        => now(),
            'customer_id' => $this->customer->id,
            'driver_id'   => $this->driver->id,
            'status'      => 0,
            'off_day'     => $offDay,
        ]);
        $order->save();

        return $order;
    }

    /** Fetch the driver's sales orders; ids returned in the response. */
    private function fetchIds(bool $unconverted): array
    {
        $query = $unconverted ? '?unconverted=1' : '';

        $response = $this->withHeaders(['session' => $this->driver->session])
            ->getJson('/api/v1/driver/salesOrder' . $query);

        $response->assertOk();
        $response->assertJson(['result' => true]);

        return collect($response->json('data'))->pluck('id')->all();
    }

    /** @test */
    public function the_packing_list_keeps_normal_and_off_day_open_orders(): void
    {
        $normal = $this->makeOrder(false);
        $offDay = $this->makeOrder(true);

        $ids = $this->fetchIds(true); // unconverted=1 → packing list

        $this->assertContains($normal->id, $ids, 'a normal open order must appear on the packing list');
        $this->assertContains($offDay->id, $ids, 'an off-day order stays on the packing list as "No Order"');
    }

    /** @test */
    public function the_driver_can_turn_a_customers_off_day_on_and_off(): void
    {
        $this->withHeaders(['session' => $this->driver->session])
            ->postJson('/api/v1/driver/customer/offday', ['customer_id' => $this->customer->id, 'off_day' => true])
            ->assertOk()->assertJson(['result' => true]);
        $this->assertTrue((bool) $this->customer->fresh()->off_day);

        $this->withHeaders(['session' => $this->driver->session])
            ->postJson('/api/v1/driver/customer/offday', ['customer_id' => $this->customer->id, 'off_day' => false])
            ->assertOk();
        $this->assertFalse((bool) $this->customer->fresh()->off_day);
    }

    /** @test */
    public function ticked_off_day_orders_become_normal_and_nothing_else_changes(): void
    {
        $offDay = $this->makeOrder(true);
        $otherOffDay = $this->makeOrder(true);
        $cancelled = $this->makeOrder(true);
        $cancelled->forceFill(['status' => 2])->save();

        $this->withHeaders(['session' => $this->driver->session])
            ->postJson('/api/v1/driver/salesOrder/offday/normal', ['sales_order_ids' => [$offDay->id, $cancelled->id]])
            ->assertOk()->assertJson(['result' => true, 'data' => ['count' => 1]]);

        $this->assertFalse((bool) $offDay->fresh()->off_day, 'the ticked open order becomes normal');
        $this->assertTrue((bool) $otherOffDay->fresh()->off_day, 'an unticked order stays off-day');
        $this->assertTrue((bool) $cancelled->fresh()->off_day, 'a cancelled order is left alone');
    }

    /** @test */
    public function an_off_day_order_is_never_forwarded_automatically(): void
    {
        $order = $this->makeOrder(true);
        $order->forceFill(['date' => now()->subDays(3)])->save();

        $this->fetchIds(true);
        $this->fetchIds(false);

        $this->assertTrue((bool) $order->fresh()->off_day, 'it stays off-day until the driver converts it');
    }

    /** @test */
    public function the_full_listing_still_includes_off_day_orders(): void
    {
        $normal = $this->makeOrder(false);
        $offDay = $this->makeOrder(true);

        $ids = $this->fetchIds(false); // no unconverted → full history listing

        $this->assertContains($normal->id, $ids);
        $this->assertContains(
            $offDay->id,
            $ids,
            'an off-day order must still be visible in the full listing (history)'
        );
    }
}
