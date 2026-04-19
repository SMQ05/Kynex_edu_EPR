<?php

declare(strict_types=1);

namespace App\Services\Sms\Drivers;

use App\Services\Sms\SmsDriverInterface;
use AndroidSmsGateway\Client as GatewayClient;
use AndroidSmsGateway\Domain\MessageBuilder;

/**
 * AndroidGatewayDriver — Sends SMS via capcom6/android-sms-gateway.
 *
 * Supports both cloud mode (api.sms-gate.app) and private server mode.
 */
class AndroidGatewayDriver implements SmsDriverInterface
{
    public function __construct(
        private readonly string $mode,
        private readonly string $serverUrl,
        private readonly string $login,
        private readonly string $password,
    ) {}

    public function send(string $to, string $message): bool
    {
        try {
            $client = $this->makeClient();

            $msg = (new MessageBuilder($message, [$to]))
                ->setWithDeliveryReport(true)
                ->build();

            $state = $client->send($msg);

            return $state !== null;
        } catch (\Exception $e) {
            report($e);
            return false;
        }
    }

    public function sendBulk(array $numbers, string $message): array
    {
        $results = [];

        foreach ($numbers as $index => $number) {
            try {
                $results[$number] = $this->send($number, $message);

                // Unofficial API rate limiting — 8-18s rotating delay
                if ($index < count($numbers) - 1) {
                    sleep(rand(8, 18));
                }
            } catch (\Exception $e) {
                report($e);
                $results[$number] = false;
            }
        }

        return $results;
    }

    private function makeClient(): GatewayClient
    {
        $serverUrl = $this->mode === 'private'
            ? $this->serverUrl
            : 'https://api.sms-gate.app/3rdparty/v1';

        return new GatewayClient(
            $this->login,
            $this->password,
            $serverUrl,
        );
    }
}
