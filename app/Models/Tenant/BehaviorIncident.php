<?php

namespace App\Models\Tenant;

use App\Enums\BehaviorIncidentStatus;
use App\Enums\BehaviorIncidentType;
use App\Models\SchoolUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BehaviorIncident extends Model
{
    use HasUlids;

    protected $fillable = [
        'student_id',
        'class_id',
        'section_id',
        'campus_id',
        'academic_year_id',
        'reported_by',
        'handled_by',
        'incident_type',
        'category',
        'title',
        'description',
        'incident_date',
        'incident_time',
        'location',
        'severity',
        'points',
        'action_taken',
        'action_details',
        'action_date',
        'follow_up_date',
        'status',
        'resolution_notes',
        'parent_notified',
        'parent_notified_date',
        'parent_response',
        'witnesses',
        'attachments',
        'is_confidential',
    ];

    protected function casts(): array
    {
        return [
            'incident_type'        => BehaviorIncidentType::class,
            'status'               => BehaviorIncidentStatus::class,
            'incident_date'        => 'date',
            'action_date'          => 'date',
            'follow_up_date'       => 'date',
            'parent_notified'      => 'boolean',
            'parent_notified_date' => 'date',
            'witnesses'            => 'array',
            'attachments'          => 'array',
            'points'               => 'integer',
            'is_confidential'      => 'boolean',
        ];
    }

    // ── Confidentiality Global Scope ─────────────────────────────
    protected static function booted(): void
    {
        static::addGlobalScope('confidential_access', function (Builder $query) {
            $user = auth()->user();

            if (! $user) {
                $query->where('is_confidential', false);
                return;
            }

            $privileged = $user->hasAnyRole([
                'SCHOOL_ADMIN', 'COUNSELOR',
            ]);

            if (! $privileged) {
                $query->where('is_confidential', false);
            }
        });
    }

    // ── Relationships ────────────────────────────────────────────

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
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

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function reportedBy(): BelongsTo
    {
        return $this->belongsTo(SchoolUser::class, 'reported_by');
    }

    public function handledBy(): BelongsTo
    {
        return $this->belongsTo(SchoolUser::class, 'handled_by');
    }

    // ── Scopes ───────────────────────────────────────────────────

    public function scopePositive($query)
    {
        return $query->where('incident_type', BehaviorIncidentType::Positive);
    }

    public function scopeNegative($query)
    {
        return $query->where('incident_type', BehaviorIncidentType::Negative);
    }

    public function scopeOpen($query)
    {
        return $query->whereIn('status', [
            BehaviorIncidentStatus::Reported,
            BehaviorIncidentStatus::Investigating,
        ]);
    }

    public function scopeForStudent($query, string $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    public function scopeConfidential($query)
    {
        return $query->where('is_confidential', true);
    }

    // ── Accessors ────────────────────────────────────────────────

    public function getIsPositiveAttribute(): bool
    {
        return $this->incident_type === BehaviorIncidentType::Positive;
    }
}
