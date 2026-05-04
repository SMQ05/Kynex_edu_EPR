<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Notifications\CustomDomainVerified;
use App\Services\CustomDomainService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Stancl\Tenancy\Database\Models\Domain;

/**
 * VerifyPendingCustomDomains — Phase 15C.5
 *
 * Periodically checks DNS TXT records for pending custom domains
 * and marks them as verified when the token is found.
 *
 * Domains older than 7 days with no verification are skipped.
 */
class VerifyPendingCustomDomains extends Command
{
    protected $signature = 'kynex:verify-pending-domains';

    protected $description = 'Check DNS and verify pending custom domain records';

    public function handle(CustomDomainService $service): int
    {
        $pendingDomains = Domain::where('is_verified', false)
            ->where('domain_type', 'custom')
            ->whereNotNull('verification_token')
            ->where('created_at', '>', now()->subDays(7))
            ->get();

        if ($pendingDomains->isEmpty()) {
            $this->info('No pending domains to verify.');

            return self::SUCCESS;
        }

        $this->info("Checking {$pendingDomains->count()} pending domain(s)...");

        $verified = 0;
        $failed   = 0;

        foreach ($pendingDomains as $domain) {
            $this->line("  Checking {$domain->domain}...");

            try {
                if ($service->verifyDomain($domain)) {
                    $verified++;
                    $this->info("    Verified: {$domain->domain}");

                    Log::info('Custom domain auto-verified', [
                        'domain'    => $domain->domain,
                        'tenant_id' => $domain->tenant_id,
                    ]);

                    // Notify the tenant's school admin
                    $this->notifySchoolAdmin($domain);
                } else {
                    $failed++;
                    $this->warn("    Not yet verified: {$domain->domain}");
                }
            } catch (\Throwable $e) {
                $failed++;
                $this->error("    Error checking {$domain->domain}: {$e->getMessage()}");

                Log::warning('Custom domain verification error', [
                    'domain' => $domain->domain,
                    'error'  => $e->getMessage(),
                ]);
            }
        }

        $this->newLine();
        $this->info("Results: {$verified} verified, {$failed} pending/failed.");

        // ── Phase 1.5: cert renewal-sweep ───────────────────────────
        // Re-dispatch provisioning for any verified custom domain whose
        // cert is failed/rate_limited/dns_mismatch/lock_timeout, OR whose
        // issued cert expires within 14 days. Belt-and-suspenders alongside
        // the in-container certbot-renew cron.
        $nearExpiry = Domain::where('is_verified', true)
            ->where('domain_type', 'custom')
            ->where(function ($q) {
                $q->whereIn('cert_status', ['failed', 'rate_limited', 'dns_mismatch', 'lock_timeout'])
                  ->orWhere(function ($q2) {
                      $q2->where('cert_status', 'issued')
                         ->where('cert_expires_at', '<', now()->addDays(14));
                  });
            })
            ->get();

        foreach ($nearExpiry as $domain) {
            $this->line("  Re-dispatching provisioning for {$domain->domain} (status={$domain->cert_status})");
            \App\Jobs\ProvisionCustomDomainCertificate::dispatch($domain->id);
        }

        if ($nearExpiry->isNotEmpty()) {
            $this->info("Re-dispatched {$nearExpiry->count()} domain(s) for cert renewal/repair.");
        }

        return self::SUCCESS;
    }

    /**
     * Send an in-app notification to the tenant's SCHOOL_ADMIN users.
     */
    private function notifySchoolAdmin(Domain $domain): void
    {
        $tenant = Tenant::find($domain->tenant_id);

        if (! $tenant) {
            return;
        }

        try {
            $tenant->run(function () use ($domain) {
                $admins = \App\Models\SchoolUser::role('SCHOOL_ADMIN')->get();

                foreach ($admins as $admin) {
                    $admin->notify(new CustomDomainVerified($domain->domain));
                }
            });
        } catch (\Throwable $e) {
            Log::warning('Failed to notify school admin of domain verification', [
                'tenant_id' => $domain->tenant_id,
                'domain'    => $domain->domain,
                'error'     => $e->getMessage(),
            ]);
        }
    }
}
