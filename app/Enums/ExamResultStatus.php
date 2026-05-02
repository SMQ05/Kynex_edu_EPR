<?php

declare(strict_types=1);

namespace App\Enums;

enum ExamResultStatus: string
{
    case Pass     = 'pass';
    case Fail     = 'fail';
    case Absent   = 'absent';
    case Withheld = 'withheld';

    public function label(): string
    {
        return match ($this) {
            self::Pass     => 'Pass',
            self::Fail     => 'Fail',
            self::Absent   => 'Absent',
            self::Withheld => 'Withheld',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pass     => 'success',
            self::Fail     => 'danger',
            self::Absent   => 'gray',
            self::Withheld => 'warning',
        };
    }
}
