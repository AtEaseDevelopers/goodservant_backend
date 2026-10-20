<?php

namespace App\Services;

use App\Models\Driver;
use App\Models\Kelindan;
use Illuminate\Support\Facades\Schema;

/**
 * Keep the kelindan list in step with the driver list. A driver can be chosen
 * as a kelindan, and a name or status change on the driver shows up there too.
 * A kelindan's own commission rate is left alone.
 */
class DriverKelindanSync
{
    public static function sync(Driver $driver): void
    {
        if (!Schema::hasColumn('kelindans', 'driver_id') || $driver->employeeid === null || $driver->employeeid === '') {
            return;
        }

        $kelindan = Kelindan::where('driver_id', $driver->id)->first();
        if (!$kelindan) {
            $kelindan = Kelindan::where('employeeid', $driver->employeeid)->first();
        }

        $employeeid = $driver->employeeid;
        $employeeTaken = Kelindan::where('employeeid', $employeeid)
            ->when($kelindan, function ($query) use ($kelindan) {
                $query->where('id', '!=', $kelindan->id);
            })
            ->exists();
        if ($employeeTaken) {
            $employeeid = $kelindan->employeeid ?? $employeeid;
        }

        $ic = $driver->ic ?: null;
        if ($ic !== null) {
            $icTaken = Kelindan::where('ic', $ic)
                ->when($kelindan, function ($query) use ($kelindan) {
                    $query->where('id', '!=', $kelindan->id);
                })
                ->exists();
            if ($icTaken) {
                $ic = $kelindan->ic ?? null;
            }
        }

        if (!$kelindan) {
            Kelindan::create([
                'employeeid' => $employeeid,
                'name' => $driver->name,
                'phone' => $driver->phone,
                'ic' => $ic,
                'status' => $driver->status ?? 1,
                'remark' => $driver->remark,
                'commissionrate' => 0,
                'driver_id' => $driver->id,
                'bankdetails1' => '',
                'bankdetails2' => '',
            ]);
            return;
        }

        $kelindan->employeeid = $employeeid;
        $kelindan->name = $driver->name;
        $kelindan->phone = $driver->phone;
        if ($ic !== null) {
            $kelindan->ic = $ic;
        }
        $kelindan->status = $driver->status;
        $kelindan->remark = $driver->remark;
        $kelindan->driver_id = $driver->id;
        $kelindan->save();
    }
}
