<?php

declare(strict_types=1);

namespace App\Services\Sms;

/**
 * SmsDriverInterface — Contract for all SMS gateway drivers.
 */
interface SmsDriverInterface
{
    /**
     * Send a single SMS message.
     */
    public function send(string $to, string $message): bool;

    /**
     * Send an SMS message to multiple recipients.
     *
     * @param  array<string>  $numbers
     * @return array<string, bool>  Map of number => success
     */
    public function sendBulk(array $numbers, string $message): array;
}
