<?php

namespace App\Services;

use App\Models\Assign;
use App\Models\Customer;
use App\Models\Driver;
use Illuminate\Support\Facades\DB;

/**
 * Keeps the `assigns` pivot in sync with debtor-type coverage: a driver tagged
 * with debtor type X is assigned to every customer whose debtor_type = X.
 *
 * Both directions are reconciled:
 *   - syncDriver()   after a driver's debtor_types change.
 *   - syncCustomer() after a customer's debtor_type changes (e.g. AutoCount sync).
 *
 * Only rows with source = 'debtor_type' are created or removed here; manual
 * assignments (source = 'manual') are read to avoid duplicates but never
 * deleted. Assign has no soft-deletes, so stale auto rows are hard-deleted,
 * matching the rest of the assign flow.
 */
class DebtorTypeAssignmentService
{
    public const SOURCE = 'debtor_type';

    /**
     * Reconcile one driver's debtor-type-driven assigns.
     */
    public static function syncDriver(Driver $driver): void
    {
        $types = array_values(array_filter(
            (array) ($driver->debtor_types ?? []),
            static fn ($type) => trim((string) $type) !== ''
        ));

        $desiredCustomerIds = empty($types)
            ? collect()
            : Customer::whereIn('debtor_type', $types)->pluck('id');

        DB::transaction(function () use ($driver, $desiredCustomerIds) {
            // Drop auto rows that no longer match (all of them when no types).
            Assign::where('driver_id', $driver->id)
                ->where('source', self::SOURCE)
                ->when($desiredCustomerIds->isNotEmpty(), fn ($q) => $q->whereNotIn('customer_id', $desiredCustomerIds))
                ->delete();

            if ($desiredCustomerIds->isEmpty()) {
                return;
            }

            // Customers already linked to this driver (manual or auto) — skip so
            // a manual row is never re-sourced and no duplicate is made.
            $existing = Assign::where('driver_id', $driver->id)->pluck('customer_id')->all();
            $toAdd = $desiredCustomerIds->reject(fn ($id) => in_array($id, $existing))->values();

            $sequence = (int) Assign::where('driver_id', $driver->id)->max('sequence');
            foreach ($toAdd as $customerId) {
                // updateOrCreate (not create) keyed on the pair: dedup-safe even
                // if a row slips in concurrently, since assigns has no unique index.
                Assign::updateOrCreate(
                    ['driver_id' => $driver->id, 'customer_id' => $customerId],
                    ['sequence' => ++$sequence, 'source' => self::SOURCE]
                );
            }
        });
    }

    /**
     * Reconcile one customer's debtor-type-driven assigns across all drivers.
     */
    public static function syncCustomer(Customer $customer): void
    {
        $type = trim((string) ($customer->debtor_type ?? ''));

        $desiredDriverIds = $type === ''
            ? collect()
            : Driver::whereJsonContains('debtor_types', $type)->pluck('id');

        DB::transaction(function () use ($customer, $desiredDriverIds) {
            // Drop auto rows to drivers no longer covering this type.
            Assign::where('customer_id', $customer->id)
                ->where('source', self::SOURCE)
                ->when($desiredDriverIds->isNotEmpty(), fn ($q) => $q->whereNotIn('driver_id', $desiredDriverIds))
                ->delete();

            if ($desiredDriverIds->isEmpty()) {
                return;
            }

            // Drivers already linked to this customer (manual or auto) — skip so
            // a manual row is never re-sourced and no duplicate is made.
            $existing = Assign::where('customer_id', $customer->id)->pluck('driver_id')->all();

            foreach ($desiredDriverIds as $driverId) {
                if (in_array($driverId, $existing)) {
                    continue;
                }
                $sequence = (int) Assign::where('driver_id', $driverId)->max('sequence');
                // updateOrCreate (not create) keyed on the pair: dedup-safe even
                // if a row slips in concurrently, since assigns has no unique index.
                Assign::updateOrCreate(
                    ['driver_id' => $driverId, 'customer_id' => $customer->id],
                    ['sequence' => $sequence + 1, 'source' => self::SOURCE]
                );
            }
        });
    }
}
