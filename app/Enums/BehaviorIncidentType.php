<?php

namespace App\Enums;

enum BehaviorIncidentType: string
{
    case Positive = 'positive';
    case Negative = 'negative';
    case Neutral = 'neutral';

    public function label(): string
    {
        return match ($this) {
            self::Positive => 'Positive',
            self::Negative => 'Negative',
            self::Neutral => 'Neutral',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Positive => 'success',
            self::Negative => 'danger',
            self::Neutral => 'gray',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Positive => 'heroicon-o-hand-thumb-up',
            self::Negative => 'heroicon-o-hand-thumb-down',
            self::Neutral => 'heroicon-o-minus-circle',
        };
    }
}
