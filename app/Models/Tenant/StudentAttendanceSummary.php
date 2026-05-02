<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Read-only model backed by the mv_student_attendance_summary materialized view.
 */
class StudentAttendanceSummary extends Model
{
    protected $table = 'mv_student_attendance_summary';

    public $timestamps = false;

    public $incrementing = false;

    protected $keyType = 'string';

    protected function casts(): array
    {
        return [
            'total_days'            => 'integer',
            'present_days'          => 'integer',
            'absent_days'           => 'integer',
            'late_days'             => 'integer',
            'attendance_percentage' => 'decimal:2',
            'last_updated_date'     => 'date',
        ];
    }

    // ── Relationships ──────────────────────────────────────────────

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    // ── Scopes ─────────────────────────────────────────────────────

    public function scopeForClass(Builder $query, string $classId): Builder
    {
        return $query->where('class_id', $classId);
    }

    public function scopeForAcademicYear(Builder $query, string $yearId): Builder
    {
        return $query->where('academic_year_id', $yearId);
    }

    // ── Accessors ──────────────────────────────────────────────────

    public function getAttendanceColorAttribute(): string
    {
        $pct = (float) $this->attendance_percentage;

        if ($pct < 75) {
            return 'danger';
        }

        if ($pct < 85) {
            return 'warning';
        }

        return 'success';
    }
}
