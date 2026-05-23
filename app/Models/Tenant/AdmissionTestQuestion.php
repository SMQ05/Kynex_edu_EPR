<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdmissionTestQuestion extends Model
{
    use HasUlids;

    protected $fillable = [
        'admission_test_id',
        'type', 'question_text', 'options',
        'correct_answer', 'marks', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'options'    => 'array',
            'marks'      => 'decimal:2',
            'sort_order' => 'integer',
        ];
    }

    public function test(): BelongsTo
    {
        return $this->belongsTo(AdmissionTest::class, 'admission_test_id');
    }

    public function isAutoGradable(): bool
    {
        return in_array($this->type, ['mcq', 'true_false', 'short_answer', 'math'], true)
            && filled($this->correct_answer);
    }

    public function needsAiGrading(): bool
    {
        // Essay always needs AI/manual; short_answer + math need AI/manual
        // when no reference answer was supplied.
        if ($this->type === 'essay') {
            return true;
        }
        return in_array($this->type, ['short_answer', 'math'], true)
            && blank($this->correct_answer);
    }
}
