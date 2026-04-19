<?php

declare(strict_types=1);

namespace App\Enums;

enum StudentStatus: string
{
    case Enrolled = 'enrolled';
    case Left = 'left';
    case Graduated = 'graduated';
    case Expelled = 'expelled';
    case Suspended = 'suspended';
}
