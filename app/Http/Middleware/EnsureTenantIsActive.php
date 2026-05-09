<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Stancl\Tenancy\Tenancy;
use Symfony\Component\HttpFoundation\Response;

/**
 * EnsureTenantIsActive — Middleware that blocks requests if the current
 * tenant is suspended or expired.
 *
 * Apply this to tenant panel routes that require an active subscription.
 */
class EnsureTenantIsActive
{
    public function __construct(
        private readonly Tenancy $tenancy,
    ) {}

    public function handle(Request $request, Closure $next): mixed
    {
        $tenant = $this->tenancy->initialized ? $this->tenancy->tenant : null;

        if (! $tenant) {
            return $next($request);
        }

        $status = $tenant->status?->value ?? $tenant->status ?? 'active';

        // Suspended tenant
        if ($status === 'suspended') {
            if ($request->expectsJson()) {
                return response()->json([
                    'error'   => 'tenant_suspended',
                    'message' => 'This school account is currently suspended. Please contact support.',
                ], 403);
            }

            return response()->view('errors.suspended', [], 403);
        }

        // Expired tenant
        if ($status === 'expired') {
            if ($request->expectsJson()) {
                return response()->json([
                    'error'   => 'tenant_expired',
                    'message' => 'This school subscription has expired. Please renew to restore access.',
                ], 403);
            }

            return response()->view('errors.expired', [], 403);
        }

        return $next($request);
    }
}
