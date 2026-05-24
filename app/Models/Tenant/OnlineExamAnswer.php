<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OnlineExamAnswer extends Model
{
    use HasUlids;

    protected $fillable = [
        'attempt_id',
        'question_id',
        'answer_text',
        'selected_option',
        'is_correct',
        'marks_awarded',
        'ai_feedback',
    ];

    protected function casts(): array
    {
        return [
            'is_correct'    => 'boolean',
            'marks_awarded' => 'decimal:2',
        ];
    }

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(OnlineExamAttempt::class, 'attempt_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(ExamQuestion::class, 'question_id');
    }
}
