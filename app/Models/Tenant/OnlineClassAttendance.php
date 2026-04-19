<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OnlineClassAttendance extends Model
{
    use HasUlids;

    protected $table = 'online_class_attendance';

    protected $fillable = [
        'online_class_id',
        'student_id',
        'joined_at',
        'left_at',
        'duration_minutes',
        'status',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'joined_at' => 'datetime',
            'left_at' => 'datetime',
            'duration_minutes' => 'integer',
        ];
    }

    // ── Relationships ──────────────────────────────────────────────

    public function onlineClass(): BelongsTo
    {
        return $this->belongsTo(OnlineClass::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    // ── Scopes ─────────────────────────────────────────────────────

    public function scopePresent($query)
    {
        return $query->where('status', 'present');
    }

    public function scopeAbsent($query)
    {
        return $query->where('status', 'absent');
    }

    public function scopeLate($query)
    {
        return $query->where('status', 'late');
    }
}
