<?php

namespace App\Http\Controllers;

use App\Models\Code;
use Flash;
use Illuminate\Http\Request;

/**
 * The Codes page now only edits the three driver-app update settings:
 * latest app version and the Android / iPhone update links.
 *
 * The other rows in the codes table are system values (running numbers,
 * day colours, email receivers, commission and reminder settings) that the
 * system reads by code. They are kept but not shown, so nobody changes or
 * deletes them by mistake.
 */
class CodeController extends AppBaseController
{
    public const APP_SETTINGS = [
        'mobile_app_latest_version' => 'Latest app version',
        'mobile_app_update_url_android' => 'Android update link',
        'mobile_app_update_url_ios' => 'iPhone update link',
    ];

    public function index()
    {
        $values = Code::whereIn('code', array_keys(self::APP_SETTINGS))->pluck('value', 'code');

        return view('codes.app_update', [
            'settings' => self::APP_SETTINGS,
            'values' => $values,
        ]);
    }

    public function saveAppUpdate(Request $request)
    {
        $data = $request->validate([
            'mobile_app_latest_version' => ['required', 'regex:/^\d+\.\d+\.\d+$/'],
            'mobile_app_update_url_android' => ['nullable', 'url', 'max:255'],
            'mobile_app_update_url_ios' => ['nullable', 'url', 'max:255'],
        ], [
            'mobile_app_latest_version.regex' => 'The version must look like 1.0.72.',
        ]);

        foreach (self::APP_SETTINGS as $code => $label) {
            Code::updateOrCreate(
                ['code' => $code],
                ['value' => trim((string) ($data[$code] ?? '')), 'description' => $label]
            );
        }

        Flash::success('App update settings saved.');

        return redirect(route('codes.index'));
    }

    /** Other Codes actions are closed: the remaining rows are system values. */
    public function create()
    {
        return redirect(route('codes.index'));
    }

    public function store(Request $request)
    {
        return redirect(route('codes.index'));
    }

    public function show($id)
    {
        return redirect(route('codes.index'));
    }

    public function edit($id)
    {
        return redirect(route('codes.index'));
    }

    public function update($id, Request $request)
    {
        return redirect(route('codes.index'));
    }

    public function destroy($id)
    {
        return redirect(route('codes.index'));
    }
}
