<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * SetPostgresUserRole — Sets the PostgreSQL session variable `app.user_role`
 * after successful authentication. This enables Row Level Security policies
 * to enforce confidentiality at the database level.
 */
class SetPostgresUserRole
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && DB::connection()->getDriverName() === 'pgsql') {
            $role = method_exists($user, 'getActiveRoleName')
                ? ($user->getActiveRoleName() ?? 'NONE')
                : 'NONE';

            // Sanitise to prevent SQL injection — role names are alphanumeric + underscore.
            // RISK 4: String interpolation in DB::statement is a known trade-off.
            // The regex allowlist (/[^A-Z_0-9]/) ensures only safe characters reach
            // the query. A parameterised "SET ... = $1" is not supported by all PG
            // drivers for session variables. If this causes audit concern, switch to
            // DB::statement("SET app.user_role = ?", [$safeRole]) after verifying
            // the driver supports parameterised SET for custom GUC variables.
            $safeRole = preg_replace('/[^A-Z_0-9]/', '', strtoupper($role));

            // SET LOCAL resets after each transaction — safe with PgBouncer transaction pooling.
            // Prevents role leaking between requests on shared connections.
            DB::statement("SET LOCAL app.user_role = '{$safeRole}'");
        }

        return $next($request);
    }
}
