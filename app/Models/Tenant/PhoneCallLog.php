<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Models\Concerns\BelongsToCampus;
use App\Models\Concerns\TracksCreator;
use App\Models\SchoolUser;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PhoneCallLog extends Model
{
    use HasUlids, SoftDeletes, BelongsToCampus, TracksCreator;

    public const CALL_TYPES = [
        'incoming' => 'Incoming',
        'outgoing' => 'Outgoing',
    ];

    public const STATUSES = [
        'completed' => 'Completed',
        'follow_up' => 'Needs Follow-up',
        'pending'   => 'Pending',
    ];

    protected $fillable = [
        'name',
        'phone',
        'call_type',
        'call_date',
        'call_time',
        'duration_minutes',
        'purpose',
        'description',
        'follow_up_date',
        'status',
        'note',
        'campus_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'call_date'        => 'date',
            'follow_up_date'   => 'date',
            'duration_minutes' => 'integer',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(SchoolUser::class, 'created_by');
    }

    public function scopeDueForFollowUp($query)
    {
        return $query->where('status', 'follow_up')
            ->whereNotNull('follow_up_date')
            ->whereDate('follow_up_date', '<=', today());
    }
}
