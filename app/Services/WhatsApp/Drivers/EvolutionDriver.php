<?php

declare(strict_types=1);

namespace App\Services\WhatsApp\Drivers;

use App\Services\WhatsApp\WhatsAppDriverInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Evolution API driver for WhatsApp.
 *
 * Evolution API is a free, open-source WhatsApp gateway that wraps
 * the Baileys library. Supports self-hosted or cloud instances.
 *
 * Required config keys:
 *   - base_url       : Evolution API server URL (e.g. https://evo.school.com)
 *   - api_key        : Global API key for the Evolution instance
 *   - instance_name  : Instance name connected to the school's WhatsApp
 */
final class EvolutionDriver implements WhatsAppDriverInterface
{
    public function __construct(
        private readonly array $config,
    ) {}

    public function sendText(string $to, string $message): bool
    {
        $payload = [
            'number' => $this->normalizeNumber($to),
            'text'   => $message,
        ];

        return $this->post('/message/sendText', $payload);
    }

    public function sendTemplate(
        string $to,
        string $templateName,
        array $parameters = [],
        string $languageCode = 'en',
    ): bool {
        // Evolution API doesn't natively support Meta-style templates.
        // We compose the template text from parameters and send as text.
        $body = $templateName;

        if (! empty($parameters)) {
            foreach ($parameters as $index => $value) {
                $placeholder = '{{' . ($index + 1) . '}}';
                $body = str_replace($placeholder, $value, $body);
            }
        }

        return $this->sendText($to, $body);
    }

    public function sendMedia(
        string $to,
        string $mediaUrl,
        string $type = 'image',
        string $caption = '',
    ): bool {
        $endpoint = match ($type) {
            'image'    => '/message/sendMedia',
            'document' => '/message/sendMedia',
            'audio'    => '/message/sendWhatsAppAudio',
            'video'    => '/message/sendMedia',
            default    => '/message/sendMedia',
        };

        $payload = [
            'number'    => $this->normalizeNumber($to),
            'mediatype' => $type,
            'media'     => $mediaUrl,
            'caption'   => $caption,
        ];

        return $this->post($endpoint, $payload);
    }

    /**
     * Send a text message to multiple recipients with rate limiting.
     *
     * Unofficial API rate limiting — 8-18s rotating delay between each send
     * to avoid bans/blocks from the Evolution/Baileys gateway.
     *
     * @param  array<string>  $recipients  Phone numbers in E.164 format
     * @param  string         $message     Plain-text message body
     * @return array<string, bool>         Map of number => success
     */
    public function sendBulkText(array $recipients, string $message): array
    {
        $results = [];

        foreach ($recipients as $index => $to) {
            $results[$to] = $this->sendText($to, $message);

            // Unofficial API rate limiting — 8-18s rotating delay
            if ($index < count($recipients) - 1) {
                sleep(rand(8, 18));
            }
        }

        return $results;
    }

    /**
     * Send a POST request to the Evolution API.
     */
    private function post(string $endpoint, array $payload): bool
    {
        $baseUrl      = rtrim($this->config['base_url'] ?? '', '/');
        $apiKey       = $this->config['api_key'] ?? '';
        $instanceName = $this->config['instance_name'] ?? '';

        $url = "{$baseUrl}/{$instanceName}{$endpoint}";

        try {
            $response = Http::withHeaders([
                'apikey'       => $apiKey,
                'Content-Type' => 'application/json',
            ])
                ->timeout(30)
                ->post($url, $payload);

            if ($response->successful()) {
                return true;
            }

            Log::warning('Evolution API error', [
                'url'    => $url,
                'status' => $response->status(),
                'body'   => $response->json(),
            ]);

            return false;
        } catch (\Throwable $e) {
            Log::error('Evolution API exception', [
                'url'     => $url,
                'message' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Normalize phone number: strip everything except digits.
     */
    private function normalizeNumber(string $number): string
    {
        return preg_replace('/[^\d]/', '', $number);
    }

    /**
     * Send a message from a teacher's personal WhatsApp number.
     *
     * Uses the personal number as the Evolution API instance/session identifier
     * instead of the school's default number. Requires the tenant to have
     * teachers_can_use_own_whatsapp enabled.
     *
     * NOTE (Risk 8): This assumes the Evolution API server has a separate instance
     * registered for each teacher's personal number. If the instance does not exist,
     * the API call will return a 404. The school admin must pre-register each teacher's
     * number as an Evolution instance before enabling this feature.
     *
     * @param  string  $personalNumber    Teacher's personal WhatsApp (E.164 format)
     * @param  string  $recipientNumber   Recipient phone number
     * @param  string  $message           Plain-text message body
     * @return array{success: bool, error?: string}
     */
    public function sendFromPersonalNumber(
        string $personalNumber,
        string $recipientNumber,
        string $message,
    ): array {
        $tenant = tenant();

        if (! $tenant || ! $tenant->teachers_can_use_own_whatsapp) {
            throw new \RuntimeException('Personal WhatsApp not permitted by school admin.');
        }

        $baseUrl = rtrim($this->config['base_url'] ?? '', '/');
        $apiKey  = $this->config['api_key'] ?? '';

        // Use the personal number as the instance name
        $instanceName = $this->normalizeNumber($personalNumber);

        $url = "{$baseUrl}/{$instanceName}/message/sendText";

        $payload = [
            'number' => $this->normalizeNumber($recipientNumber),
            'text'   => $message,
        ];

        try {
            $response = Http::withHeaders([
                'apikey'       => $apiKey,
                'Content-Type' => 'application/json',
            ])
                ->timeout(30)
                ->post($url, $payload);

            if ($response->successful()) {
                return ['success' => true];
            }

            Log::warning('Evolution API personal number error', [
                'url'    => $url,
                'status' => $response->status(),
                'body'   => $response->json(),
            ]);

            return ['success' => false, 'error' => 'Evolution API returned ' . $response->status()];
        } catch (\Throwable $e) {
            Log::error('Evolution API personal number exception', [
                'url'     => $url,
                'message' => $e->getMessage(),
            ]);

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
