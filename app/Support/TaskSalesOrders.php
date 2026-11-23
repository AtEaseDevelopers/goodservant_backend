<?php

namespace App\Support;

use App\Models\SalesOrder;
use App\Models\Trip;

/**
 * When a customer's task moves to another driver (driver app transfer, or
 * the admin changing the task's driver), the customer's open sales orders of
 * the old driver move with it, so the new driver sees and converts them.
 *
 * Open = not converted (no invoice / DO) and not cancelled; any date, Off Day
 * SOs included. The SO keeps its number (it carries the code of the driver
 * who made it); the invoice or DO made from it gets the number of the driver
 * who converts it. Moving the task back moves the SOs back.
 */
class TaskSalesOrders
{
    /** @return int how many sales orders moved */
    public static function move($customerId, $fromDriverId, $toDriverId): int
    {
        if (!$customerId || !$fromDriverId || !$toDriverId || (int) $fromDriverId === (int) $toDriverId) {
            return 0;
        }

        $changes = ['driver_id' => $toDriverId];

        // Put them on the new driver's open trip, if any.
        $trip = Trip::where('driver_id', $toDriverId)->orderBy('date', 'desc')->orderBy('id', 'desc')->first();
        if ($trip && (int) $trip->type === 1) {
            $changes['trip_id'] = $trip->id;
            $changes['kelindan_id'] = $trip->kelindan_id;
        }

        return SalesOrder::where('customer_id', $customerId)
            ->where('driver_id', $fromDriverId)
            ->where('status', '!=', 2)
            ->whereNull('invoice_id')
            ->whereNull('deliveryorder_id')
            ->update($changes);
    }
}
