<?php

namespace App\Models\Tenant;

use App\Enums\HealthRecordType;
use App\Models\SchoolUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HealthRecord extends Model
{
    use HasUlids;

    protected $fillable = [
        'student_id',
        'campus_id',
        'recorded_by',
        'record_type',
        'title',
        'description',
        'record_date',
        'symptoms',
        'diagnosis',
        'treatment',
        'medication_given',
        'action_taken',
        'vaccine_name',
        'next_dose_date',
        'severity',
        'is_chronic',
        'temperature',
        'blood_pressure',
        'pulse_rate',
        'weight_kg',
        'height_cm',
        'parent_notified',
        'notes',
        'is_active',
        'is_confidential',
    ];

    protected function casts(): array
    {
        return [
            'record_type'     => HealthRecordType::class,
            'record_date'     => 'date',
            'next_dose_date'  => 'date',
            'is_chronic'      => 'boolean',
            'parent_notified' => 'boolean',
            'is_active'       => 'boolean',
            'is_confidential' => 'boolean',
            'temperature'     => 'decimal:1',
            'weight_kg'       => 'decimal:2',
            'height_cm'       => 'decimal:1',
        ];
    }

    // ── Confidentiality Global Scope ─────────────────────────────
    protected static function booted(): void
    {
        static::addGlobalScope('confidential_access', function (Builder $query) {
            $user = auth()->user();

            if (! $user) {
                $query->where('is_confidential', false);
                return;
            }

            $privileged = $user->hasAnyRole([
                'SCHOOL_ADMIN', 'NURSE', 'COUNSELOR',
            ]);

            if (! $privileged) {
                $query->where('is_confidential', false);
            }
        });
    }

    // ── Relationships ────────────────────────────────────────────

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(SchoolUser::class, 'recorded_by');
    }

    // ── Scopes ───────────────────────────────────────────────────

    public function scopeOfType($query, HealthRecordType $type)
    {
        return $query->where('record_type', $type);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForStudent($query, string $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    public function scopeAllergies($query)
    {
        return $query->where('record_type', HealthRecordType::Allergy)->where('is_active', true);
    }

    public function scopeConfidential($query)
    {
        return $query->where('is_confidential', true);
    }
}
