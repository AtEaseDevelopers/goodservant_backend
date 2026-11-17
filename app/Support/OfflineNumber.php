<?php

namespace App\Support;

use App\Models\DeliveryOrder;
use App\Models\Driver;
use App\Models\Invoice;
use App\Models\SalesOrder;

/**
 * Numbers the phone gives documents made without signal.
 *
 *   Invoice      IV2611/BK07/0038     the driver's normal series, continued
 *   Cash Sale    CS2611/BK07/0038     on the phone from the last number it
 *   Delivery     DO2611/BK07/0011     knows - the same series as online
 *   Sales Order  SO2611/BK07/0012
 *
 * YYMM is the month of the document date, BK07 the driver's document code.
 * The server never saves a number twice: a number already used (e.g. taken
 * online while the phone was offline) is renumbered and flagged.
 */
class OfflineNumber
{
    public const PREFIXES = ['IV', 'CS', 'DO', 'SO'];

    private const TABLES = [
        'IV' => [Invoice::class, 'invoiceno'],
        'CS' => [Invoice::class, 'invoiceno'],
        'DO' => [DeliveryOrder::class, 'dono'],
        'SO' => [SalesOrder::class, 'sono'],
    ];

    /** Offline numbers use the normal series (no mark); kept as one place to change. */
    private static function mark(string $prefix): string
    {
        return '';
    }

    public static function format(string $prefix, string $period, string $docCode, int $sequence): string
    {
        return $prefix . $period . '/' . $docCode . '/' . static::mark($prefix) . sprintf('%04d', $sequence);
    }

    /**
     * Split a number into its parts, or null when it is not one of ours.
     *
     * @return array{prefix:string, period:string, code:string, sequence:int, offline:bool}|null
     */
    public static function parse(?string $number): ?array
    {
        if ($number === null || !preg_match('/^(IV|CS|DO|SO)(\d{4})\/([A-Z0-9]{1,6})\/(A?)(\d{4,})$/', $number, $m)) {
            return null;
        }

        return ['prefix' => $m[1], 'period' => $m[2], 'code' => $m[3], 'sequence' => (int) $m[5], 'offline' => $m[4] === 'A'];
    }

    /**
     * Whether $number is well formed for this driver, document type and the
     * month of $date. Both the normal series (IV2611/BK07/0038) and the old
     * offline series that apps 1.0.72 - 1.0.76 print (IV2611/BK07/A0005) are
     * kept as printed; the unique index still refuses any repeat.
     */
    public static function fits(?string $number, string $prefix, Driver $driver, string $date): bool
    {
        $parts = static::parse($number);

        return $parts !== null
            && $parts['prefix'] === $prefix
            && $parts['code'] === $driver->documentCode()
            && $parts['period'] === date('ym', strtotime($date))
            && $parts['sequence'] > 0;
    }

    /** Highest number of this series used for this driver, type and month. */
    public static function last(string $prefix, string $period, Driver $driver): int
    {
        [$model, $column] = self::TABLES[$prefix];
        $stem = $prefix . $period . '/' . $driver->documentCode() . '/' . static::mark($prefix);

        $query = $model::query()->toBase()->where($column, 'like', $stem . '%');
        if (static::mark($prefix) === '') {
            // Old offline numbers (.../A0001) are a separate, finished series.
            $query->where($column, 'not like', $stem . 'A%');
        }
        $row = $query
            ->selectRaw('MAX(CAST(SUBSTRING(' . $column . ', ?) AS UNSIGNED)) as seq', [strlen($stem) + 1])
            ->first();

        return (int) ($row->seq ?? 0);
    }

    /** The next free number of this series for this driver, type and month. */
    public static function next(string $prefix, string $period, Driver $driver): string
    {
        return static::format($prefix, $period, $driver->documentCode(), static::last($prefix, $period, $driver) + 1);
    }

    /**
     * Last used number per type for this month and the two before, e.g.
     * ['IV2611' => 10, 'CS2611' => 38, 'SO2611' => 3]. The phone continues
     * from the higher of this and its own count.
     */
    public static function lastUsedMap(Driver $driver): array
    {
        $map = [];
        foreach ([0, 1, 2] as $monthsBack) {
            $period = date('ym', strtotime("first day of -$monthsBack month"));
            foreach (self::PREFIXES as $prefix) {
                $last = static::last($prefix, $period, $driver);
                if ($last > 0) {
                    $map[$prefix . $period] = $last;
                }
            }
        }

        return $map;
    }
}
