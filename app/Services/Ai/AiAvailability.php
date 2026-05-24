<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Models\Tenant;

/**
 * Non-throwing checks for whether AI features can run for the current
 * tenant. Use this to show/hide "✨ AI" buttons in Filament without the
 * UI ever hitting AiAssistant's exceptions (which are meant for the
 * actual call site).
 */
class AiAvailability
{
    /** AI is enabled for the current tenant AND has budget headroom. */
    public static function enabled(): bool
    {
        $tenant = self::tenant();
        if (! $tenant instanceof Tenant) {
            return false;
        }

        if (! $tenant->ai_enabled) {
            return false;
        }

        return ! self::budgetExhausted($tenant);
    }

    /** Monthly budget cap reached (0 cap = unlimited). */
    public static function budgetExhausted(?Tenant $tenant = null): bool
    {
        $tenant ??= self::tenant();
        if (! $tenant instanceof Tenant) {
            return false;
        }

        $cap = (int) ($tenant->ai_monthly_budget_paisas ?? 0);

        return $cap > 0 && (int) $tenant->ai_used_this_month_paisas >= $cap;
    }

    /** Human-readable reason AI is off, for tooltips/notifications. */
    public static function reason(): ?string
    {
        $tenant = self::tenant();
        if (! $tenant instanceof Tenant) {
            return 'No active school context.';
        }
        if (! $tenant->ai_enabled) {
            return 'AI is turned off for this school. Enable it under Settings → AI Settings.';
        }
        if (self::budgetExhausted($tenant)) {
            return 'This month\'s AI budget is used up. Raise it under Settings → AI Settings.';
        }

        return null;
    }

    private static function tenant(): ?Tenant
    {
        $tenant = tenancy()->tenant;

        return $tenant instanceof Tenant ? $tenant : null;
    }
}
