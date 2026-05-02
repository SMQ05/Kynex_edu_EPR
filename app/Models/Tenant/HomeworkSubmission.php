<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * HomeworkSubmission — A student's submission for a homework assignment.
 *
 * @property string           $id
 * @property string           $homework_id
 * @property string           $student_id
 * @property string|null      $submission_text
 * @property string|null      $attachment_path
 * @property \Carbon\Carbon   $submitted_at
 * @property string|null      $grade
 * @property string|null      $feedback
 * @property string|null      $graded_by
 * @property \Carbon\Carbon|null $graded_at
 */
class HomeworkSubmission extends Model
{
    use HasUlids;

    protected $table = 'homework_submissions';

    protected $fillable = [
        'homework_id',
        'student_id',
        'submission_text',
        'attachment_path',
        'submitted_at',
        'grade',
        'marks_obtained',
        'total_marks',
        'feedback',
        'graded_by',
        'graded_at',
    ];

    protected function casts(): array
    {
        return [
            'submitted_at'   => 'datetime',
            'graded_at'      => 'datetime',
            'marks_obtained' => 'decimal:2',
            'total_marks'    => 'integer',
        ];
    }

    /** Convert marks_obtained → percentage (0-100) using total_marks. */
    public function percentage(): ?float
    {
        if ($this->total_marks === null || (int) $this->total_marks === 0) {
            return null;
        }
        if ($this->marks_obtained === null) {
            return null;
        }
        return round(((float) $this->marks_obtained) * 100 / (int) $this->total_marks, 2);
    }

    // ── Relationships ────────────────────────────────────────────────

    public function homework(): BelongsTo
    {
        return $this->belongsTo(HomeworkAssignment::class, 'homework_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    /** The teacher/admin who graded the submission. */
    public function gradedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\SchoolUser::class, 'graded_by');
    }

    // ── Helpers ──────────────────────────────────────────────────────

    public function isGraded(): bool
    {
        return ! is_null($this->grade);
    }

    public function isPending(): bool
    {
        return is_null($this->grade);
    }
}
