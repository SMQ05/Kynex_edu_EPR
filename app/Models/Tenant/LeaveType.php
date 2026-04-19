<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Enums\LeaveApplicableTo;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class LeaveType extends Model
{
    use HasUlids, SoftDeletes;

    protected $fillable = [
        'name',
        'applicable_to',
        'max_days_per_year',
        'is_paid',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'applicable_to' => LeaveApplicableTo::class,
            'max_days_per_year' => 'integer',
            'is_paid' => 'boolean',
        ];
    }

    // ── Relationships ──────────────────────────────────────────────

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    // ── Scopes ─────────────────────────────────────────────────────

    public function scopeForStaff($query)
    {
        return $query->whereIn('applicable_to', [
            LeaveApplicableTo::Staff,
            LeaveApplicableTo::Both,
        ]);
    }

    public function scopeForStudents($query)
    {
        return $query->whereIn('applicable_to', [
            LeaveApplicableTo::Student,
            LeaveApplicableTo::Both,
        ]);
    }
}
