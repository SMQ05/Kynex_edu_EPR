<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Models\SchoolUser;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatInvitation extends Model
{
    use HasUlids;

    public const STATUSES = [
        'pending'  => 'Pending',
        'accepted' => 'Accepted',
        'declined' => 'Declined',
    ];

    protected $fillable = [
        'inviter_id',
        'invitee_id',
        'status',
        'message',
        'responded_at',
    ];

    protected function casts(): array
    {
        return [
            'responded_at' => 'datetime',
        ];
    }

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(SchoolUser::class, 'inviter_id');
    }

    public function invitee(): BelongsTo
    {
        return $this->belongsTo(SchoolUser::class, 'invitee_id');
    }
}
