<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Enums\WhatsAppMessageDirection;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\SchoolUser;

/**
 * WhatsAppMessage — A single message in a WhatsApp conversation.
 *
 * direction = 'inbound'  → message sent BY a parent/guardian
 * direction = 'outbound' → message sent TO the parent (by bot or staff)
 *
 * @property string                   $id
 * @property string                   $conversation_id
 * @property WhatsAppMessageDirection $direction
 * @property string                   $body
 * @property string|null              $sender_phone
 * @property string|null              $sent_by_user_id
 * @property bool                     $is_bot_reply
 * @property string|null              $provider_message_id
 * @property \Carbon\Carbon|null      $read_at
 */
class WhatsAppMessage extends Model
{
    use HasUlids;

    protected $fillable = [
        'conversation_id',
        'direction',
        'body',
        'sender_phone',
        'sent_by_user_id',
        'is_bot_reply',
        'provider_message_id',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'direction'    => WhatsAppMessageDirection::class,
            'is_bot_reply' => 'boolean',
            'read_at'      => 'datetime',
        ];
    }

    // ── Relationships ──────────────────────────────────────────

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(WhatsAppConversation::class, 'conversation_id');
    }

    public function sentByUser(): BelongsTo
    {
        return $this->belongsTo(SchoolUser::class, 'sent_by_user_id');
    }

    // ── Helpers ────────────────────────────────────────────────

    public function isInbound(): bool
    {
        return $this->direction === WhatsAppMessageDirection::Inbound;
    }

    public function isOutbound(): bool
    {
        return $this->direction === WhatsAppMessageDirection::Outbound;
    }
}
