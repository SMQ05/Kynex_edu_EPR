<?php

declare(strict_types=1);

namespace App\Enums;

enum SalaryCalculationType: string
{
    case Fixed = 'fixed';
    case Percentage = 'percentage';
}
