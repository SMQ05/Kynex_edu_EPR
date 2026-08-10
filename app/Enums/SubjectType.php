<?php

declare(strict_types=1);

namespace App\Enums;

enum SubjectType: string
{
    case Theory = 'theory';
    case Practical = 'practical';
    case Both = 'both';
    case Compulsory = 'compulsory';

    // The shipped demo data marks Computer Science, Physics, Chemistry and
    // Biology as electives, and the enum already carries Compulsory, so its
    // counterpart belongs here. Without it, seeding a brand new tenant died
    // with "'elective' is not a valid backing value" partway through, leaving
    // the tenant half-built.
    case Elective = 'elective';
}
