<?php

declare(strict_types=1);

namespace App\Enums;

enum CommunicationStatus: string
{
    case Queued = 'queued';
    case Sent = 'sent';
    case Delivered = 'delivered';
    case Failed = 'failed';
}
