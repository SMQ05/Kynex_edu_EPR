<?php

declare(strict_types=1);

namespace App\Services\Sms\Drivers;

use App\Services\Sms\SmsDriverInterface;
use Illuminate\Support\Facades\Http;

/**
 * JazzSmsDriver — Sends SMS via Jazz Pakistan SMS API.
 *
 * URL: http://cbs.jazz.com.pk/SMSServices/Sendsms/
 * Params: username, password, mobileNumber, message, CallerID (sender mask)
 */
class JazzSmsDriver implements SmsDriverInterface
{
    private const API_URL = 'http://cbs.jazz.com.pk/SMSServices/Sendsms/';

    public function __construct(
        private readonly string $username,
        private readonly string $password,
        private readonly string $mask,
    ) {}

    public function send(string $to, string $message): bool
    {
        try {
            $response = Http::post(self::API_URL, [
                'username'     => $this->username,
                'password'     => $this->password,
                'mobileNumber' => $to,
                'message'      => $message,
                'CallerID'     => $this->mask,
            ]);

            return $response->ok() && str_contains($response->body(), 'Sent');
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
