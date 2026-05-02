<?php

namespace App\Models\Tenant;

use App\Enums\EmploymentType;
use App\Models\Concerns\HasPaisaAttributes;
use App\Models\SchoolUser;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class StaffProfile extends Model
{
    use HasUlids, SoftDeletes, HasPaisaAttributes;

    protected $fillable = [
        'school_user_id',
        'employee_id',
        'department_id',
        'designation_id',
        'joining_date',
        'employment_type',
        'qualification',
        'experience_years',
        'emergency_contact_name',
        'emergency_contact_phone',
        'personal_whatsapp',
        'bank_name',
        'bank_account',
        'basic_salary_paisas',
        'campus_id',
    ];

    protected array $paisaFields = ['basic_salary_paisas'];

    protected function casts(): array
    {
        return [
            'joining_date'     => 'date',
            'employment_type'  => EmploymentType::class,
            'experience_years' => 'integer',
            'basic_salary_paisas' => 'integer',
        ];
    }

    /* ── Relations ─────────────────────────── */

    public function schoolUser(): BelongsTo
    {
        return $this->belongsTo(SchoolUser::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function designation(): BelongsTo
    {
        return $this->belongsTo(Designation::class);
    }

    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class);
    }

    public function payrolls(): HasMany
    {
        return $this->hasMany(StaffPayroll::class);
    }
}
