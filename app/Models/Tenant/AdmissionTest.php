<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AdmissionTest extends Model
{
    use HasUlids;

    protected $fillable = [
        'academic_year_id', 'class_id',
        'name', 'description', 'instructions',
        'duration_minutes', 'total_marks', 'passing_marks',
        'shuffle_questions', 'show_score_to_applicant',
        'is_active',
        'scheduled_date', 'window_opens_at', 'window_closes_at',
        'result_publication_mode', 'results_published_at',
    ];

    protected function casts(): array
    {
        return [
            'duration_minutes'        => 'integer',
            'total_marks'             => 'integer',
            'passing_marks'           => 'integer',
            'shuffle_questions'       => 'boolean',
            'show_score_to_applicant' => 'boolean',
            'is_active'               => 'boolean',
            'scheduled_date'          => 'date',
            'window_opens_at'         => 'datetime',
            'window_closes_at'        => 'datetime',
            'results_published_at'    => 'datetime',
        ];
    }

    /** True when now() is within the configured exam window (or no window is set). */
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

    /** True when results should be visible to the applicant right now. */
    public function areResultsPublished(): bool
    {
        return match ($this->result_publication_mode) {
            'auto'      => true,
            'manual'    => $this->results_published_at !== null,
            'scheduled' => $this->results_published_at !== null && now()->greaterThanOrEqualTo($this->results_published_at),
            default     => false,
        };
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(AdmissionTestQuestion::class)->orderBy('sort_order');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(AdmissionTestAttempt::class);
    }

    /**
     * Pick the active test for a given (academic_year, class) — prefer
     * class-specific, fall back to the year-wide test if any.
     */
    public static function resolveFor(?string $academicYearId, ?string $classId): ?self
    {
        if (! $academicYearId) {
            return null;
        }

        return static::query()
            ->where('academic_year_id', $academicYearId)
            ->where('is_active', true)
            ->where(function ($q) use ($classId) {
                $q->where('class_id', $classId)->orWhereNull('class_id');
            })
            ->orderByRaw('class_id IS NULL')
            ->first();
    }
}
