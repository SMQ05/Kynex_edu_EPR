<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Stancl\Tenancy\Database\Models\Domain;
use Symfony\Component\HttpFoundation\Response;

/**
 * BlockCentralPortalOnTenantHost
 *
 * The school self-service portal (login, register, forgot-password,
 * dashboard…) and the SaaS admin panel (/saas) only make sense on the
 * central host (sms.kynexsolutions.com). On a tenant subdomain or a
 * school's own custom domain those URLs must 404 — visitors looking
 * to manage their school should be sent back to the central portal.
 *
 * The middleware is path-aware: it only blocks well-known central-only
 * paths, so the public website (/site/{tenant}, /apply, /verify/*) and
 * tenant-aware downloads (/payslip, /result-card, /fee-receipt) keep
 * working on a tenant host.
 */
class BlockCentralPortalOnTenantHost
{
    /**
     * Path prefixes that only make sense on the central host.
     */
    private const BLOCKED_PREFIXES = [
        '/login',
        '/logout',
        '/register',
        '/forgot-password',
        '/reset-password',
        '/set-password',
        '/verify-email',
        '/dashboard',
        '/saas',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $host = strtolower($request->getHost());
        $centralDomains = array_map('strtolower', (array) config('tenancy.central_domains', []));

        if (in_array($host, $centralDomains, true)) {
            return $next($request);
        }

        if (! $this->isTenantHost($host, $centralDomains)) {
            return $next($request);
        }

        if ($this->pathIsBlocked($request->getPathInfo())) {
            abort(404, 'This page is only available on the KynexEdu portal at sms.kynexsolutions.com.');
        }

        return $next($request);
    }

    private function isTenantHost(string $host, array $centralDomains): bool
    {
        try {
            if (Domain::query()->where('domain', $host)->exists()) {
                return true;
            }
        } catch (\Throwable) {
            // central DB not reachable — fall back to suffix check
        }

        usort($centralDomains, fn ($a, $b) => strlen($b) <=> strlen($a));

        foreach ($centralDomains as $central) {
            if (str_ends_with($host, '.' . $central)) {
                return true;
            }
        }

        return false;
    }

    private function pathIsBlocked(string $path): bool
    {
        $path = '/' . ltrim($path, '/');

        foreach (self::BLOCKED_PREFIXES as $prefix) {
            if ($path === $prefix || str_starts_with($path, $prefix . '/')) {
                return true;
            }
        }

        return false;
    }
}
