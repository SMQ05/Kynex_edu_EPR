<?php

declare(strict_types=1);

namespace App\Services\WhatsApp\Drivers;

use App\Services\WhatsApp\WhatsAppDriverInterface;

/**
 * Null/no-op WhatsApp driver.
 *
 * Used when the tenant has whatsapp_channel = 'none'.
 * All methods silently return true so callers don't need
 * to check whether WhatsApp is enabled before calling.
 */
final class NullWhatsAppDriver implements WhatsAppDriverInterface
{
    public function sendText(string $to, string $message): bool
    {
        return true;
    }

    public function sendTemplate(
        string $to,
        string $templateName,
        array $parameters = [],
        string $languageCode = 'en',
    ): bool {
        return true;
    }

    public function sendMedia(
        string $to,
        string $mediaUrl,
        string $type = 'image',
        string $caption = '',
    ): bool {
        return true;
    }
}
