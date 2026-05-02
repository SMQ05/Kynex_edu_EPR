<?php

declare(strict_types=1);

namespace App\Enums;

enum ExamType: string
{
    case Quarterly = 'quarterly';
    case MidTerm   = 'mid_term';
    case SemiFinal = 'semi_final';
    case Final     = 'final';
    case Annual    = 'annual';
    case Custom    = 'custom';

    public function label(): string
    {
        return match ($this) {
            self::Quarterly => 'Quarterly',
            self::MidTerm   => 'Mid-Term',
            self::SemiFinal => 'Semi-Final',
            self::Final     => 'Final',
            self::Annual    => 'Annual',
            self::Custom    => 'Custom',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Quarterly => 'info',
            self::MidTerm   => 'warning',
            self::SemiFinal => 'primary',
            self::Final     => 'danger',
            self::Annual    => 'success',
            self::Custom    => 'gray',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $c) => [$c->value => $c->label()])
            ->all();
    }
}
