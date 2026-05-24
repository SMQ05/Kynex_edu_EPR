<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Models\Concerns\TracksCreator;
use App\Models\SchoolUser;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatBlockedUser extends Model
{
    use HasUlids, TracksCreator;

    protected $table = 'chat_blocked_users';

    protected $fillable = [
        'blocker_id',
        'blocked_id',
        'reason',
        'created_by',
    ];

    public function blocker(): BelongsTo
    {
        return $this->belongsTo(SchoolUser::class, 'blocker_id');
    }

    public function blocked(): BelongsTo
    {
        return $this->belongsTo(SchoolUser::class, 'blocked_id');
    }
}
