<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Enums\ApplicationStatus;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class StudentApplication extends Model
{
    use HasUlids, SoftDeletes;

    protected $fillable = [
        'academic_year_id', 'class_id', 'section_id', 'campus_id',
        'first_name', 'last_name', 'date_of_birth', 'gender',
        'phone', 'email', 'address', 'city',
        'student_cnic', 'parent_cnic',
        'father_name', 'mother_name', 'guardian_phone', 'guardian_email',
        'previous_school', 'notes', 'applicant_photo',
        'status',
        'entry_test_scheduled_at', 'entry_test_room', 'test_session_id',
        'entry_test_score', 'entry_test_notes',
        'interview_scheduled_at', 'interview_room', 'interview_panel', 'interview_session_id',
        'interview_score', 'interview_notes',
        'previous_school_score', 'previous_score_max',
        'final_score', 'final_percentage', 'weighted_components',
        'auto_rejected', 'auto_reject_reason',
        'auto_decision_status', 'auto_decision_at',
        'reviewed_by', 'reviewed_at', 'decision_notes',
        'student_id',
        'public_token',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth'           => 'date',
            'entry_test_scheduled_at' => 'datetime',
            'interview_scheduled_at'  => 'datetime',
            'auto_decision_at'        => 'datetime',
            'reviewed_at'             => 'datetime',
            'entry_test_score'        => 'decimal:2',
            'interview_score'         => 'decimal:2',
            'previous_school_score'   => 'decimal:2',
            'previous_score_max'      => 'decimal:2',
            'final_score'             => 'decimal:2',
            'final_percentage'        => 'decimal:2',
            'weighted_components'     => 'array',
            'auto_rejected'           => 'boolean',
            'status'                  => ApplicationStatus::class,
        ];
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function testSession(): BelongsTo
    {
        return $this->belongsTo(AdmissionSession::class, 'test_session_id');
    }

    public function interviewSession(): BelongsTo
    {
        return $this->belongsTo(AdmissionSession::class, 'interview_session_id');
    }

    public function testAttempts(): HasMany
    {
        return $this->hasMany(AdmissionTestAttempt::class, 'student_application_id');
    }

    public function latestTestAttempt(): HasOne
    {
        return $this->hasOne(AdmissionTestAttempt::class, 'student_application_id')->latestOfMany();
    }

    /**
     * True when the entry_test_score on this application was written by
     * the online test pipeline (a submitted AdmissionTestAttempt exists).
     * Such scores are immutable from the marks-entry UI.
     */
    public function wasTestScoreFromOnlineAttempt(): bool
    {
        return $this->testAttempts()
            ->whereNotNull('submitted_at')
            ->whereIn('status', ['submitted', 'graded', 'expired'])
            ->exists();
    }
}
