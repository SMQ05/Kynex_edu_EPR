<?php

declare(strict_types=1);

namespace App\Services\WhatsApp\Drivers;

use App\Services\WhatsApp\WhatsAppDriverInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * SendPK WhatsApp Driver — Official Pakistan WhatsApp via wa.sendpk.com.
 *
 * API endpoint : https://wa.sendpk.com/api/send.php
 * Auth         : api_key parameter
 * Docs         : https://wa.sendpk.com/api.php
 *
 * Two modes:
 *  1. Template message  — uses template_id + template_data (pre-approved templates)
 *  2. Free-form message — uses free_form JSON (only within 24h of user reply)
 */
final class SendPkWhatsAppDriver implements WhatsAppDriverInterface
{
    private const API_URL = 'https://wa.sendpk.com/api/send.php';

    public function __construct(
        private readonly string $apiKey,
        private readonly string $whatsappId = '',   // optional: send from specific WhatsApp ID
    ) {}

    /**
     * Send a plain text message as a free-form message.
     * Note: Only works within 24 hours of the recipient replying to you.
     * For outbound-only use sendTemplate() instead.
     */
    public function sendText(string $to, string $message): bool
    {
        $freeForm = json_encode([[
            'mobile' => $this->normalizeNumber($to),
            'type'   => 'text',
            'text'   => ['body' => $message],
        ]]);

        $params = [
            'api_key'   => $this->apiKey,
            'free_form' => $freeForm,
        ];

        if ($this->whatsappId !== '') {
            $params['whatsapp_id'] = $this->whatsappId;
        }

        return $this->post($params);
    }

    /**
     * Send a template message (approved templates — works for outbound).
     *
     * @param  string  $to            Phone number in E.164 or local format
     * @param  string  $templateName  The template_id from SendPK dashboard
     * @param  array   $parameters    Named replacements: ['name' => 'Ahmed', 'fee' => '5000']
     * @param  string  $languageCode  Unused (SendPK doesn't require this)
     */
    public function sendTemplate(
        string $to,
        string $templateName,
        array $parameters = [],
        string $languageCode = 'en',
    ): bool {
        // Build template_data: required field is 'mobile', rest are body params
        $templateData = [['mobile' => $this->normalizeNumber($to)]];

        // If parameters provided, add body array with text components
        if (! empty($parameters)) {
            $bodyComponents = array_map(
                fn (string $value) => ['type' => 'text', 'text' => $value],
                array_values($parameters),
            );

            $templateData[0]['body'] = $bodyComponents;
        }

        $params = [
            'api_key'       => $this->apiKey,
            'template_id'   => $templateName,
            'template_data' => json_encode($templateData),
        ];

        if ($this->whatsappId !== '') {
            $params['whatsapp_id'] = $this->whatsappId;
        }

        return $this->post($params);
    }

    /**
     * Send media (image/document/video) as a free-form message.
     */
    public function sendMedia(
        string $to,
        string $mediaUrl,
        string $type = 'image',
        string $caption = '',
    ): bool {
        $message = [
            'mobile' => $this->normalizeNumber($to),
            'type'   => $type,
            $type    => ['link' => $mediaUrl],
        ];

        if ($caption !== '' && in_array($type, ['image', 'document', 'video'], true)) {
            $message[$type]['caption'] = $caption;
        }

        $params = [
            'api_key'   => $this->apiKey,
            'free_form' => json_encode([$message]),
        ];

        if ($this->whatsappId !== '') {
            $params['whatsapp_id'] = $this->whatsappId;
        }

        return $this->post($params);
    }

    /**
     * Send a text message to multiple recipients.
     */
    public function sendBulkText(array $recipients, string $message): array
    {
        $results = [];
        foreach ($recipients as $to) {
            $results[$to] = $this->sendText($to, $message);
        }
        return $results;
    }

    /**
     * POST to the SendPK WhatsApp API.
     */
    private function post(array $params): bool
    {
        try {
            $response = Http::timeout(15)->post(self::API_URL, $params);

            $body = trim($response->body());

            if ($response->successful() && ! str_starts_with($body, 'Error')) {
                return true;
            }

            Log::warning('SendPK WhatsApp API error', [
                'status' => $response->status(),
                'body'   => $body,
            ]);

            return false;
        } catch (\Throwable $e) {
            Log::error('SendPK WhatsApp exception', ['message' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Strip non-digit characters from phone number.
     */
    private function normalizeNumber(string $number): string
    {
        return preg_replace('/[^\d]/', '', $number);
    }
}
