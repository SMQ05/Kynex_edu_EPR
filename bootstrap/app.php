<?php

use App\Http\Middleware\ClearStaleTenantSession;
use App\Http\Middleware\EnsureTenantIsActive;
use App\Http\Middleware\InitializeTenancyBySubdomainOrDomain;
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
            // Register tenant routes with tenancy middleware
            Route::middleware([
                'web',
                InitializeTenancyBySubdomainOrDomain::class,
                PreventAccessFromCentralDomains::class,
            ])->group(base_path('routes/tenancy.php'));

            // Register tenant API routes with tenancy + api middleware
            Route::middleware([
                InitializeTenancyBySubdomainOrDomain::class,
                PreventAccessFromCentralDomains::class,
            ])->group(base_path('routes/api.php'));

            // Register central portal routes LAST so they win over tenant routes
            // on the central domain (127.0.0.1 / kynexedu.com)
            Route::middleware('web')->group(base_path('routes/web.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Trust nginx reverse proxy so HTTPS URLs are generated correctly
        $middleware->trustProxies(at: '*');

        // Append tenancy init + stale-session cleanup to every web request,
        // including Livewire's internal /livewire/update AJAX route which is
        // outside routes/tenancy.php and therefore missed the per-route group.
        // The middleware itself is idempotent (skips if already initialized).
        $middleware->web(append: [
            InitializeTenancyBySubdomainOrDomain::class,
            ClearStaleTenantSession::class,
        ]);

        // Third-party webhooks / device callbacks (tenant routes) POST in
        // without a Laravel CSRF token. They live in the `web` group, so
        // exempt them from CSRF or every callback 419s:
        //   - JazzCash / EasyPaisa payment callbacks (money confirmation)
        //   - ZKTeco ADMS biometric devices (iclock push protocol)
        //   - Meta WhatsApp webhook (verified by signature/token instead)
        $middleware->validateCsrfTokens(except: [
            'payment/*/callback',
            'iclock/*',
            'whatsapp/webhook',
        ]);

        // Override default guest redirect so unauthenticated users are sent
        // to the portal login instead of the non-existent 'login' route.
        $middleware->redirectGuestsTo(fn () => route('school.login'));

        // Global middleware aliases
        $middleware->alias([
            'tenant.initial' => InitializeTenancyBySubdomainOrDomain::class,
            'tenant.active' => EnsureTenantIsActive::class,
        ]);

        // Ensure tenant initialization runs BEFORE authentication middleware.
        // Laravel's priority list places Authenticate (AuthenticatesRequests) after
        // ShareErrorsFromSession. We insert tenant.initial before it so the tenant
        // DB connection is established before auth tries to load the user.
        $middleware->prependToPriorityList(
            \Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests::class,
            InitializeTenancyBySubdomainOrDomain::class,
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Redirect authenticated panel users who hit a 403 to their panel's
        // AccessDenied page so they see the full sidebar/header chrome instead
        // of a stripped error view. Public-route 403s fall through to 403.blade.php.
        $exceptions->render(function (
            \Symfony\Component\HttpKernel\Exception\HttpException $e,
            \Illuminate\Http\Request $request,
        ) {
            if ($e->getStatusCode() !== 403) {
                return null;
            }

            $panels = [
                'admin'  => ['school_users', 'filament.school-admin.pages.access-denied'],
                'saas'   => ['saas_admin',   'filament.saas-admin.pages.access-denied'],
                'parent' => ['school_users', 'filament.parent.pages.access-denied'],
            ];

            foreach ($panels as $prefix => [$guard, $route]) {
                if ($request->is($prefix . '/*') && auth()->guard($guard)->check()) {
                    return redirect()->route($route)
                        ->with('denied_url', $request->url());
                }
            }

            return null;
        });
    })
    ->create();
