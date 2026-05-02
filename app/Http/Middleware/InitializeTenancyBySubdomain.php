<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Stancl\Tenancy\Tenancy;
use Symfony\Component\HttpFoundation\Response;

/**
 * InitializeTenancyBySubdomain — Resolves the tenant from the subdomain
 * and initializes tenancy before the request is handled.
 *
 * For requests to {slug}.kynexedu.com, this middleware:
 *  1. Extracts the subdomain as the tenant ID
 *  2. Finds the matching Tenant model
 *  3. Calls tenancy()->initialize($tenant) to bootstrap tenant awareness
 *
 * If no subdomain is present (e.g. kynexedu.com), the central app is served.
 */
class InitializeTenancyBySubdomain
{
    public function __construct(
        private readonly Tenancy $tenancy,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $host = $request->getHost();
        $centralDomains = config('tenancy.central_domains', []);

        // If the host is a central domain, check for a session-stored tenant ID.
        // This allows the Filament /admin panel to work on the central domain when
        // there is no subdomain (e.g. 127.0.0.1 in local dev, or as a fallback).
        if (in_array($host, $centralDomains, true)) {
            // Order: session > ?tenant= query string > nothing.
            // The query-string fallback lets public pages (school website,
            // admissions form) work on the central domain when DNS isn't
            // pointing each tenant at a subdomain yet.
            $tenantId = ($request->hasSession() ? $request->session()->get('tenant_id') : null)
                ?: $request->query('tenant');

            if ($tenantId) {
                $tenant = \App\Models\Tenant::find($tenantId);

                if ($tenant) {
                    $this->tenancy->initialize($tenant);
                } else {
                    // Stale session pointing at a tenant that no longer
                    // exists. Clear the cookie + auth so we don't keep
                    // crashing on every Livewire poll.
                    if ($request->hasSession()) {
                        $request->session()->forget('tenant_id');
                    }
                    \Illuminate\Support\Facades\Auth::guard('school_users')->logout();
                }
            }

            return $next($request);
        }

        // Extract tenant slug from subdomain
        // e.g. "greenfield-academy.kynexedu.com" → "greenfield-academy"
        $tenantSlug = $this->extractSubdomain($host, $centralDomains);

        if (! $tenantSlug) {
            return $next($request);
        }

        // Look up the tenant by its ID (which is the slug)
        $tenant = \App\Models\Tenant::find($tenantSlug);

        if (! $tenant) {
            abort(404, 'School not found.');
        }

        // Initialize tenant context (DB, cache, filesystem, queue tags)
        $this->tenancy->initialize($tenant);

        return $next($request);
    }

    /**
     * Extract the subdomain part from the host.
     */
    private function extractSubdomain(string $host, array $centralDomains): ?string
    {
        foreach ($centralDomains as $domain) {
            if (str_ends_with($host, '.'.$domain)) {
                return substr($host, 0, -strlen('.'.$domain));
            }
        }

        // If no central domain pattern matched, treat the whole host as the identifier
        // (useful for local testing with custom hosts)
        return null;
    }
}
