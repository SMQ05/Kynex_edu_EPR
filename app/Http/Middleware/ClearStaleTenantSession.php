<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

/**
 * Clears stale `school_users` auth + `tenant_id` session entries when
 * the underlying tenant no longer exists. Runs on every web request
 * so public pages (e.g. /set-password, /forgot-password) don't 500
 * because Filament's notifications Livewire component tries to load
 * the stale user from a dropped tenant DB.
 */
class ClearStaleTenantSession
{
    public function handle(Request $request, Closure $next): mixed
    {
        if (! $request->hasSession()) {
            return $next($request);
        }

        $session = $request->session();

        // 1. If a tenant_id is stashed in the session but that tenant
        //    no longer exists in the central DB, drop it.
        $tenantId = $session->get('tenant_id');
        if ($tenantId && ! Tenant::query()->whereKey($tenantId)->exists()) {
            $session->forget('tenant_id');
            Auth::guard('school_users')->logout();
            $session->invalidate();
            $session->regenerateToken();
            Log::info('ClearStaleTenantSession: dropped session for missing tenant', [
                'tenant_id' => $tenantId,
            ]);
            return $next($request);
        }

        // 2. If we have a school_users auth ID in the session but no
        //    tenancy is active AND the central DB has no school_users
        //    table, the lookup will crash. Pre-empt it: confirm the
        //    table is reachable before letting Filament Notifications
        //    try to render.
        try {
            if (Auth::guard('school_users')->check() && ! tenancy()->initialized) {
                $hasTable = Schema::connection(config('database.default'))
                    ->hasTable('school_users');
                if (! $hasTable) {
                    Auth::guard('school_users')->logout();
                    $session->forget('tenant_id');
                }
            }
        } catch (\Throwable $e) {
            // Defensive: if even the existence check explodes, just log
            // out and move on.
            Auth::guard('school_users')->logout();
            $session->forget('tenant_id');
            Log::warning('ClearStaleTenantSession: forced logout after exception', [
                'error' => $e->getMessage(),
            ]);
        }

        return $next($request);
    }
}
