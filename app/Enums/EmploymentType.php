<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum EmploymentType: string implements HasLabel, HasColor
{
    case Permanent = 'permanent';
    case Contract  = 'contract';
    case PartTime  = 'part_time';

    public function getLabel(): string
    {
        return match ($this) {
            self::Permanent => 'Permanent',
            self::Contract  => 'Contract',
            self::PartTime  => 'Part Time',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Permanent => 'success',
            self::Contract  => 'warning',
            self::PartTime  => 'info',
        };
    }
}
