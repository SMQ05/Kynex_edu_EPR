<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Enums\ExamResultStatus;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamResult extends Model
{
    use HasUlids;

    protected $fillable = [
        'exam_id',
        'student_id',
        'class_id',
        'total_marks',
        'marks_obtained',
        'percentage',
        'weighted_percentage',
        'grade',
        'grade_point',
        'rank',
        'status',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'total_marks'    => 'decimal:2',
            'marks_obtained' => 'decimal:2',
            'percentage'     => 'decimal:2',
            'grade_point'    => 'decimal:1',
            'rank'           => 'integer',
            'status'         => ExamResultStatus::class,
        ];
    }

    // ── Relationships ──────────────────────────────────────────────

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    // ── Scopes ─────────────────────────────────────────────────────

    public function scopePassed($query)
    {
        return $query->where('status', ExamResultStatus::Pass);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', ExamResultStatus::Fail);
    }

    public function scopeForClass($query, string $classId)
    {
        return $query->where('class_id', $classId);
    }

    public function scopeRanked($query)
    {
        return $query->orderBy('rank', 'asc');
    }
}
