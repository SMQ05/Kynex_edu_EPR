<?php

declare(strict_types=1);

namespace App\Enums;

enum StudentGender: string
{
    case Male = 'male';
    case Female = 'female';
    case Other = 'other';
}
