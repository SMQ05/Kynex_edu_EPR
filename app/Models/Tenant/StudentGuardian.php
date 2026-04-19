<?php

namespace App\Models\Tenant;

use App\Enums\GuardianType;
use App\Models\SchoolUser;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentGuardian extends Model
{
    use HasUlids;

    protected $fillable = [
        'student_id',
        'guardian_type',
        'name',
        'relationship',
        'phone',
        'whatsapp',
        'email',
        'occupation',
        'address',
        'cnic',
        'is_primary_contact',
        'can_access_portal',
        'school_user_id',
    ];

    protected function casts(): array
    {
        return [
            'guardian_type'      => GuardianType::class,
            'is_primary_contact' => 'boolean',
            'can_access_portal'  => 'boolean',
        ];
    }

    /* ── Relations ─────────────────────────── */

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function schoolUser(): BelongsTo
    {
        return $this->belongsTo(SchoolUser::class);
    }
}
