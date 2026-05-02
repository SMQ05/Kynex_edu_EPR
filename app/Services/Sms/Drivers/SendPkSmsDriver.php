<?php

declare(strict_types=1);

namespace App\Services\Sms\Drivers;

use App\Services\Sms\SmsDriverInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * SendPkSmsDriver — Official Pakistan SMS via sendpk.com.
 *
 * API docs: https://sendpk.com/api/sms.php
 * Rate: official provider — no artificial delay needed.
 */
class SendPkSmsDriver implements SmsDriverInterface
{
    private const API_URL = 'https://sendpk.com/api/sms.php';

    public function __construct(
        private readonly string $username,
        private readonly string $password,
        private readonly string $sender,
    ) {}

    public function send(string $to, string $message): bool
    {
        try {
            $response = Http::timeout(15)->get(self::API_URL, [
                'username' => $this->username,
                'password' => $this->password,
                'sender'   => $this->sender,
                'mobile'   => $to,
                'message'  => $message,
            ]);

            $body = trim($response->body());

            // SendPK returns "Message Submitted" on success
            if ($response->successful() && str_contains($body, 'Submitted')) {
                return true;
            }

            Log::warning('SendPK SMS failed', [
                'to'     => $to,
                'status' => $response->status(),
                'body'   => $body,
            ]);

            return false;
        } catch (\Throwable $e) {
            Log::error('SendPK SMS exception', ['to' => $to, 'error' => $e->getMessage()]);
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
