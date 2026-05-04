<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Tenant;
use Illuminate\Support\Str;
use Stancl\Tenancy\Database\Models\Domain;

/**
 * CustomDomainService — Phase 15C.3
 *
 * Handles the lifecycle of custom domain records:
 *  - Initiate verification (create domain + generate token)
 *  - Verify via DNS TXT lookup
 *  - Remove a custom domain
 *  - Return verification instructions for the admin UI
 */
class CustomDomainService
{
    /**
     * Start the verification flow for a custom domain.
     *
     * Creates an unverified Domain record with a verification token.
     *
     * @throws \InvalidArgumentException If domain format is invalid or already taken.
     */
    public function initiateVerification(Tenant $tenant, string $customDomain): Domain
    {
        $customDomain = $this->sanitizeDomain($customDomain);

        $this->validateDomainFormat($customDomain);
        $this->ensureDomainAvailable($customDomain, $tenant->id);

        $token = 'kynexedu-verify-' . Str::random(32);

        /** @var Domain $domain */
        $domain = $tenant->domains()->create([
            'domain'             => $customDomain,
            'is_primary'         => false,
            'is_verified'        => false,
            'verification_token' => $token,
            'domain_type'        => 'custom',
        ]);

        return $domain;
    }

    /**
     * Check DNS TXT records for the verification token.
     *
     * Looks for a TXT record at _kynexedu-verify.{domain} whose value
     * matches the stored verification_token.
     */
    public function verifyDomain(Domain $domain): bool
    {
        if ($domain->is_verified) {
            return true;
        }

        if (! $domain->verification_token) {
            return false;
        }

        $records = @dns_get_record('_kynexedu-verify.' . $domain->domain, DNS_TXT);

        if (! is_array($records)) {
            return false;
        }

        foreach ($records as $record) {
            $txtValue = trim($record['txt'] ?? '');

            if ($txtValue === $domain->verification_token) {
                $domain->update([
                    'is_verified'        => true,
                    'verified_at'        => now(),
                    'verification_token' => null,
                ]);

                \App\Jobs\ProvisionCustomDomainCertificate::dispatch($domain->id);

                return true;
            }
        }

        return false;
    }

    /**
     * Dispatch a cert provisioning attempt for a verified custom domain.
     *
     * @throws \LogicException When the domain is not yet verified or is not custom.
     */
    public function provisionCert(Domain $domain, bool $force = false): void
    {
        if (! $domain->is_verified) {
            throw new \LogicException('Cannot provision cert for an unverified domain.');
        }

        if ($domain->domain_type !== 'custom') {
            throw new \LogicException('Cert provisioning is for custom domains only.');
        }

        \App\Jobs\ProvisionCustomDomainCertificate::dispatch($domain->id, $force);
    }

    /**
     * Force a re-issuance of an existing cert (used for repair after a
     * deleted cert, a botched deploy, or to switch chain types).
     */
    public function reissueCert(Domain $domain): void
    {
        $this->provisionCert($domain, force: true);
    }

    /**
     * Remove a custom domain record.
     *
     * Cannot remove a primary (subdomain) domain.
     *
     * @throws \LogicException If attempting to remove a primary domain.
     */
    public function removeDomain(Domain $domain): void
    {
        if ($domain->is_primary) {
            throw new \LogicException('Cannot remove the primary subdomain.');
        }

        // Best-effort cleanup of the in-container nginx conf + cert files
        // via the listener. Failures here are logged but do not block the
        // row delete — the SaaS admin still wants the row gone.
        if ($domain->domain_type === 'custom' && $domain->is_verified) {
            try {
                $this->callListenerRemove($domain->domain);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('[cert] Remove cleanup failed', [
                    'domain' => $domain->domain,
                    'error'  => $e->getMessage(),
                ]);
            }
        }

        $domain->delete();
    }

    private function callListenerRemove(string $domain): void
    {
        if (config('cert.stub_mode')) {
            \Illuminate\Support\Facades\Log::warning('[cert] STUB MODE: would have called listener /remove', [
                'domain' => $domain,
                'url'    => rtrim((string) config('cert.listener_url'), '/') . '/remove',
            ]);
            return;
        }

        \Illuminate\Support\Facades\Http::withHeaders([
            'X-Cert-Listener-Secret' => (string) config('cert.listener_secret'),
            'Accept'                 => 'application/json',
        ])
            ->timeout((int) config('cert.remove_timeout_seconds', 120))
            ->connectTimeout((int) config('cert.http_connect_timeout_seconds', 5))
            ->post(rtrim((string) config('cert.listener_url'), '/') . '/remove', ['domain' => $domain]);
    }

    /**
     * Return DNS setup instructions for the SaaS admin UI.
     *
     * @return array{domain: string, txt_record_name: string, txt_record_value: string|null, cname_record: string, instructions: string[]}
     */
    public function getVerificationInstructions(Domain $domain): array
    {
        $cnameTarget = (string) config('cert.cname_target', 'kynexedu.com');

        return [
            'domain'           => $domain->domain,
            'txt_record_name'  => '_kynexedu-verify.' . $domain->domain,
            'txt_record_value' => $domain->verification_token,
            'cname_record'     => $cnameTarget,
            'instructions'     => [
                'Go to your DNS provider (GoDaddy, Cloudflare, Namecheap, etc.)',
                'Add a CNAME record pointing your domain to ' . $cnameTarget,
                'Add a TXT record:',
                '  Name: _kynexedu-verify.' . $domain->domain,
                '  Value: ' . $domain->verification_token,
                '  TTL: 300',
                'Click "Verify Now" below (DNS propagation may take up to 10 minutes)',
            ],
        ];
    }

    // ── Private helpers ──────────────────────────────────────────

    /**
     * Strip protocol, trailing slashes, and paths from the domain input.
     */
    private function sanitizeDomain(string $domain): string
    {
        $domain = strtolower(trim($domain));
        $domain = preg_replace('#^https?://#', '', $domain);
        $domain = rtrim($domain, '/');
        $domain = explode('/', $domain)[0]; // strip any path

        return $domain;
    }

    /**
     * Ensure the domain string looks like a valid hostname.
     *
     * @throws \InvalidArgumentException
     */
    private function validateDomainFormat(string $domain): void
    {
        if (! preg_match('/^([a-z0-9]([a-z0-9-]*[a-z0-9])?\.)+[a-z]{2,}$/', $domain)) {
            throw new \InvalidArgumentException("Invalid domain format: {$domain}");
        }

        // Block attempts to add central domains or their subdomains
        $centralDomains = config('tenancy.central_domains', []);
        foreach ($centralDomains as $central) {
            if ($domain === $central || str_ends_with($domain, '.' . $central)) {
                throw new \InvalidArgumentException("Cannot use a KynexEdu system domain as a custom domain.");
            }
        }
    }

    /**
     * Ensure the domain isn't already registered to another tenant.
     *
     * @throws \InvalidArgumentException
     */
    private function ensureDomainAvailable(string $domain, string $tenantId): void
    {
        $existing = Domain::where('domain', $domain)->first();

        if ($existing && $existing->tenant_id !== $tenantId) {
            throw new \InvalidArgumentException("This domain is already registered to another school.");
        }

        if ($existing && $existing->tenant_id === $tenantId) {
            throw new \InvalidArgumentException("This domain is already added to this school.");
        }
    }
}
