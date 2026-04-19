<?php

declare(strict_types=1);

namespace App\Services\WhatsApp\Drivers;

use App\Services\WhatsApp\WhatsAppDriverInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Meta Official Cloud API driver.
 *
 * Uses the Graph API v21.0 to send text, template, and media messages.
 *
 * Required config keys:
 *   - phone_number_id : Meta Business phone number ID
 *   - access_token    : Permanent system user access token
 *   - waba_id         : WhatsApp Business Account ID (optional, for future use)
 */
final class MetaOfficialDriver implements WhatsAppDriverInterface
{
    private const API_VERSION = 'v21.0';
    private const BASE_URL    = 'https://graph.facebook.com';

    public function __construct(
        private readonly array $config,
    ) {}

    public function sendText(string $to, string $message): bool
    {
        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type'    => 'individual',
            'to'                => $this->normalizeNumber($to),
            'type'              => 'text',
            'text'              => [
                'preview_url' => false,
                'body'        => $message,
            ],
        ];

        return $this->send($payload);
    }

    public function sendTemplate(
        string $to,
        string $templateName,
        array $parameters = [],
        string $languageCode = 'en',
    ): bool {
        $components = [];

        if (! empty($parameters)) {
            $bodyParams = array_map(
                fn (string $value) => ['type' => 'text', 'text' => $value],
                array_values($parameters),
            );

            $components[] = [
                'type'       => 'body',
                'parameters' => $bodyParams,
            ];
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type'    => 'individual',
            'to'                => $this->normalizeNumber($to),
            'type'              => 'template',
            'template'          => [
                'name'       => $templateName,
                'language'   => ['code' => $languageCode],
                'components' => $components,
            ],
        ];

        return $this->send($payload);
    }

    public function sendMedia(
        string $to,
        string $mediaUrl,
        string $type = 'image',
        string $caption = '',
    ): bool {
        $mediaPayload = ['link' => $mediaUrl];

        if ($caption !== '' && in_array($type, ['image', 'document', 'video'], true)) {
            $mediaPayload['caption'] = $caption;
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type'    => 'individual',
            'to'                => $this->normalizeNumber($to),
            'type'              => $type,
            $type               => $mediaPayload,
        ];

        return $this->send($payload);
    }

    /**
     * Send the payload to the Graph API.
     */
    private function send(array $payload): bool
    {
        $phoneNumberId = $this->config['phone_number_id'] ?? '';
        $accessToken   = $this->config['access_token'] ?? '';

        $url = sprintf(
            '%s/%s/%s/messages',
            self::BASE_URL,
            self::API_VERSION,
            $phoneNumberId,
        );

        try {
            $response = Http::withToken($accessToken)
                ->timeout(30)
                ->post($url, $payload);

            if ($response->successful()) {
                return true;
            }

            Log::warning('Meta WhatsApp API error', [
                'status' => $response->status(),
                'body'   => $response->json(),
            ]);

            return false;
        } catch (\Throwable $e) {
            Log::error('Meta WhatsApp API exception', [
                'message' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Strip non-numeric characters (keep leading +).
     */
    private function normalizeNumber(string $number): string
    {
        return preg_replace('/[^\d]/', '', $number);
    }
}
