<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Models\Concerns\TracksCreator;
use App\Models\SchoolUser;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Online exam for ENROLLED students. Modeled on the admission-test engine
 * but draws from the reusable question bank (ExamQuestion) via a pivot.
 * AI auto-grading of short/essay answers is allowed for exams.
 */
class OnlineExam extends Model
{
    use HasUlids, SoftDeletes, TracksCreator;

    protected $fillable = [
        'academic_year_id',
        'class_id',
        'section_id',
        'subject_id',
        'exam_id',
        'name',
        'description',
        'instructions',
        'duration_minutes',
        'total_marks',
        'passing_marks',
        'shuffle_questions',
        'show_score_to_student',
        'ai_grade_enabled',
        'status',
        'window_opens_at',
        'window_closes_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'duration_minutes'      => 'integer',
            'total_marks'           => 'integer',
            'passing_marks'         => 'integer',
            'shuffle_questions'     => 'boolean',
            'show_score_to_student' => 'boolean',
            'ai_grade_enabled'      => 'boolean',
            'window_opens_at'       => 'datetime',
            'window_closes_at'      => 'datetime',
        ];
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

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function questions(): BelongsToMany
    {
        return $this->belongsToMany(ExamQuestion::class, 'online_exam_questions', 'online_exam_id', 'exam_question_id')
            ->withPivot(['marks', 'sort_order'])
            ->withTimestamps()
            ->orderByPivot('sort_order');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(OnlineExamAttempt::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(SchoolUser::class, 'created_by');
    }

    /** True when now() is within the configured window (or no window set). */
    public function isWindowOpen(): bool
    {
        $now = now();
        if ($this->window_opens_at && $now->lessThan($this->window_opens_at)) {
            return false;
        }
        if ($this->window_closes_at && $now->greaterThan($this->window_closes_at)) {
            return false;
        }

        return true;
    }

    /** Recompute total_marks from attached questions (pivot override wins). */
    public function recomputeTotalMarks(): int
    {
        $total = (int) round(
            $this->questions->sum(fn (ExamQuestion $q) => (float) ($q->pivot->marks ?? $q->marks))
        );

        $this->update(['total_marks' => $total]);

        return $total;
    }
}
