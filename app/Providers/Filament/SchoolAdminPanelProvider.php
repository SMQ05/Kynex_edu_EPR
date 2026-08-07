<?php

namespace App\Providers\Filament;

use App\Http\Middleware\EnsureTenantIsActive;
use App\Http\Middleware\InitializeTenancyBySubdomainOrDomain;
use App\Http\Middleware\RedirectToPortalLogin;
use App\Http\Middleware\SetPostgresUserRole;
use App\Http\Responses\SchoolAdminLogoutResponse;
use Filament\Auth\Http\Responses\Contracts\LogoutResponse as LogoutResponseContract;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\MenuItem;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\App;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class SchoolAdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('school-admin')
            ->path('admin')
            ->authGuard('school_users')
            ->profile(isSimple: true)
            ->userMenuItems([
                'switch-role' => MenuItem::make()
                    ->label('Switch Role')
                    ->icon('heroicon-o-arrows-right-left')
                    ->url(fn () => route('filament.school-admin.pages.switch-role'))
                    ->visible(fn () => auth('school_users')->check() && (auth('school_users')->user()?->roles->count() ?? 0) > 1),
                MenuItem::make()
                    ->label('Logout')
                    ->icon('heroicon-o-arrow-left-on-rectangle')
                    ->url(fn () => route('filament.school-admin.auth.logout'))
                    ->postAction(fn () => route('filament.school-admin.auth.logout')),
            ])
            ->brandName('KynexEdu School Panel')
            ->colors([
                'primary' => Color::Emerald,
                'danger'  => Color::Red,
                'success' => Color::Green,
                'warning' => Color::Amber,
                'gray'    => Color::Slate,
            ])
            ->darkMode()
            ->defaultAvatarProvider(\App\Filament\LocalInitialsAvatarProvider::class)
            ->discoverResources(
                in: app_path('Filament/SchoolAdmin/Resources'),
                for: 'App\\Filament\\SchoolAdmin\\Resources',
            )
            ->discoverPages(
                in: app_path('Filament/SchoolAdmin/Pages'),
                for: 'App\\Filament\\SchoolAdmin\\Pages',
            )
            ->discoverWidgets(
                in: app_path('Filament/SchoolAdmin/Widgets'),
                for: 'App\\Filament\\SchoolAdmin\\Widgets',
            )
            ->navigationGroups([
                'Dashboard',
                'Admissions',
                'Students',
                'Academic Setup',
                'Examinations',
                'Attendance',
                'Staff & HR',
                'Fees & Finance',
                'Communication',
                'Front Office',
                'Library',
                'Transport',
                'Hostel',
                'Inventory',
                'Cafeteria',
                'Health & Wellbeing',
                'Online Classes',
                'Certificates & ID Cards',
                'Fees',
                'Finance',
                'Reports',
                'Resources',
                'Website CMS',
                'Settings',
                'Compliance',
                'System',
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
            // Re-run these on Livewire AJAX updates so tenancy and auth guard are always initialized.
            ->middleware([DispatchServingFilamentEvent::class, InitializeTenancyBySubdomainOrDomain::class, EnsureTenantIsActive::class, SetPostgresUserRole::class], isPersistent: true)
            ->authMiddleware([
                RedirectToPortalLogin::class,
            ])
            ->renderHook(
                'panels::head.end',
                fn () => app(\Illuminate\Foundation\Vite::class)('resources/css/filament-custom.css'),
            )
            ->renderHook(
                'panels::head.end',
                fn () => $this->getUrduFontLink(),
            )
            ->renderHook(
                'panels::head.end',
                fn () => $this->getAppearanceStyles(),
            )
            ->renderHook(
                'panels::body.start',
                fn () => $this->getUrduBodyClass(),
            )
            ->renderHook(
                'panels::content.start',
                fn () => view('filament.school-admin.components.contact-kynex-banner'),
            )
            ->renderHook(
                'panels::body.end',
                fn () => view('livewire.ai-assistant-launcher'),
            )
            ->renderHook(
                PanelsRenderHook::GLOBAL_SEARCH_AFTER,
                fn () => view('livewire.notification-bell-wrapper'),
            );
    }

    public function register(): void
    {
        parent::register();

        // This panel does not use Filament's built-in Login page. The
        // `filament.school-admin.auth.login` name is bound in routes/web.php
        // to a redirect into the school portal /login (so internal callers
        // like Panel::getLoginUrl() resolve cleanly). The LogoutResponse is
        // also overridden to redirect to the portal login on logout.
        $this->app->singleton(LogoutResponseContract::class, SchoolAdminLogoutResponse::class);
    }

    public function boot(): void
    {
        // ── Urdu / Bilingual locale support ──────────────────────
        $tenant = function_exists('tenant') ? tenant() : null;

        if ($tenant && ($tenant->preferred_language ?? 'en') === 'ur') {
            App::setLocale('ur');
        }
    }

    /**
     * Inject the Noto Nastaliq Urdu font when locale is Urdu.
     */
    private function getUrduFontLink(): string
    {
        if (App::getLocale() !== 'ur') {
            return '';
        }

        return <<<'HTML'
<link href="https://fonts.googleapis.com/css2?family=Noto+Nastaliq+Urdu&display=swap" rel="stylesheet">
<style>
    body { direction: rtl; font-family: 'Noto Nastaliq Urdu', sans-serif; }
    .fi-sidebar-nav { text-align: right; }
    .fi-topbar { direction: rtl; }
    .fi-header { direction: rtl; }
    .fi-main { direction: rtl; }
    /* RTL table adjustments */
    .fi-ta-header-cell { text-align: right; }
    .fi-ta-cell { text-align: right; }
</style>
HTML;
    }

    /**
     * Add RTL direction attribute to body when Urdu is active.
     */
    private function getUrduBodyClass(): string
    {
        if (App::getLocale() !== 'ur') {
            return '';
        }

        return '<script>document.documentElement.setAttribute("dir","rtl");</script>';
    }

    /**
     * Inject per-tenant appearance/theme CSS (Phase 7 "Style"). Evaluated at
     * render time when tenancy is initialized; fail-empty so it can never
     * break the panel (e.g. before the appearance_settings table exists).
     */
    private function getAppearanceStyles(): string
    {
        try {
            if (! function_exists('tenant') || ! tenant()) {
                return '';
            }

            $a = \App\Models\Tenant\AppearanceSetting::current();
            $css = '';

            if (! empty($a->panel_background_color)) {
                $css .= '.fi-body{background-color:' . $a->panel_background_color . ';}';
            }
            if (! empty($a->font_family)) {
                $css .= "body{font-family:'" . $a->font_family . "',sans-serif;}";
            }
            if (! empty($a->primary_color)) {
                $css .= ':root{--kx-primary:' . $a->primary_color . ';}'
                    . '.fi-btn-color-primary{background-color:' . $a->primary_color . ' !important;}';
            }

            return $css === '' ? '' : '<style>' . $css . '</style>';
        } catch (\Throwable) {
            return '';
        }
    }
}
