<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\StudentDeactivated;
use App\Jobs\SyncTenantActiveStudentCount;

/**
 * SyncStudentCountOnDeactivate — Queues a job to decrement the
 * central active_student_count when a student is deactivated.
 */
class SyncStudentCountOnDeactivate
{
    public function handle(StudentDeactivated $event): void
    {
        $tenantId = tenancy()->tenant?->id;

        if (! $tenantId) {
            return;
        }

        SyncTenantActiveStudentCount::dispatch($tenantId);
    }
}
