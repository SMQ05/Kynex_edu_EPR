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
        Events\TenantCreated::class => [
            Jobs\CreateDatabase::class,
        ],
        Events\TenantDeleted::class => [
            Jobs\DeleteDatabase::class,
        ],

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
    }

    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
