<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Each driver gets a short code used in invoice and cash-sale numbers
 * (IV2610/CODE/0001). Existing drivers are also copied onto the kelindan
 * list so the same people can be chosen as kelindan.
 */
class AddDriverDocCodeAndCopyDriversToKelindans extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('drivers', 'doc_code')) {
            Schema::table('drivers', function (Blueprint $table) {
                $table->string('doc_code', 6)->nullable()->after('employeeid');
            });
        }

        if (Schema::hasTable('kelindans') && !Schema::hasColumn('kelindans', 'driver_id')) {
            Schema::table('kelindans', function (Blueprint $table) {
                $table->unsignedBigInteger('driver_id')->nullable()->index();
            });
        }

        $used = [];
        $drivers = DB::table('drivers')->orderBy('id')->get();
        foreach ($drivers as $driver) {
            if (!empty($driver->doc_code)) {
                $used[strtoupper($driver->doc_code)] = true;
                continue;
            }

            $base = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', (string) $driver->employeeid), 0, 4));
            if ($base === '') {
                $base = 'D' . $driver->id;
            }
            $code = substr($base, 0, 6);
            $suffix = 2;
            while (isset($used[$code])) {
                $code = substr($base, 0, 4) . $suffix;
                $suffix++;
            }
            $used[$code] = true;
            DB::table('drivers')->where('id', $driver->id)->update(['doc_code' => $code]);
            $driver->doc_code = $code;
        }

        Schema::table('drivers', function (Blueprint $table) {
            $table->unique('doc_code');
        });

        if (!Schema::hasTable('kelindans') || !Schema::hasColumn('kelindans', 'driver_id')) {
            return;
        }

        $kelindanColumns = Schema::getColumnListing('kelindans');
        foreach ($drivers as $driver) {
            $existingId = DB::table('kelindans')->where('driver_id', $driver->id)->value('id');
            if (!$existingId && !empty($driver->employeeid)) {
                $existingId = DB::table('kelindans')->where('employeeid', $driver->employeeid)->value('id');
            }

            $payload = [
                'employeeid' => $driver->employeeid,
                'name' => $driver->name,
                'phone' => $driver->phone,
                'status' => $driver->status ?? 1,
                'remark' => $driver->remark,
                'driver_id' => $driver->id,
                'updated_at' => now(),
            ];

            if ($existingId) {
                $employeeTaken = DB::table('kelindans')
                    ->where('employeeid', $driver->employeeid)
                    ->where('id', '!=', $existingId)
                    ->exists();
                if ($employeeTaken) {
                    unset($payload['employeeid']);
                }
                $payload = array_intersect_key($payload, array_flip($kelindanColumns));
                DB::table('kelindans')->where('id', $existingId)->update($payload);
                continue;
            }

            $payload['commissionrate'] = 0;
            $payload['created_at'] = now();
            if (in_array('ic', $kelindanColumns, true) && !empty($driver->ic)) {
                $icTaken = DB::table('kelindans')->where('ic', $driver->ic)->exists();
                if (!$icTaken) {
                    $payload['ic'] = $driver->ic;
                }
            }
            foreach (['bankdetails1', 'bankdetails2'] as $column) {
                if (in_array($column, $kelindanColumns, true)) {
                    $payload[$column] = '';
                }
            }
            $payload = array_intersect_key($payload, array_flip($kelindanColumns));
            DB::table('kelindans')->insert($payload);
        }
    }

    public function down()
    {
        if (Schema::hasColumn('kelindans', 'driver_id')) {
            Schema::table('kelindans', function (Blueprint $table) {
                $table->dropColumn('driver_id');
            });
        }
        if (Schema::hasColumn('drivers', 'doc_code')) {
            Schema::table('drivers', function (Blueprint $table) {
                $table->dropColumn('doc_code');
            });
        }
    }
}
