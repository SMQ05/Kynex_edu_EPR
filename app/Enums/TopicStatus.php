<?php

declare(strict_types=1);

namespace App\Enums;

enum TopicStatus: string
{
    case Planned    = 'planned';
    case InProgress = 'in_progress';
    case Completed  = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::Planned    => 'Planned',
            self::InProgress => 'In Progress',
            self::Completed  => 'Completed',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Planned    => 'gray',
            self::InProgress => 'info',
            self::Completed  => 'success',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $c) => [$c->value => $c->label()])
            ->all();
    }
}
