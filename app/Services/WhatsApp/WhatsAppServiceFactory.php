<?php

declare(strict_types=1);

namespace App\Services\WhatsApp;

use App\Models\Tenant;
use App\Services\WhatsApp\Drivers\EvolutionDriver;
use App\Services\WhatsApp\Drivers\MetaOfficialDriver;
use App\Services\WhatsApp\Drivers\NullWhatsAppDriver;
use App\Services\WhatsApp\Drivers\SendPkWhatsAppDriver;
use App\Services\WhatsApp\Drivers\TwilioWhatsAppDriver;
use InvalidArgumentException;

/**
 * Resolves the correct WhatsApp driver for a given tenant
 * based on the tenant's `whatsapp_channel` setting.
 */
final class WhatsAppServiceFactory
{
    /**
     * Build the appropriate WhatsApp driver for the tenant.
     *
     * @throws InvalidArgumentException If the channel is not supported.
     */
    public static function make(Tenant $tenant): WhatsAppDriverInterface
    {
        $channel = $tenant->whatsapp_channel ?? 'none';
        $config  = $tenant->whatsapp_config ?? [];

        return match ($channel) {
            'sendpk_whatsapp'  => new SendPkWhatsAppDriver(
                apiKey:     $config['api_key'] ?? '',
                whatsappId: $config['whatsapp_id'] ?? '',
            ),
            'meta_official'    => new MetaOfficialDriver($config),
            'evolution'        => new EvolutionDriver($config),
            'twilio_whatsapp'  => new TwilioWhatsAppDriver($config),
            'none'             => new NullWhatsAppDriver(),
            default            => throw new InvalidArgumentException(
                "Unsupported WhatsApp channel: {$channel}",
            ),
        };
    }
}
