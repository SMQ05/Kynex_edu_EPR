<?php

namespace App\Enums;

enum BehaviorIncidentStatus: string
{
    case Reported = 'reported';
    case Investigating = 'investigating';
    case Resolved = 'resolved';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Reported => 'Reported',
            self::Investigating => 'Investigating',
            self::Resolved => 'Resolved',
            self::Closed => 'Closed',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Reported => 'warning',
            self::Investigating => 'info',
            self::Resolved => 'success',
            self::Closed => 'gray',
        };
    }
}
