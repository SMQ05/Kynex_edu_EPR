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
        $host = strtolower($request->getHost());
        $centralDomains = array_map('strtolower', (array) config('tenancy.central_domains', []));

        // ── 1. Central domain (e.g. sms.kynexsolutions.com) ──────────
        // The /admin panel and SaaS panel live on the central host.
        // Tenancy is initialised from session > ?tenant= query string.
        if (in_array($host, $centralDomains, true)) {
            $tenantId = ($request->hasSession() ? $request->session()->get('tenant_id') : null)
                ?: $request->query('tenant');

            if ($tenantId) {
                $tenant = \App\Models\Tenant::find($tenantId);
                if ($tenant) {
                    $this->tenancy->initialize($tenant);
                } else {
                    if ($request->hasSession()) {
                        $request->session()->forget('tenant_id');
                    }
                    \Illuminate\Support\Facades\Auth::guard('school_users')->logout();
                }
            }
            return $next($request);
        }

        // ── 2. Custom domain or tenant subdomain ─────────────────────
        // A school can register their own domain (e.g. school.example.com)
        // for their PUBLIC WEBSITE. The mapping lives in the central
        // `domains` table; routing only points to the public-site
        // controller — the /admin and /saas panels stay on the central
        // host so admins always log in at sms.kynexsolutions.com.

        // First try an exact-host match against the domains table.
        try {
            $domainRow = \Stancl\Tenancy\Database\Models\Domain::query()
                ->where('domain', $host)
                ->first();
        } catch (\Throwable) {
            $domainRow = null;
        }

        if ($domainRow && $domainRow->tenant_id) {
            $tenant = \App\Models\Tenant::find($domainRow->tenant_id);
            if ($tenant) {
                $this->tenancy->initialize($tenant);

                // For a custom domain, rewrite root requests to the
                // public site so visitors landing on the apex see the
                // home page automatically. Don't touch /admin or /saas
                // — those should remain unreachable on custom domains.
                $path = $request->getPathInfo();
                if ($path === '/' || $path === '') {
                    return redirect('/site/' . $tenant->id);
                }
                if (\Illuminate\Support\Str::startsWith($path, ['/admin', '/saas', '/login', '/saas-login', '/register'])) {
                    abort(404, 'Not available on this domain. Visit your KynexEdu portal at sms.kynexsolutions.com to manage your school.');
                }

                return $next($request);
            }
        }

        // ── 3. Fall back: extract tenant slug from sub-domain part ───
        $tenantSlug = $this->extractSubdomain($host, $centralDomains);
        if (! $tenantSlug) {
            return $next($request);
        }

        // Browsers normalise hostnames to lowercase, but tenant ids
        // include a mixed-case random suffix (e.g. "BEb3S9"). Look up
        // case-insensitively so the canonical mixed-case id is found.
        $tenant = \App\Models\Tenant::query()
            ->whereRaw('LOWER(id) = ?', [strtolower($tenantSlug)])
            ->first();
        if (! $tenant) {
            abort(404, 'School not found.');
        }

        $this->tenancy->initialize($tenant);

        return $next($request);
    }

    /**
     * Extract the subdomain part from the host.
     *
     * Match the longest central domain suffix first — otherwise a tenant on
     * sms.kynexsolutions.com would be parsed under kynexsolutions.com and
     * the resulting slug would still contain ".sms".
     */
    private function extractSubdomain(string $host, array $centralDomains): ?string
    {
        usort($centralDomains, fn ($a, $b) => strlen($b) <=> strlen($a));

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
