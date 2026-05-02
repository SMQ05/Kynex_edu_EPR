<?php

declare(strict_types=1);

namespace App\Enums;

enum GuardianType: string
{
    case Father = 'father';
    case Mother = 'mother';
    case Guardian = 'guardian';
}
