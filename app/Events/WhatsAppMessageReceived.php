<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Tenant\WhatsAppConversation;
use App\Models\Tenant\WhatsAppMessage;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when a new inbound WhatsApp message is received and saved.
 * The listener sends push notifications + in-app bell alerts to all staff
 * who have the 'view_whatsapp_inbox' permission.
 */
class WhatsAppMessageReceived
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly WhatsAppConversation $conversation,
        public readonly WhatsAppMessage $message,
    ) {}
}
