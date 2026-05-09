<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * SetPostgresUserRole — Sets the PostgreSQL session variable `app.user_role`
 * after successful authentication. This enables Row Level Security policies
 * to enforce confidentiality at the database level.
 */
class SetPostgresUserRole
{
    public function handle(Request $request, Closure $next): mixed
    {
        $connection = DB::connection();

        if ($connection->getDriverName() !== 'pgsql') {
            return $next($request);
        }

        // Resolving the school_users guard hits the school_users table.
        // That table only exists inside a tenant DB. If tenancy isn't
        // initialised (e.g. the request is /set-password on the central
        // domain, or the session holds a stale tenant_id pointing at a
        // dropped DB), the lookup throws "school_users does not exist"
        // and the whole request 500s. Skip the guard resolve in that case
        // and just leave RLS in NONE mode.
        $user = null;
        if (tenancy()->initialized) {
            try {
                $user = $request->user('school_users')
                    ?? Auth::guard('school_users')->user()
                    ?? $request->user();
            } catch (\Throwable $e) {
                // Stale session referring to a deleted tenant — clear it
                // so the next request doesn't hit the same crash.
                if ($request->hasSession()) {
                    $request->session()->forget('tenant_id');
                    Auth::guard('school_users')->logout();
                }
                $user = null;
            }
        }

        $role = ($user && method_exists($user, 'getActiveRoleName'))
            ? ($user->getActiveRoleName() ?? 'NONE')
            : 'NONE';

        $safeRole = preg_replace('/[^A-Z_0-9]/', '', strtoupper($role)) ?: 'NONE';

        // Use set_config(..., false) so the role remains available for all queries
        // in this request. SET LOCAL outside a transaction may be discarded.
        $connection->select("SELECT set_config('app.user_role', ?, false)", [$safeRole]);

        return $next($request);
    }
}
