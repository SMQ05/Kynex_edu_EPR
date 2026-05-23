<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class AdmissionCriteria extends Model
{
    use HasUlids;

    protected $table = 'admission_criteria';

    protected $fillable = [
        'academic_year_id', 'name',
        'applies_to_all_classes',
        'test_weightage', 'interview_weightage', 'previous_score_weightage',
        'test_full_marks', 'interview_full_marks',
        'min_test_score', 'min_interview_score', 'min_final_percentage',
        'auto_reject_below_test', 'auto_reject_below_interview',
        'skip_interview_for_all',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'applies_to_all_classes'      => 'boolean',
            'test_weightage'              => 'integer',
            'interview_weightage'         => 'integer',
            'previous_score_weightage'    => 'integer',
            'test_full_marks'             => 'integer',
            'interview_full_marks'        => 'integer',
            'min_test_score'              => 'decimal:2',
            'min_interview_score'         => 'decimal:2',
            'min_final_percentage'        => 'decimal:2',
            'auto_reject_below_test'      => 'boolean',
            'auto_reject_below_interview' => 'boolean',
            'skip_interview_for_all'      => 'boolean',
            'is_active'                   => 'boolean',
        ];
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function classes(): BelongsToMany
    {
        return $this->belongsToMany(
            SchoolClass::class,
            'admission_criteria_class',
            'admission_criteria_id',
            'class_id',
        );
    }

    /**
     * Resolve the active criteria for an application.
     *
     * Priority:
     *   1. A class-specific criteria — i.e. an active row for the same
     *      academic year that explicitly includes this class via the
     *      pivot. Works for both single- and multi-class criteria.
     *   2. A whole-school criteria — applies_to_all_classes = true for
     *      the same academic year.
     *
     * Returns null if neither is configured.
     */
    public static function resolveFor(?string $academicYearId, ?string $classId): ?self
    {
        if (! $academicYearId) {
            return null;
        }

        if ($classId) {
            $specific = static::query()
                ->where('academic_year_id', $academicYearId)
                ->where('is_active', true)
                ->whereHas('classes', fn ($q) => $q->where('classes.id', $classId))
                ->first();

            if ($specific) {
                return $specific;
            }
        }

        return static::query()
            ->where('academic_year_id', $academicYearId)
            ->where('is_active', true)
            ->where('applies_to_all_classes', true)
            ->first();
    }
}
