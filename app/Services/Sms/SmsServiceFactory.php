<?php

declare(strict_types=1);

namespace App\Services\Sms;

use App\Models\Tenant;
use App\Services\Sms\Drivers\AndroidGatewayDriver;
use App\Services\Sms\Drivers\JazzSmsDriver;
use App\Services\Sms\Drivers\NullSmsDriver;
use App\Services\Sms\Drivers\SendPkSmsDriver;
use App\Services\Sms\Drivers\TelenorSmsDriver;
use App\Services\Sms\Drivers\TwilioSmsDriver;
use InvalidArgumentException;

/**
 * SmsServiceFactory — Creates the correct SMS driver based on tenant config.
 */
class SmsServiceFactory
{
    /**
     * Create an SMS driver instance for the given tenant.
     */
    public static function make(Tenant $tenant): SmsDriverInterface
    {
        $channel = $tenant->sms_channel ?? 'none';
        $config  = $tenant->sms_config ?? [];

        return match ($channel) {
            'sendpk' => new SendPkSmsDriver(
                username: $config['username'] ?? '',
                password: $config['password'] ?? '',
                sender:   $config['sender'] ?? '',
            ),
            'twilio', 'twilio_sms' => new TwilioSmsDriver(
                accountSid: $config['account_sid'] ?? '',
                authToken:  $config['auth_token'] ?? '',
                fromNumber: $config['from_number'] ?? '',
            ),
            'android_sms_gateway', 'android_gateway' => new AndroidGatewayDriver(
                mode:      $config['mode'] ?? 'cloud',
                serverUrl: $config['server_url'] ?? 'https://api.sms-gate.app/3rdparty/v1',
                login:     $config['login'] ?? '',
                password:  $config['password'] ?? '',
            ),
            'jazz_sms' => new JazzSmsDriver(
                username: $config['username'] ?? '',
                password: $config['password'] ?? '',
                mask:     $config['mask'] ?? '',
            ),
            'telenor_sms' => new TelenorSmsDriver(
                apiKey:   $config['api_key'] ?? '',
                senderId: $config['sender_id'] ?? '',
            ),
            'zong_sms' => new NullSmsDriver(), // Zong placeholder — same as null for now
            'none'     => new NullSmsDriver(),
            default    => throw new InvalidArgumentException("Unknown SMS channel: {$channel}"),
        };
    }
}
