<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Tenant\AdmissionTest;

class AdmissionSession extends Model
{
    use HasUlids;

    protected $fillable = [
        'academic_year_id', 'class_id', 'type', 'test_id',
        'scheduled_at', 'room', 'panel', 'max_capacity', 'notes',
        'credentials_dispatched_at', 'exam_started_at',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at'                => 'datetime',
            'credentials_dispatched_at'   => 'datetime',
            'exam_started_at'             => 'datetime',
            'max_capacity'                => 'integer',
        ];
    }

    public function admissionTest(): BelongsTo
    {
        return $this->belongsTo(AdmissionTest::class, 'test_id');
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function testApplications(): HasMany
    {
        return $this->hasMany(StudentApplication::class, 'test_session_id');
    }

    public function interviewApplications(): HasMany
    {
        return $this->hasMany(StudentApplication::class, 'interview_session_id');
    }

    /** All applications assigned to this session (regardless of type). */
    public function applications(): HasMany
    {
        $fk = $this->type === 'interview' ? 'interview_session_id' : 'test_session_id';
        return $this->hasMany(StudentApplication::class, $fk);
    }

    public function assignedCount(): int
    {
        return $this->applications()->count();
    }

    public function availableSlots(): int
    {
        return max(0, $this->max_capacity - $this->assignedCount());
    }

    public function isFull(): bool
    {
        return $this->availableSlots() === 0;
    }

    /** True if session is within the next 7 days (credentials should send now). */
    public function isWithinCredentialWindow(): bool
    {
        return $this->scheduled_at->diffInDays(now(), absolute: true) <= 7
            || $this->scheduled_at->lessThanOrEqualTo(now()->addWeek());
    }
}
