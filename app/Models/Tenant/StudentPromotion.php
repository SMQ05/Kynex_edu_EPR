<?php

namespace App\Models\Tenant;

use App\Models\SchoolUser;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentPromotion extends Model
{
    use HasUlids;

    protected $fillable = [
        'student_id',
        'from_class_id',
        'from_section_id',
        'from_academic_year_id',
        'to_class_id',
        'to_section_id',
        'to_academic_year_id',
        'promoted_by',
        'promoted_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'promoted_at' => 'datetime',
        ];
    }

    /* ── Relations ─────────────────────────── */

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function fromClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'from_class_id');
    }

    public function fromSection(): BelongsTo
    {
        return $this->belongsTo(Section::class, 'from_section_id');
    }

    public function fromAcademicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'from_academic_year_id');
    }

    public function toClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'to_class_id');
    }

    public function toSection(): BelongsTo
    {
        return $this->belongsTo(Section::class, 'to_section_id');
    }

    public function toAcademicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'to_academic_year_id');
    }

    public function promoter(): BelongsTo
    {
        return $this->belongsTo(SchoolUser::class, 'promoted_by');
    }
}
