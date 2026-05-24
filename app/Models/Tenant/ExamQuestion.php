<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Models\Concerns\TracksCreator;
use App\Models\SchoolUser;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Reusable question-bank item. Mirrors AdmissionTestQuestion but is
 * subject/group scoped and attached to OnlineExam via a pivot.
 */
class ExamQuestion extends Model
{
    use HasUlids, SoftDeletes, TracksCreator;

    protected $fillable = [
        'question_group_id',
        'subject_id',
        'type',
        'difficulty',
        'question_text',
        'options',
        'correct_answer',
        'explanation',
        'marks',
        'is_active',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'options'   => 'array',
            'marks'     => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        // Inherit the group's subject when one isn't set explicitly.
        static::creating(function (self $q): void {
            if (blank($q->subject_id) && filled($q->question_group_id)) {
                $q->subject_id = QuestionGroup::query()
                    ->whereKey($q->question_group_id)
                    ->value('subject_id');
            }
        });
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(QuestionGroup::class, 'question_group_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(SchoolUser::class, 'created_by');
    }

    public function isAutoGradable(): bool
    {
        return in_array($this->type, ['mcq', 'true_false', 'short_answer', 'math'], true)
            && filled($this->correct_answer);
    }

    public function needsAiGrading(): bool
    {
        if ($this->type === 'essay') {
            return true;
        }

        return in_array($this->type, ['short_answer', 'math'], true)
            && blank($this->correct_answer);
    }
}
