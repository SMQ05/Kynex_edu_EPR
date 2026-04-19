<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Tenant\Campus;

/**
 * BelongsToCampus — Adds campus relationship and auto-scoping.
 *
 * Queries are automatically scoped to the user's assigned campus
 * when campus_id is set. Institute-level roles (INSTITUTE_HEAD,
 * MULTI_INSTITUTE_HEAD, SCHOOL_ADMIN) see all records unscoped.
 */
trait BelongsToCampus
{
    public static function bootBelongsToCampus(): void
    {
        static::addGlobalScope('campus', function (Builder $builder) {
            $user = auth()->guard('school_users')->user();

            if (! $user) {
                return;
            }

            // Institute-level roles see everything — no scope
            if ($user->hasRole(['SCHOOL_ADMIN', 'INSTITUTE_HEAD', 'MULTI_INSTITUTE_HEAD'])) {
                return;
            }

            // Campus-bound roles see only their campus
            if ($user->campus_id) {
                $builder->where($builder->getModel()->getTable() . '.campus_id', $user->campus_id);
            }
        });
    }

    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class, 'campus_id');
    }
}
