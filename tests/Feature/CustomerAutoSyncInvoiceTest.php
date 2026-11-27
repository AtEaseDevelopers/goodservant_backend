<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Http\Controllers\InvoiceController;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Covers the per-customer "auto-sync invoices to AutoCount" flag
 * (customers.auto_sync_autocount) and the nightly batch command.
 *
 *  - On create, a flagged customer's invoice is auto-queued to SYNC_PENDING
 *    (Invoice::booted() creating hook), covering every creation path.
 *  - An already-synced invoice stays editable for a flagged customer and an
 *    edit re-queues it; a plain customer's synced invoice stays locked.
 *  - CronJob:autocountnightlysync requeues all unsynced (none/failed) invoices
 *    while leaving syncing/synced ones untouched.
 *
 * Uses DatabaseTransactions (not RefreshDatabase) so the configured dev DB is
 * rolled back after the test, never rebuilt.
 */
class CustomerAutoSyncInvoiceTest extends TestCase
{
    use DatabaseTransactions;

    private function makeCustomer(bool $autoSync): Customer
    {
        return Customer::create([
            'code' => 'ASCUST-' . uniqid(),
            'company' => 'Auto-Sync Test Customer',
            'paymentterm' => 0,
            'status' => 1,
            'auto_sync_autocount' => $autoSync,
        ]);
    }

    private function makeInvoice(int $customerId, ?int $syncStatus = null): Invoice
    {
        $attrs = [
            'invoiceno' => 'AS-' . strtoupper(uniqid()),
            'date' => now()->toDateString(),
            'customer_id' => $customerId,
            'paymentterm' => 0,
            'status' => 0,
        ];

        if ($syncStatus !== null) {
            $attrs['sync_status'] = $syncStatus;
        }

        return Invoice::create($attrs);
    }

    /** @test */
    public function flagged_customer_invoice_is_auto_queued_on_create()
    {
        $customer = $this->makeCustomer(true);

        $invoice = $this->makeInvoice($customer->id);

        $this->assertSame(Invoice::SYNC_PENDING, $invoice->fresh()->sync_status);
    }

    /** @test */
    public function plain_customer_invoice_stays_unsynced_on_create()
    {
        $customer = $this->makeCustomer(false);

        $invoice = $this->makeInvoice($customer->id);

        $this->assertSame(Invoice::SYNC_NONE, $invoice->fresh()->sync_status);
    }

    /** @test */
    public function explicit_sync_status_is_not_overridden_on_create()
    {
        $customer = $this->makeCustomer(true);

        // A creation path that already set a sync state must be respected.
        $invoice = $this->makeInvoice($customer->id, Invoice::SYNC_SYNCED);

        $this->assertSame(Invoice::SYNC_SYNCED, $invoice->fresh()->sync_status);
    }

    /** @test */
    public function synced_invoice_is_editable_and_requeues_for_flagged_customer()
    {
        $customer = $this->makeCustomer(true);
        $invoice = $this->makeInvoice($customer->id, Invoice::SYNC_SYNCED);

        $locked = new ReflectionMethod(InvoiceController::class, 'lockedByAutoCount');
        $locked->setAccessible(true);
        $requeue = new ReflectionMethod(InvoiceController::class, 'requeueForAutoSync');
        $requeue->setAccessible(true);

        $controller = app(InvoiceController::class);

        // A synced invoice is NOT locked when the customer auto-syncs.
        $this->assertFalse($locked->invoke($controller, $invoice->fresh()));

        // Editing it re-queues it to pending.
        $requeue->invoke($controller, $invoice->fresh());
        $this->assertSame(Invoice::SYNC_PENDING, $invoice->fresh()->sync_status);
    }

    /** @test */
    public function synced_invoice_stays_locked_for_plain_customer()
    {
        $customer = $this->makeCustomer(false);
        $invoice = $this->makeInvoice($customer->id, Invoice::SYNC_SYNCED);

        $locked = new ReflectionMethod(InvoiceController::class, 'lockedByAutoCount');
        $locked->setAccessible(true);
        $requeue = new ReflectionMethod(InvoiceController::class, 'requeueForAutoSync');
        $requeue->setAccessible(true);

        $controller = app(InvoiceController::class);

        $this->assertTrue($locked->invoke($controller, $invoice->fresh()));

        // Re-queue is a no-op for a plain customer.
        $requeue->invoke($controller, $invoice->fresh());
        $this->assertSame(Invoice::SYNC_SYNCED, $invoice->fresh()->sync_status);
    }

    /** @test */
    public function syncing_invoice_is_always_locked_even_for_flagged_customer()
    {
        $customer = $this->makeCustomer(true);
        $invoice = $this->makeInvoice($customer->id, Invoice::SYNC_SYNCING);

        $locked = new ReflectionMethod(InvoiceController::class, 'lockedByAutoCount');
        $locked->setAccessible(true);
        $requeue = new ReflectionMethod(InvoiceController::class, 'requeueForAutoSync');
        $requeue->setAccessible(true);

        $controller = app(InvoiceController::class);

        // Mid-flight sync must never be disturbed.
        $this->assertTrue($locked->invoke($controller, $invoice->fresh()));
        $requeue->invoke($controller, $invoice->fresh());
        $this->assertSame(Invoice::SYNC_SYNCING, $invoice->fresh()->sync_status);
    }

    /** @test */
    public function nightly_command_requeues_unsynced_and_leaves_in_flight_untouched()
    {
        $customer = $this->makeCustomer(false);

        $none    = $this->makeInvoice($customer->id, Invoice::SYNC_NONE);
        $failed  = $this->makeInvoice($customer->id, Invoice::SYNC_FAILED);
        $syncing = $this->makeInvoice($customer->id, Invoice::SYNC_SYNCING);
        $synced  = $this->makeInvoice($customer->id, Invoice::SYNC_SYNCED);

        $this->artisan('CronJob:autocountnightlysync')->assertExitCode(0);

        $this->assertSame(Invoice::SYNC_PENDING, $none->fresh()->sync_status);
        $this->assertSame(Invoice::SYNC_PENDING, $failed->fresh()->sync_status);
        $this->assertSame(Invoice::SYNC_SYNCING, $syncing->fresh()->sync_status);
        $this->assertSame(Invoice::SYNC_SYNCED, $synced->fresh()->sync_status);
    }
}
