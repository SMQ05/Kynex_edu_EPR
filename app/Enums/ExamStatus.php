<?php

declare(strict_types=1);

namespace App\Enums;

enum ExamStatus: string
{
    case Draft      = 'draft';
    case Scheduled  = 'scheduled';
    case Ongoing    = 'ongoing';
    case Completed  = 'completed';
    case Cancelled  = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft     => 'Draft',
            self::Scheduled => 'Scheduled',
            self::Ongoing   => 'Ongoing',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft     => 'gray',
            self::Scheduled => 'info',
            self::Ongoing   => 'warning',
            self::Completed => 'success',
            self::Cancelled => 'danger',
        };
    }
}
