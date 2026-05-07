<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Casts\AsEnum;
use App\Enums\AttendanceStatus;
use App\Models\SchoolUser;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceRecord extends Model
{
    use HasUlids;

    protected $fillable = [
        'student_id',
        'class_id',
        'section_id',
        'academic_year_id',
        'date',
        'status',
        'marked_by',
        'remarks',
        'late_minutes',
        'notified_at',
    ];

    protected function casts(): array
    {
        return [
            'date'         => 'date',
            'status'       => AsEnum::of(AttendanceStatus::class),
            'late_minutes' => 'integer',
            'notified_at'  => 'datetime',
        ];
    }

    // ── Relationships ──────────────────────────────────────────────

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

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function marker(): BelongsTo
    {
        return $this->belongsTo(SchoolUser::class, 'marked_by');
    }

    // ── Scopes ─────────────────────────────────────────────────────

    public function scopeForClass($query, string $classId, string $sectionId)
    {
        return $query->where('class_id', $classId)
            ->where('section_id', $sectionId);
    }

    public function scopeOnDate($query, $date)
    {
        return $query->whereDate('date', $date);
    }

    public function scopeAbsent($query)
    {
        return $query->where('status', AttendanceStatus::Absent);
    }
}
