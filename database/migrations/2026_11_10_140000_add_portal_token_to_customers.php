<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class AddPortalTokenToCustomers extends Migration
{
    /**
     * Give every customer a random, revocable portal_token. The ordering-portal
     * "magic login" link is keyed on this unguessable token instead of a
     * permanent APP_KEY signature, so one customer's link can be revoked
     * (rotated) without breaking anyone else, and the link cannot be forged
     * from APP_KEY alone.
     */
    public function up()
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('portal_token', 64)->nullable()->unique()->after('status');
        });

        // Backfill existing customers so their ordering links work immediately.
        DB::table('customers')->select('id')->orderBy('id')->chunkById(500, function ($rows) {
            foreach ($rows as $row) {
                DB::table('customers')->where('id', $row->id)->update(['portal_token' => Str::random(48)]);
            }
        });
    }

    public function down()
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropUnique(['portal_token']);
            $table->dropColumn('portal_token');
        });
    }
}
