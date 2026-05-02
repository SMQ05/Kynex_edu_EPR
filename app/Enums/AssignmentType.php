<?php

declare(strict_types=1);

namespace App\Enums;

enum AssignmentType: string
{
    case Homework        = 'homework';
    case ClassAssignment = 'class_assignment';
    case ClassTest       = 'class_test';

    public function label(): string
    {
        return match ($this) {
            self::Homework        => 'Homework',
            self::ClassAssignment => 'Class Assignment',
            self::ClassTest       => 'Class Test',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Homework        => 'info',
            self::ClassAssignment => 'success',
            self::ClassTest       => 'warning',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $c) => [$c->value => $c->label()])
            ->all();
    }
}
