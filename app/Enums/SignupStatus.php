<?php

declare(strict_types=1);

namespace App\Enums;

enum SignupStatus: string
{
    case New = 'new';
    case Contacted = 'contacted';
    case Invited = 'invited';
    case Onboarded = 'onboarded';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::New       => 'New',
            self::Contacted => 'Contacted',
            self::Invited   => 'Invited',
            self::Onboarded => 'Onboarded',
            self::Rejected  => 'Rejected',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::New       => 'info',
            self::Contacted => 'warning',
            self::Invited   => 'primary',
            self::Onboarded => 'success',
            self::Rejected  => 'danger',
        };
    }
}
