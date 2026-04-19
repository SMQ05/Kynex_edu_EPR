<?php

namespace App\Providers;

use App\Models\SchoolUser;
use App\Models\Tenant\Notice;
use App\Observers\NoticeObserver;
use App\Policies\SchoolUserPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Event;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        //
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        // Phase 9.6 — Auto-notify when a notice is published
        Notice::observe(NoticeObserver::class);

        // Role hierarchy — SchoolUser can only be edited/deleted by higher-ranked users
        Gate::policy(SchoolUser::class, SchoolUserPolicy::class);
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
