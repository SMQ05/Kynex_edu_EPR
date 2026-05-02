<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Filament\Http\Middleware\Authenticate;

/**
 * Custom Filament Authenticate middleware that redirects unauthenticated users
 * to our custom portal /login page instead of Filament's built-in /admin/login.
 */
class RedirectToPortalLogin extends Authenticate
{
    protected function redirectTo($request): ?string
    {
        return route('school.login');
    }
}
