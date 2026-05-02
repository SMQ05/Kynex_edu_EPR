<?php

declare(strict_types=1);

namespace App\Enums;

enum SyllabusStatus: string
{
    case Draft     = 'draft';
    case Published = 'published';
    case Archived  = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft     => 'Draft',
            self::Published => 'Published',
            self::Archived  => 'Archived',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft     => 'gray',
            self::Published => 'success',
            self::Archived  => 'warning',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $c) => [$c->value => $c->label()])
            ->all();
    }
}
