<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Models\Concerns\TracksCreator;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * Simple key-value JSON store for exam-module configuration
 * (format/position/admit-card/seat-plan options).
 */
class ExamSetting extends Model
{
    use HasUlids, TracksCreator;

    protected $fillable = [
        'key',
        'value',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'array',
        ];
    }

    /** Read a setting value (with default fallback). */
    public static function get(string $key, mixed $default = null): mixed
    {
        $row = static::query()->where('key', $key)->first();

        return $row ? ($row->value ?? $default) : $default;
    }

    /** Upsert a setting value. */
    public static function put(string $key, mixed $value): void
    {
        static::query()->updateOrCreate(['key' => $key], ['value' => $value]);
    }
}
