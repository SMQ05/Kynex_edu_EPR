<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Tenant\SchoolSetting;

/**
 * Generic per-tenant key/value settings helper backed by the
 * `school_settings` table. Values are stored as JSON, so any
 * scalar / array is preserved.
 *
 * Reads are cached for the duration of the request to avoid
 * repeated queries on pages that touch many settings.
 *
 * Usage:
 *   SchoolSettings::get('currency.code', 'PKR');
 *   SchoolSettings::set('currency.code', 'PKR', group: 'currency');
 *   SchoolSettings::many(['currency.code', 'currency.symbol']);
 */
class SchoolSettings
{
    /** @var array<string,mixed>|null Per-request memo of all settings (key => value). */
    protected static ?array $cache = null;

    /** Read a single setting, falling back to $default when absent. */
    public static function get(string $key, mixed $default = null): mixed
    {
        $all = static::all();

        return array_key_exists($key, $all) ? $all[$key] : $default;
    }

    /**
     * Read several keys at once.
     *
     * @param  array<int,string>  $keys
     * @return array<string,mixed>  key => value (default null for missing keys)
     */
    public static function many(array $keys): array
    {
        $all = static::all();
        $out = [];
        foreach ($keys as $key) {
            $out[$key] = $all[$key] ?? null;
        }

        return $out;
    }

    /** Upsert a single setting. Invalidates the request cache. */
    public static function set(string $key, mixed $value, string $group = 'general'): void
    {
        SchoolSetting::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'group' => $group],
        );

        static::$cache = null;
    }

    /**
     * Upsert many settings in one go.
     *
     * @param  array<string,mixed>  $values  key => value
     */
    public static function setMany(array $values, string $group = 'general'): void
    {
        foreach ($values as $key => $value) {
            SchoolSetting::query()->updateOrCreate(
                ['key' => $key],
                ['value' => $value, 'group' => $group],
            );
        }

        static::$cache = null;
    }

    /**
     * All settings as key => value, memoised for the request.
     *
     * @return array<string,mixed>
     */
    public static function all(): array
    {
        if (static::$cache !== null) {
            return static::$cache;
        }

        try {
            return static::$cache = SchoolSetting::query()
                ->pluck('value', 'key')
                ->all();
        } catch (\Throwable) {
            // Table not migrated yet (e.g. during install) — fail safe.
            return static::$cache = [];
        }
    }

    /** Clear the request cache (useful in tests / after bulk writes). */
    public static function flush(): void
    {
        static::$cache = null;
    }
}
