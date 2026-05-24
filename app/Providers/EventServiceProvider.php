<?php

declare(strict_types=1);

namespace App\Providers;

use App\Events\StudentDeactivated;
use App\Events\StudentEnrolled;
use App\Events\WhatsAppMessageReceived;
use App\Listeners\SendWhatsAppInboxNotification;
use App\Listeners\SyncStudentCountOnDeactivate;
use App\Listeners\SyncStudentCountOnEnroll;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;
use Stancl\JobPipeline\JobPipeline;
use Stancl\Tenancy\Events;
use Stancl\Tenancy\Jobs;
use Stancl\Tenancy\Listeners;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        // ── Stancl Tenancy lifecycle ──────────────────────────
        Events\TenancyInitialized::class => [
            Listeners\BootstrapTenancy::class,
        ],
        Events\TenancyEnded::class => [
            Listeners\RevertToCentralContext::class,
        ],

        // TenantCreated and TenantDeleted use JobPipeline and are
        // registered in boot() because $listen only accepts
        // compile-time constants, not closures.

        // ── Application events ───────────────────────────────
        StudentEnrolled::class => [
            SyncStudentCountOnEnroll::class,
        ],
        StudentDeactivated::class => [
            SyncStudentCountOnDeactivate::class,
        ],
        WhatsAppMessageReceived::class => [
            SendWhatsAppInboxNotification::class,
        ],
    ];

    public function boot(): void
    {
        parent::boot();

        // JobPipeline registrations can't live in $listen (they use
        // closures, which aren't compile-time constants).  Register
        // them here instead so the tenancy database lifecycle works.
        Event::listen(
            Events\TenantCreated::class,
            JobPipeline::make([
                Jobs\CreateDatabase::class,
            ])->send(function (Events\TenantCreated $event) {
                return $event->tenant;
            })->shouldBeQueued(false)->toListener(),
        );

        Event::listen(
            Events\TenantDeleted::class,
            JobPipeline::make([
                Jobs\DeleteDatabase::class,
            ])->send(function (Events\TenantDeleted $event) {
                return $event->tenant;
            })->shouldBeQueued(false)->toListener(),
        );

        // User activity log (Phase 7) — record school-user logins/logouts.
        // UserActivity is fail-silent, so this never breaks auth.
        Event::listen(\Illuminate\Auth\Events\Login::class, function (\Illuminate\Auth\Events\Login $event): void {
            if (($event->guard ?? null) === 'school_users' && $event->user) {
                \App\Support\UserActivity::for($event->user)->record('login', null, null, 'Logged in');
            }
        });

        Event::listen(\Illuminate\Auth\Events\Logout::class, function (\Illuminate\Auth\Events\Logout $event): void {
            if (($event->guard ?? null) === 'school_users' && $event->user) {
                \App\Support\UserActivity::for($event->user)->record('logout', null, null, 'Logged out');
            }
        });
    }

    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
