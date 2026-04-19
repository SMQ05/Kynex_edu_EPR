<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AppType;
use App\Enums\DevicePlatform;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * DeviceToken — Stores FCM push notification tokens for mobile devices.
 *
 * Lives in the tenant database (device_tokens table) but uses the App\Models
 * namespace (same pattern as SchoolUser). Queries run against the active
 * tenant connection automatically via stancl/tenancy database bootstrapper.
 *
 * NOTE (Risk 2): This model lives in App\Models, not App\Models\Tenant,
 * matching the SchoolUser pattern. The tenant DB connection is resolved at
 * runtime by the tenancy bootstrapper — no explicit connection override needed.
 */
class DeviceToken extends Model
{
    use HasUlids;

    protected $fillable = [
        'school_user_id',
        'token',
        'platform',
        'app_type',
        'device_name',
        'last_used_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'platform'     => DevicePlatform::class,
            'app_type'     => AppType::class,
            'last_used_at' => 'datetime',
            'is_active'    => 'boolean',
        ];
    }

    // ── Relationships ────────────────────────────────────────────

    public function schoolUser(): BelongsTo
    {
        return $this->belongsTo(SchoolUser::class);
    }

    // ── Scopes ───────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // ── Static Helpers ───────────────────────────────────────────

    /**
     * Register a new device token or update an existing one.
     */
    public static function registerOrUpdate(
        string $schoolUserId,
        string $token,
        string $platform,
        string $appType,
        ?string $deviceName = null,
    ): self {
        return static::updateOrCreate(
            [
                'school_user_id' => $schoolUserId,
                'token'          => $token,
            ],
            [
                'platform'     => $platform,
                'app_type'     => $appType,
                'device_name'  => $deviceName,
                'last_used_at' => now(),
                'is_active'    => true,
            ],
        );
    }
}
