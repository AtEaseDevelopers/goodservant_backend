<?php

namespace App\Http\Middleware;

use App\Models\Driver;
use Closure;
use Illuminate\Http\Request;

/**
 * Every driver API call (api/v1/driver/...) must carry the session of a
 * logged-in driver. Without this, a call with no `session` header matched a
 * driver whose session is empty (logged out) and was answered with that
 * driver's data.
 *
 * Open without a session: login, logout and the session check (they carry
 * the session in the body) and the crash log (sent before login too).
 */
class RequireDriverSession
{
    private const OPEN = [
        'api/v1/driver/login',
        'api/v1/driver/logout',
        'api/v1/driver/session',
        'api/v1/driver/error-log',
    ];

    public function handle(Request $request, Closure $next)
    {
        if (!$request->is('api/v1/driver/*') || in_array($request->path(), self::OPEN, true)) {
            return $next($request);
        }

        $session = trim((string) $request->header('session'));
        if ($session === '' || !Driver::where('session', $session)->exists()) {
            return response()->json([
                'result' => false,
                'message' => __LINE__ . '|api.message.invalid_session',
                'data' => null,
            ], 401);
        }

        return $next($request);
    }
}
