<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Models\Concerns\TracksCreator;
use App\Models\SchoolUser;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CmsContactMessage extends Model
{
    use HasUlids, SoftDeletes, TracksCreator;

    public const STATUSES = [
        'new'      => 'New',
        'read'     => 'Read',
        'replied'  => 'Replied',
        'archived' => 'Archived',
    ];

    protected $fillable = [
        'name',
        'email',
        'phone',
        'subject',
        'message',
        'status',
        'reply',
        'replied_at',
        'replied_by',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'replied_at' => 'datetime',
        ];
    }

    public function repliedBy(): BelongsTo
    {
        return $this->belongsTo(SchoolUser::class, 'replied_by');
    }

    public function scopeUnhandled($query)
    {
        return $query->whereIn('status', ['new', 'read']);
    }
}
