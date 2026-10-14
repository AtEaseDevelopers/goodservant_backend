<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Covers POST /invoices/markpendingsync (InvoiceController::markpendingsync),
 * the "mark pending sync" / AutoCount resync action.
 *
 * The behaviour under test: an invoice that is already SYNCED (3), FAILED (4)
 * or NONE (0) CAN be re-queued to pending (1) — the core change — while an
 * invoice currently SYNCING (2) must be left untouched so an in-flight sync is
 * never disturbed. The endpoint returns the number of rows actually updated.
 *
 * Uses DatabaseTransactions (not RefreshDatabase) so the configured dev DB
 * (`snoodle`) is rolled back after the test, never rebuilt.
 */
class InvoiceMarkPendingSyncTest extends TestCase
{
    use DatabaseTransactions;

    private function actingAsInvoiceUser(): User
    {
        // Ensure the `invoice` permission exists on the web guard and grant it
        // to a freshly-created user, then authenticate as that user.
        $permission = Permission::firstOrCreate([
            'name' => 'invoice',
            'guard_name' => 'web',
        ]);

        $user = User::create([
            'name' => 'Resync Tester',
            'email' => 'resync-tester-' . uniqid() . '@example.test',
            'password' => bcrypt('secret'),
        ]);

        $user->givePermissionTo($permission);

        $this->actingAs($user, 'web');

        return $user;
    }

    private function makeCustomer(): Customer
    {
        return Customer::create([
            'code' => 'RSCUST-' . uniqid(),
            'company' => 'Resync Test Customer',
            'paymentterm' => 0,
            'status' => 1,
        ]);
    }

    private function makeInvoice(int $customerId, int $syncStatus): Invoice
    {
        return Invoice::create([
            'invoiceno' => 'RS-' . strtoupper(uniqid()),
            'date' => now()->toDateString(),
            'customer_id' => $customerId,
            'paymentterm' => 0,
            'status' => 0,
            'sync_status' => $syncStatus,
            'sync_error' => 'previous error',
        ]);
    }

    /** @test */
    public function synced_failed_and_none_invoices_are_requeued_while_syncing_is_skipped()
    {
        $this->actingAsInvoiceUser();

        $customer = $this->makeCustomer();

        $synced  = $this->makeInvoice($customer->id, Invoice::SYNC_SYNCED);   // 3
        $failed  = $this->makeInvoice($customer->id, Invoice::SYNC_FAILED);   // 4
        $none    = $this->makeInvoice($customer->id, Invoice::SYNC_NONE);     // 0
        $syncing = $this->makeInvoice($customer->id, Invoice::SYNC_SYNCING);  // 2

        $ids = [$synced->id, $failed->id, $none->id, $syncing->id];

        $response = $this->post('/invoices/markpendingsync', ['ids' => $ids]);

        $response->assertOk();

        // Three of the four rows were requeued; the SYNCING row was skipped.
        $this->assertSame(3, (int) $response->getContent());

        // Synced CAN be re-queued (the core change), error cleared.
        $synced->refresh();
        $this->assertSame(Invoice::SYNC_PENDING, $synced->sync_status);
        $this->assertNull($synced->sync_error);

        // Failed requeued, error cleared.
        $failed->refresh();
        $this->assertSame(Invoice::SYNC_PENDING, $failed->sync_status);
        $this->assertNull($failed->sync_error);

        // None requeued, error cleared.
        $none->refresh();
        $this->assertSame(Invoice::SYNC_PENDING, $none->sync_status);
        $this->assertNull($none->sync_error);

        // Syncing left completely untouched.
        $syncing->refresh();
        $this->assertSame(Invoice::SYNC_SYNCING, $syncing->sync_status);
        $this->assertSame('previous error', $syncing->sync_error);
    }
}
