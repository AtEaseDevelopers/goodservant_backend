<?php

namespace App\Models;

use Eloquent as Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/**
 * Key/value application settings, read and written through static helpers so
 * callers never touch the table directly. Values are cached per key; writes
 * forget the cache.
 *
 * Known keys:
 *  - autocount_nightly_sync_enabled  '1' | '0'
 *  - autocount_nightly_sync_time     'HH:MM' (24h, Asia/Kuala_Lumpur)
 */
class Setting extends Model
{
    public $table = 'app_settings';

    public $fillable = [
        'key',
        'value',
    ];

    public const AUTOCOUNT_NIGHTLY_SYNC_ENABLED = 'autocount_nightly_sync_enabled';
    public const AUTOCOUNT_NIGHTLY_SYNC_TIME    = 'autocount_nightly_sync_time';

    public const DEFAULT_NIGHTLY_SYNC_TIME = '23:00';

    private const CACHE_PREFIX = 'app_setting:';

    /**
     * Read a setting, falling back to $default when it is missing. Tolerant of
     * the table not existing yet (e.g. before migrations) so it is safe to call
     * from the console scheduler.
     */
    public static function get(string $key, $default = null)
    {
        return Cache::rememberForever(self::CACHE_PREFIX . $key, function () use ($key, $default) {
            if (!Schema::hasTable('app_settings')) {
                return $default;
            }

            $row = static::query()->where('key', $key)->first();

            return $row ? $row->value : $default;
        });
    }

    /**
     * Write a setting and refresh its cached value.
     */
    public static function set(string $key, $value): void
    {
        static::query()->updateOrCreate(['key' => $key], ['value' => $value]);

        Cache::forget(self::CACHE_PREFIX . $key);
    }

    public static function nightlySyncEnabled(): bool
    {
        return (bool) ((int) static::get(self::AUTOCOUNT_NIGHTLY_SYNC_ENABLED, '0'));
    }

    /**
     * The configured nightly-sync time as 'HH:MM', always valid: an empty or
     * malformed stored value falls back to the default so the scheduler never
     * receives garbage.
     */
    public static function nightlySyncTime(): string
    {
        $time = (string) static::get(self::AUTOCOUNT_NIGHTLY_SYNC_TIME, self::DEFAULT_NIGHTLY_SYNC_TIME);

        if (!preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $time)) {
            return self::DEFAULT_NIGHTLY_SYNC_TIME;
        }

        return $time;
    }
}
