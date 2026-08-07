<?php

declare(strict_types=1);

namespace App\Providers\Filament;

use App\Http\Middleware\EnsureParentRole;
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
 * Parent portal — a separate Filament panel mounted at /parent that
 * shares the school_users guard with the admin panel but only exposes
 * read-only views for guardians: list of their children, each child's
 * marks/results/upcoming homework/attendance summary.
 */
class ParentPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('parent')
            ->path('parent')
            ->authGuard('school_users')
            ->login()
            ->brandName('KynexEdu Parent Portal')
            ->colors([
                'primary' => Color::Indigo,
                'danger'  => Color::Red,
                'success' => Color::Green,
                'warning' => Color::Amber,
            ])
            ->darkMode()
            ->defaultAvatarProvider(\App\Filament\LocalInitialsAvatarProvider::class)
            ->discoverResources(
                in: app_path('Filament/ParentPortal/Resources'),
                for: 'App\\Filament\\ParentPortal\\Resources',
            )
            ->discoverPages(
                in: app_path('Filament/ParentPortal/Pages'),
                for: 'App\\Filament\\ParentPortal\\Pages',
            )
            ->userMenuItems([
                MenuItem::make()
                    ->label('Logout')
                    ->icon('heroicon-o-arrow-left-on-rectangle')
                    ->url(fn () => route('filament.parent.auth.logout'))
                    ->postAction(fn () => route('filament.parent.auth.logout')),
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
            ->middleware([DispatchServingFilamentEvent::class, InitializeTenancyBySubdomainOrDomain::class, EnsureTenantIsActive::class, SetPostgresUserRole::class], isPersistent: true)
            ->authMiddleware([Authenticate::class, EnsureParentRole::class])
            ->renderHook(
                'panels::body.end',
                fn () => view('livewire.ai-assistant-launcher'),
            );
    }
}
