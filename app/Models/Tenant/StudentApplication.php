<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Enums\ApplicationStatus;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class StudentApplication extends Model
{
    use HasUlids, SoftDeletes;

    protected $fillable = [
        'academic_year_id', 'class_id', 'campus_id',
        'first_name', 'last_name', 'date_of_birth', 'gender',
        'phone', 'email', 'address', 'city',
        'father_name', 'mother_name', 'guardian_phone', 'guardian_email',
        'previous_school', 'notes',
        'status',
        'entry_test_scheduled_at', 'entry_test_room',
        'entry_test_score', 'entry_test_notes',
        'reviewed_by', 'reviewed_at', 'decision_notes',
        'student_id',
        'public_token',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth'           => 'date',
            'entry_test_scheduled_at' => 'datetime',
            'reviewed_at'             => 'datetime',
            'entry_test_score'        => 'decimal:2',
            'status'                  => ApplicationStatus::class,
        ];
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
