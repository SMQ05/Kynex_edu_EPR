<?php

declare(strict_types=1);

namespace App\Services\WhatsApp\Drivers;

use App\Services\WhatsApp\WhatsAppDriverInterface;
use Illuminate\Support\Facades\Log;
use Twilio\Rest\Client as TwilioClient;

/**
 * Twilio WhatsApp driver.
 *
 * Uses the Twilio Messaging API with whatsapp: prefixed numbers.
 *
 * Required config keys:
 *   - account_sid  : Twilio Account SID
 *   - auth_token   : Twilio Auth Token
 *   - from_number  : Twilio WhatsApp sender (whatsapp:+1234567890)
 */
final class TwilioWhatsAppDriver implements WhatsAppDriverInterface
{
    private TwilioClient $client;

    public function __construct(
        private readonly array $config,
    ) {
        $this->client = new TwilioClient(
            $this->config['account_sid'] ?? '',
            $this->config['auth_token'] ?? '',
        );
    }

    public function sendText(string $to, string $message): bool
    {
        try {
            $this->client->messages->create(
                "whatsapp:{$to}",
                [
                    'from' => $this->config['from_number'] ?? '',
                    'body' => $message,
                ],
            );

            return true;
        } catch (\Throwable $e) {
            Log::error('Twilio WhatsApp send failed', [
                'to'      => $to,
                'message' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function sendTemplate(
        string $to,
        string $templateName,
        array $parameters = [],
        string $languageCode = 'en',
    ): bool {
        // Twilio uses Content Templates via ContentSid.
        // For now, we compose the template as a regular message.
        // To use Twilio Content API, set $templateName = ContentSid.
        try {
            $options = [
                'from' => $this->config['from_number'] ?? '',
            ];

            // If templateName looks like a Twilio ContentSid (starts with HX)
            if (str_starts_with($templateName, 'HX')) {
                $options['contentSid'] = $templateName;

                if (! empty($parameters)) {
                    $options['contentVariables'] = json_encode(
                        array_combine(
                            array_map(fn (int $i) => (string) ($i + 1), array_keys($parameters)),
                            array_values($parameters),
                        ),
                    );
                }
            } else {
                // Fall back to plain text with parameter substitution
                $body = $templateName;
                foreach ($parameters as $index => $value) {
                    $body = str_replace('{{' . ($index + 1) . '}}', $value, $body);
                }
                $options['body'] = $body;
            }

            $this->client->messages->create("whatsapp:{$to}", $options);

            return true;
        } catch (\Throwable $e) {
            Log::error('Twilio WhatsApp template send failed', [
                'to'       => $to,
                'template' => $templateName,
                'message'  => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function sendMedia(
        string $to,
        string $mediaUrl,
        string $type = 'image',
        string $caption = '',
    ): bool {
        try {
            $options = [
                'from'     => $this->config['from_number'] ?? '',
                'mediaUrl' => [$mediaUrl],
            ];

            if ($caption !== '') {
                $options['body'] = $caption;
            }

            $this->client->messages->create("whatsapp:{$to}", $options);

            return true;
        } catch (\Throwable $e) {
            Log::error('Twilio WhatsApp media send failed', [
                'to'      => $to,
                'message' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
