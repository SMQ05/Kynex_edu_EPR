<?php

namespace App\Models\Tenant;

use App\Enums\PayrollStatus;
use App\Models\Concerns\HasPaisaAttributes;
use App\Models\SchoolUser;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class StaffPayroll extends Model
{
    use HasUlids, SoftDeletes, HasPaisaAttributes;

    protected $fillable = [
        'staff_profile_id',
        'school_user_id',
        'month',
        'year',
        'working_days',
        'present_days',
        'basic_salary_paisas',
        'allowances_paisas',
        'deductions_paisas',
        'net_salary_paisas',
        'status',
        'paid_at',
        'payslip_path',
        'processed_by',
    ];

    protected array $paisaFields = [
        'basic_salary_paisas',
        'allowances_paisas',
        'deductions_paisas',
        'net_salary_paisas',
    ];

    protected function casts(): array
    {
        return [
            'month'                => 'integer',
            'year'                 => 'integer',
            'working_days'         => 'integer',
            'present_days'         => 'integer',
            'basic_salary_paisas'  => 'integer',
            'allowances_paisas'    => 'integer',
            'deductions_paisas'    => 'integer',
            'net_salary_paisas'    => 'integer',
            'status'               => PayrollStatus::class,
            'paid_at'              => 'datetime',
        ];
    }

    /* ── Relations ─────────────────────────── */

    public function staffProfile(): BelongsTo
    {
        return $this->belongsTo(StaffProfile::class);
    }

    public function schoolUser(): BelongsTo
    {
        return $this->belongsTo(SchoolUser::class);
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(SchoolUser::class, 'processed_by');
    }
}
