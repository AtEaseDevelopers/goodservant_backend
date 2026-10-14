<?php

namespace App\Observers;

use App\Models\Customer;
use App\Services\DebtorTypeAssignmentService;

class CustomerObserver
{
    /**
     * Whenever a customer's debtor_type changes (e.g. the AutoCount customer
     * sync writes it), re-assign the customer to the drivers covering that type.
     * No-op for the ordinary customer saves that never touch debtor_type.
     */
    public function saved(Customer $customer): void
    {
        // wasChanged() is empty right after an insert, so a newly-created
        // customer that already carries a debtor_type is covered explicitly.
        $shouldSync = $customer->wasChanged('debtor_type')
            || ($customer->wasRecentlyCreated && filled($customer->debtor_type));

        if ($shouldSync) {
            DebtorTypeAssignmentService::syncCustomer($customer);
        }
    }
}
