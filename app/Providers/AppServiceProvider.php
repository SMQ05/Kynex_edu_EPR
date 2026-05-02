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
    }
}
