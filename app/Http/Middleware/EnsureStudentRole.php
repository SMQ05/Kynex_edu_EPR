<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Restricts the /student panel to logged-in school_users that hold the
 * STUDENT role and are linked to an actual student record.
 *
 * The student-record check matters: every page in this panel scopes its
 * queries through the signed-in student's own row, so a STUDENT-role login
 * with no linked record would render empty pages rather than fail loudly.
 * Better to say so here.
 */
class EnsureStudentRole
{
    public function handle(Request $request, Closure $next): mixed
    {
        $user = auth()->guard('school_users')->user();

        if (! $user) {
            return redirect()->route('school.login');
        }

        if (! $user->hasRole('STUDENT')) {
            abort(403, 'Student portal is only available to accounts with the STUDENT role.');
        }

        if (! $user->student()->exists()) {
            abort(403, 'This student login is not linked to a student record. Contact the school office.');
        }

        return $next($request);
    }
}
