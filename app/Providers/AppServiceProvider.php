<?php

namespace App\Providers;

use App\Models\SchoolUser;
use App\Models\Tenant\Notice;
use App\Observers\NoticeObserver;
use App\Policies\SchoolUserPolicy;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TimePicker;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Bind TenantWithDatabase interface so CreateDatabase job can be resolved
        $this->app->bind(
            \Stancl\Tenancy\Contracts\TenantWithDatabase::class,
            \App\Models\Tenant::class,
        );

        // Force a robust session config. The original container image
        // bakes SESSION_DRIVER=file / SESSION_LIFETIME=120 into the OS
        // env, which causes Filament/Livewire to fail with "Error while
        // loading page" once the CSRF token expires (~2h of idle) and
        // wipes every session on container restart. Database-backed
        // sessions survive restarts; an 8-hour lifetime matches a
        // workday.
        //
        // We only override when the OS env is still pointing at the
        // legacy defaults so explicitly-set values still win.
        $this->app->booted(function () {
            $cfg = config();
            if ($cfg->get('session.driver') === 'file') {
                $cfg->set('session.driver', 'database');
            }
            if ((int) $cfg->get('session.lifetime') < 480) {
                $cfg->set('session.lifetime', 480);
            }
            if (! $cfg->get('session.secure')) {
                $cfg->set('session.secure', true);
            }
        });
    }

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        // Phase 9.6 — Auto-notify when a notice is published
        Notice::observe(NoticeObserver::class);

        // Role hierarchy — SchoolUser can only be edited/deleted by higher-ranked users
        Gate::policy(SchoolUser::class, SchoolUserPolicy::class);

        // Filament date/time pickers default to a Flatpickr UI without a confirm
        // button, so users had to click outside the popup to commit a value.
        // Force native HTML5 inputs everywhere — they auto-commit on change and
        // expose the OS-level Done/Set affordance on touch devices.
        DatePicker::configureUsing(fn (DatePicker $component) => $component->native()->closeOnDateSelection());
        DateTimePicker::configureUsing(fn (DateTimePicker $component) => $component->native()->closeOnDateSelection());
        TimePicker::configureUsing(fn (TimePicker $component) => $component->native());

        // Register Livewire components that aren't auto-discovered by
        // Filament's panel discoverers.
        Livewire::component('ai-assistant-panel', \App\Livewire\AiAssistantPanel::class);

        // Register a fault-tolerant Eloquent user provider that swallows
        // "school_users does not exist" errors. This prevents stale
        // browser tabs from constantly 500-ing when their session points
        // at a tenant DB that has since been dropped (the row stays in
        // session, but the table is gone). Used by all guards that
        // resolve users from the school_users table.
        Auth::provider('safe-eloquent', function ($app, array $config) {
            return new \App\Auth\SafeEloquentUserProvider($app['hash'], $config['model']);
        });

        // Share $siteBase + $applyBase with every CMS view so internal
        // links work both on /site/{tenant}/... (central domain) and on
        // tenant subdomains where the prefix is empty.
        \Illuminate\Support\Facades\View::composer('cms.*', function ($view) {
            $tenantId = function_exists('tenant') ? optional(tenant())->id : null;
            $path = request()->getPathInfo();
            $onCentralSitePath = $tenantId && \Illuminate\Support\Str::startsWith($path, '/site/');
            $siteBase = $onCentralSitePath ? '/site/' . $tenantId : '';
            $view->with('siteBase', $siteBase);
            $view->with('applyBase', $siteBase ? $siteBase . '/apply' : '/apply');
        });
    }
}
