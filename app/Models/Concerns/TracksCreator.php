<?php

declare(strict_types=1);

namespace App\Models\Concerns;

/**
 * Auto-fills `created_by` with the current school user on insert (when the
 * column exists and isn't already set). Covers all creation paths —
 * Filament create pages, relation managers, jobs run in a user context.
 * Safe in non-auth contexts (queue/CLI): simply leaves it null.
 */
trait TracksCreator
{
    public static function bootTracksCreator(): void
    {
        static::creating(function ($model): void {
            if (empty($model->created_by)) {
                $id = auth()->guard('school_users')->id();
                if ($id) {
                    $model->created_by = $id;
                }
            }
        });
    }
}
