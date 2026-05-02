<?php

declare(strict_types=1);

namespace App\Enums;

enum WhatsAppConversationStatus: string
{
    case Open        = 'open';
    case BotHandled  = 'bot_handled';
    case Resolved    = 'resolved';

    public function label(): string
    {
        return match ($this) {
            self::Open        => 'Open',
            self::BotHandled  => 'Bot Handled',
            self::Resolved    => 'Resolved',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Open        => 'info',
            self::BotHandled  => 'warning',
            self::Resolved    => 'success',
        };
    }
}
