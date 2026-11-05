<?php

namespace App\Observers;

use App\Models\Driver;
use App\Services\DebtorTypeAssignmentService;

class DriverObserver
{
    /**
     * Keep debtor-type assigns current on every driver save. Matching only on
     * debtor_types changes left a driver short when customers were added later
     * and the driver was never re-saved with a type change.
     */
    public function saved(Driver $driver): void
    {
        DebtorTypeAssignmentService::syncDriver($driver);
    }
}
