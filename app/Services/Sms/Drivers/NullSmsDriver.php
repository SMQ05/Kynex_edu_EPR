<?php

declare(strict_types=1);

namespace App\Services\Sms\Drivers;

use App\Services\Sms\SmsDriverInterface;

/**
 * NullSmsDriver — Silent no-op driver for when SMS is disabled or for testing.
 */
class NullSmsDriver implements SmsDriverInterface
{
    public function send(string $to, string $message): bool
    {
        return true;
    }

    public function sendBulk(array $numbers, string $message): array
    {
        $results = [];
        foreach ($numbers as $number) {
            $results[$number] = true;
        }

        return $results;
    }
}
