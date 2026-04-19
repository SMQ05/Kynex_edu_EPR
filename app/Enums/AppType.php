<?php

declare(strict_types=1);

namespace App\Enums;

enum AppType: string
{
    case Management = 'management';
    case StudentParent = 'student_parent';
}
