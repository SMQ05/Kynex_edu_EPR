<?php

declare(strict_types=1);

namespace App\Filament\SaasAdmin\Pages\Auth;

use Filament\Auth\Pages\Login as BaseLogin;
use Illuminate\Validation\ValidationException;

class Login extends BaseLogin
{
    /**
     * Override the default "invalid credentials" error with a custom
     * IP-restriction message directing the user to contact support.
     */
    protected function throwFailureValidationException(): never
    {
        throw ValidationException::withMessages([
            'data.email' => 'Your IP is not allowed. Please contact qasim@kynexsolutions.com',
        ]);
    }
}
