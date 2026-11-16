<?php

namespace App\Models;

use Eloquent as Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Class Code
 * @package App\Models
 * @version July 16, 2022, 1:08 pm UTC
 *
 * @property string $code
 * @property string $description
 * @property string $value
 * @property integer $sequence
 * @property string $STR_UDF1
 * @property string $STR_UDF2
 * @property string $STR_UDF3
 * @property integer $INT_UDF1
 * @property integer $INT_UDF2
 * @property integer $INT_UDF3
 */
class Code extends Model
{
    // use SoftDeletes;

    use HasFactory;

    public $table = 'codes';
    

    protected $dates = ['deleted_at'];


    protected $primaryKey = 'id';

    public $fillable = [
        'code',
        'description',
        'value',
        'sequence',
        'STR_UDF1',
        'STR_UDF2',
        'STR_UDF3',
        'INT_UDF1',
        'INT_UDF2',
        'INT_UDF3'
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'code' => 'string',
        'description' => 'string',
        'value' => 'string',
        'sequence' => 'integer',
        'STR_UDF1' => 'string',
        'STR_UDF2' => 'string',
        'STR_UDF3' => 'string',
        'INT_UDF1' => 'integer',
        'INT_UDF2' => 'integer',
        'INT_UDF3' => 'integer'
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'code' => 'required',
        'value' => 'required',
        'sequence' => 'required'
    ];

    /**
     * Generate the next running number for a document type, formatted as
     * {prefix}{yymm}/{0001}, resetting to 0001 whenever the calendar month
     * changes. STR_UDF1 (an unused generic extension column on this table)
     * stores which yymm period the current `value` belongs to, so a month
     * change can be detected without a schema migration.
     */
    public static function nextRunningNumber(string $code, string $prefix, bool $reuseCancelled = true): string
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($code, $prefix, $reuseCancelled) {
            $row = static::where('code', $code)->lockForUpdate()->firstOrFail();
            $period = date('ym');

            // Invoice numbers: a number freed by a cancelled invoice of this
            // month is handed out again before the counter moves on.
            if ($reuseCancelled && in_array($code, ['invoicerunningnumber', 'cashsalesrunningnumber'], true)) {
                $reused = static::takeCancelledInvoiceNumber($prefix, $period);
                if ($reused !== null) {
                    return $reused;
                }
            }

            if ($row->STR_UDF1 !== $period) {
                $row->value = 1;
                $row->STR_UDF1 = $period;
            } else {
                $row->value = ((int) $row->value) + 1;
            }
            $row->save();

            return $prefix . $period . '/' . sprintf('%04d', $row->value);
        });
    }

    /**
     * Next invoice, cash-sale or DO number for one driver:
     * {IV|CS|DO}{yymm}/{driver code}/{0001}.
     *
     * The number is the driver's highest number of that type this month + 1,
     * read from the documents themselves (no counter is kept): the first one
     * of each month is 0001. Cancelled documents still count (their number
     * stays used); offline numbers (.../A0001) are a separate series and do
     * not count. A cancelled number of this month is handed out again first,
     * as before.
     *
     * The driver's row is held until the caller's transaction ends, so two
     * documents for one driver cannot take the same number; the unique index
     * on the number column is the last line of defence.
     *
     * A driver with no code uses "00".
     */
    public static function nextDocumentNumber(string $prefix, $driverId = null, bool $reuseCancelled = true): string
    {
        $prefix = strtoupper($prefix);
        if (!in_array($prefix, ['IV', 'CS', 'DO'], true)) {
            $prefix = 'CS';
        }

        return \Illuminate\Support\Facades\DB::transaction(function () use ($prefix, $driverId, $reuseCancelled) {
            $driver = $driverId ? Driver::where('id', $driverId)->lockForUpdate()->first() : null;
            if (!$driver) {
                static::lockDocumentCounters($prefix);
            }
            $docCode = $driver ? $driver->documentCode() : '00';
            $period = date('ym');

            if ($reuseCancelled && $prefix !== 'DO') {
                $reused = static::takeCancelledInvoiceNumber($prefix, $period, $docCode);
                if ($reused !== null) {
                    return $reused;
                }
            }

            $stem = $prefix . $period . '/' . $docCode . '/';
            [$table, $column] = $prefix === 'DO' ? ['deliveryorders', 'dono'] : ['invoices', 'invoiceno'];
            $row = \Illuminate\Support\Facades\DB::table($table)
                ->where($column, 'like', $stem . '%')
                ->where($column, 'not like', $stem . 'A%')
                ->selectRaw('MAX(CAST(SUBSTRING(' . $column . ', ?) AS UNSIGNED)) as seq', [strlen($stem) + 1])
                ->first();

            return $stem . sprintf('%04d', ((int) ($row->seq ?? 0)) + 1);
        });
    }

    /**
     * Kept for older app versions that send their own number: nothing to do,
     * because the next number is always read from the saved documents.
     */
    public static function acknowledgeDocumentNumber(string $prefix, $driverId, ?string $number): void
    {
    }

    /**
     * Documents without a driver: hold a shared row so two of them cannot take
     * the same number.
     */
    protected static function lockDocumentCounters(string $prefix): void
    {
        $shared = ['IV' => 'invoicerunningnumber', 'CS' => 'cashsalesrunningnumber', 'DO' => 'dorunningnumber'][$prefix] ?? 'cashsalesrunningnumber';
        static::where('code', $shared)->lockForUpdate()->first();
    }

    /**
     * Lowest running number of this month held by a cancelled invoice, or null.
     *
     * The cancelled invoice keeps its record but gives the number up: it is
     * renamed with a "-C" suffix (IV2610/0005 -> IV2610/0005-C) so the number
     * stays unique. Invoices already sent to AutoCount are left alone - their
     * number exists there and must not be issued twice.
     *
     * Must run inside the caller's transaction (the driver row is held).
     */
    protected static function takeCancelledInvoiceNumber(string $prefix, string $period, ?string $docCode = null): ?string
    {
        $pattern = $docCode
            ? '^' . $prefix . $period . '/' . $docCode . '/[0-9]+$'
            : '^' . $prefix . $period . '/[0-9]+$';

        $candidates = Invoice::where('status', 2)
            ->where('invoiceno', 'REGEXP', $pattern)
            ->whereNull('api_invoice_id')
            ->whereNotIn('sync_status', [Invoice::SYNC_SYNCING, Invoice::SYNC_SYNCED])
            ->orderBy('invoiceno')
            ->lockForUpdate()
            ->get(['id', 'invoiceno']);

        foreach ($candidates as $cancelled) {
            $number = $cancelled->invoiceno;

            // Never hand out a number another invoice is still using
            if (Invoice::where('invoiceno', $number)->where('id', '!=', $cancelled->id)->exists()) {
                continue;
            }

            $suffix = 1;
            do {
                $renamed = $number . '-C' . ($suffix > 1 ? $suffix : '');
                $suffix++;
            } while (Invoice::where('invoiceno', $renamed)->exists());

            Invoice::where('id', $cancelled->id)->update(['invoiceno' => $renamed]);

            return $number;
        }

        return null;
    }
}
