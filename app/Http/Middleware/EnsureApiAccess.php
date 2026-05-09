<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * EnsureApiAccess — Middleware that checks the tenant's subscription plan
 * has api_access enabled. Returns 403 if the plan does not include API access.
 */
class EnsureApiAccess
{
    public function handle(Request $request, Closure $next): mixed
    {
        if (! tenancy()->initialized) {
            return response()->json([
                'success' => false,
                'message' => 'Tenant not identified.',
            ], 403);
        }

        $tenant = tenant();
        $plan = $tenant->plan ?? null;

        if (! $plan || ! $plan->api_access) {
            return response()->json([
                'success' => false,
                'message' => 'API access requires Enterprise plan.',
            ], 403);
        }

        return $next($request);
    }
}
