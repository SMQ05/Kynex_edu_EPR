<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Refuses requests whose Host header is not in tenancy.central_domains.
 * Apply to routes that must only render on the SaaS host (school self-signup,
 * SaaS marketing pages) — never on a tenant's custom domain.
 *
 * On a non-central host returns the neutral, unbranded
 * errors.domain-not-configured 404 view rather than leaking SaaS chrome.
 */
class EnsureCentralHost
{
    public function handle(Request $request, Closure $next): mixed
    {
        $central = config('tenancy.central_domains', []);

        if (in_array($request->getHost(), $central, true)) {
            return $next($request);
        }

        return response()->view('errors.domain-not-configured', [], 404);
    }
}
