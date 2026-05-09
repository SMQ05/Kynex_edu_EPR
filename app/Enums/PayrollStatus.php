<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum PayrollStatus: string implements HasLabel, HasColor, HasIcon
{
    case Pending   = 'pending';
    case Draft     = 'draft';
    case Processed = 'processed';
    case Paid      = 'paid';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pending   => 'Pending',
            self::Draft     => 'Draft',
            self::Processed => 'Processed',
            self::Paid      => 'Paid',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pending   => 'gray',
            self::Draft     => 'warning',
            self::Processed => 'info',
            self::Paid      => 'success',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Pending   => 'heroicon-o-clock',
            self::Draft     => 'heroicon-o-pencil',
            self::Processed => 'heroicon-o-check-circle',
            self::Paid      => 'heroicon-o-banknotes',
        };
    }
}
