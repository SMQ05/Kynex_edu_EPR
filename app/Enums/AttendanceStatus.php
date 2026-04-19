<?php

declare(strict_types=1);

namespace App\Enums;

enum AttendanceStatus: string
{
    case Present = 'present';
    case Absent = 'absent';
    case Late = 'late';
    case HalfDay = 'half_day';
    case Holiday = 'holiday';
    case Excused = 'excused';

    public function label(): string
    {
        return match ($this) {
            self::Present => 'Present',
            self::Absent  => 'Absent',
            self::Late    => 'Late',
            self::HalfDay => 'Half Day',
            self::Holiday => 'Holiday',
            self::Excused => 'Excused',
        };
    }

    public function shortLabel(): string
    {
        return match ($this) {
            self::Present => 'P',
            self::Absent  => 'A',
            self::Late    => 'L',
            self::HalfDay => 'HD',
            self::Holiday => 'H',
            self::Excused => 'E',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Present => 'success',
            self::Absent  => 'danger',
            self::Late    => 'warning',
            self::HalfDay => 'info',
            self::Holiday => 'gray',
            self::Excused => 'primary',
        };
    }
}
