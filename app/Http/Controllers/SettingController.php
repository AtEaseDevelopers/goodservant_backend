<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Flash;

class SettingController extends Controller
{
    /**
     * AutoCount sync settings page (nightly batch time + enable toggle).
     */
    public function autocount()
    {
        return view('settings.autocount', [
            'nightlySyncEnabled' => Setting::nightlySyncEnabled(),
            'nightlySyncTime' => Setting::nightlySyncTime(),
        ]);
    }

    /**
     * Persist the AutoCount nightly-sync settings. The scheduler re-reads these
     * on its next run, so changes take effect without a redeploy.
     */
    public function updateAutocount(Request $request)
    {
        $data = $request->validate([
            'nightly_sync_time' => ['required', 'date_format:H:i'],
        ]);

        Setting::set(
            Setting::AUTOCOUNT_NIGHTLY_SYNC_ENABLED,
            $request->has('nightly_sync_enabled') ? '1' : '0'
        );
        Setting::set(Setting::AUTOCOUNT_NIGHTLY_SYNC_TIME, $data['nightly_sync_time']);

        Flash::success('AutoCount sync settings saved.');

        return redirect(route('settings.autocount'));
    }
}
