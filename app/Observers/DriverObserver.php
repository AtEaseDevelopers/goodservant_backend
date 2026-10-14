<?php

namespace App\Observers;

use App\Models\Driver;
use App\Services\DebtorTypeAssignmentService;

class DriverObserver
{
    /**
     * Whenever a driver's debtor_types change (including on create), re-assign
     * the matching customers.
     */
    public function saved(Driver $driver): void
    {
        // wasChanged() is empty right after an insert, so cover creation too.
        if ($driver->wasRecentlyCreated || $driver->wasChanged('debtor_types')) {
            DebtorTypeAssignmentService::syncDriver($driver);
        }
    }
}
