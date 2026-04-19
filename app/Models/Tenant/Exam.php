<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Enums\ExamStatus;
use App\Models\SchoolUser;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Exam extends Model
{
    use HasUlids;

    protected $fillable = [
        'academic_year_id',
        'name',
        'description',
        'start_date',
        'end_date',
        'status',
        'publish_results',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'start_date'      => 'date',
            'end_date'        => 'date',
            'status'          => ExamStatus::class,
            'publish_results' => 'boolean',
        ];
    }

    // ── Relationships ──────────────────────────────────────────────

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(SchoolUser::class, 'created_by');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(ExamSchedule::class);
    }

    public function results(): HasMany
    {
        return $this->hasMany(ExamResult::class);
    }

    // ── Scopes ─────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->whereNotIn('status', [ExamStatus::Cancelled]);
    }

    public function scopePublished($query)
    {
        return $query->where('publish_results', true);
    }

    public function scopeForAcademicYear($query, string $academicYearId)
    {
        return $query->where('academic_year_id', $academicYearId);
    }
}
