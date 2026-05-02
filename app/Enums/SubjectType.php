<?php

declare(strict_types=1);

namespace App\Enums;

enum SubjectType: string
{
    case Theory = 'theory';
    case Practical = 'practical';
    case Both = 'both';
    case Compulsory = 'compulsory';
}
