<?php

declare(strict_types=1);

namespace App\Enums;

enum SalaryComponentType: string
{
    case Earning   = 'earning';
    case Allowance = 'allowance';
    case Deduction = 'deduction';
}
