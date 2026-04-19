<?php

declare(strict_types=1);

namespace App\Enums;

enum BookIssueStatus: string
{
    case Issued   = 'issued';
    case Returned = 'returned';
    case Overdue  = 'overdue';
    case Lost     = 'lost';

    public function label(): string
    {
        return match ($this) {
            self::Issued   => 'Issued',
            self::Returned => 'Returned',
            self::Overdue  => 'Overdue',
            self::Lost     => 'Lost',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Issued   => 'info',
            self::Returned => 'success',
            self::Overdue  => 'warning',
            self::Lost     => 'danger',
        };
    }
}
