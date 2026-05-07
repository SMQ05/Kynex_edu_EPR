<?php

declare(strict_types=1);

namespace App\Enums;

enum FeePaymentMethod: string
{
    case Cash = 'cash';
    case Bank = 'bank';
    case Cheque = 'cheque';
    case Online = 'online';
    case Wallet = 'wallet';
    case Refund = 'refund';
}
