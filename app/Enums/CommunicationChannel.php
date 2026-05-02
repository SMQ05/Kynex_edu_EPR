<?php

declare(strict_types=1);

namespace App\Enums;

enum CommunicationChannel: string
{
    case Sms = 'sms';
    case WhatsAppText = 'whatsapp_text';
    case WhatsAppTemplate = 'whatsapp_template';
    case Email = 'email';
    case Push = 'push';
}
