<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One run through a lecture's practice quiz.
 *
 * Practice is NOT assessment. Every attempt is kept rather than only the best
 * or latest, so a student can see improvement, and nothing here is read by a
 * report card or an exam result. That distinction is deliberate: the moment a
 * practice score counts for something, students stop using it to find out what
 * they do not know.
 */
class LectureQuizAttempt extends Model
{
    use HasUlids;

    protected $fillable = [
        'study_material_id',
        'student_id',
        'score',
        'total',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'integer',
            'total' => 'integer',
            'completed_at' => 'datetime',
        ];
    }

    public function lecture(): BelongsTo
    {
        return $this->belongsTo(StudyMaterial::class, 'study_material_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    /** Percentage for this run, or null when the quiz had no questions. */
    public function getPercentageAttribute(): ?float
    {
        return $this->total > 0 ? round($this->score / $this->total * 100, 1) : null;
    }
}
