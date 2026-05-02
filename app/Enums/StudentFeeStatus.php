<?php

declare(strict_types=1);

namespace App\Enums;

enum StudentFeeStatus: string
{
    case Pending = 'pending';
    case Partial = 'partial';
    case Paid = 'paid';
    case Waived = 'waived';
    case Refunded = 'refunded';
}
