<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

/**
 * SetTenantLocale — Reads the tenant's preferred_language setting and
 * sets the Laravel application locale for the current request.
 *
 * Supported locales: 'en' (default), 'ur' (Urdu/RTL)
 *
 * This middleware runs after tenancy is initialized (it's registered in
 * SchoolAdminPanelProvider which runs inside the tenant context).
 * The actual locale switching is already done in SchoolAdminPanelProvider::boot(),
 * but this middleware provides the same functionality for non-Filament
 * tenant routes (e.g., API routes, CMS routes).
 *
 * Usage in bootstrap/app.php or panel provider:
 *   ->middleware([\App\Http\Middleware\SetTenantLocale::class])
 */
class SetTenantLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        // Only applies when tenancy is active
        if (! function_exists('tenant') || ! tenant()) {
            return $next($request);
        }

        $tenant = tenant();

        // Read preferred_language from tenant settings; default to 'en'
        $locale = $tenant->preferred_language ?? 'en';

        // Validate against supported locales to prevent arbitrary locale injection
        $supported = ['en', 'ur'];

        if (in_array($locale, $supported, true)) {
            App::setLocale($locale);
        } else {
            App::setLocale('en');
        }

        return $next($request);
    }
}
