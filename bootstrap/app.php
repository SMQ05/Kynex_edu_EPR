<?php

use App\Http\Middleware\EnsureTenantIsActive;
use App\Http\Middleware\InitializeTenancyBySubdomain;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            // Register tenant routes with tenancy middleware (subdomains only)
            Route::middleware([
                'web',
                InitializeTenancyBySubdomain::class,
                PreventAccessFromCentralDomains::class,
            ])->group(base_path('routes/tenancy.php'));

            // Register tenant API routes with tenancy + api middleware
            Route::middleware([
                InitializeTenancyBySubdomain::class,
                PreventAccessFromCentralDomains::class,
            ])->group(base_path('routes/api.php'));

            // Register central portal routes LAST so they win over tenant routes
            // on the central domain (127.0.0.1 / kynexedu.com)
            Route::middleware('web')->group(base_path('routes/web.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Global middleware aliases
        $middleware->alias([
            'tenant.initial' => InitializeTenancyBySubdomain::class,
            'tenant.active' => EnsureTenantIsActive::class,
        ]);

        // Ensure tenant initialization runs BEFORE authentication middleware.
        // Laravel's priority list places Authenticate (AuthenticatesRequests) after
        // ShareErrorsFromSession. We insert tenant.initial before it so the tenant
        // DB connection is established before auth tries to load the user.
        $middleware->prependToPriorityList(
            \Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests::class,
            InitializeTenancyBySubdomain::class,
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->create();
