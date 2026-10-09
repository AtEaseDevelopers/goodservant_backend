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
     * Lowest running number of this month held by a cancelled invoice, or null.
     *
     * The cancelled invoice keeps its record but gives the number up: it is
     * renamed with a "-C" suffix (IV2610/0005 -> IV2610/0005-C) so the number
     * stays unique. Invoices already sent to AutoCount are left alone - their
     * number exists there and must not be issued twice.
     *
     * Must run inside the caller's transaction (the counter row is locked).
     */
    protected static function takeCancelledInvoiceNumber(string $prefix, string $period): ?string
    {
        $candidates = Invoice::where('status', 2)
            ->where('invoiceno', 'REGEXP', '^' . $prefix . $period . '/[0-9]+$')
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
