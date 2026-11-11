<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Driver;
use App\Models\SalesOrder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Covers DriverController::getsalesorder() with unconverted=1 — the driver
 * "packing list" — and its whereSalesOrderStillOpen() helper, which now
 * excludes off-day orders (sales_orders.off_day = false).
 *
 * An off-day order is placed but held back from today's delivery, so it must
 * NOT appear on today's packing list, while it SHOULD still appear in the full
 * (history) listing. A normal open order must appear on both.
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
    public function the_packing_list_excludes_off_day_orders_but_keeps_normal_open_orders(): void
    {
        $normal = $this->makeOrder(false);
        $offDay = $this->makeOrder(true);

        $ids = $this->fetchIds(true); // unconverted=1 → packing list

        $this->assertContains($normal->id, $ids, 'a normal open order must appear on the packing list');
        $this->assertNotContains($offDay->id, $ids, 'an off-day order must NOT appear on the packing list');
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
