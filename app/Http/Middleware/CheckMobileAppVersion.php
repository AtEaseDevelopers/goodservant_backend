<?php

namespace App\Http\Middleware;

use App\Models\Code;
use Closure;
use Illuminate\Http\Request;

/**
 * Non-blocking app-update notice for the driver mobile API. The client
 * sends its current version in the App-Version header; if it doesn't
 * match the admin-configured latest version (Codes: code =
 * 'mobile_app_latest_version', value = e.g. "1.0.10"), every JSON
 * response gets an extra top-level 'app_update' key the app can show a
 * dismissible "update available" prompt from. Never blocks the request -
 * if the header is missing or no latest version is configured, nothing
 * changes.
 */
class CheckMobileAppVersion
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        $clientVersion = $request->header('App-Version');
        if (empty($clientVersion)) {
            return $response;
        }

        $latestVersion = Code::where('code', 'mobile_app_latest_version')->value('value');
        if (empty($latestVersion)) {
            return $response;
        }

        if (method_exists($response, 'getData')) {
            $data = $response->getData(true);
            if (is_array($data)) {
                $data['app_update'] = [
                    'available' => trim($clientVersion) !== trim($latestVersion),
                    'latest_version' => $latestVersion,
                ];
                $response->setData($data);
            }
        }

        return $response;
    }
}
