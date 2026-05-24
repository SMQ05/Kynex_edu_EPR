<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Models\Concerns\TracksCreator;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * Generic per-tenant key/value JSON settings store. Prefer the
 * App\Support\SchoolSettings helper for reads/writes (it caches
 * per request); this model is the underlying storage.
 */
class SchoolSetting extends Model
{
    use HasUlids, TracksCreator;

    protected $fillable = [
        'key',
        'value',
        'group',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'array',
        ];
    }
}
