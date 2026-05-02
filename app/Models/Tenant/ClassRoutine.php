<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Enums\DayOfWeek;
use App\Models\SchoolUser;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClassRoutine extends Model
{
    use HasUlids;

    protected $fillable = [
        'class_id',
        'section_id',
        'academic_year_id',
        'day_of_week',
        'period_number',
        'subject_id',
        'teacher_id',
        'room_number',
        'start_time',
        'end_time',
        'is_break',
        'break_label',
    ];

    protected function casts(): array
    {
        return [
            'day_of_week' => DayOfWeek::class,
            'period_number' => 'integer',
            'start_time' => 'datetime:H:i',
            'end_time' => 'datetime:H:i',
            'is_break' => 'boolean',
        ];
    }

    // ── Relationships ────────────────────────────────────────────

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class, 'section_id');
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(SchoolUser::class, 'teacher_id');
    }
}
