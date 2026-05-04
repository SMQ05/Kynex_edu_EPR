<?php

declare(strict_types=1);

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Stancl\Tenancy\Database\Models\Domain;
use Throwable;

/**
 * ProvisionCustomDomainCertificate — Phase 1.5
 *
 * Orchestrates one provisioning attempt by calling the in-container
 * cert-listener over the kynex_kynex docker network. Updates the
 * Domain row with cert_status / cert_issued_at / cert_expires_at /
 * cert_last_error / cert_attempt_count.
 *
 * Endpoint selection:
 *   $force=false  →  POST /provision   (idempotent, --keep-until-expiring)
 *   $force=true   →  POST /reissue     (forced, --force-renewal)
 *                    + header X-Cert-Reissue-Confirm: true
 *
 * Stub mode (CERT_STUB_MODE=true) short-circuits the HTTP call and
 * just logs the intended payload — Sub-phase 3 ships this way.
 */
class ProvisionCustomDomainCertificate implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries;
    public int $backoff;
    public int $timeout;

    public function __construct(public int $domainId, public bool $force = false)
    {
        $this->tries   = (int) config('cert.job_tries', 3);
        $this->backoff = (int) config('cert.job_backoff_seconds', 60);
        $this->timeout = (int) config('cert.job_timeout_seconds', 180);
    }

    public function handle(): void
    {
        $domain = Domain::find($this->domainId);

        if (! $domain) {
            Log::warning('[cert] Provision skipped: domain row missing', [
                'domain_id' => $this->domainId,
            ]);
            return;
        }

        if (! $domain->is_verified) {
            Log::info('[cert] Provision skipped: domain not verified', [
                'domain' => $domain->domain,
            ]);
            return;
        }

        $sweepDays = (int) config('cert.renewal_sweep_days', 14);
        if (! $this->force
            && $domain->cert_status === 'issued'
            && $domain->cert_expires_at
            && Carbon::parse($domain->cert_expires_at)->isAfter(now()->addDays($sweepDays))) {
            Log::info('[cert] Provision skipped: already issued, fresh', [
                'domain'     => $domain->domain,
                'expires_at' => $domain->cert_expires_at,
            ]);
            return;
        }

        $domain->forceFill([
            'cert_status'        => 'issuing',
            'cert_attempt_count' => $domain->cert_attempt_count + 1,
        ])->save();

        $url     = rtrim((string) config('cert.listener_url'), '/');
        $secret  = (string) config('cert.listener_secret');
        $timeout = (int) config('cert.listener_timeout_seconds', 120);

        $endpoint = $this->force ? '/reissue' : '/provision';
        $headers  = [
            'X-Cert-Listener-Secret' => $secret,
            'Accept'                 => 'application/json',
        ];
        if ($this->force) {
            $headers['X-Cert-Reissue-Confirm'] = 'true';
        }

        if (config('cert.stub_mode')) {
            // warning-level so it surfaces in prod (LOG_LEVEL=warning).
            // After Sub-phase 5 flips CERT_STUB_MODE=false this will
            // never fire — if it does, that's a real signal.
            Log::warning('[cert] STUB MODE: would have called listener', [
                'domain'   => $domain->domain,
                'url'      => $url . $endpoint,
                'force'    => $this->force,
                'headers'  => array_keys($headers),
                'attempt'  => $domain->cert_attempt_count,
            ]);
            $domain->forceFill([
                'cert_status'     => 'pending',
                'cert_last_error' => '[stub mode] listener call skipped',
            ])->save();
            return;
        }

        try {
            $response = Http::withHeaders($headers)
                ->timeout($timeout)
                ->connectTimeout((int) config('cert.http_connect_timeout_seconds', 5))
                ->post($url . $endpoint, ['domain' => $domain->domain]);
        } catch (Throwable $e) {
            $this->markFailure($domain, 'failed', 'listener unreachable: ' . $e->getMessage());
            throw $e;
        }

        $payload = $response->json() ?? [];
        $status  = $payload['status'] ?? 'failed';

        switch ($status) {
            case 'issued':
                $domain->forceFill([
                    'cert_status'     => 'issued',
                    'cert_issued_at'  => now(),
                    'cert_expires_at' => isset($payload['cert_expires_at'])
                        ? Carbon::parse($payload['cert_expires_at'])
                        : null,
                    'cert_last_error' => null,
                ])->save();
                Log::info('[cert] Provisioned', ['domain' => $domain->domain]);
                break;

            case 'rate_limited':
                $this->markFailure($domain, 'rate_limited', $payload['error'] ?? 'rate-limited by Let\'s Encrypt');
                $this->fail();
                break;

            case 'dns_mismatch':
                $this->markFailure($domain, 'dns_mismatch', $payload['error'] ?? 'DNS does not resolve to this server');
                $this->fail();
                break;

            case 'lock_timeout':
                $this->markFailure($domain, 'lock_timeout', $payload['error'] ?? 'nginx-mutate lock contention');
                $this->fail();
                break;

            case 'failed':
            default:
                $this->markFailure($domain, 'failed', $payload['error'] ?? 'unknown failure');
                throw new \RuntimeException('Cert provisioning failed: ' . ($payload['error'] ?? 'unknown'));
        }
    }

    public function failed(Throwable $e): void
    {
        $domain = Domain::find($this->domainId);
        if ($domain) {
            $domain->forceFill([
                'cert_status'     => $domain->cert_status === 'issuing' ? 'failed' : $domain->cert_status,
                'cert_last_error' => substr($e->getMessage(), 0, 1000),
            ])->save();
        }
    }

    private function markFailure(Domain $domain, string $status, string $error): void
    {
        $domain->forceFill([
            'cert_status'     => $status,
            'cert_last_error' => substr($error, 0, 1000),
        ])->save();
    }
}
