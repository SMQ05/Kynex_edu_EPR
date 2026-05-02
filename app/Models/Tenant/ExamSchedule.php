<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExamSchedule extends Model
{
    use HasUlids;

    protected $fillable = [
        'exam_id',
        'class_id',
        'section_id',
        'subject_id',
        'exam_date',
        'start_time',
        'end_time',
        'room',
        'full_marks',
        'pass_marks',
    ];

    protected function casts(): array
    {
        return [
            'exam_date'  => 'date',
            'start_time' => 'datetime:H:i',
            'end_time'   => 'datetime:H:i',
            'full_marks' => 'integer',
            'pass_marks' => 'integer',
        ];
    }

    // ── Relationships ──────────────────────────────────────────────

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

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

    public function marks(): HasMany
    {
        return $this->hasMany(ExamMark::class);
    }

    // ── Scopes ─────────────────────────────────────────────────────

    public function scopeForClass($query, string $classId, ?string $sectionId = null)
    {
        $query->where('class_id', $classId);

        if ($sectionId) {
            $query->where('section_id', $sectionId);
        }

        return $query;
    }
}
