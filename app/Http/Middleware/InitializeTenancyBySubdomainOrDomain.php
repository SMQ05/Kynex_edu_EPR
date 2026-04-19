<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Stancl\Tenancy\Database\Models\Domain;
use Stancl\Tenancy\Tenancy;
use Symfony\Component\HttpFoundation\Response;

/**
 * InitializeTenancyBySubdomainOrDomain — Phase 15C.2
 *
 * Resolves the tenant from EITHER a subdomain (school.kynexedu.com)
 * or a verified custom domain (portal.brightfuture.com).
 *
 * Logic:
 *  1. If the host ends with a central domain → subdomain lookup (tenant ID = slug)
 *  2. Otherwise → full-domain lookup in the domains table (verified custom domains)
 *  3. No match → abort 404 "School not found"
 */
class InitializeTenancyBySubdomainOrDomain
{
    public function __construct(
        private readonly Tenancy $tenancy,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $host = $request->getHost();
        $centralDomains = config('tenancy.central_domains', []);

        // If the host IS a central domain (no subdomain), skip — serve central app
        if (in_array($host, $centralDomains, true)) {
            return $next($request);
        }

        // ── Try subdomain resolution first ──────────────────────
        $tenantSlug = $this->extractSubdomain($host, $centralDomains);

        if ($tenantSlug) {
            $tenant = \App\Models\Tenant::find($tenantSlug);

            if ($tenant) {
                $this->tenancy->initialize($tenant);

                return $next($request);
            }

            // Subdomain pattern matched but no tenant found — abort
            abort(404, 'School not found.');
        }

        // ── Try custom domain resolution ────────────────────────
        $domain = Domain::where('domain', $host)
            ->where('is_verified', true)
            ->where('domain_type', 'custom')
            ->first();

        if ($domain) {
            $tenant = \App\Models\Tenant::find($domain->tenant_id);

            if ($tenant) {
                $this->tenancy->initialize($tenant);

                return $next($request);
            }
        }

        // ── Nothing matched ─────────────────────────────────────
        abort(404, 'School not found.');
    }

    /**
     * Extract the subdomain part from the host.
     * Returns null if the host doesn't match any central domain pattern.
     */
    private function extractSubdomain(string $host, array $centralDomains): ?string
    {
        foreach ($centralDomains as $domain) {
            if (str_ends_with($host, '.' . $domain)) {
                return substr($host, 0, -strlen('.' . $domain));
            }
        }

        return null;
    }
}
