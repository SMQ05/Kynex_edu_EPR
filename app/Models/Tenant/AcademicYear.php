<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AcademicYear extends Model
{
    use HasUlids, SoftDeletes;

    protected $fillable = [
        'name',
        'start_date',
        'end_date',
        'is_current',
        'exam_weight_percent',
        'homework_weight_percent',
        'class_assignment_weight_percent',
    ];

    protected function casts(): array
    {
        return [
            'start_date'                       => 'date',
            'end_date'                         => 'date',
            'is_current'                       => 'boolean',
            'exam_weight_percent'              => 'integer',
            'homework_weight_percent'          => 'integer',
            'class_assignment_weight_percent'  => 'integer',
        ];
    }

    // ── Relationships ────────────────────────────────────────────

    public function classSubjects(): HasMany
    {
        return $this->hasMany(ClassSubject::class, 'academic_year_id');
    }

    public function classRoutines(): HasMany
    {
        return $this->hasMany(ClassRoutine::class, 'academic_year_id');
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class, 'academic_year_id');
    }

    public function feeMasters(): HasMany
    {
        return $this->hasMany(FeeMaster::class, 'academic_year_id');
    }

    public function budgets(): HasMany
    {
        return $this->hasMany(Budget::class, 'academic_year_id');
    }

    // ── Scopes ───────────────────────────────────────────────────

    public function scopeCurrent($query)
    {
        return $query->where('is_current', true);
    }
}
