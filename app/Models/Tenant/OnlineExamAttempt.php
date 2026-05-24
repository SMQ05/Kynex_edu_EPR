<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Models\SchoolUser;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OnlineExamAttempt extends Model
{
    use HasUlids;

    protected $fillable = [
        'online_exam_id',
        'student_id',
        'started_at',
        'submitted_at',
        'expires_at',
        'status',
        'total_marks',
        'obtained_marks',
        'percentage',
        'needs_manual_grading',
        'graded_by',
        'graded_at',
    ];

    protected function casts(): array
    {
        return [
            'started_at'           => 'datetime',
            'submitted_at'         => 'datetime',
            'expires_at'           => 'datetime',
            'graded_at'            => 'datetime',
            'total_marks'          => 'decimal:2',
            'obtained_marks'       => 'decimal:2',
            'percentage'           => 'decimal:2',
            'needs_manual_grading' => 'boolean',
        ];
    }

    public function onlineExam(): BelongsTo
    {
        return $this->belongsTo(OnlineExam::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(OnlineExamAnswer::class, 'attempt_id');
    }

    public function grader(): BelongsTo
    {
        return $this->belongsTo(SchoolUser::class, 'graded_by');
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && now()->greaterThan($this->expires_at);
    }

    public function isSubmitted(): bool
    {
        return in_array($this->status, ['submitted', 'graded', 'expired'], true);
    }
}
