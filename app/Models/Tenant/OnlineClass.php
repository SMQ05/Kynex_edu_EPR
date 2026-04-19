<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Enums\OnlineClassStatus;
use App\Models\SchoolUser;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class OnlineClass extends Model
{
    use HasUlids, SoftDeletes;

    protected $fillable = [
        'title',
        'class_id',
        'section_id',
        'subject_id',
        'teacher_id',
        'platform_id',
        'meeting_url',
        'meeting_id',
        'passcode',
        'scheduled_at',
        'duration_minutes',
        'status',
        'recording_url',
        'notes',
        // Phase 3 additions
        'actual_start_at',
        'actual_end_at',
        'attendance_required',
        'quiz_enabled',
        'max_participants',
        'join_before_minutes',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'actual_start_at' => 'datetime',
            'actual_end_at' => 'datetime',
            'duration_minutes' => 'integer',
            'status' => OnlineClassStatus::class,
            'attendance_required' => 'boolean',
            'quiz_enabled' => 'boolean',
            'max_participants' => 'integer',
            'join_before_minutes' => 'integer',
        ];
    }

    // ── Relationships ──────────────────────────────────────────────

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(SchoolUser::class, 'teacher_id');
    }

    public function platform(): BelongsTo
    {
        return $this->belongsTo(OnlineClassPlatform::class, 'platform_id');
    }

    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(OnlineClassAttendance::class);
    }

    public function quizzes(): HasMany
    {
        return $this->hasMany(OnlineClassQuiz::class);
    }

    // ── Scopes ─────────────────────────────────────────────────────

    public function scopeScheduled($query)
    {
        return $query->where('status', OnlineClassStatus::Scheduled);
    }

    public function scopeLive($query)
    {
        return $query->where('status', OnlineClassStatus::Live);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('status', OnlineClassStatus::Scheduled)
            ->where('scheduled_at', '>=', now());
    }

    // ── Helpers ─────────────────────────────────────────────────────

    public function isJoinable(): bool
    {
        if ($this->status === OnlineClassStatus::Live) {
            return true;
        }

        if ($this->status === OnlineClassStatus::Scheduled) {
            return now()->gte($this->scheduled_at->subMinutes($this->join_before_minutes ?? 5));
        }

        return false;
    }
}
