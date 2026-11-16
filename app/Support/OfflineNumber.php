<?php

namespace App\Support;

use App\Models\DeliveryOrder;
use App\Models\Driver;
use App\Models\Invoice;
use App\Models\SalesOrder;

/**
 * Offline document numbers. The phone makes them while it has no signal,
 * from its own counter, so they never share the online series:
 *
 *   Invoice      IV2611/BK07/A0001     (online IV2611/BK07/0001)
 *   Cash Sale    CS2611/BK07/A0001     (online CS2611/BK07/0001)
 *   Delivery     DO2611/BK07/A0001     (online DO2611/BK07/0001)
 *   Sales Order  SO2611/BK07/A0001     (online SO2611/0001)
 *
 * YYMM is the month of the sale (the document date), BK07 the driver's
 * document code, and A + 4 digits the offline running number. The counter
 * is per driver, per type, per month.
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

    public static function format(string $prefix, string $period, string $docCode, int $sequence): string
    {
        return $prefix . $period . '/' . $docCode . '/A' . sprintf('%04d', $sequence);
    }

    /**
     * Split an offline number into its parts, or null when it is not one.
     *
     * @return array{prefix:string, period:string, code:string, sequence:int}|null
     */
    public static function parse(?string $number): ?array
    {
        if ($number === null || !preg_match('/^(IV|CS|DO|SO)(\d{4})\/([A-Z0-9]{1,6})\/A(\d{4,})$/', $number, $m)) {
            return null;
        }

        return ['prefix' => $m[1], 'period' => $m[2], 'code' => $m[3], 'sequence' => (int) $m[4]];
    }

    /**
     * Whether $number is a well formed offline number for this driver, this
     * document type and the month of $date.
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

    /** Highest offline running number used for this driver, type and month. */
    public static function last(string $prefix, string $period, Driver $driver): int
    {
        [$model, $column] = self::TABLES[$prefix];
        $stem = $prefix . $period . '/' . $driver->documentCode() . '/A';

        $row = $model::query()
            ->toBase()
            ->where($column, 'like', $stem . '%')
            ->selectRaw('MAX(CAST(SUBSTRING(' . $column . ', ?) AS UNSIGNED)) as seq', [strlen($stem) + 1])
            ->first();

        return (int) ($row->seq ?? 0);
    }

    /** The next free offline number for this driver, type and month. */
    public static function next(string $prefix, string $period, Driver $driver): string
    {
        return static::format($prefix, $period, $driver->documentCode(), static::last($prefix, $period, $driver) + 1);
    }

    /**
     * Last used offline number per type for this month and the two before,
     * e.g. ['IV2611' => 4, 'CS2611' => 12, 'SO2610' => 3]. The phone takes
     * the higher of this and its own counter, so a reinstalled app or a
     * new phone carries on from the right number.
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
