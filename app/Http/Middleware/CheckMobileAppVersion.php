<?php

namespace App\Http\Middleware;

use App\Models\Code;
use Closure;
use Illuminate\Http\Request;

/**
 * Non-blocking app-update notice for the driver mobile API. The client
 * sends its current version in the App-Version header (and its phone type in
 * the Platform header); when that version is older than the admin-configured
 * latest version (Codes: code = 'mobile_app_latest_version', value e.g.
 * "1.0.72"), every JSON response gets an extra top-level 'app_update' key:
 *
 *   {"available": true, "latest_version": "1.0.72",
 *    "update_url": "<link for this phone type>"}
 *
 * The link comes from the Codes 'mobile_app_update_url_android' /
 * 'mobile_app_update_url_ios' (APK download today; the Google Play / App
 * Store link once the app is published there). The dashboard shows a banner
 * the driver can tap. Never blocks the request - if the header is missing or
 * no latest version is configured, nothing changes.
 *
 * Set the latest version only once the new version can really be installed
 * from that link (after store approval), or the banner points at nothing new.
 */
class CheckMobileAppVersion
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        $clientVersion = trim((string) $request->header('App-Version'));
        if ($clientVersion === '') {
            return $response;
        }

        $codes = Code::whereIn('code', ['mobile_app_latest_version', 'mobile_app_update_url_android', 'mobile_app_update_url_ios'])
            ->pluck('value', 'code');
        $latestVersion = trim((string) ($codes['mobile_app_latest_version'] ?? ''));
        if ($latestVersion === '') {
            return $response;
        }

        $platform = strtolower((string) $request->header('Platform', ''));
        $url = $platform === 'ios'
            ? (string) ($codes['mobile_app_update_url_ios'] ?? '')
            : (string) ($codes['mobile_app_update_url_android'] ?? '');

        if (method_exists($response, 'getData')) {
            $data = $response->getData(true);
            if (is_array($data)) {
                $data['app_update'] = [
                    'available' => static::isOlder($clientVersion, $latestVersion),
                    'latest_version' => $latestVersion,
                    'update_url' => trim($url),
                ];
                $response->setData($data);
            }
        }

        return $response;
    }

    /** "1.0.9" is older than "1.0.10"; a newer test build is not "older". */
    public static function isOlder(string $client, string $latest): bool
    {
        $a = array_map('intval', explode('.', preg_replace('/[^0-9.]/', '', $client)));
        $b = array_map('intval', explode('.', preg_replace('/[^0-9.]/', '', $latest)));
        for ($i = 0; $i < max(count($a), count($b)); $i++) {
            $x = $a[$i] ?? 0;
            $y = $b[$i] ?? 0;
            if ($x !== $y) {
                return $x < $y;
            }
        }

        return false;
    }
}
