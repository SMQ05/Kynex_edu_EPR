<?php

declare(strict_types=1);

namespace App\Enums;

enum OnlineClassStatus: string
{
    case Scheduled = 'scheduled';
    case Live = 'live';
    case Ended = 'ended';
    case Cancelled = 'cancelled';
}
