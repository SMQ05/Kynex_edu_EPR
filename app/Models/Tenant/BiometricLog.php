<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Models\SchoolUser;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BiometricLog extends Model
{
    use HasUlids;

    protected $fillable = [
        'device_id',
        'device_user_id',
        'school_user_id',
        'student_id',
        'punch_time',
        'punch_type',
        'is_processed',
    ];

    protected function casts(): array
    {
        return [
            'punch_time'   => 'datetime',
            'is_processed' => 'boolean',
        ];
    }

    // ── Relationships ──────────────────────────────────────────────

    public function device(): BelongsTo
    {
        return $this->belongsTo(AttendanceDevice::class, 'device_id');
    }

    public function schoolUser(): BelongsTo
    {
        return $this->belongsTo(SchoolUser::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    // ── Scopes ─────────────────────────────────────────────────────

    public function scopeUnprocessed($query)
    {
        return $query->where('is_processed', false);
    }
}
