<?php

declare(strict_types=1);

namespace App\Enums;

enum LeaveApplicableTo: string
{
    case Staff = 'staff';
    case Student = 'student';
    case Both = 'both';
}
