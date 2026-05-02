<?php

declare(strict_types=1);

namespace App\Enums;

enum StaffAttendanceStatus: string
{
    case Present = 'present';
    case Absent = 'absent';
    case Late = 'late';
    case HalfDay = 'half_day';
    case OnLeave = 'on_leave';
    case Holiday = 'holiday';

    public function label(): string
    {
        return match ($this) {
            self::Present => 'Present',
            self::Absent  => 'Absent',
            self::Late    => 'Late',
            self::HalfDay => 'Half Day',
            self::OnLeave => 'On Leave',
            self::Holiday => 'Holiday',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Present => 'success',
            self::Absent  => 'danger',
            self::Late    => 'warning',
            self::HalfDay => 'info',
            self::OnLeave => 'primary',
            self::Holiday => 'gray',
        };
    }
}
