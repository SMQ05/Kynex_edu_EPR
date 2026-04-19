<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttendanceDevice extends Model
{
    use HasUlids;

    protected $fillable = [
        'name',
        'device_type',
        'serial_number',
        'ip_address',
        'port',
        'location',
        'campus_id',
        'is_active',
        'last_sync_at',
        'pending_commands',
    ];

    protected function casts(): array
    {
        return [
            'port'             => 'integer',
            'is_active'        => 'boolean',
            'last_sync_at'     => 'datetime',
            'pending_commands' => 'array',
        ];
    }

    // ── Relationships ──────────────────────────────────────────────

    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class);
    }

    public function biometricLogs(): HasMany
    {
        return $this->hasMany(BiometricLog::class, 'device_id');
    }

    // ── Scopes ─────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
