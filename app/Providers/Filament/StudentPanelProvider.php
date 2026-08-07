<?php

declare(strict_types=1);

namespace App\Providers\Filament;

use App\Http\Middleware\EnsureStudentRole;
use App\Http\Middleware\EnsureTenantIsActive;
use App\Http\Middleware\InitializeTenancyBySubdomainOrDomain;
use App\Http\Middleware\SetPostgresUserRole;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\MenuItem;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

/**
 * Student portal — a Filament panel mounted at /student.
 *
 * Shares the school_users guard with the admin and parent panels, but every
 * page here scopes strictly to the signed-in student's own record: their
 * lectures, their assignments, their results, their fees, their ID card.
 *
 * Access is gated twice on purpose. EnsureStudentRole checks the STUDENT role
 * and that a student record actually exists, and SchoolUser::canAccessPanel()
 * separately confines STUDENT accounts to this panel while keeping everyone
 * else out of it.
 */
class StudentPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('student')
            ->path('student')
            ->authGuard('school_users')
            ->login()
            ->brandName('Student Portal')
            ->colors([
                'primary' => Color::Teal,
                'danger'  => Color::Red,
                'success' => Color::Green,
                'warning' => Color::Amber,
            ])
            ->darkMode()
            ->defaultAvatarProvider(\App\Filament\LocalInitialsAvatarProvider::class)
            ->discoverPages(
                in: app_path('Filament/StudentPortal/Pages'),
                for: 'App\\Filament\\StudentPortal\\Pages',
            )
            ->discoverWidgets(
                in: app_path('Filament/StudentPortal/Widgets'),
                for: 'App\\Filament\\StudentPortal\\Widgets',
            )
            ->userMenuItems([
                MenuItem::make()
                    ->label('Logout')
                    ->icon('heroicon-o-arrow-left-on-rectangle')
                    ->url(fn () => route('filament.student.auth.logout'))
                    ->postAction(fn () => route('filament.student.auth.logout')),
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                'tenant.initial',
                'tenant.active',
                SetPostgresUserRole::class,
            ])
            ->middleware([
                DispatchServingFilamentEvent::class,
                InitializeTenancyBySubdomainOrDomain::class,
                EnsureTenantIsActive::class,
                SetPostgresUserRole::class,
            ], isPersistent: true)
            ->authMiddleware([Authenticate::class, EnsureStudentRole::class]);
    }
}
