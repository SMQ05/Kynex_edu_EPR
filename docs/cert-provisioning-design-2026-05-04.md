# Cert Provisioning — Sub-phase 2 Design Document

**Date:** 2026-05-04
**Revision:** rev2 (2026-05-04)
**Workstream:** Phase 1.5 — Automated TLS for custom school domains
**Predecessor:** [`cert-provisioning-investigation-2026-05-04.md`](cert-provisioning-investigation-2026-05-04.md)
**Status:** Awaiting user spot-check (rev2). No code, infra, or compose changes have been made.

This document specifies the EXACT changes to be made in Sub-phases 3, 4,
5 and 6. Every file path, function signature, env var, Dockerfile line,
nginx directive, and shell command is committed here. Sub-phase 3 starts
only after this design is approved.

### Rev2 changelog

User spot-check on rev1 found three gaps. Rev2 fixes each plus three additions:

| Fix | Section(s) updated | Summary |
|-----|--------------------|---------|
| 1 — atomic write race around `nginx -s reload` | §3.4 cron file, §3.6 provision-cert.sh, §3.7 remove-cert.sh, §7 case 4, §8.1 happy path | Added a global short-lived lock `/var/lock/nginx-mutate.lock` taken (`flock -w 30`) by every process that mutates `sites-enabled/*` OR runs `nginx -s reload`. Closes the window during which another process could load a transiently-broken file. Cron's `--post-hook` reload is wrapped too. Fail-loud on lock-acquisition timeout. |
| 2 — per-domain lock filename | §3.6, §7 case 1 | Renamed `/var/lock/cert-<domain>.lock` → `/var/lock/cert-provision-<domain>.lock` for clearer grep target. Per-domain semantics unchanged. |
| 4 — sed migration safety | §5.2 | Added backup-before-edit (`cp .bak.$TS`), grep-verify-after-edit, and a documented rollback command path. Bails and restores from backup if sed silently produced no change. |
| 5 — provision vs reissue distinction | §2.1.B Job, §3.6 provision-cert.sh, §3.8 cert-listener.php, §1 architecture, §9 test plan | Listener now exposes two paths: `/provision` (idempotent, certbot `--keep-until-expiring`) and `/reissue` (force, certbot `--force-renewal`). `/reissue` requires an additional `X-Cert-Reissue-Confirm: true` header — defence-in-depth so a buggy client can't accidentally burn an LE rate-limit slot. The Job branches on the `force` flag to pick endpoint and header. |

---

## 0. Decisions locked in (from Sub-phase 1 + user answers)

| # | Decision | Source |
|---|----------|--------|
| 1 | Contact email for ACME: `ops@kynexsolutions.com` | user answer #1 |
| 2 | Compose mount `/etc/letsencrypt:ro` → drop `:ro` (read-write) | user answer #2 + pre-flight: kynex-app is sole consumer |
| 3 | Per-domain HTTP server block: **skip**; rely on existing block (b) catch-all for ACME | user answer #3 |
| 4 | Migrate the 3 existing renewal configs to in-container in Sub-phase 6: `sms`, `aqm` (webroot path edit) and **decision for `ai`: leave on host nginx-plugin renewal, document as follow-up** (rationale: `ai.kynexsolutions.com`'s renewal uses certbot's nginx plugin, which would require running `certbot --nginx` inside kynex-app — that touches block (d) of `kynex.conf`, which is sacred per constraint (b)) | user answer #4 |
| 5 | Listener bind: `0.0.0.0:9090` inside `kynex-app`. Port NOT in compose `ports:` → not host-exposed → only reachable via `kynex_kynex` docker network. | user answer #5 |
| 6 | `SHARED_CERT_LISTENER_SECRET` rotation: manual, quarterly + on team-membership change | user answer #6 |
| 7 | Listener runtime: PHP CLI built-in server, single file. PHP 8.4 already in image. | Sub-phase 1 §6 |
| 8 | Webroot canonicalised on **`/var/www/certbot/www`** in container = bind-mount source on host. New issuances + Sub-phase 6 migrations all use this path. | Sub-phase 1 §2b |
| 9 | LE staging API in Sub-phases 3-5; switch to production API in Sub-phase 6 | Sub-phase 1 §4 |
| 10 | Global `/var/lock/nginx-mutate.lock` (`flock -w 30`) wraps every mutation of `sites-enabled/*` AND every `nginx -s reload` (provision-cert.sh, remove-cert.sh, certbot-renew cron `--post-hook`). Fail-loud on 30s timeout. | rev2 spot-check fix 1 |
| 11 | Per-domain lock renamed `/var/lock/cert-provision-<domain>.lock` | rev2 spot-check fix 2 |
| 12 | Listener exposes `/provision` (idempotent) AND `/reissue` (force; requires header `X-Cert-Reissue-Confirm: true`). Script accepts optional `--force` arg → swaps `--keep-until-expiring` for `--force-renewal`. | rev2 spot-check fix 5 |
| 13 | Sub-phase 6 sed migration backs up renewal configs first (`cp .bak.$TS`), greps to verify the change took effect, restores from backup on silent failure. | rev2 spot-check fix 4 |

### Pre-flight result for decision #2

```
$ docker inspect $(docker ps -aq) | grep -B2 letsencrypt
=== /kynex-app (running) ===
/etc/letsencrypt -> /etc/letsencrypt
```

`kynex-app` is the only container that mounts `/etc/letsencrypt`.
The compose flip is safe.

---

## 1. End-to-end architecture (locked)

```
                                   ┌────────────────────────────────────┐
                                   │            host (port 80/443)      │
                                   └──────────────┬─────────────────────┘
                                                  │
                                                  ▼
┌─────────────────────────────┐    docker     ┌──────────────────────────────────────┐
│       kynexedu-app          │   kynex_kynex │              kynex-app               │
│   (Laravel SMS app)         │  ─────────►   │  (php-fpm + nginx + supervisord +    │
│                             │   :9090       │   cron + certbot + cert-listener)    │
│  ┌───────────────────────┐  │               │                                      │
│  │ ProvisionCustom       │  │               │  ┌─────────────────────────────┐    │
│  │ DomainCertificate Job │  │  HTTP/JSON    │  │ cert-listener.php           │    │
│  │   POST /provision  OR │──┼──────────────►│  │   php -S 0.0.0.0:9090       │    │
│  │        /reissue       │  │  shared       │  │   X-Cert-Listener-Secret    │    │
│  │   X-Cert-Listener-    │  │  secret       │  │   X-Cert-Reissue-Confirm    │    │
│  │     Secret: ***       │  │               │  │     (only on /reissue)      │    │
│  │   X-Cert-Reissue-     │  │               │  │   ┌──► provision-cert.sh    │    │
│  │     Confirm: true     │  │               │  │   │     [--force]           │    │
│  │     (force only)      │  │               │  │   └──► remove-cert.sh       │    │
│  └───────────────────────┘  │               │  └─────────────────────────────┘    │
│  ┌───────────────────────┐  │               │                                      │
│  │ CustomDomainService   │  │               │  ┌─────────────────────────────┐    │
│  │   provisionCert()     │  │               │  │ certbot 2.x                 │    │
│  │   reissueCert()       │  │               │  │   --webroot                 │    │
│  │   removeDomain() *    │  │               │  │   /var/www/certbot/www      │    │
│  └───────────────────────┘  │               │  └────────────┬────────────────┘    │
│                             │               │               ▼                      │
│  Filament SaasAdmin         │               │  /etc/letsencrypt/live/<domain>/    │
│   DomainsRelationManager    │               │                                      │
│     - "Provision Cert Now"  │               │  ┌─────────────────────────────┐    │
│     - "Reissue Certificate" │               │  │ nginx (1.26.3)              │    │
│                             │               │  │  /etc/nginx/sites-enabled/  │    │
│  VerifyPendingCustomDomains │               │  │    custom-<domain>.conf     │    │
│   (scheduled, also          │               │  │  reload after each issue    │    │
│    re-dispatches near       │               │  └─────────────────────────────┘    │
│    expiry)                  │               │                                      │
└─────────────────────────────┘               │  cron 2× daily:                      │
                                              │   /etc/cron.d/certbot-renew          │
                                              │   → certbot renew --quiet            │
                                              │     --post-hook "flock -w 30         │
                                              │       nginx-mutate.lock              │
                                              │       nginx -s reload"               │
                                              └──────────────────────────────────────┘
```

`*` `removeDomain()` is the SaasAdmin-only path; school-side UI never
touches it (per `feedback_no_school_side_domain_management`).

---

## 2. Sub-phase 3 — Laravel side build plan

Goal: ship Laravel changes that wire end-to-end up to the listener
boundary, but the actual HTTP call is gated by an env flag so nothing
real fires until Sub-phase 5. Single commit, push, then stop.

### 2.1 New files

#### A. Migration

**Path:** `database/migrations/2026_05_04_000001_add_cert_columns_to_domains_table.php`

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Phase 1.5 — Add cert provisioning state columns to the domains table.
     */
    public function up(): void
    {
        Schema::table('domains', function (Blueprint $table) {
            $table->string('cert_status', 20)->default('pending')->after('domain_type');
            $table->timestamp('cert_issued_at')->nullable()->after('cert_status');
            $table->timestamp('cert_expires_at')->nullable()->after('cert_issued_at');
            $table->text('cert_last_error')->nullable()->after('cert_expires_at');
            $table->unsignedSmallInteger('cert_attempt_count')->default(0)->after('cert_last_error');

            // Index supports the renewal sweep query
            // (cert_status IN (...) OR cert_expires_at < ?).
            $table->index(['cert_status', 'cert_expires_at'], 'domains_cert_sweep_idx');
        });
    }

    public function down(): void
    {
        Schema::table('domains', function (Blueprint $table) {
            $table->dropIndex('domains_cert_sweep_idx');
            $table->dropColumn([
                'cert_status',
                'cert_issued_at',
                'cert_expires_at',
                'cert_last_error',
                'cert_attempt_count',
            ]);
        });
    }
};
```

Allowed `cert_status` values (enforced in Job/Service, not DB):
`pending | issuing | issued | failed | rate_limited | dns_mismatch`.

Back-fill is intentionally omitted — every existing row stays at
`pending` and will be picked up by the Sub-phase 6 backfill command
described in §7.

#### B. Job

**Path:** `app/Jobs/ProvisionCustomDomainCertificate.php`

```php
<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\CustomDomainService;
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
 * domain row with the resulting cert_status / cert_issued_at /
 * cert_expires_at / cert_last_error / cert_attempt_count.
 */
class ProvisionCustomDomainCertificate implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;            // 60s between retries
    public int $timeout = 180;           // > listener timeout

    public function __construct(public int $domainId, public bool $force = false)
    {
    }

    public function handle(CustomDomainService $service): void
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

        // Skip if already issued and not forced and not near expiry.
        if (! $this->force
            && $domain->cert_status === 'issued'
            && $domain->cert_expires_at
            && $domain->cert_expires_at->isAfter(now()->addDays(14))) {
            Log::info('[cert] Provision skipped: already issued, fresh', [
                'domain'     => $domain->domain,
                'expires_at' => $domain->cert_expires_at->toIso8601String(),
            ]);
            return;
        }

        $domain->forceFill([
            'cert_status'        => 'issuing',
            'cert_attempt_count' => $domain->cert_attempt_count + 1,
        ])->save();

        $url     = config('cert.listener_url');
        $secret  = config('cert.listener_secret');
        $timeout = (int) config('cert.listener_timeout', 120);

        // Pick endpoint based on force flag. /reissue requires an additional
        // confirmation header — defence in depth so a buggy client can't
        // accidentally burn an LE rate-limit slot. The Job is the ONLY
        // caller that should ever set force=true.
        $endpoint = $this->force ? '/reissue' : '/provision';
        $headers  = [
            'X-Cert-Listener-Secret' => $secret,
            'Accept'                 => 'application/json',
        ];
        if ($this->force) {
            $headers['X-Cert-Reissue-Confirm'] = 'true';
        }

        // Stub mode for Sub-phase 3 — wiring without infra.
        if (config('cert.stub_mode')) {
            Log::info('[cert] STUB: would have called listener', [
                'domain'   => $domain->domain,
                'url'      => $url . $endpoint,
                'force'    => $this->force,
            ]);
            $domain->forceFill([
                'cert_status'     => 'pending',  // back to pending; nothing actually happened
                'cert_last_error' => '[stub mode] listener call skipped',
            ])->save();
            return;
        }

        try {
            $response = Http::withHeaders($headers)
                ->timeout($timeout)
                ->connectTimeout(5)
                ->post($url . $endpoint, ['domain' => $domain->domain]);
        } catch (Throwable $e) {
            $this->markFailure($domain, 'failed', 'listener unreachable: ' . $e->getMessage());
            throw $e;  // triggers retry
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
                // Don't retry; let cron pick it up later.
                $this->fail();
                break;

            case 'dns_mismatch':
                $this->markFailure($domain, 'dns_mismatch', $payload['error'] ?? 'DNS does not resolve to this server');
                $this->fail();  // retrying won't help
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
```

#### C. Config file

**Path:** `config/cert.php`  *(new)*

```php
<?php

declare(strict_types=1);

return [
    'listener_url'     => env('CERT_LISTENER_URL', 'http://kynex-app:9090'),
    'listener_secret'  => env('SHARED_CERT_LISTENER_SECRET'),
    'listener_timeout' => (int) env('CERT_LISTENER_TIMEOUT', 120),

    // When true, the Job logs the intended call instead of making it.
    // Sub-phase 3 ships with this true; flipped to false in Sub-phase 5.
    'stub_mode' => filter_var(env('CERT_STUB_MODE', true), FILTER_VALIDATE_BOOL),
];
```

### 2.2 Edits to existing files

#### D. `app/Services/CustomDomainService.php`

Three changes:

**D.1** — On successful `verifyDomain`, dispatch the provisioning job.

```php
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

            // ── Phase 1.5: trigger cert provisioning ──
            \App\Jobs\ProvisionCustomDomainCertificate::dispatch($domain->id);

            return true;
        }
    }

    return false;
}
```

**D.2** — New `provisionCert()` method.

```php
/**
 * Dispatch a cert provisioning attempt for a verified custom domain.
 *
 * @throws \LogicException When the domain is not yet verified.
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

public function reissueCert(Domain $domain): void
{
    $this->provisionCert($domain, force: true);
}
```

**D.3** — `removeDomain` extended to clean up cert + nginx conf.

```php
public function removeDomain(Domain $domain): void
{
    if ($domain->is_primary) {
        throw new \LogicException('Cannot remove the primary subdomain.');
    }

    // Best-effort cleanup of nginx conf + cert files via the listener.
    // Failures here are logged but do not block the row delete — the
    // SaaS admin still wants the row gone.
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
        \Illuminate\Support\Facades\Log::info('[cert] STUB: would have called listener /remove', [
            'domain' => $domain,
        ]);
        return;
    }

    \Illuminate\Support\Facades\Http::withHeaders([
        'X-Cert-Listener-Secret' => config('cert.listener_secret'),
        'Accept'                 => 'application/json',
    ])
        ->timeout((int) config('cert.listener_timeout', 60))
        ->connectTimeout(5)
        ->post(rtrim(config('cert.listener_url'), '/') . '/remove', ['domain' => $domain]);
}
```

#### E. `app/Console/Commands/VerifyPendingCustomDomains.php`

Add a renewal-sweep block at the end of `handle()`. (The verify path
already triggers provisioning via `CustomDomainService::verifyDomain`
above, so no edits to the existing loop.)

```php
// ── Phase 1.5: renewal sweep ────────────────────────────────
$nearExpiry = Domain::where('is_verified', true)
    ->where('domain_type', 'custom')
    ->where(function ($q) {
        $q->whereIn('cert_status', ['failed', 'rate_limited', 'dns_mismatch'])
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
```

#### F. `app/Filament/SaasAdmin/Resources/TenantResource/RelationManagers/DomainsRelationManager.php`

**F.1** — Two new columns inside `->columns([...])`:

```php
Tables\Columns\TextColumn::make('cert_status')
    ->label('Cert')
    ->badge()
    ->color(fn (?string $state): string => match ($state) {
        'issued'        => 'success',
        'issuing'       => 'warning',
        'rate_limited'  => 'warning',
        'dns_mismatch'  => 'danger',
        'failed'        => 'danger',
        default         => 'gray',
    })
    ->placeholder('—'),

Tables\Columns\TextColumn::make('cert_expires_at')
    ->label('Cert Expires')
    ->dateTime('M d, Y')
    ->placeholder('—')
    ->toggleable(),
```

**F.2** — Two new row actions appended to `->actions([...])`:

```php
Action::make('provisionCertNow')
    ->label('Provision Cert Now')
    ->icon('heroicon-o-lock-closed')
    ->color('info')
    ->visible(fn (Domain $record): bool =>
        $record->is_verified
        && $record->domain_type === 'custom'
        && ! in_array($record->cert_status, ['issuing', 'issued'], true)
    )
    ->requiresConfirmation()
    ->modalHeading('Provision SSL certificate')
    ->modalDescription(fn (Domain $record): string =>
        "Queue a Let's Encrypt cert issuance for {$record->domain}. " .
        "DNS must already point to this server."
    )
    ->action(function (Domain $record) {
        try {
            app(CustomDomainService::class)->provisionCert($record);
            Notification::make()
                ->title('Provisioning queued')
                ->body("Cert provisioning for {$record->domain} has been queued.")
                ->success()
                ->send();
        } catch (\LogicException $e) {
            Notification::make()
                ->title('Cannot provision')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }),

Action::make('reissueCertificate')
    ->label('Reissue Cert')
    ->icon('heroicon-o-arrow-path')
    ->color('warning')
    ->visible(fn (Domain $record): bool =>
        $record->is_verified
        && $record->domain_type === 'custom'
    )
    ->requiresConfirmation()
    ->modalHeading('Reissue SSL certificate')
    ->modalDescription(fn (Domain $record): string =>
        "Force a new Let's Encrypt cert for {$record->domain}, even if " .
        "the existing one is still valid. Use this for repair after a " .
        "failed deploy or a manual cert deletion."
    )
    ->action(function (Domain $record) {
        app(CustomDomainService::class)->reissueCert($record);
        Notification::make()
            ->title('Reissuance queued')
            ->body("Cert reissuance for {$record->domain} has been queued.")
            ->success()
            ->send();
    }),
```

The existing `removeDomain` action needs no Filament-level edit — the
service it calls is the one we extended in **D.3**.

### 2.3 Env additions

Add to **both** `.env.production` and `.env.docker.example`:

```
# ── Phase 1.5: cert provisioning ────────────────────────────
# Listener URL (docker network only — never host-exposed)
CERT_LISTENER_URL=http://kynex-app:9090
# HTTP request timeout for the listener call (seconds)
CERT_LISTENER_TIMEOUT=120
# Shared secret with the in-container listener.
# Generate with: openssl rand -base64 32
# DO NOT commit a real value. Leave empty in .env.docker.example.
SHARED_CERT_LISTENER_SECRET=
# When true, Job logs intended call instead of making it. Set false in
# Sub-phase 5 once the listener is live.
CERT_STUB_MODE=true
```

Note: `.env.docker.example` keeps `SHARED_CERT_LISTENER_SECRET=` (empty)
per Amendment A. `.env.production` (gitignored) gets the real value at
Sub-phase 5 cutover.

### 2.4 Sub-phase 3 test plan (Laravel-only)

After committing all of the above:

1. `docker exec kynexedu-app php artisan migrate --pretend` — confirm
   only the new migration runs, no destructive changes.
2. `docker exec kynexedu-app php artisan migrate --force` — apply.
3. `docker exec kynexedu-app php artisan tinker` →
   ```php
   $d = \Stancl\Tenancy\Database\Models\Domain::where('domain', 'aqmdigital.com')->first();
   $d->is_verified;          // true expected
   $d->cert_status;          // 'pending' expected (back-fill default)
   app(\App\Services\CustomDomainService::class)->provisionCert($d);
   ```
4. Tail `storage/logs/laravel.log` — expect `[cert] STUB: would have
   called listener` line, with the URL `http://kynex-app:9090/provision`.
5. Re-fetch row: `cert_status` should still be `pending` (stub mode
   intentionally doesn't progress the state machine).
6. Test Filament action via the SaaS admin UI: visit `aqmdigital.com`'s
   row, click "Provision Cert Now" → toast appears, log line written.
7. Run `docker exec kynexedu-app php artisan kynex:verify-pending-domains`
   — expect either "No pending" or zero re-dispatches (no domain
   currently meets the renewal-sweep criteria with stub-mode pending).
8. Single commit, push to `main`. Sub-phase 3 done.

### 2.5 What Sub-phase 3 leaves dormant

- `SHARED_CERT_LISTENER_SECRET` is empty in committed example file; not
  yet generated.
- `CERT_STUB_MODE=true` so no real HTTP call occurs.
- The listener doesn't exist yet — but Laravel doesn't try to call it.
- The `removeDomain` cleanup path is wired but stub-modes out — the row
  delete still happens, just no nginx cleanup yet.

---

## 3. Sub-phase 4 — kynex-app build plan (no rebuild yet)

Goal: write all files into `/var/www/kynex/`, show diffs, stop.

### 3.1 `Dockerfile` additions

**Path:** `/var/www/kynex/Dockerfile`

Diff (additions only, marked `+`):

```diff
 FROM php:8.4-fpm

 RUN apt-get update && apt-get install -y --no-install-recommends \
     libpq-dev \
     libzip-dev \
     libpng-dev \
     libjpeg-dev \
     libfreetype6-dev \
     libicu-dev \
     unzip \
     git \
     supervisor \
     nginx \
     cron \
+    certbot \
+    dnsutils \
+    procps \
     && docker-php-ext-configure gd --with-freetype --with-jpeg \
     && docker-php-ext-configure intl \
     && docker-php-ext-install -j$(nproc) \
         pdo_pgsql \
         pgsql \
         zip \
         gd \
         bcmath \
         opcache \
         pcntl \
         posix \
         intl \
     && pecl install redis \
     && docker-php-ext-enable redis \
     && rm -rf /etc/nginx/sites-enabled/default \
     && apt-get clean \
     && rm -rf /var/lib/apt/lists/*

 COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

 WORKDIR /var/www

 COPY docker/supervisor.conf /etc/supervisor/conf.d/kynex.conf
 COPY docker/nginx.conf /etc/nginx/sites-enabled/kynex.conf
 COPY docker/php.ini /usr/local/etc/php/conf.d/kynex.ini
 COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
 RUN chmod +x /usr/local/bin/entrypoint.sh

+# ── Phase 1.5: cert provisioning ─────────────────────────────
+COPY docker/cert/cert-listener.php       /usr/local/bin/cert-listener.php
+COPY docker/cert/provision-cert.sh       /usr/local/bin/provision-cert.sh
+COPY docker/cert/remove-cert.sh          /usr/local/bin/remove-cert.sh
+COPY docker/cert/custom-domain.conf.tpl  /usr/local/share/cert-listener/custom-domain.conf.tpl
+COPY docker/cert/certbot-renew.cron      /etc/cron.d/certbot-renew
+RUN chmod +x /usr/local/bin/provision-cert.sh /usr/local/bin/remove-cert.sh \
+    && chmod 644 /etc/cron.d/certbot-renew \
+    && mkdir -p /var/www/certbot/www/.well-known/acme-challenge \
+    && touch /var/log/certbot-renew.log /var/log/cert-listener.log
+
 COPY . /var/www
```

Rationale:

- `certbot` — the cert client itself.
- `dnsutils` — provides `dig` for DNS pre-check.
- `procps` — provides `pgrep` etc. for the listener's optional
  pid-based concurrency guard (see §6 Idempotency case 1).
- The `mkdir -p .../acme-challenge` ensures the webroot dir exists in
  the image (volumes will overlay if mounted, but if not, certbot still
  has a place to write).
- Log files are pre-created so cron + listener can tail them
  immediately on first run without permission surprises.

### 3.2 Compose change

**Path:** `/var/www/kynex/docker-compose.yml`

```diff
 services:
   app:
     build: .
     image: kynex-app
     container_name: kynex-app
     restart: unless-stopped
     ports:
       - 80:80
       - 443:443
     depends_on:
       postgres:
         condition: service_healthy
       redis:
         condition: service_healthy
     networks:
       - kynex
     volumes:
       - app-storage:/var/www/storage
       - ./.env:/var/www/.env
-      - /etc/letsencrypt:/etc/letsencrypt:ro
+      - /etc/letsencrypt:/etc/letsencrypt
       - ./certbot/www:/var/www/certbot/www
```

One line. `:ro` dropped. Pre-flight in §0 confirms kynex-app is the
sole consumer.

The `./certbot/www:/var/www/certbot/www` mount stays. Sub-phase 4
must `mkdir -p /var/www/kynex/certbot/www/.well-known/acme-challenge`
on the host so the bind-mount source exists *before* the recreate.

### 3.3 supervisor.conf additions

**Path:** `/var/www/kynex/docker/supervisor.conf`

```diff
 [program:php-fpm]
 command=php-fpm -F
 ...

 [program:nginx]
 command=nginx -g "daemon off;"
 ...

 [program:kynex-horizon]
 ...

 [program:kynex-queue]
 ...
+
+[program:cron]
+command=cron -f
+autostart=true
+autorestart=true
+stdout_logfile=/dev/stdout
+stdout_logfile_maxbytes=0
+stderr_logfile=/dev/stderr
+stderr_logfile_maxbytes=0
+numprocs=1
+
+[program:cert-listener]
+command=php -S 0.0.0.0:9090 /usr/local/bin/cert-listener.php
+directory=/usr/local/bin
+autostart=true
+autorestart=true
+stdout_logfile=/var/log/cert-listener.log
+stdout_logfile_maxbytes=10MB
+stdout_logfile_backups=3
+stderr_logfile=/var/log/cert-listener.log
+stderr_logfile_maxbytes=10MB
+stderr_logfile_backups=3
+numprocs=1
+environment=SHARED_CERT_LISTENER_SECRET="%(ENV_SHARED_CERT_LISTENER_SECRET)s",CERT_CONTACT_EMAIL="%(ENV_CERT_CONTACT_EMAIL)s",CERT_LE_SERVER="%(ENV_CERT_LE_SERVER)s"
```

The `environment=` line forwards the three env vars from kynex-app's
`.env` into the listener subprocess. (Supervisord doesn't inherit
shell env unless asked.)

### 3.4 Cron file

**Path:** `/var/www/kynex/docker/cert/certbot-renew.cron`

```
SHELL=/bin/bash
PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin

# Twice daily — LE recommendation. Random offset minute to avoid
# global API-traffic herd. Container time is UTC.
#
# The --post-hook reload is wrapped in flock against the global nginx
# mutate lock so it cannot race with provision-cert.sh / remove-cert.sh.
# `flock -w 30` waits up to 30s for the lock; if it can't acquire, it
# exits non-zero (logged in /var/log/certbot-renew.log) — fail-loud,
# never hang silently.
17 3,15 * * * root /usr/bin/certbot renew --quiet --post-hook "/usr/bin/flock -w 30 /var/lock/nginx-mutate.lock /usr/sbin/nginx -s reload" >> /var/log/certbot-renew.log 2>&1
```

### 3.5 Per-domain nginx template

**Path:** `/var/www/kynex/docker/cert/custom-domain.conf.tpl`

```nginx
# Custom domain: __DOMAIN__ → KynexEdu ERP
# Auto-generated by provision-cert.sh on __ISSUED_AT__.
# Edits will be overwritten on next provisioning run.

server {
    listen 443 ssl;
    listen [::]:443 ssl;
    server_name __DOMAIN__;

    ssl_certificate     /etc/letsencrypt/live/__DOMAIN__/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/__DOMAIN__/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;
    ssl_prefer_server_ciphers on;

    client_max_body_size 32M;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;

    location / {
        proxy_pass http://kynexedu-app:80;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_set_header X-Forwarded-Host $host;
        proxy_set_header X-Forwarded-Port $server_port;
    }
}
```

Verbatim copy of the existing `custom-aqmdigital.com.conf` with
`aqmdigital.com` → `__DOMAIN__` and a generation comment added.

### 3.6 `provision-cert.sh` — full source

**Path:** `/var/www/kynex/docker/cert/provision-cert.sh`

```bash
#!/usr/bin/env bash
#
# provision-cert.sh — issue / renew a Let's Encrypt cert for a custom
# domain and write the corresponding nginx server block.
#
# Usage:
#   provision-cert.sh <domain> [--force]
#
# Without --force: certbot --keep-until-expiring (idempotent / no-op when
# cert is fresh). With --force: certbot --force-renewal (always issues
# a new cert). Only the listener's /reissue endpoint passes --force.
#
# Concurrency:
#   - Per-domain flock on /var/lock/cert-provision-<domain>.lock
#     (-n, fail-fast on contention) — prevents same-domain double-runs.
#   - GLOBAL flock on /var/lock/nginx-mutate.lock around the
#     mv → nginx -t → reload section (-w 30, wait up to 30s) — prevents
#     races with remove-cert.sh and the certbot-renew cron --post-hook.
#
# Outputs a single JSON document on stdout. Diagnostics on stderr.
#
# Required env (forwarded by listener):
#   CERT_CONTACT_EMAIL    e.g. ops@kynexsolutions.com
#   CERT_LE_SERVER        ACME directory URL (staging or prod)
#
# Exit codes are not relied upon; status is encoded in the JSON output.

set -u
set -o pipefail

DOMAIN="${1:-}"
FORCE_FLAG="${2:-}"
TEMPLATE="/usr/local/share/cert-listener/custom-domain.conf.tpl"
SITES_DIR="/etc/nginx/sites-enabled"
WEBROOT="/var/www/certbot/www"
LOCK_DIR="/var/lock"
LOCK_FILE="${LOCK_DIR}/cert-provision-${DOMAIN}.lock"
NGINX_LOCK="${LOCK_DIR}/nginx-mutate.lock"
NGINX_LOCK_WAIT=30
EXPECTED_V4="178.104.180.160"
EXPECTED_V6="2a01:4f8:c014:4657::1"

emit() {
    # emit STATUS [error_message] [extra_json_fragment]
    local status="$1"; local err="${2:-}"; local extra="${3:-}"
    printf '{"status":"%s"' "$status"
    if [ -n "$err" ]; then
        printf ',"error":%s' "$(jq -Rn --arg s "$err" '$s' 2>/dev/null || printf '"%s"' "${err//\"/\\\"}")"
    fi
    if [ -n "$extra" ]; then
        printf ',%s' "$extra"
    fi
    printf '}\n'
}

log() { echo "[$(date -Iseconds)] [$DOMAIN] $*" >&2; }

# ── Validation ──────────────────────────────────────────────────────
if [ -z "$DOMAIN" ]; then
    emit "failed" "missing domain argument"
    exit 1
fi

if ! [[ "$DOMAIN" =~ ^([a-z0-9]([a-z0-9-]*[a-z0-9])?\.)+[a-z]{2,}$ ]]; then
    emit "failed" "invalid domain format: $DOMAIN"
    exit 1
fi

# ── Concurrency guard ───────────────────────────────────────────────
mkdir -p "$LOCK_DIR"
exec 200>"$LOCK_FILE"
if ! flock -n 200; then
    emit "failed" "another provisioning run is in progress for $DOMAIN"
    exit 1
fi

# ── DNS pre-check ───────────────────────────────────────────────────
A_RECORDS=$(dig +short A    "$DOMAIN" 2>/dev/null || true)
AAAA_RECORDS=$(dig +short AAAA "$DOMAIN" 2>/dev/null || true)

dns_ok=0
echo "$A_RECORDS"    | grep -qx "$EXPECTED_V4" && dns_ok=1
echo "$AAAA_RECORDS" | grep -qx "$EXPECTED_V6" && dns_ok=1

if [ "$dns_ok" -ne 1 ]; then
    log "DNS pre-check failed. A=[${A_RECORDS//$'\n'/,}] AAAA=[${AAAA_RECORDS//$'\n'/,}]"
    emit "dns_mismatch" "Domain $DOMAIN does not resolve to this server (expected A=$EXPECTED_V4 or AAAA=$EXPECTED_V6)"
    exit 0
fi

# ── certbot ─────────────────────────────────────────────────────────
# Build certbot args; default uses --keep-until-expiring (idempotent),
# but if invoked with --force we use --force-renewal instead. The two
# are mutually exclusive.
if [ "$FORCE_FLAG" = "--force" ]; then
    log "Force mode: certbot --force-renewal for $DOMAIN"
    CERTBOT_FRESHNESS_ARG="--force-renewal"
else
    log "Idempotent mode: certbot --keep-until-expiring for $DOMAIN"
    CERTBOT_FRESHNESS_ARG="--keep-until-expiring"
fi

CERTBOT_OUT=$(/usr/bin/certbot certonly \
    --webroot --webroot-path "$WEBROOT" \
    --domain "$DOMAIN" \
    --email "$CERT_CONTACT_EMAIL" \
    --agree-tos --no-eff-email \
    --key-type ecdsa \
    "$CERTBOT_FRESHNESS_ARG" \
    --non-interactive \
    --server "$CERT_LE_SERVER" 2>&1)
CERTBOT_EXIT=$?

log "certbot exit=$CERTBOT_EXIT"
log "certbot output: $(echo "$CERTBOT_OUT" | tr '\n' ' ' | head -c 500)"

if [ "$CERTBOT_EXIT" -ne 0 ]; then
    if echo "$CERTBOT_OUT" | grep -qi "too many certificates\|rate limit"; then
        emit "rate_limited" "$(echo "$CERTBOT_OUT" | tail -5 | tr '\n' ' ')"
        exit 0
    fi
    emit "failed" "certbot failed (exit $CERTBOT_EXIT): $(echo "$CERTBOT_OUT" | tail -5 | tr '\n' ' ')"
    exit 0
fi

# Confirm cert files exist
LIVE_DIR="/etc/letsencrypt/live/$DOMAIN"
if [ ! -f "$LIVE_DIR/fullchain.pem" ] || [ ! -f "$LIVE_DIR/privkey.pem" ]; then
    emit "failed" "certbot reported success but cert files are missing in $LIVE_DIR"
    exit 0
fi

# ── nginx block + test + reload (under global mutate lock) ─────────
# Render template into a tempfile in /tmp (NOT in sites-enabled/, so
# nginx never sees it during the staging phase). Then take the global
# mutate lock, do the cmp/mv/test/reload as one atomic sequence.
TARGET="$SITES_DIR/custom-${DOMAIN}.conf"
TMP=$(mktemp /tmp/cert-stage-${DOMAIN}.XXXXXX)
ISSUED_AT=$(date -Iseconds)

sed -e "s|__DOMAIN__|${DOMAIN}|g" \
    -e "s|__ISSUED_AT__|${ISSUED_AT}|g" \
    "$TEMPLATE" > "$TMP"

# Acquire the global nginx mutate lock. Wait up to NGINX_LOCK_WAIT
# seconds. Fail-loud if not acquired (do NOT silently bypass).
exec 201>"$NGINX_LOCK"
if ! flock -w "$NGINX_LOCK_WAIT" 201; then
    log "Could not acquire $NGINX_LOCK within ${NGINX_LOCK_WAIT}s"
    rm -f "$TMP"
    emit "failed" "could not acquire nginx-mutate lock within ${NGINX_LOCK_WAIT}s; another nginx mutation/reload may be stuck"
    exit 1
fi

# Inside the global lock: nothing else can mv into sites-enabled/ or
# run nginx -s reload until we release.

# Idempotent install: only mv if content actually differs.
if [ -f "$TARGET" ] && cmp -s "$TMP" "$TARGET"; then
    log "nginx conf unchanged for $DOMAIN; removing staged temp"
    rm -f "$TMP"
    NGINX_CONF_CHANGED=0
else
    mv "$TMP" "$TARGET"
    chmod 644 "$TARGET"
    NGINX_CONF_CHANGED=1
fi

# ── nginx -t (rollback on fail) ─────────────────────────────────────
NGINX_TEST=$(/usr/sbin/nginx -t 2>&1)
NGINX_EXIT=$?

if [ "$NGINX_EXIT" -ne 0 ]; then
    log "nginx -t failed: $NGINX_TEST"
    if [ "$NGINX_CONF_CHANGED" -eq 1 ]; then
        log "Removing newly-written conf and re-testing"
        rm -f "$TARGET"
        NGINX_TEST_AFTER=$(/usr/sbin/nginx -t 2>&1)
        NGINX_EXIT_AFTER=$?
        # release global lock before emit
        flock -u 201 2>/dev/null || true
        if [ "$NGINX_EXIT_AFTER" -ne 0 ]; then
            emit "failed" "nginx -t failing for unrelated reason; aborting (left other configs intact): $(echo "$NGINX_TEST_AFTER" | tail -2 | tr '\n' ' ')"
            exit 0
        fi
        emit "failed" "nginx -t rejected our new conf for $DOMAIN: $(echo "$NGINX_TEST" | tail -3 | tr '\n' ' ')"
        exit 0
    fi
    # Conf wasn't changed by us; just report the existing breakage.
    flock -u 201 2>/dev/null || true
    emit "failed" "nginx -t failing pre-existing: $(echo "$NGINX_TEST" | tail -3 | tr '\n' ' ')"
    exit 0
fi

# ── reload nginx ────────────────────────────────────────────────────
if [ "$NGINX_CONF_CHANGED" -eq 1 ]; then
    /usr/sbin/nginx -s reload 2>&1 | tee -a /var/log/cert-listener.log
fi

# Release global lock (also released automatically on script exit when
# fd 201 is closed; explicit for clarity).
flock -u 201 2>/dev/null || true

# ── Compute expiry for caller ───────────────────────────────────────
EXPIRES=""
if command -v openssl >/dev/null 2>&1; then
    EXPIRES=$(openssl x509 -in "$LIVE_DIR/fullchain.pem" -noout -enddate 2>/dev/null | sed 's/^notAfter=//')
    if [ -n "$EXPIRES" ]; then
        EXPIRES_ISO=$(date -d "$EXPIRES" -Iseconds 2>/dev/null || true)
    fi
fi

EXTRA=""
if [ -n "${EXPIRES_ISO:-}" ]; then
    EXTRA="\"cert_expires_at\":\"$EXPIRES_ISO\""
fi

emit "issued" "" "$EXTRA"
exit 0
```

### 3.7 `remove-cert.sh` — full source

**Path:** `/var/www/kynex/docker/cert/remove-cert.sh`

```bash
#!/usr/bin/env bash
#
# remove-cert.sh — clean up nginx conf + cert for a removed domain.
# Best-effort; never errors hard. Output: JSON on stdout.
#
# Concurrency: the rm + nginx -t + reload sequence is protected by the
# same /var/lock/nginx-mutate.lock that provision-cert.sh uses. The
# certbot delete runs outside the lock — it doesn't touch nginx state.

set -u

DOMAIN="${1:-}"
SITES_DIR="/etc/nginx/sites-enabled"
NGINX_LOCK="/var/lock/nginx-mutate.lock"
NGINX_LOCK_WAIT=30

if [ -z "$DOMAIN" ] || ! [[ "$DOMAIN" =~ ^([a-z0-9]([a-z0-9-]*[a-z0-9])?\.)+[a-z]{2,}$ ]]; then
    echo '{"status":"failed","error":"invalid domain"}'
    exit 0
fi

TARGET="$SITES_DIR/custom-${DOMAIN}.conf"
REMOVED_CONF=0; REMOVED_CERT=0; ERROR=""

# Acquire global mutate lock around rm + test + reload. Fail-loud if
# we can't get it within the timeout.
exec 201>"$NGINX_LOCK"
if ! flock -w "$NGINX_LOCK_WAIT" 201; then
    echo '{"status":"failed","error":"could not acquire nginx-mutate lock within '"$NGINX_LOCK_WAIT"'s"}'
    exit 1
fi

if [ -f "$TARGET" ]; then
    rm -f "$TARGET" && REMOVED_CONF=1
fi

if /usr/sbin/nginx -t >/dev/null 2>&1; then
    /usr/sbin/nginx -s reload 2>&1 | tee -a /var/log/cert-listener.log >/dev/null
else
    ERROR="nginx -t failed after removing conf"
fi

flock -u 201 2>/dev/null || true

# certbot delete is idempotent and safe to call when cert doesn't exist.
# Outside the nginx lock — pure /etc/letsencrypt operation.
CERTBOT_OUT=$(/usr/bin/certbot delete --cert-name "$DOMAIN" --non-interactive 2>&1)
CERTBOT_EXIT=$?
if [ "$CERTBOT_EXIT" -eq 0 ]; then
    REMOVED_CERT=1
fi

printf '{"status":"removed","conf_removed":%d,"cert_removed":%d' "$REMOVED_CONF" "$REMOVED_CERT"
if [ -n "$ERROR" ]; then printf ',"error":"%s"' "$ERROR"; fi
printf '}\n'
exit 0
```

### 3.8 `cert-listener.php` — full source

**Path:** `/var/www/kynex/docker/cert/cert-listener.php`

```php
<?php
/**
 * cert-listener.php — minimal HTTP listener for cert provisioning.
 *
 * Run via: php -S 0.0.0.0:9090 /usr/local/bin/cert-listener.php
 *          (under supervisord; not host-exposed via compose ports:)
 *
 * Endpoints (POST, JSON):
 *   /provision  { "domain": "..." }   — idempotent issue / no-op renew
 *   /reissue    { "domain": "..." }   — forced re-issue
 *                                       REQUIRES header
 *                                       X-Cert-Reissue-Confirm: true
 *   /remove     { "domain": "..." }
 *
 * Auth: every request must carry header
 *   X-Cert-Listener-Secret: <SHARED_CERT_LISTENER_SECRET env value>
 *
 * /reissue requires the additional header X-Cert-Reissue-Confirm: true
 * — defence in depth so a buggy client (or future code) can't burn a
 * Let's Encrypt rate-limit slot by accident.
 *
 * Outputs JSON, status code 200 unless misuse.
 */

declare(strict_types=1);

// Only accept connections we understand. Block everything else loudly.
$method         = $_SERVER['REQUEST_METHOD'] ?? '';
$path           = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$secret         = getenv('SHARED_CERT_LISTENER_SECRET') ?: '';
$header         = $_SERVER['HTTP_X_CERT_LISTENER_SECRET'] ?? '';
$reissueConfirm = $_SERVER['HTTP_X_CERT_REISSUE_CONFIRM'] ?? '';

header('Content-Type: application/json');
header('X-Listener-Version: phase-1.5');

$logLine = function (string $msg): void {
    fwrite(STDERR, '[' . date('c') . '] ' . $msg . "\n");
};

if ($secret === '' || ! hash_equals($secret, $header)) {
    http_response_code(401);
    $logLine("auth-fail path=$path remote=" . ($_SERVER['REMOTE_ADDR'] ?? '?'));
    echo json_encode(['status' => 'failed', 'error' => 'unauthorized']);
    return;
}

if ($method !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'failed', 'error' => 'method not allowed']);
    return;
}

$raw  = file_get_contents('php://input') ?: '';
$body = json_decode($raw, true);
if (! is_array($body) || ! isset($body['domain']) || ! is_string($body['domain'])) {
    http_response_code(400);
    echo json_encode(['status' => 'failed', 'error' => 'missing domain']);
    return;
}
$domain = $body['domain'];

// Defence in depth — final regex check before exec.
if (! preg_match('/^([a-z0-9]([a-z0-9-]*[a-z0-9])?\.)+[a-z]{2,}$/', $domain)) {
    http_response_code(400);
    echo json_encode(['status' => 'failed', 'error' => 'invalid domain']);
    return;
}

// Endpoint dispatch. /reissue requires the confirmation header; we
// reject before spawning the script so misuse never reaches certbot.
$scriptArgs = null;

switch ($path) {
    case '/provision':
        $scriptArgs = ['/usr/local/bin/provision-cert.sh', $domain];
        break;

    case '/reissue':
        if (! hash_equals('true', $reissueConfirm)) {
            http_response_code(400);
            $logLine("reissue-no-confirm path=$path domain=$domain");
            echo json_encode([
                'status' => 'failed',
                'error'  => '/reissue requires header X-Cert-Reissue-Confirm: true',
            ]);
            return;
        }
        $scriptArgs = ['/usr/local/bin/provision-cert.sh', $domain, '--force'];
        break;

    case '/remove':
        $scriptArgs = ['/usr/local/bin/remove-cert.sh', $domain];
        break;

    default:
        http_response_code(404);
        echo json_encode(['status' => 'failed', 'error' => 'unknown endpoint']);
        return;
}

$logLine("dispatch path=$path domain=$domain force=" . (in_array('--force', $scriptArgs, true) ? '1' : '0'));

$descriptors = [
    0 => ['pipe', 'r'],
    1 => ['pipe', 'w'],
    2 => ['pipe', 'w'],
];

// Pass through env vars the script needs.
$env = [
    'PATH'                => '/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin',
    'CERT_CONTACT_EMAIL'  => getenv('CERT_CONTACT_EMAIL') ?: '',
    'CERT_LE_SERVER'      => getenv('CERT_LE_SERVER')
                              ?: 'https://acme-v02.api.letsencrypt.org/directory',
];

$proc = proc_open($scriptArgs, $descriptors, $pipes, null, $env);

if (! is_resource($proc)) {
    http_response_code(500);
    echo json_encode(['status' => 'failed', 'error' => 'failed to spawn script']);
    return;
}

fclose($pipes[0]);
$stdout = stream_get_contents($pipes[1]); fclose($pipes[1]);
$stderr = stream_get_contents($pipes[2]); fclose($pipes[2]);
$exit   = proc_close($proc);

$logLine("done path=$path domain=$domain exit=$exit stdout=" . substr(trim($stdout), 0, 200));

// Script's stdout IS our response body — it's already JSON. Pass through.
$decoded = json_decode($stdout, true);
if (! is_array($decoded)) {
    http_response_code(500);
    echo json_encode([
        'status'    => 'failed',
        'error'     => 'script did not return JSON',
        'stdout'    => substr($stdout, 0, 1000),
        'stderr'    => substr($stderr, 0, 1000),
        'exit_code' => $exit,
    ]);
    return;
}

// Annotate with exit code for caller's logs.
$decoded['exit_code'] = $exit;
echo json_encode($decoded);
```

### 3.9 kynex-app `.env` additions

**Path:** `/var/www/kynex/.env`  (existing file; append)

```
# ── Phase 1.5: cert provisioning ────────────────────────────
SHARED_CERT_LISTENER_SECRET=
CERT_CONTACT_EMAIL=ops@kynexsolutions.com
CERT_LE_SERVER=https://acme-staging-v02.api.letsencrypt.org/directory
```

(`SHARED_CERT_LISTENER_SECRET` left empty in the design doc, populated
at Sub-phase 5 cutover from the same value as `kynexedu` side.
`CERT_LE_SERVER` is **staging** for Sub-phases 5 testing; flipped to
production in Sub-phase 6.)

### 3.10 Sub-phase 4 deliverables

After writing all files into `/var/www/kynex/`:

- `/var/www/kynex/Dockerfile` (edited)
- `/var/www/kynex/docker-compose.yml` (edited)
- `/var/www/kynex/docker/supervisor.conf` (edited)
- `/var/www/kynex/docker/cert/cert-listener.php` (new)
- `/var/www/kynex/docker/cert/provision-cert.sh` (new)
- `/var/www/kynex/docker/cert/remove-cert.sh` (new)
- `/var/www/kynex/docker/cert/custom-domain.conf.tpl` (new)
- `/var/www/kynex/docker/cert/certbot-renew.cron` (new)
- `/var/www/kynex/.env` (one section appended; no real secret yet)

Show `git diff` (or `diff -u`) for each. Stop. User reviews.

**No `docker compose build`. No recreate. No host changes.**

---

## 4. Sub-phase 5 — Image rebuild + integration test

After Sub-phase 4 review:

### 4.1 Pre-rebuild prep

1. Generate the shared secret on host:
   ```
   openssl rand -base64 32
   ```
   Write the output to BOTH:
   - `/var/www/kynex/.env` line `SHARED_CERT_LISTENER_SECRET=...`
   - `/var/www/kynexedu/.env.production` line `SHARED_CERT_LISTENER_SECRET=...`
   (The same value in both places.)
2. Flip `/var/www/kynexedu/.env.production` line `CERT_STUB_MODE=false`.
3. Ensure host webroot dir exists (so the bind-mount source isn't
   docker-auto-created with wrong perms):
   ```
   mkdir -p /var/www/kynex/certbot/www/.well-known/acme-challenge
   chmod -R 755 /var/www/kynex/certbot
   ```
4. Capture aqmdigital.com's existing nginx conf (belt-and-suspenders
   in case rebuild loses it before the new code re-creates it):
   ```
   docker exec kynex-app cat /etc/nginx/sites-enabled/custom-aqmdigital.com.conf > /tmp/custom-aqmdigital.com.conf.bak
   ```

### 4.2 Build + recreate

```
cd /var/www/kynex
docker compose build app
docker compose up -d app
```

Estimated downtime for `ai.kynexsolutions.com`: ~30s (authorised).

### 4.3 Canary tests (post-recreate, before cert tests)

1. `docker exec kynex-app supervisorctl status` — expect 6 programs:
   `php-fpm`, `nginx`, `kynex-horizon`, `kynex-queue:*` (4 procs),
   `cron`, `cert-listener` — all RUNNING.
2. `docker exec kynex-app sh -c 'certbot --version && which dig && which php'`
   — confirm tools present.
3. `curl -k https://ai.kynexsolutions.com/up` from host — confirm the
   AI app still responds. (Sacred block (d) untouched.)
4. `curl -k https://sms.kynexsolutions.com/login` — confirm 200 OK.
5. `docker exec kynexedu-app php artisan tinker` →
   `\Illuminate\Support\Facades\Http::get('http://kynex-app:9090/provision')`
   — expect HTTP 401 (auth missing), proves listener is reachable.
6. Same call WITH the secret header but no body — expect HTTP 400.

### 4.4 aqmdigital.com regeneration test

The recreate wiped `custom-aqmdigital.com.conf`. Trigger the new code
to regenerate it.

> ⚠ Use **"Provision Cert Now"** (the idempotent `/provision` path),
> NOT "Reissue Cert". aqmdigital's existing cert in
> `/etc/letsencrypt/live/aqmdigital.com/` was issued from PROD LE,
> while Sub-phase 5 has `CERT_LE_SERVER=staging`. Hitting `/reissue`
> (`--force-renewal --server staging`) would either error out on the
> server-URL conflict or, worse, replace the prod cert with a staging
> "Fake LE Intermediate X1" cert — instantly breaking
> `https://aqmdigital.com` in browsers. The `/provision` path uses
> `--keep-until-expiring`, which is a no-op when the cert is fresh
> regardless of server URL — exactly what we want here. The reissue
> path is exercised separately in §4.6 against a throwaway staging
> domain.

1. SaaS admin UI → Tenants → haji-qamar → Domains → aqmdigital.com row
   → **"Provision Cert Now"** action. Confirm.
2. Watch logs:
   ```
   docker exec kynexedu-app tail -f storage/logs/laravel.log
   docker logs -f kynex-app | grep cert-listener
   ```
3. Expected: Job dispatched → POST `/provision` → listener spawns
   `provision-cert.sh aqmdigital.com` (no `--force` arg) → certbot
   reports "Certificate not yet due for renewal" (cert was issued
   2026-05-02, well within validity) → conf written from template →
   nginx -t → nginx -s reload (all under `nginx-mutate.lock`).
4. Verify in browser: `https://aqmdigital.com/login` returns 200 with
   green padlock (the existing PROD cert is still in use; we only
   regenerated the nginx conf).
5. Verify file: `docker exec kynex-app ls -la /etc/nginx/sites-enabled/`
   — `custom-aqmdigital.com.conf` present, mtime fresh.

If anything fails at this step, restore from backup:
```
docker cp /tmp/custom-aqmdigital.com.conf.bak kynex-app:/etc/nginx/sites-enabled/custom-aqmdigital.com.conf
docker exec kynex-app nginx -s reload
```

### 4.5 Throwaway test domain (LE staging)

Use a domain you control that points an A record to `178.104.180.160`.
Suggested: a subdomain of `kynexsolutions.com` you don't otherwise
use, e.g. `cert-test.kynexsolutions.com`.

Add a tenant + domain row through SaaS admin UI:
- Add Custom Domain → `cert-test.kynexsolutions.com`
- Set DNS A → `178.104.180.160`, plus the TXT record from the modal
- Click "Verify Now" → triggers `verifyDomain` → dispatches job → cert
  issued via LE STAGING (cert will be untrusted; that's expected)
- `curl -k https://cert-test.kynexsolutions.com/login` — expect 200.
- Cert subject in browser shows "Fake LE Intermediate X1".

### 4.6 Failure-path tests (all against the throwaway staging domain `cert-test.kynexsolutions.com` from §4.5 unless noted)

1. **DNS mismatch.** Add a separate domain row for `not-pointed.example.com`
   (or any domain whose DNS doesn't resolve here). Click "Provision
   Cert Now". Expect Job final state `cert_status='dns_mismatch'`,
   `cert_last_error` populated.
2. **nginx -t artificial fail.** Manually `docker exec kynex-app sh -c
   'echo "garbage{" >> /etc/nginx/sites-enabled/zz-broken.conf'`. Then
   click "Reissue Cert" on `cert-test.kynexsolutions.com`. Expect
   script's `nginx -t failing pre-existing` branch — does NOT delete
   the new custom domain's conf, returns failure status. Restore
   manually (`docker exec kynex-app rm /etc/nginx/sites-enabled/zz-broken.conf`).
3. **Rate limit simulation.** Hard to force on staging; instead inject
   a synthetic certbot output by temporarily swapping the binary path
   in `provision-cert.sh` to a stub script that prints "too many
   certificates" and exits 1. Expect `cert_status='rate_limited'`. (Or
   skip this test and rely on code review of the parsing branch.)
4. **Per-domain concurrency.** Click "Provision Cert Now" twice in
   quick succession on `cert-test.kynexsolutions.com`. Second call
   should hit the per-domain flock at
   `/var/lock/cert-provision-cert-test.kynexsolutions.com.lock` and
   return `failed: another provisioning run is in progress`.
5. **Global mutate-lock timeout.** Take the global lock manually and
   hold it longer than 30s, then trigger a provision:
   ```
   docker exec -d kynex-app sh -c 'flock /var/lock/nginx-mutate.lock sleep 90'
   # within 30s, click "Provision Cert Now" on cert-test
   ```
   Expect listener response `failed: could not acquire nginx-mutate
   lock within 30s; another nginx mutation/reload may be stuck`.
   Cleanup: `docker exec kynex-app pkill -f 'flock.*nginx-mutate'`.
6. **Reissue without confirm header.** From host:
   ```
   docker exec kynexedu-app curl -s -X POST \
     -H "X-Cert-Listener-Secret: $(grep SHARED_CERT_LISTENER_SECRET /var/www/kynexedu/.env.production | cut -d= -f2-)" \
     -H "Content-Type: application/json" \
     -d '{"domain":"cert-test.kynexsolutions.com"}' \
     http://kynex-app:9090/reissue
   ```
   Expect HTTP 400, body `"/reissue requires header X-Cert-Reissue-Confirm: true"`. No script spawned (verify in `docker logs kynex-app`).
7. **Reissue with confirm header (positive path).** Same as #6 but
   add `-H "X-Cert-Reissue-Confirm: true"`. Expect HTTP 200 and the
   STAGING cert to be re-issued (different "not before" timestamp on
   `/etc/letsencrypt/live/cert-test.kynexsolutions.com/cert.pem`).
8. **Cron post-hook lock acquisition.** Force a renewal cycle in a
   way that exercises the wrapped post-hook:
   ```
   docker exec kynex-app /usr/bin/certbot renew --cert-name cert-test.kynexsolutions.com --dry-run --post-hook "/usr/bin/flock -w 30 /var/lock/nginx-mutate.lock /usr/sbin/nginx -s reload"
   ```
   Expect dry-run to succeed and the post-hook flock to acquire/release
   cleanly (visible via `lsof /var/lock/nginx-mutate.lock` mid-run if
   timed right).
9. **Remove cleanup.** Remove `cert-test.kynexsolutions.com` via the
   Filament "Remove" action. Expect: row deleted, `custom-cert-test.kynexsolutions.com.conf`
   gone, `certbot delete` ran (cert dir removed from
   `/etc/letsencrypt/live/`).

### 4.7 Sub-phase 5 stop point

After all tests above pass, write a final report under
`docs/cert-provisioning-subphase5-test-results-<date>.md` with
captured logs and outputs. Stop. User approves before Sub-phase 6.

---

## 5. Sub-phase 6 — Production cutover + existing-cert migration

Per user answer #4, the existing 3 certs are migrated **in this
workstream**, not deferred.

### 5.1 Switch to production LE

In `/var/www/kynex/.env`:
```
CERT_LE_SERVER=https://acme-v02.api.letsencrypt.org/directory
```
Then `docker exec kynex-app supervisorctl restart cert-listener`.

### 5.2 Migrate `sms.kynexsolutions.com` and `aqmdigital.com` renewal configs

These use `authenticator = webroot` with `webroot_path =
/var/www/kynex/docker/certbot/www` (a HOST path, unreachable from
in-container nginx — currently latently broken).

The migration is done in three guarded steps: **backup → edit → verify
(or rollback)**. We do the edit only after confirming the file as it
exists matches the expected pattern, and bail loudly if sed completes
but doesn't actually change anything.

```bash
# Step 1 — capture a timestamped backup of both files.
TS=$(date +%Y%m%d-%H%M%S)
docker exec kynex-app sh -c "
  cp -a /etc/letsencrypt/renewal/sms.kynexsolutions.com.conf  /etc/letsencrypt/renewal/sms.kynexsolutions.com.conf.bak.$TS
  cp -a /etc/letsencrypt/renewal/aqmdigital.com.conf          /etc/letsencrypt/renewal/aqmdigital.com.conf.bak.$TS
  ls -la /etc/letsencrypt/renewal/*.bak.$TS
"

# Step 2 — sed in place. (No -i.bak suffix; we already captured a
# backup explicitly so the audit trail is clean.)
docker exec kynex-app sh -c "
  sed -i 's|/var/www/kynex/docker/certbot/www|/var/www/certbot/www|g' \
    /etc/letsencrypt/renewal/sms.kynexsolutions.com.conf \
    /etc/letsencrypt/renewal/aqmdigital.com.conf
"

# Step 3 — verify the change actually took effect. If grep finds
# nothing, sed was a no-op (file mid-rotated, unexpected encoding,
# etc.). Restore from backup and abort.
docker exec kynex-app sh -c "
  for f in sms.kynexsolutions.com.conf aqmdigital.com.conf; do
    if ! grep -q '/var/www/certbot/www' /etc/letsencrypt/renewal/\$f; then
      echo 'ERROR: sed did not produce expected change in '\$f
      cp -a /etc/letsencrypt/renewal/\$f.bak.$TS /etc/letsencrypt/renewal/\$f
      exit 1
    fi
    if grep -q '/var/www/kynex/docker/certbot/www' /etc/letsencrypt/renewal/\$f; then
      echo 'ERROR: stale path still present in '\$f
      cp -a /etc/letsencrypt/renewal/\$f.bak.$TS /etc/letsencrypt/renewal/\$f
      exit 1
    fi
  done
  echo 'sed migration verified for both renewal configs.'
"
```

If step 3 reports `sed migration verified`, proceed. If it reports an
ERROR, the file has already been restored from the backup — investigate
manually before retrying.

**Manual rollback** (e.g., if the post-edit `certbot renew` below
behaves unexpectedly): the backups are at
`/etc/letsencrypt/renewal/<file>.bak.<TS>` inside the container. Either:

```bash
docker exec kynex-app cp -a /etc/letsencrypt/renewal/sms.kynexsolutions.com.conf.bak.$TS  /etc/letsencrypt/renewal/sms.kynexsolutions.com.conf
docker exec kynex-app cp -a /etc/letsencrypt/renewal/aqmdigital.com.conf.bak.$TS          /etc/letsencrypt/renewal/aqmdigital.com.conf
```

(The backup files are ignored by `certbot renew` because they don't
match the `*.conf` glob.)

Force a re-renewal with the new path to prove it works:
```
docker exec kynex-app certbot renew --cert-name sms.kynexsolutions.com --force-renewal --post-hook "/usr/bin/flock -w 30 /var/lock/nginx-mutate.lock /usr/sbin/nginx -s reload"
docker exec kynex-app certbot renew --cert-name aqmdigital.com --force-renewal --post-hook "/usr/bin/flock -w 30 /var/lock/nginx-mutate.lock /usr/sbin/nginx -s reload"
```

Verify cert chains updated (mtime moves; `not before` advances).

### 5.3 `ai.kynexsolutions.com` decision

**Decision: leave on host renewal mechanism, document as follow-up.**

Rationale:
- The `ai.kynexsolutions.com` cert uses `authenticator = nginx` +
  `installer = nginx`. Migrating it to in-container webroot would
  require either (a) running `certbot --nginx` inside kynex-app — but
  certbot's nginx plugin EDITS the matching server block (block (d)),
  which is the SACRED ai.kynexsolutions.com block per constraint (b),
  or (b) reissuing it under webroot mode and re-editing block (d) by
  hand to point at the new cert path — which technically isn't
  required (the path is the same), but the "remove the nginx-plugin
  hooks from the renewal config" step still risks accidentally
  triggering an in-place block (d) edit.
- The host's `certbot.timer` continues to handle this cert until
  someone deliberately restructures it. Until then, the cert renewal
  for `ai.kynexsolutions.com` falls back to the host-side mechanism
  that was set up by the AI app's owner.

**Follow-up docket entry** added in §8 below.

### 5.4 Disable host certbot.timer (optional, end of Sub-phase 6)

After Sub-phase 6 §5.2 + §5.3 are stable for ≥ 7 days:
```
systemctl stop certbot.timer
systemctl disable certbot.timer
```
Leaves host certbot installed (still needed for `ai.kynexsolutions.com`
manual renewal until that's restructured), but the in-container cron
becomes the sole automated runner.

Recommendation: **leave this OFF for now**. Both timers running with
locking is safe; disabling is a one-liner we can do later.

### 5.5 Backfill domains table cert state

For verified custom domains that already have certs but `cert_status =
'pending'` (the backfill default from §2.1.A):

```
docker exec kynexedu-app php artisan tinker
>>> \Stancl\Tenancy\Database\Models\Domain::where('is_verified', true)
        ->where('domain_type', 'custom')
        ->where('cert_status', 'pending')
        ->each(fn ($d) => \App\Jobs\ProvisionCustomDomainCertificate::dispatch($d->id));
```

The job uses `--keep-until-expiring` so this is safe — for already-issued
certs it's a no-op except for updating `cert_status`/`cert_expires_at`
columns from the existing cert.

### 5.6 Onboarding doc

**Path:** `/var/www/kynexedu/scripts/onboard-school.md` (new)

Contents (sketch):
- "When a new school requests a custom domain"
- Step 1: SaaS admin opens TenantResource → school → Domains tab
- Step 2: Add Custom Domain → enter the domain
- Step 3: Send the school the displayed CNAME + TXT instructions
- Step 4: Wait for school to confirm DNS is set
- Step 5: Click "Verify Now" → if green, cert is auto-queued
- Step 6: Within ~60s, status badge moves pending → issuing → issued
- Step 7: Verify in browser: `https://<domain>/login` should be live
- Step 8: If status stays `dns_mismatch`, the school's DNS isn't right;
  send them the IP and ask them to fix
- Step 9: If `failed` for unclear reason, click "Reissue Cert" once;
  if still failing, check `docker logs kynex-app | grep cert-listener`
- Rotation procedure for `SHARED_CERT_LISTENER_SECRET` (see §6 below).

### 5.7 Final commit + push

One commit covering Sub-phase 6 changes (any compose/env edits, the
onboarding doc). Push.

---

## 6. Shared secret strategy (Amendment A)

### 6.1 Generation

Initial value generated **on the production host** by the SaaS owner
(the user) at Sub-phase 5 cutover time:

```
openssl rand -base64 32
```

Output is a 44-character base64 string with ~256 bits of entropy.
**Generated on the prod host directly so the value never leaves it.**

### 6.2 Transmission

The same value is written into TWO files, both on the prod host, both
gitignored:

| File                                       | Owner      | Notes                                       |
|--------------------------------------------|------------|---------------------------------------------|
| `/var/www/kynexedu/.env.production`        | root:root 600 | Loaded by `docker-compose.prod.yml` env_file |
| `/var/www/kynex/.env`                      | root:root 644 | Mounted into kynex-app via `./.env:/var/www/.env` |

Both files are in `.gitignore` (verified: `.env*` patterns in both
projects' .gitignores per Laravel convention). **The secret never
leaves the prod host.**

### 6.3 No placeholder strings in committed code or examples

- `config/cert.php` reads from env, has no default.
- `.env.docker.example` has the line `SHARED_CERT_LISTENER_SECRET=`
  with NO value (empty string).
- `cert-listener.php` reads from env, refuses requests if env is empty
  (the `hash_equals` check with empty secret never passes).
- The Dockerfile does NOT bake any secret in — env is passed at
  runtime via supervisord's `environment=` directive (which reads
  process env, which comes from the mounted `.env` file).

### 6.4 Rotation procedure

Frequency: **quarterly, OR immediately after any team-membership
change** (per user answer #6).

Steps:
1. SSH to prod host.
2. Generate new value: `NEW=$(openssl rand -base64 32)`
3. Edit `/var/www/kynexedu/.env.production` → replace
   `SHARED_CERT_LISTENER_SECRET=...` line with new value.
4. Edit `/var/www/kynex/.env` → same.
5. Restart Laravel queue workers (so they pick up the new env):
   `docker exec kynexedu-queue supervisorctl restart all`
6. Restart the listener:
   `docker exec kynex-app supervisorctl restart cert-listener`
7. (Optional) verify: `docker exec kynexedu-app php artisan tinker`
   → dispatch a no-op provision (e.g. on an already-issued domain).
   Confirm log line shows `[cert] Provisioned` with no auth errors.

Total downtime for cert provisioning: ~5 seconds (worker restarts).
Provisioning itself is rare so any in-flight call would be retried by
the Job's retry policy.

This procedure is added to `scripts/onboard-school.md` (§5.6).

---

## 7. Idempotency cases (Amendment B)

`provision-cert.sh` must handle these explicitly. Current handling:

### Case 1 — Two calls within 60 seconds for the same domain

**Handling:** per-domain flock at start of script (rev2 rename for
clearer grep target).

- Script opens `/var/lock/cert-provision-<domain>.lock` and calls
  `flock -n 200`.
- First call acquires; second call gets EWOULDBLOCK → emits
  `{"status":"failed","error":"another provisioning run is in
  progress for <domain>"}` and exits 1.
- Listener returns this verbatim to Laravel; Job's `handle()` switch
  treats it as `failed` and retries 60s later (one of `$tries=3`
  attempts). By the second attempt the lock is released.

This is the chosen approach over a queue/wait because:
- Provisioning is rare (one per onboarding).
- Lock-then-fail is simpler than lock-then-wait.
- Job retry already provides the wait-then-retry behaviour.

Cross-domain provisioning runs in parallel — different domains use
different lock files. Only the brief mv→test→reload section
(microseconds–milliseconds) serialises across domains, via the
**second**, global `/var/lock/nginx-mutate.lock` (see Case 4).

### Case 2 — Domain already has a valid, unexpired cert

**Handling:** `certbot --keep-until-expiring` + cmp-then-mv on conf.

- `certbot certonly --keep-until-expiring` is a no-op when cert exists
  and is > 30d from expiry. Output looks like "Certificate not yet due
  for renewal", exit 0. Script proceeds.
- nginx conf write: script generates content into a tempfile, then
  `cmp -s tmp target`:
  - If identical: `rm tmp`, set `NGINX_CONF_CHANGED=0`, skip reload.
  - If different (template changed, e.g. proxy headers updated): `mv
    tmp target`, set `NGINX_CONF_CHANGED=1`, run nginx -t + reload.
- Net result: no-op when nothing actually changed; idempotent; no
  spurious reloads.
- Job updates `cert_status='issued'` + `cert_expires_at` regardless,
  so the DB row is brought into sync even if the on-disk state was
  already correct.

### Case 3 — Mid-failure recovery (previous run wrote partial state)

Possible partial states:

| Partial state | Recovery on next run |
|---------------|----------------------|
| Cert dir present but missing `privkey.pem` (certbot was killed) | `certbot --keep-until-expiring` sees the cert as broken/missing and reissues. |
| Conf file present but cert dir missing | The conf references files that don't exist. nginx -t will FAIL on the next reload attempt. Recovery: provision-cert.sh's nginx-test branch removes the offending conf and fails cleanly; user can rerun once cert is back. |
| Cert dir present but conf file missing | Next run regenerates conf normally. |
| Lock file from killed previous run | `flock -n` is process-scoped; when the holding process dies, the lock is released automatically by the kernel. Stale lock files on disk are harmless. |

Concrete recovery flow on retry:

1. Job retries (3 tries with 60s backoff handles transient cases).
2. If still failing after 3 tries, status sticks at `failed` and the
   renewal-sweep in `kynex:verify-pending-domains` re-dispatches
   nightly.
3. SaaS admin can hit "Reissue Cert" any time to force.

### Case 4 — Pre-existing nginx -t failure (unrelated to this domain)

**Handling:** detect-and-don't-touch, plus global mutate-lock to close
the race rev1 had.

The previous design had a brief window between `mv → custom-<domain>.conf`
and `rm -f` (when -t failed) where another process running
`nginx -s reload` could pick up our broken file. Rev2 closes this:

- Render the conf to a tempfile in `/tmp/` (NOT in `sites-enabled/`)
  — staged where nginx never sees it.
- Acquire the global lock `/var/lock/nginx-mutate.lock` via `flock -w 30`
  (wait up to 30s; fail-loud on timeout).
- Inside the lock: cmp/mv → `nginx -t` → reload (or rollback).
- The same lock is taken by `remove-cert.sh`'s rm/test/reload section
  AND by the `certbot-renew` cron's `--post-hook` reload. So while we
  hold the lock, nothing else can `mv` into `sites-enabled/`, `rm`
  from it, or run `nginx -s reload`.

Inside the lock, the rest of the rev1 logic is unchanged:

- Script writes its conf, runs `nginx -t`. If it fails, script removes
  *only* the file it just wrote, then runs `nginx -t` *again*.
- If the second `nginx -t` passes → it was our file's fault. Emit
  `failed` with parsed error.
- If the second `nginx -t` still fails → the kynex-app container has
  pre-existing nginx breakage we didn't cause. Emit `failed` with
  message "nginx -t failing for unrelated reason; aborting (left
  other configs intact)".
- **Critical:** the script never removes any other domain's conf
  (only its own `custom-<domain>.conf`). The `rm -f` targets are
  hardcoded to the path constructed from the domain arg, never
  glob-deleted.

This protects the writable layer from being damaged by our script
during a degraded state.

### Case 5 — Lock-acquisition timeout (fail-loud)

If `flock -w 30 /var/lock/nginx-mutate.lock` cannot acquire within 30
seconds, the script:

1. Logs `Could not acquire $NGINX_LOCK within 30s` to stderr.
2. Removes the staged tempfile in `/tmp/` (no garbage left behind).
3. Emits `{"status":"failed","error":"could not acquire nginx-mutate lock within 30s; another nginx mutation/reload may be stuck"}`.
4. Exits 1.

Job retry kicks in (3 attempts × 60s backoff). If lock contention is
sustained for 3+ minutes, the failure surfaces in the Filament badge
and operator can investigate (`docker exec kynex-app fuser /var/lock/nginx-mutate.lock`).
This is fail-loud not hang-silent — exactly the behaviour we want.

---

## 8. Sequence diagrams

### 8.1 Happy path — new domain provisioning

```
  SaaS admin                                           kynex-app
  Filament UI       kynexedu-app (Laravel)             (nginx + certbot + listener)
  ──────────        ─────────────────────              ──────────────────────────
  click             route('saas-admin.tenants.edit')
  "Verify Now"  →   DomainsRelationManager
                    → CustomDomainService::verifyDomain
                       └─► dns_get_record TXT
                       └─► UPDATE domains SET is_verified=true
                       └─► ProvisionCustomDomainCertificate::dispatch(id)
                            (queued in redis)
                    ✓ Notification: "Domain verified!"

  (Horizon worker picks up job)
                    Job::handle($id)
                    → load Domain
                    → UPDATE domains SET cert_status='issuing',
                                         cert_attempt_count=cert_attempt_count+1
                    → endpoint = $force ? '/reissue' : '/provision'
                    → POST http://kynex-app:9090<endpoint>
                          {"domain":"foo.com"}
                          X-Cert-Listener-Secret: <secret>
                          X-Cert-Reissue-Confirm: true   (only on /reissue)
                                                                     → cert-listener.php
                                                                       ├─ hash_equals(secret) ✓
                                                                       ├─ /reissue path?
                                                                       │   require X-Cert-Reissue-Confirm: true ✓
                                                                       │   args = [provision-cert.sh, foo.com, --force]
                                                                       ├─ /provision path?
                                                                       │   args = [provision-cert.sh, foo.com]
                                                                       ├─ proc_open ...
                                                                                                            ├─ flock -n /var/lock/cert-provision-foo.com.lock ✓
                                                                                                            ├─ dig A/AAAA → match ✓
                                                                                                            ├─ certbot certonly
                                                                                                            │     --webroot
                                                                                                            │     --keep-until-expiring
                                                                                                            │       (or --force-renewal if --force)
                                                                                                            │     ✓ → live/foo.com/
                                                                                                            ├─ render template → /tmp/cert-stage-foo.com.XXXXXX
                                                                                                            ├─ flock -w 30 /var/lock/nginx-mutate.lock ✓
                                                                                                            │   ├─ cmp tmp vs target → differs
                                                                                                            │   ├─ mv tmp → custom-foo.com.conf
                                                                                                            │   ├─ nginx -t ✓
                                                                                                            │   ├─ nginx -s reload
                                                                                                            │   └─ release nginx-mutate.lock
                                                                                                            └─ emit {"status":"issued",
                                                                                                                     "cert_expires_at":"..."}
                                                                       └─ proc_close → return JSON
                    ← 200 {"status":"issued","cert_expires_at":"..."}
                    → UPDATE domains SET cert_status='issued',
                                         cert_issued_at=now(),
                                         cert_expires_at=...,
                                         cert_last_error=NULL
                    Job done.
```

### 8.2 DNS mismatch

```
  Job::handle → POST /provision → cert-listener spawns provision-cert.sh
                                  → dig A/AAAA → no match
                                  → emit {"status":"dns_mismatch","error":"..."}
  Job parses → markFailure(status='dns_mismatch', error)
  Job calls $this->fail()  ← no retry
  UI badge flips to "dns_mismatch" (red).
  SaaS admin reads error from cert_last_error column (or via tinker).
```

### 8.3 Rate-limited

```
  certbot returns exit ≠ 0; output contains "too many certificates"
  → emit {"status":"rate_limited","error":"..."}
  Job parses → markFailure → $this->fail() ← no retry
  Renewal sweep in `kynex:verify-pending-domains` re-dispatches
  nightly; LE rate limit windows are typically a week, so retries
  resume automatically.
```

### 8.4 nginx -t fails on our new conf

```
  certbot ✓ (cert issued)
  mv tmp → custom-foo.com.conf
  nginx -t ✗
  → rm -f custom-foo.com.conf
  → nginx -t ✓ (was our fault)
  → emit {"status":"failed","error":"nginx -t rejected our new conf for foo.com: ..."}
  Job retries (up to 3 times). If template is genuinely broken, all
  retries fail; cert_status='failed' until human investigates.
  Cert files are kept on disk (issuance succeeded); next attempt is
  pure re-template-and-test.
```

### 8.5 Listener unreachable

```
  Job → POST http://kynex-app:9090/provision → connect timeout / 503
  Throwable in Http::post()
  Job catches → markFailure(status='failed', "listener unreachable: ...")
  Throwable rethrown → triggers retry
  3 retries with backoff=60s. If still unreachable, $failed callback
  records the failure permanently.
```

---

## 9. Test plan summary table

| Sub-phase | Tests |
|-----------|-------|
| 3 | `migrate --pretend`, `migrate --force`, tinker dispatch (stub mode) for both `provisionCert()` and `reissueCert()` — confirm log lines show different endpoints (`/provision` vs `/reissue`) and that the reissue stub log shows `force=true`. Filament action clicks (stub) for both buttons. `verify-pending-domains` command run. Single commit. |
| 4 | Code review of diffs (no execution). Stop. |
| 5 | Build + recreate; supervisor status; canary curls (`ai`, `sms`); listener auth tests (incl. `/reissue` WITHOUT confirm header → expect 400); aqmdigital regenerate via **`/provision`** (NOT `/reissue` — see §4.4 warning); throwaway-domain LE-staging `/provision` and `/reissue`; failure-path tests (DNS mismatch, nginx-t fail, per-domain concurrent, global-lock timeout, remove). Verify cron's wrapped `--post-hook` actually runs (force a renewal at staging via `--dry-run`). |
| 6 | LE prod cutover; existing-cert migration (sms, aqm) with backup→edit→verify→rollback flow; decision-doc on ai; backfill; onboarding doc; commit + push. |

---

## 10. Follow-up docket (post-Phase-1.5)

These remain explicitly NOT-done by Phase 1.5:

| # | Item | Why deferred |
|---|------|--------------|
| 1 | `ai.kynexsolutions.com` cert renewal end-to-end | Uses certbot's nginx plugin which would touch the SACRED block (d). Restructuring requires coordinating with the AI app's owner. Currently host certbot.timer keeps trying but reaches no useful endpoint. |
| 2 | Disable host `certbot.timer` once in-container is proven over ≥7 days | Optional cleanup; harmless to leave both running with file-locking. |
| 3 | Multi-domain SAN certs | Each cert is single-domain today. Future: support `apex + www` as one cert per row. Schema change required. |
| 4 | Wildcard + DNS-01 challenges | Would require DNS provider API tokens (Cloudflare etc.) per tenant. Out of scope for HTTP-01 happy path. |
| 5 | OCSP stapling in per-domain template | Modest perf win, not a correctness issue. |
| 6 | Email/Slack alert on cert provisioning failure | Today the failure is visible in the Filament badge but not pushed. Future: Notification channel. |

---

## 11. Constraints check

| Constraint | Status in this design |
|------------|-----------------------|
| (a) blocks (a)-(e) of `kynex.conf` not modified | ✓ — only ADD `custom-<domain>.conf` files |
| (b) `ai.kynexsolutions.com` block sacred | ✓ — design avoids any operation that touches it |
| (c) Dockerfile editable | ✓ — additions only, diff in §3.1 |
| (d) ~30s `ai.kynexsolutions.com` downtime authorised | ✓ — only at §4.2 recreate |
| (e) `/var/www/kynexedu` primary scope | ✓ |
| (f) listener: docker-network-only, secret-auth, separate process, supervised, logged | ✓ — see §3.3, §3.8 |
| (g) `provision-cert.sh` idempotent | ✓ — see §7 cases 1-4 |
| (h) no secrets in git | ✓ — §6 generation is on host, .env files gitignored |
| (i) Sub-phase 1 was read-only | ✓ — confirmed in investigation report |

Plus durable rules from memory:

- Scoping respected: only `/var/www/kynexedu` + `/var/www/kynex/{Dockerfile,docker-compose.yml,docker/cert/}` + `/var/www/kynex/.env` (already mounted, just appending vars).
- No school-side domain UI: all Filament additions are in `app/Filament/SaasAdmin/...`; no new school-side files.
- Ephemeral aqmdigital conf: §4.4 restores it via the new code path immediately after recreate, using "Reissue Cert".

---

## 12. End of Sub-phase 2

Awaiting user review of this design doc. After approval, Sub-phase 3
begins: write the Laravel migration, Job, Service edits, Filament
action additions, config file, and env additions. Single commit.
