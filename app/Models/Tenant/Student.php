<?php

namespace App\Models\Tenant;

use App\Enums\BloodGroup;
use App\Enums\StudentGender;
use App\Enums\StudentStatus;
use App\Models\Concerns\RequiresApproval;
use App\Models\SchoolUser;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Student extends Model
{
    use HasUlids, SoftDeletes, RequiresApproval;

    protected $fillable = [
        'admission_number',
        'registration_number',
        'roll_number',
        'admission_date',
        'academic_year_id',
        'class_id',
        'section_id',
        'category_id',
        'campus_id',
        'first_name',
        'last_name',
        'date_of_birth',
        'gender',
        'blood_group',
        'religion',
        'nationality',
        'profile_photo_path',
        'phone',
        'email',
        'address',
        'city',
        'status',
        'status_changed_at',
        'status_change_reason',
        'school_user_id',
        'special_needs_notes',
        'medical_notes',
        'previous_school',
        'transport_route_id',
        'hostel_room_id',
        'biometric_device_id',
    ];

    protected function casts(): array
    {
        return [
            'admission_date'   => 'date',
            'date_of_birth'    => 'date',
            'gender'           => StudentGender::class,
            'blood_group'      => BloodGroup::class,
            'status'           => StudentStatus::class,
            'status_changed_at' => 'datetime',
        ];
    }

    /* ── Relations ─────────────────────────── */

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(StudentCategory::class, 'category_id');
    }

    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class);
    }

    public function schoolUser(): BelongsTo
    {
        return $this->belongsTo(SchoolUser::class);
    }

    public function guardians(): HasMany
    {
        return $this->hasMany(StudentGuardian::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(StudentDocument::class);
    }

    public function promotions(): HasMany
    {
        return $this->hasMany(StudentPromotion::class);
    }

    public function fees(): HasMany
    {
        return $this->hasMany(StudentFee::class);
    }

    public function feePayments(): HasMany
    {
        return $this->hasMany(FeePayment::class);
    }

    public function hostelAllocations(): HasMany
    {
        return $this->hasMany(HostelAllocation::class);
    }

    public function hostelGatePasses(): HasMany
    {
        return $this->hasMany(HostelGatePass::class);
    }

    public function generatedCertificates(): HasMany
    {
        return $this->hasMany(GeneratedCertificate::class);
    }

    /* ── Scopes ────────────────────────────── */

    public function scopeEnrolled($query)
    {
        return $query->where('status', StudentStatus::Enrolled);
    }

    /**
     * Full-text search using PostgreSQL tsvector.
     * Falls back to ILIKE for very short terms (< 2 chars).
     */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        if (strlen($term) < 2) {
            return $query->where('first_name', 'ilike', "%{$term}%");
        }

        return $query->whereRaw(
            "search_vector @@ plainto_tsquery('english', ?)",
            [$term]
        )->orderByRaw(
            "ts_rank(search_vector, plainto_tsquery('english', ?)) DESC",
            [$term]
        );
    }

    /* ── Accessors ─────────────────────────── */

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }
}
