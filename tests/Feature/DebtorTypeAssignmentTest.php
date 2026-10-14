<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\V1\DriverController;
use App\Models\Assign;
use App\Models\Customer;
use App\Models\Driver;
use App\Services\DebtorTypeAssignmentService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Tests\TestCase;

/**
 * A driver's `debtor_types` (a master-data multi-select) auto-maintains the
 * `assigns` pivot: the driver is assigned to every customer whose debtor_type
 * matches, kept in sync when either side changes, and never clobbering manual
 * assignments (source = 'manual').
 *
 * Debtor types are suffixed with a per-run token so the assertions are isolated
 * from the real customers already living in the configured dev DB.
 *
 * Uses DatabaseTransactions (not RefreshDatabase) so the dev DB is rolled back,
 * never rebuilt.
 */
class DebtorTypeAssignmentTest extends TestCase
{
    use DatabaseTransactions;

    private int $seq = 0;
    private string $tok = '';

    protected function setUp(): void
    {
        parent::setUp();
        $this->tok = strtoupper(substr(md5(uniqid('', true)), 0, 8));
        config(['services.autocount.token' => 'test-token', 'services.autocount.dry_run' => false]);
    }

    /** A debtor type unique to this test run. */
    private function type(string $base): string
    {
        return $base . '-' . $this->tok;
    }

    private function makeDriver(array $types): Driver
    {
        $driver = new Driver();
        $driver->employeeid = 'EMP-' . (++$this->seq) . '-' . uniqid();
        $driver->password = 'secret';
        $driver->name = 'Driver ' . $this->seq;
        $driver->status = 1;
        $driver->debtor_types = $types;
        $driver->save();

        return $driver;
    }

    private function makeCustomer(string $code, ?string $debtorType): Customer
    {
        $customer = new Customer();
        $customer->code = $code . '-' . uniqid();
        $customer->company = $code;
        $customer->paymentterm = 2;
        $customer->status = 1;
        $customer->debtor_type = $debtorType;
        $customer->save();

        return $customer;
    }

    private function autoCustomerIds(int $driverId): array
    {
        $ids = Assign::where('driver_id', $driverId)
            ->where('source', 'debtor_type')
            ->pluck('customer_id')
            ->all();
        sort($ids);

        return $ids;
    }

    private function headers(): array
    {
        return ['X-AutoCount-Token' => 'test-token'];
    }

    /** @test */
    public function selecting_debtor_types_on_a_driver_assigns_matching_customers()
    {
        $food = $this->makeCustomer('FOODCO', $this->type('FOOD'));
        $whole = $this->makeCustomer('WHOLECO', $this->type('WHOLE'));
        $retail = $this->makeCustomer('RETAILCO', $this->type('RETAIL'));

        $driver = $this->makeDriver([$this->type('FOOD'), $this->type('WHOLE')]);

        $expected = [$food->id, $whole->id];
        sort($expected);

        $this->assertSame($expected, $this->autoCustomerIds($driver->id));
        $this->assertDatabaseMissing('assigns', ['driver_id' => $driver->id, 'customer_id' => $retail->id]);
        // Auto rows carry an appended (non-null, positive) sequence.
        $this->assertGreaterThan(0, Assign::where('driver_id', $driver->id)->min('sequence'));
    }

    /** @test */
    public function dropping_a_type_removes_only_its_auto_rows_and_keeps_manual_ones()
    {
        $food = $this->makeCustomer('FOODCO', $this->type('FOOD'));
        $whole = $this->makeCustomer('WHOLECO', $this->type('WHOLE'));
        $manual = $this->makeCustomer('MANUALCO', $this->type('RETAIL'));

        $driver = $this->makeDriver([$this->type('FOOD'), $this->type('WHOLE')]);

        // A hand-made assignment to a customer outside the driver's types.
        Assign::create([
            'driver_id' => $driver->id,
            'customer_id' => $manual->id,
            'sequence' => 99,
            'source' => 'manual',
        ]);

        // Drop WHOLE.
        $driver->debtor_types = [$this->type('FOOD')];
        $driver->save();

        $this->assertDatabaseHas('assigns', ['driver_id' => $driver->id, 'customer_id' => $food->id, 'source' => 'debtor_type']);
        $this->assertDatabaseMissing('assigns', ['driver_id' => $driver->id, 'customer_id' => $whole->id]);
        // Manual assignment survives.
        $this->assertDatabaseHas('assigns', ['driver_id' => $driver->id, 'customer_id' => $manual->id, 'source' => 'manual']);
    }

    /** @test */
    public function clearing_all_types_removes_every_auto_row_but_keeps_manual()
    {
        $food = $this->makeCustomer('FOODCO', $this->type('FOOD'));
        $manual = $this->makeCustomer('MANUALCO', $this->type('RETAIL'));

        $driver = $this->makeDriver([$this->type('FOOD')]);
        Assign::create([
            'driver_id' => $driver->id,
            'customer_id' => $manual->id,
            'sequence' => 50,
            'source' => 'manual',
        ]);

        $driver->debtor_types = [];
        $driver->save();

        $this->assertSame([], $this->autoCustomerIds($driver->id));
        $this->assertDatabaseHas('assigns', ['driver_id' => $driver->id, 'customer_id' => $manual->id, 'source' => 'manual']);
    }

    /** @test */
    public function a_manual_assign_matching_a_type_is_not_duplicated()
    {
        $food = $this->makeCustomer('FOODCO', $this->type('FOOD'));
        $driver = $this->makeDriver([]);

        Assign::create([
            'driver_id' => $driver->id,
            'customer_id' => $food->id,
            'sequence' => 5,
            'source' => 'manual',
        ]);

        // Now cover FOOD — the customer is already assigned manually.
        $driver->debtor_types = [$this->type('FOOD')];
        $driver->save();

        $this->assertSame(1, Assign::where('driver_id', $driver->id)->where('customer_id', $food->id)->count());
        // Still manual (untouched), not converted to / duplicated as an auto row.
        $this->assertDatabaseHas('assigns', ['driver_id' => $driver->id, 'customer_id' => $food->id, 'source' => 'manual']);
    }

    /** @test */
    public function autocount_customer_sync_assigns_remaps_and_clears_across_drivers()
    {
        $dFood = $this->makeDriver([$this->type('FOOD')]);
        $dWhole = $this->makeDriver([$this->type('WHOLE')]);
        $dManual = $this->makeDriver([]); // never covers the type

        // New customer with a FOOD debtor type.
        $this->postJson('/api/v1/autocount/customers', [
            'customers' => [['code' => 'ACC-1', 'company' => 'Acme', 'debtor_type' => $this->type('FOOD')]],
        ], $this->headers())->assertOk()->assertJson(['result' => true]);

        $customer = Customer::where('code', 'ACC-1')->firstOrFail();
        $this->assertDatabaseHas('assigns', ['driver_id' => $dFood->id, 'customer_id' => $customer->id, 'source' => 'debtor_type']);
        $this->assertDatabaseMissing('assigns', ['driver_id' => $dWhole->id, 'customer_id' => $customer->id]);

        // A hand-made assignment to a non-covering driver must survive every re-map.
        Assign::create([
            'driver_id' => $dManual->id,
            'customer_id' => $customer->id,
            'sequence' => 10,
            'source' => 'manual',
        ]);

        // Re-sync as WHOLE -> auto row moves from dFood to dWhole.
        $this->postJson('/api/v1/autocount/customers', [
            'customers' => [['code' => 'ACC-1', 'company' => 'Acme', 'debtor_type' => $this->type('WHOLE')]],
        ], $this->headers())->assertOk();

        $this->assertDatabaseMissing('assigns', ['driver_id' => $dFood->id, 'customer_id' => $customer->id]);
        $this->assertDatabaseHas('assigns', ['driver_id' => $dWhole->id, 'customer_id' => $customer->id, 'source' => 'debtor_type']);

        // Clear the debtor type -> all auto rows gone, manual survives.
        $this->postJson('/api/v1/autocount/customers', [
            'customers' => [['code' => 'ACC-1', 'company' => 'Acme', 'debtor_type' => '']],
        ], $this->headers())->assertOk();

        $this->assertSame(0, Assign::where('customer_id', $customer->id)->where('source', 'debtor_type')->count());
        $this->assertDatabaseHas('assigns', ['driver_id' => $dManual->id, 'customer_id' => $customer->id, 'source' => 'manual']);
    }

    /** @test */
    public function changing_a_customer_debtor_type_directly_remaps_assignments()
    {
        $driverX = $this->makeDriver([$this->type('X')]);
        $customer = $this->makeCustomer('CUSTX', $this->type('X'));

        $this->assertDatabaseHas('assigns', ['driver_id' => $driverX->id, 'customer_id' => $customer->id, 'source' => 'debtor_type']);

        // Move to a type no driver covers -> auto row removed.
        $customer->debtor_type = $this->type('Y');
        $customer->save();

        $this->assertDatabaseMissing('assigns', ['driver_id' => $driverX->id, 'customer_id' => $customer->id]);
    }

    /** @test */
    public function re_running_the_sync_updates_the_same_row_and_never_duplicates()
    {
        $driver = $this->makeDriver([$this->type('FOOD')]);
        $customer = $this->makeCustomer('FOODCO', $this->type('FOOD'));

        // One row after creation.
        $this->assertSame(1, Assign::where('driver_id', $driver->id)->where('customer_id', $customer->id)->count());

        // Re-run both directions repeatedly — still exactly one row.
        DebtorTypeAssignmentService::syncDriver($driver->fresh());
        DebtorTypeAssignmentService::syncCustomer($customer->fresh());
        DebtorTypeAssignmentService::syncDriver($driver->fresh());

        $this->assertSame(1, Assign::where('driver_id', $driver->id)->where('customer_id', $customer->id)->count());
        $this->assertDatabaseHas('assigns', ['driver_id' => $driver->id, 'customer_id' => $customer->id, 'source' => 'debtor_type']);
    }

    /** @test */
    public function getcustomer_returns_debtor_type_assigned_customers()
    {
        $driver = $this->makeDriver([$this->type('FOOD')]);
        $customer = $this->makeCustomer('VISIBLECO', $this->type('FOOD'));

        $driver->session = 'sess-' . uniqid();
        $driver->save(); // only session changes -> no re-sync

        $request = Request::create('/api/v1/driver/customer', 'GET');
        $request->headers->set('session', $driver->session);

        $response = app(DriverController::class)->getcustomer($request);
        $payload = json_decode($response->getContent(), true);

        $this->assertTrue($payload['result']);
        $ids = array_column($payload['data'], 'id');
        $this->assertContains($customer->id, $ids);
    }
}
