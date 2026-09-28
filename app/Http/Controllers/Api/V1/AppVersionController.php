<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Code;
use Illuminate\Http\Request;

/**
 * Deploy-time hook that publishes the latest mobile app version.
 *
 * Called by the mobile build script (van_sales build_script/release_*.sh)
 * right after a release APK is built. It updates the Codes row
 * (code = 'mobile_app_latest_version') that CheckMobileAppVersion reads,
 * so every driver's next API call carries app_update.available = true and
 * the home screen shows the "update available" banner - no manual edit of
 * the Codes page needed.
 *
 * Protected by a shared secret: the request must carry an X-Deploy-Token
 * header equal to DEPLOY_VERSION_TOKEN in .env. When that env is not set,
 * the endpoint is disabled entirely.
 */
class AppVersionController extends Controller
{
    private function authorized(Request $request): bool
    {
        $token = config('services.deploy.version_token');

        return !empty($token) && hash_equals($token, (string) $request->header('X-Deploy-Token'));
    }

    public function update(Request $request)
    {
        if (!$this->authorized($request)) {
            return response()->json(['result' => false, 'message' => 'Unauthorized.'], 403);
        }

        $data = $request->validate([
            'version' => ['required', 'regex:/^\d+\.\d+\.\d+$/'],
        ]);

        Code::updateOrCreate(
            ['code' => 'mobile_app_latest_version'],
            ['value' => $data['version'], 'description' => 'Latest mobile app version (set by the release build script)']
        );

        return response()->json([
            'result' => true,
            'latest_version' => $data['version'],
        ]);
    }
}
