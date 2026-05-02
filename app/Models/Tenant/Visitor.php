<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Models\Concerns\BelongsToCampus;
use App\Models\SchoolUser;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Visitor extends Model
{
    use HasUlids, BelongsToCampus;

    protected $fillable = [
        'visitor_name',
        'phone',
        'cnic',
        'organization',
        'purpose',
        'whom_to_meet',
        'school_user_to_meet_id',
        'check_in_time',
        'check_out_time',
        'badge_number',
        'photo_path',
        'vehicle_number',
        'status',
        'campus_id',
        'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'check_in_time' => 'datetime',
            'check_out_time' => 'datetime',
        ];
    }

    /* ── Relations ─────────────────────────── */

    public function personToMeet(): BelongsTo
    {
        return $this->belongsTo(SchoolUser::class, 'school_user_to_meet_id');
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(SchoolUser::class, 'recorded_by');
    }

    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class);
    }

    /* ── Scopes ────────────────────────────── */

    public function scopeCheckedIn($query)
    {
        return $query->where('status', 'checked_in');
    }

    public function scopeToday($query)
    {
        return $query->whereDate('check_in_time', today());
    }
}
