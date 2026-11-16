<?php

namespace App\Http\Middleware;

use App\Models\Driver;
use Closure;
use Illuminate\Http\Request;

/**
 * The driver app sends, with every request, how many documents it still has
 * waiting to upload (Offline-Pending) and which phone it is (Device-Id).
 * Kept on the driver so that logging in on another phone can warn that the
 * old phone still holds documents. Written only when the value changes.
 */
class ReportOfflinePending
{
    public function handle(Request $request, Closure $next)
    {
        $pending = $request->header('Offline-Pending');
        $device = $request->header('Device-Id');
        $session = $request->header('session');

        if ($pending !== null && is_numeric($pending) && $device && $session) {
            $driver = Driver::where('session', $session)->first(['id', 'offline_pending', 'offline_device']);
            if ($driver) {
                $pending = max(0, (int) $pending);
                $device = substr($device, 0, 64);
                // A phone with nothing waiting does not take over the record
                // of another phone that still has documents.
                $otherPhoneWaiting = $driver->offline_device && $driver->offline_device !== $device && $driver->offline_pending > 0;
                if (($pending > 0 || !$otherPhoneWaiting)
                    && ((int) $driver->offline_pending !== $pending || $driver->offline_device !== $device)) {
                    Driver::where('id', $driver->id)->update([
                        'offline_pending' => $pending,
                        'offline_device' => $device,
                        'offline_reported_at' => now(),
                    ]);
                }
            }
        }

        return $next($request);
    }
}
