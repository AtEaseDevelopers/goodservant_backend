<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Offline documents get their own number series (IV2611/BK07/A0001) made on
 * the phone, and every document can carry the phone's request id
 * (client_ref) so a resent upload or a retried create returns the saved
 * document instead of making a second one.
 *
 * The document numbers themselves become unique in the database as the last
 * line of defence against duplicates.
 */
class AddOfflineDocumentFields extends Migration
{
    private const TABLES = [
        'invoices' => 'invoiceno',
        'deliveryorders' => 'dono',
        'sales_orders' => 'sono',
    ];

    public function up()
    {
        foreach (self::TABLES as $table => $numberColumn) {
            $duplicates = DB::table($table)
                ->select($numberColumn, DB::raw('count(*) as n'))
                ->groupBy($numberColumn)
                ->having('n', '>', 1)
                ->pluck($numberColumn)
                ->all();
            if ($duplicates) {
                throw new RuntimeException(
                    "Cannot make $table.$numberColumn unique: these numbers are used more than once -> "
                    . implode(', ', array_slice($duplicates, 0, 20))
                );
            }

            Schema::table($table, function (Blueprint $t) use ($table, $numberColumn) {
                $t->string('client_ref', 64)->nullable()->after($numberColumn);
                $t->boolean('is_offline')->default(false)->after('client_ref');
                $t->string('renumbered_from', 40)->nullable()->after('is_offline');
                $t->text('review_note')->nullable()->after('renumbered_from');
                $t->timestamp('reviewed_at')->nullable()->after('review_note');
                $t->string('reviewed_by', 191)->nullable()->after('reviewed_at');
                $t->timestamp('uploaded_at')->nullable()->after('reviewed_by');

                $t->unique('client_ref', $table . '_client_ref_unique');
                $t->unique($numberColumn, $table . '_' . $numberColumn . '_unique');
            });
        }

        Schema::table('drivers', function (Blueprint $t) {
            // What the driver's phone last reported: documents still waiting
            // to upload, and which phone. Used to warn when the driver logs
            // in on another phone while the old one still holds documents.
            $t->unsignedInteger('offline_pending')->default(0);
            $t->string('offline_device', 64)->nullable();
            $t->timestamp('offline_reported_at')->nullable();
        });
    }

    public function down()
    {
        foreach (self::TABLES as $table => $numberColumn) {
            Schema::table($table, function (Blueprint $t) use ($table, $numberColumn) {
                $t->dropUnique($table . '_client_ref_unique');
                $t->dropUnique($table . '_' . $numberColumn . '_unique');
                $t->dropColumn(['client_ref', 'is_offline', 'renumbered_from', 'review_note', 'reviewed_at', 'reviewed_by', 'uploaded_at']);
            });
        }

        Schema::table('drivers', function (Blueprint $t) {
            $t->dropColumn(['offline_pending', 'offline_device', 'offline_reported_at']);
        });
    }
}
