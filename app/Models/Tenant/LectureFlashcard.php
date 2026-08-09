<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A single front/back revision card belonging to one lecture.
 *
 * Separate from exam_questions on purpose: a card has no options, no marks and
 * no correct answer to grade against, so reusing the question model would leave
 * four columns permanently null and introduce a question type nothing can mark.
 */
class LectureFlashcard extends Model
{
    use HasUlids;
    use SoftDeletes;

    protected $fillable = [
        'study_material_id',
        'front',
        'back',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function lecture(): BelongsTo
    {
        return $this->belongsTo(StudyMaterial::class, 'study_material_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
