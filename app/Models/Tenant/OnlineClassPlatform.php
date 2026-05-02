<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Enums\OnlineClassPlatformType;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class OnlineClassPlatform extends Model
{
    use HasUlids, SoftDeletes;

    protected $fillable = [
        'name',
        'platform_type',
        'config',
        'is_active',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'platform_type' => OnlineClassPlatformType::class,
            'config' => 'array',
            'is_active' => 'boolean',
        ];
    }

    // ── Relationships ──────────────────────────────────────────────

    public function onlineClasses(): HasMany
    {
        return $this->hasMany(OnlineClass::class, 'platform_id');
    }

    // ── Scopes ─────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
