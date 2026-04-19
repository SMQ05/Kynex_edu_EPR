<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * DailyActivityLog — Records daily activity scores per student per subject.
 *
 * Used for exam weightage calculation:
 *   - participation_score: 0–10
 *   - homework_score:      0–10
 *   - behaviour_score:     0–10
 *   - total_score:         0–30 (DB-computed)
 *
 * Aggregate scores across a term contribute to the weighted final result.
 *
 * @property string      $id
 * @property string      $student_id
 * @property string      $class_id
 * @property string|null $section_id
 * @property string|null $subject_id
 * @property string      $academic_year_id
 * @property string      $recorded_by
 * @property \Carbon\Carbon $log_date
 * @property int         $participation_score
 * @property int         $homework_score
 * @property int         $behaviour_score
 * @property int         $total_score
 * @property string|null $notes
 */
class DailyActivityLog extends Model
{
    use HasUlids;

    protected $table = 'daily_activity_logs';

    protected $fillable = [
        'student_id',
        'class_id',
        'section_id',
        'subject_id',
        'academic_year_id',
        'recorded_by',
        'log_date',
        'participation_score',
        'homework_score',
        'behaviour_score',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'log_date'             => 'date',
            'participation_score'  => 'integer',
            'homework_score'       => 'integer',
            'behaviour_score'      => 'integer',
            'total_score'          => 'integer',
        ];
    }

    // ── Relationships ────────────────────────────────────────────────

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class, 'section_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id');
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\SchoolUser::class, 'recorded_by');
    }

    // ── Scopes ───────────────────────────────────────────────────────

    public function scopeForStudent($query, string $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    public function scopeForAcademicYear($query, string $yearId)
    {
        return $query->where('academic_year_id', $yearId);
    }

    public function scopeForSubject($query, string $subjectId)
    {
        return $query->where('subject_id', $subjectId);
    }

    // ── Helpers ──────────────────────────────────────────────────────

    /**
     * Convert the 0–30 total_score to a 0–100 percentage.
     */
    public function getTotalPercentageAttribute(): float
    {
        return round(($this->total_score / 30) * 100, 2);
    }
}
