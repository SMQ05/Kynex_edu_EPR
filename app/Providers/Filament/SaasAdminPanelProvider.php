<?php

namespace App\Providers\Filament;

use App\Filament\SaasAdmin\Pages\Auth\Login;
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

class SaasAdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('saas-admin')
            ->path('saas')
            ->brandName('KynexEdu Admin')
            ->login(Login::class)
            ->profile(isSimple: true)
            ->authGuard('saas_admin')
            ->userMenuItems([
                MenuItem::make()
                    ->label('Logout')
                    ->icon('heroicon-o-arrow-left-on-rectangle')
                    ->url(fn () => route('filament.saas-admin.auth.logout'))
                    ->postAction(fn () => route('filament.saas-admin.auth.logout')),
            ])
            ->darkMode()
            ->defaultAvatarProvider(\App\Filament\LocalInitialsAvatarProvider::class)
            ->colors([
                'primary' => '#2563EB',
                'danger'  => Color::Red,
                'success' => Color::Green,
            ])
            ->discoverResources(
                in: app_path('Filament/SaasAdmin/Resources'),
                for: 'App\\Filament\\SaasAdmin\\Resources',
            )
            ->discoverPages(
                in: app_path('Filament/SaasAdmin/Pages'),
                for: 'App\\Filament\\SaasAdmin\\Pages',
            )
            ->discoverWidgets(
                in: app_path('Filament/SaasAdmin/Widgets'),
                for: 'App\\Filament\\SaasAdmin\\Widgets',
            )
            ->discoverClusters(
                in: app_path('Filament/SaasAdmin/Clusters'),
                for: 'App\\Filament\\SaasAdmin\\Clusters',
            )
            ->middleware([
                \App\Http\Middleware\BlockCentralPortalOnTenantHost::class,
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->renderHook(
                'panels::head.end',
                fn () => app(\Illuminate\Foundation\Vite::class)('resources/css/filament-custom.css'),
            )
            ->renderHook(
                'panels::head.end',
                fn () => view('filament.panel-theme'),
            );
    }
}
