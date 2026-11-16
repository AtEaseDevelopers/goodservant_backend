<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Where the dashboard's "new version" banner sends the driver, per phone
 * type. Edited on the admin Codes page: today the Android link is the APK
 * download; once the app is live on Google Play / the App Store, put the
 * store links here. The banner shows when the phone's version is older than
 * mobile_app_latest_version.
 */
class AddMobileAppUpdateUrls extends Migration
{
    public function up()
    {
        $now = now();
        $rows = [
            'mobile_app_update_url_android' => [
                'value' => rtrim((string) config('app.url'), '/') . '/downloads/snoodle.apk',
                'description' => 'Android update link for the "new version" banner (APK download, or the Google Play link once on Play)',
            ],
            'mobile_app_update_url_ios' => [
                'value' => '',
                'description' => 'iPhone update link for the "new version" banner (the App Store link)',
            ],
        ];
        foreach ($rows as $code => $row) {
            if (!DB::table('codes')->where('code', $code)->exists()) {
                DB::table('codes')->insert(['code' => $code, 'value' => $row['value'], 'description' => $row['description'], 'sequence' => 0, 'created_at' => $now, 'updated_at' => $now]);
            }
        }
    }

    public function down()
    {
        DB::table('codes')->whereIn('code', ['mobile_app_update_url_android', 'mobile_app_update_url_ios'])->delete();
    }
}
