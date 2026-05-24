<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Models\Concerns\TracksCreator;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Leave Define: a per-role leave QUOTA against an existing LeaveType so leave
 * balances are defined (e.g. TEACHER → 14 days Annual, yearly).
 */
class LeaveQuota extends Model
{
    use HasUlids, SoftDeletes, TracksCreator;

    public const PERIODS = [
        'yearly'  => 'Per Year',
        'monthly' => 'Per Month',
        'term'    => 'Per Term',
    ];

    protected $fillable = [
        'leave_type_id',
        'applies_to_role',
        'academic_year_id',
        'days_allowed',
        'period',
        'carry_forward',
        'max_carry_forward_days',
        'is_active',
        'note',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'days_allowed'           => 'integer',
            'carry_forward'          => 'boolean',
            'max_carry_forward_days' => 'integer',
            'is_active'              => 'boolean',
        ];
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }
}
