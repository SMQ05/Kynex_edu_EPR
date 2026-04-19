<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Models\Concerns\RequiresApproval;
use App\Models\SchoolUser;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamMark extends Model
{
    use HasUlids, RequiresApproval;

    protected $fillable = [
        'exam_schedule_id',
        'student_id',
        'marks_obtained',
        'is_absent',
        'remarks',
        'entered_by',
    ];

    protected function casts(): array
    {
        return [
            'marks_obtained' => 'decimal:2',
            'is_absent'      => 'boolean',
        ];
    }

    // ── Relationships ──────────────────────────────────────────────

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(ExamSchedule::class, 'exam_schedule_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function enteredBy(): BelongsTo
    {
        return $this->belongsTo(SchoolUser::class, 'entered_by');
    }

    // ── Accessors ──────────────────────────────────────────────────

    public function getPercentageAttribute(): ?float
    {
        if ($this->is_absent || $this->marks_obtained === null) {
            return null;
        }

        $fullMarks = $this->schedule?->full_marks;

        return $fullMarks > 0
            ? round(($this->marks_obtained / $fullMarks) * 100, 2)
            : null;
    }

    public function getIsPassAttribute(): ?bool
    {
        if ($this->is_absent || $this->marks_obtained === null) {
            return null;
        }

        return $this->marks_obtained >= ($this->schedule?->pass_marks ?? 0);
    }
}
