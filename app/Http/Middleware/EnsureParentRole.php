<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restricts the /parent panel to logged-in school_users that hold the
 * PARENT role. Anyone else gets redirected back to the school login
 * with a flash message.
 */
class EnsureParentRole
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->guard('school_users')->user();

        if (! $user) {
            return redirect()->route('school.login');
        }

        if (! $user->hasRole('PARENT')) {
            abort(403, 'Parent portal is only available to accounts with the PARENT role.');
        }

        return $next($request);
    }
}
