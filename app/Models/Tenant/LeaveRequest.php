<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Enums\LeaveStatus;
use App\Models\SchoolUser;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class LeaveRequest extends Model
{
    use HasUlids, SoftDeletes;

    protected $fillable = [
        'school_user_id',
        'leave_type_id',
        'from_date',
        'to_date',
        'total_days',
        'reason',
        'status',
        'reviewed_by',
        'reviewed_at',
        'review_note',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'from_date' => 'date',
            'to_date' => 'date',
            'total_days' => 'integer',
            'status' => LeaveStatus::class,
            'reviewed_at' => 'datetime',
        ];
    }

    // ── Relationships ──────────────────────────────────────────────

    public function schoolUser(): BelongsTo
    {
        return $this->belongsTo(SchoolUser::class);
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(SchoolUser::class, 'reviewed_by');
    }

    // ── Scopes ─────────────────────────────────────────────────────

    public function scopePending($query)
    {
        return $query->where('status', LeaveStatus::Pending);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', LeaveStatus::Approved);
    }
}
