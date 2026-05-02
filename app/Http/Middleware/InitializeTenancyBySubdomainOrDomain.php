<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Stancl\Tenancy\Database\Models\Domain;
use Stancl\Tenancy\Tenancy;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the tenant from one of:
 *   1. Central host  → session('tenant_id') or ?tenant= query (load-bearing
 *      for the SaaS host login flow — sms.kynexsolutions.com/admin relies on
 *      this; a Filament Livewire poll without it crashes on every request).
 *   2. Subdomain pattern (e.g. school1.kynexedu.com) → tenant id == slug.
 *   3. Verified custom domain in the central `domains` table.
 *   4. Otherwise → pass through with no tenancy (PublicSiteController and
 *      similar URL-param routes still work; tenant-only routes will fail
 *      downstream, which is the correct signal).
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
                    Auth::guard('school_users')->logout();
                }
            }

            return $next($request);
        }

        $tenantSlug = $this->extractSubdomain($host, $centralDomains);

        if ($tenantSlug) {
            $tenant = \App\Models\Tenant::find($tenantSlug);

            if (! $tenant) {
                abort(404, 'School not found.');
            }

            $this->tenancy->initialize($tenant);

            return $next($request);
        }

        $domain = Domain::where('domain', $host)
            ->where('is_verified', true)
            ->where('domain_type', 'custom')
            ->first();

        if ($domain) {
            $tenant = \App\Models\Tenant::find($domain->tenant_id);

            if ($tenant) {
                $this->tenancy->initialize($tenant);
            }
        }

        return $next($request);
    }

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
