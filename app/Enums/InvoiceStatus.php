<?php

declare(strict_types=1);

namespace App\Enums;

enum InvoiceStatus: string
{
    case Draft = 'draft';
    case Sent = 'sent';
    case Paid = 'paid';
    case Overdue = 'overdue';

    public function label(): string
    {
        return match ($this) {
            self::Draft   => 'Draft',
            self::Sent    => 'Sent',
            self::Paid    => 'Paid',
            self::Overdue => 'Overdue',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft   => 'gray',
            self::Sent    => 'info',
            self::Paid    => 'success',
            self::Overdue => 'danger',
        };
    }
}
