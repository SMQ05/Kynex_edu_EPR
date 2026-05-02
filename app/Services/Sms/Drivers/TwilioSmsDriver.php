<?php

declare(strict_types=1);

namespace App\Services\Sms\Drivers;

use App\Services\Sms\SmsDriverInterface;
use Twilio\Rest\Client;

/**
 * TwilioSmsDriver — Sends SMS via Twilio REST API.
 */
class TwilioSmsDriver implements SmsDriverInterface
{
    private Client $client;

    public function __construct(
        private readonly string $accountSid,
        private readonly string $authToken,
        private readonly string $fromNumber,
    ) {
        $this->client = new Client($this->accountSid, $this->authToken);
    }

    public function send(string $to, string $message): bool
    {
        try {
            $this->client->messages->create($to, [
                'from' => $this->fromNumber,
                'body' => $message,
            ]);

            return true;
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
