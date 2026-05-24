<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Models\Concerns\TracksCreator;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * Due-fees login block configuration (Infix "Due Fees Login Permission").
 * Single-row config. Storage only — enforcement is reported, not wired
 * into auth here.
 */
class DueFeesLoginSetting extends Model
{
    use HasUlids, TracksCreator;

    public const APPLIES_TO = [
        'students'  => 'Students',
        'guardians' => 'Guardians',
        'both'      => 'Students & Guardians',
    ];

    protected $fillable = [
        'enabled',
        'grace_days',
        'applies_to',
        'min_due_paisas',
        'block_message',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'enabled'        => 'boolean',
            'grace_days'     => 'integer',
            'min_due_paisas' => 'integer',
        ];
    }

    /** Fetch (or lazily create) the single config row. */
    public static function current(): self
    {
        return static::query()->firstOrCreate([], [
            'enabled'    => false,
            'grace_days' => 0,
            'applies_to' => 'students',
        ]);
    }
}
