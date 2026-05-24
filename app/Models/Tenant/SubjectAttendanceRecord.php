<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Enums\AttendanceStatus;
use App\Models\Concerns\TracksCreator;
use App\Models\SchoolUser;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Per-subject (per-period) attendance row.
 */
class SubjectAttendanceRecord extends Model
{
    use HasUlids, TracksCreator;

    protected $fillable = [
        'student_id',
        'class_id',
        'section_id',
        'subject_id',
        'date',
        'period',
        'status',
        'remarks',
        'marked_by',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'date'   => 'date',
            'status' => AttendanceStatus::class,
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
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

    public function marker(): BelongsTo
    {
        return $this->belongsTo(SchoolUser::class, 'marked_by');
    }
}
