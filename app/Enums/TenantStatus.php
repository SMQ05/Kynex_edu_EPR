<?php

declare(strict_types=1);

namespace App\Enums;

enum TenantStatus: string
{
    case Trial = 'trial';
    case Active = 'active';
    case Suspended = 'suspended';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Trial     => 'Trial',
            self::Active    => 'Active',
            self::Suspended => 'Suspended',
            self::Expired   => 'Expired',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Trial     => 'info',
            self::Active    => 'success',
            self::Suspended => 'danger',
            self::Expired   => 'warning',
        };
    }
}
