<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\StudentEnrolled;
use App\Jobs\SyncTenantActiveStudentCount;

/**
 * SyncStudentCountOnEnroll — Queues a job to increment the
 * central active_student_count when a student is enrolled.
 */
class SyncStudentCountOnEnroll
{
    public function handle(StudentEnrolled $event): void
    {
        $tenantId = tenancy()->tenant?->id;

        if (! $tenantId) {
            return;
        }

        SyncTenantActiveStudentCount::dispatch($tenantId);
    }
}
