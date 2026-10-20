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
     * Next invoice or cash-sale number for one driver:
     * {IV|CS}{yymm}/{driver code}/{0001}. Each driver has a separate counter
     * that resets every calendar month. Online and offline invoices share it.
     *
     * A driver with no code uses "00".
     */
    public static function nextDocumentNumber(string $prefix, $driverId = null, bool $reuseCancelled = true): string
    {
        $prefix = strtoupper($prefix) === 'IV' ? 'IV' : 'CS';

        return \Illuminate\Support\Facades\DB::transaction(function () use ($prefix, $driverId, $reuseCancelled) {
            static::lockDocumentCounters($prefix);

            $driver = $driverId ? Driver::find($driverId) : null;
            $docCode = $driver ? $driver->documentCode() : '00';
            $row = static::documentCounterRow($prefix, $driver->id ?? 0);
            $period = date('ym');

            if ($reuseCancelled) {
                $reused = static::takeCancelledInvoiceNumber($prefix, $period, $docCode);
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

            return $prefix . $period . '/' . $docCode . '/' . sprintf('%04d', $row->value);
        });
    }

    /**
     * A number the phone already used (an offline invoice uploaded with its
     * own number) must move this driver's counter forward, so the next one
     * issued here does not repeat it.
     */
    public static function acknowledgeDocumentNumber(string $prefix, $driverId, ?string $number): void
    {
        if (!$driverId || $number === null || $number === '') {
            return;
        }

        $prefix = strtoupper($prefix) === 'IV' ? 'IV' : 'CS';
        $driver = Driver::find($driverId);
        if (!$driver) {
            return;
        }

        $pattern = '/^' . $prefix . '(\d{4})\/' . preg_quote($driver->documentCode(), '/') . '\/(\d+)$/';
        if (!preg_match($pattern, $number, $matches)) {
            return;
        }

        $period = $matches[1];
        $sequence = (int) $matches[2];

        \Illuminate\Support\Facades\DB::transaction(function () use ($prefix, $driver, $period, $sequence) {
            static::lockDocumentCounters($prefix);
            $row = static::documentCounterRow($prefix, $driver->id);

            if ($row->STR_UDF1 !== $period) {
                $row->STR_UDF1 = $period;
                $row->value = $sequence;
            } else {
                $row->value = max((int) $row->value, $sequence);
            }
            $row->save();
        });
    }

    /**
     * Hold the shared counter row so two invoices cannot take the same number.
     */
    protected static function lockDocumentCounters(string $prefix): void
    {
        $shared = $prefix === 'IV' ? 'invoicerunningnumber' : 'cashsalesrunningnumber';
        static::where('code', $shared)->lockForUpdate()->first();
    }

    protected static function documentCounterRow(string $prefix, int $driverId): self
    {
        $key = strtolower($prefix) . 'runningnumber:' . $driverId;
        $row = static::where('code', $key)->lockForUpdate()->first();
        if ($row) {
            return $row;
        }

        $row = new static();
        $row->code = $key;
        $row->description = $prefix . ' running number for driver ' . $driverId;
        $row->value = 0;
        $row->sequence = 0;
        $row->STR_UDF1 = date('ym');
        $row->save();

        return static::where('id', $row->id)->lockForUpdate()->first();
    }

    /**
     * Lowest running number of this month held by a cancelled invoice, or null.
     *
     * The cancelled invoice keeps its record but gives the number up: it is
     * renamed with a "-C" suffix (IV2610/0005 -> IV2610/0005-C) so the number
     * stays unique. Invoices already sent to AutoCount are left alone - their
     * number exists there and must not be issued twice.
     *
     * Must run inside the caller's transaction (the counter row is locked).
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
