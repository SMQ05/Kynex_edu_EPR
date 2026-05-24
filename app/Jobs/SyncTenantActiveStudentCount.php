<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Tenant;
use App\Models\Tenant\Student;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * SyncTenantActiveStudentCount — Recounts enrolled students in the
 * tenant DB and updates the central tenants.active_student_count.
 *
 * This job switches to the tenant database, counts enrolled students,
 * switches back to central, and updates the tenant record.
 */
class SyncTenantActiveStudentCount implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        public readonly string $tenantId,
    ) {}

    public function handle(): void
    {
        $tenant = Tenant::on('central')->find($this->tenantId);

        if (! $tenant) {
            Log::warning('SyncTenantActiveStudentCount: tenant not found', [
                'tenant_id' => $this->tenantId,
            ]);
            return;
        }

        // Initialize tenancy to switch to tenant DB — preserve any caller's
        // existing tenancy state when this job runs sync inside a request.
        $weInitialized = false;
        if (! tenancy()->initialized || tenant()?->id !== $tenant->id) {
            tenancy()->initialize($tenant);
            $weInitialized = true;
        }

        try {
            $count = Student::where('status', 'enrolled')->count();
        } finally {
            if ($weInitialized) {
                tenancy()->end();
            }
        }

        // Update central tenant record
        $tenant->updateQuietly(['active_student_count' => $count]);

        Log::info('SyncTenantActiveStudentCount: updated', [
            'tenant_id' => $this->tenantId,
            'count'     => $count,
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('SyncTenantActiveStudentCount: failed', [
            'tenant_id' => $this->tenantId,
            'error'    => $exception->getMessage(),
        ]);
    }
}
