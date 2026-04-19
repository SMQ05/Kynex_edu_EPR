<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Enums\CommunicationChannel;
use App\Enums\CommunicationStatus;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class CommunicationLog extends Model
{
    use HasUlids;

    protected $fillable = [
        'channel',
        'recipient_phone',
        'recipient_email',
        'message_preview',
        'status',
        'provider_used',
        'provider_message_id',
        'sent_at',
        'failed_reason',
        'feature_context',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'channel' => CommunicationChannel::class,
            'status' => CommunicationStatus::class,
            'sent_at' => 'datetime',
        ];
    }

    // ── Scopes ─────────────────────────────────────────────────────

    public function scopeFailed($query)
    {
        return $query->where('status', CommunicationStatus::Failed);
    }

    public function scopeByChannel($query, CommunicationChannel $channel)
    {
        return $query->where('channel', $channel);
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }
}
