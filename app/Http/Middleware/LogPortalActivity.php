<?php

namespace App\Http\Middleware;

use App\Support\ActivityLogger;
use Closure;
use Illuminate\Http\Request;

/**
 * Admin / customer portal: record every request that saves or deletes
 * something, with what was sent (passwords left out). Page views are not
 * logged. See App\Support\ActivityLogger.
 */
class LogPortalActivity
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if (!in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true)) {
            ActivityLogger::request($request, $response);
        }

        return $response;
    }
}
