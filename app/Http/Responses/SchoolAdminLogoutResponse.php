<?php

declare(strict_types=1);

namespace App\Http\Responses;

use Filament\Auth\Http\Responses\Contracts\LogoutResponse as Responsable;
use Illuminate\Http\RedirectResponse;
use Livewire\Features\SupportRedirects\Redirector;

/**
 * Sends school-admin logout traffic to our custom portal login route
 * instead of Filament's built-in `filament.school-admin.auth.login`,
 * which is intentionally not registered (web.php redirects /admin/login
 * to /login so all auth flows happen through the school portal).
 */
class SchoolAdminLogoutResponse implements Responsable
{
    public function toResponse($request): RedirectResponse | Redirector
    {
        return redirect()->to(route('school.login'));
    }
}
