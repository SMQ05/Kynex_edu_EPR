<?php

declare(strict_types=1);

namespace App\Services\Sms\Drivers;

use App\Services\Sms\SmsDriverInterface;
use Illuminate\Support\Facades\Http;

/**
 * TelenorSmsDriver — Sends SMS via Telenor Pakistan bulk SMS API.
 *
 * Placeholder implementation — update endpoint and auth as needed.
 */
class TelenorSmsDriver implements SmsDriverInterface
{
    private const API_URL = 'https://telenorcsms.com.pk:27677/corporate_sms2/api/sendsms.jsp';

    public function __construct(
        private readonly string $apiKey,
        private readonly string $senderId,
    ) {}

    public function send(string $to, string $message): bool
    {
        try {
            $response = Http::get(self::API_URL, [
                'session_id' => $this->apiKey,
                'to'         => $to,
                'text'       => $message,
                'mask'       => $this->senderId,
            ]);

            return $response->ok();
        } catch (\Exception $e) {
            report($e);
            return false;
        }
    }

    public function sendBulk(array $numbers, string $message): array
    {
        $results = [];
        foreach ($numbers as $number) {
            $results[$number] = $this->send($number, $message);
        }

        return $results;
    }
}
