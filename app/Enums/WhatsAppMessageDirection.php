<?php

declare(strict_types=1);

namespace App\Enums;

enum WhatsAppMessageDirection: string
{
    case Inbound  = 'inbound';
    case Outbound = 'outbound';
}
