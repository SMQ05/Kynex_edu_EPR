<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Enums\SyllabusStatus;
use App\Models\SchoolUser;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Syllabus extends Model
{
    use HasUlids, SoftDeletes;

    protected $table = 'syllabi';

    protected $fillable = [
        'class_id',
        'section_id',
        'subject_id',
        'academic_year_id',
        'teacher_id',
        'title',
        'description',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => SyllabusStatus::class,
        ];
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class, 'section_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(SchoolUser::class, 'teacher_id');
    }

    public function topics(): HasMany
    {
        return $this->hasMany(SyllabusTopic::class)->orderBy('sort_order')->orderBy('week_number');
    }
}
