<?php

namespace App\Listeners;

use App\Models\Driver;
use App\Services\DriverKelindanSync;

/**
 * Keeps each driver's linked kelindan record in sync whenever the driver is
 * saved.
 *
 * Registered in EventServiceProvider rather than added to DriverObserver:
 * on the production server app/Observers and AppServiceProvider are owned by
 * root, so a deploy cannot update them and any commit touching them fails to
 * pull. Leave those two files unchanged until the server ownership is fixed.
 */
class DriverKelindanSyncObserver
{
    public function saved(Driver $driver): void
    {
        DriverKelindanSync::sync($driver);
    }
}
