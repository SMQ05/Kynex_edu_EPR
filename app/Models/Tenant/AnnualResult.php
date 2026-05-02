<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnnualResult extends Model
{
    use HasUlids;

    protected $fillable = [
        'academic_year_id',
        'student_id',
        'class_id',
        'section_id',
        'exam_score',
        'homework_score',
        'class_assignment_score',
        'exam_weight_percent',
        'homework_weight_percent',
        'class_assignment_weight_percent',
        'final_percentage',
        'grade',
        'grade_point',
        'rank',
        'status',
        'remarks',
        'generated_at',
    ];

    protected function casts(): array
    {
        return [
            'exam_score'                       => 'decimal:2',
            'homework_score'                   => 'decimal:2',
            'class_assignment_score'           => 'decimal:2',
            'final_percentage'                 => 'decimal:2',
            'grade_point'                      => 'decimal:2',
            'exam_weight_percent'              => 'integer',
            'homework_weight_percent'          => 'integer',
            'class_assignment_weight_percent'  => 'integer',
            'rank'                             => 'integer',
            'generated_at'                     => 'datetime',
        ];
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }
}
