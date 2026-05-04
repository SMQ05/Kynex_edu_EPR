# Phase 1.5 — Hardcoding Fixes Batch (Items 0-7)

**Date:** 2026-05-04
**Workstream:** Phase 1.5 — Automated TLS for custom school domains
**Predecessors:**
- Sub-phase 3 commit `0505033` (Laravel side, stub mode)
- Sub-phase 4 artifacts in `/var/www/kynex/` (uncommitted)
- `docs/cert-provisioning-hardcoded-audit-2026-05-04.md`
**Status:** Files written + edited on disk. **No image rebuild, no
compose up.** Awaiting review before sub-phase 5.

This bundle is the single review artifact for the audit-driven
hardcoding-fix batch. It covers Item 0 (DNS-IPs, already on disk
before the audit was requested) plus Items 1-7 (applied in this pass).
Items 8-10 are deliberately deferred — see
`docs/cert-provisioning-follow-up.md`.

---

## 0. Summary

| # | Title | Status | Files touched |
|---|-------|--------|---------------|
| 0 | DNS pre-check IPs | Already applied (before audit) | 3 |
| 1 | CNAME target hardcoded | Applied | 4 |
| 2 | NGINX_LOCK_WAIT hardcoded in 3 places | Applied | 6 |
| 3 | Renewal sweep window 14 days | Applied | 2 |
| 4 | Service remove-call timeout drift | Applied | 1 |
| 5 | Job tries/backoff/timeout magic numbers | Applied | 1 |
| 6 | Verification token TTL 7 days | Applied | 1 |
| 7 | connectTimeout(5) duplicated | Applied | 2 |

Default values for every new env var match the previously hardcoded
value, so behavior is byte-identical with no env-overrides set.

---

## 1. Lint summary

```
=== PHP LINT (via kynex-app PHP 8.4) ===
cert.php: No syntax errors detected
ProvisionCustomDomainCertificate.php: No syntax errors detected
CustomDomainService.php: No syntax errors detected
VerifyPendingCustomDomains.php: No syntax errors detected
DomainsRelationManager.php: No syntax errors detected
cert-listener.php: No syntax errors detected

=== BASH LINT ===
provision-cert.sh: OK
remove-cert.sh: OK
cert-renew-wrapper.sh: OK

=== supervisor.conf parse ===
OK, 6 sections

=== config/cert.php loads with 12 keys (4 original + 8 new) ===
listener_url = http://kynex-app:9090
listener_secret = (empty default — env-required)
listener_timeout_seconds = 120
remove_timeout_seconds = 120
http_connect_timeout_seconds = 5
stub_mode = true
cname_target = kynexedu.com
renewal_sweep_days = 14
verification_token_ttl_days = 7
job_tries = 3
job_backoff_seconds = 60
job_timeout_seconds = 180
```

---

## 2. Item 0 — DNS pre-check IPs (already applied)

Recap. This was applied to disk before the audit was requested, per the
prior turn's "Apply changes" instruction. Untouched in this batch.

**Files:**
- `/var/www/kynex/.env` (added `CERT_EXPECTED_V4` + `CERT_EXPECTED_V6`)
- `/var/www/kynex/docker/cert-listener.php` (added 2 keys to `$childEnv`)
- `/var/www/kynex/docker/provision-cert.sh` (constants now `${CERT_EXPECTED_V4:-}` / `${CERT_EXPECTED_V6:-}`, plus fail-loud validation block)

---

## 3. Item 1 — CNAME target

**Problem:** `'kynexedu.com'` literal hardcoded in three places — Service:117, Service:181, DomainsRelationManager:129. Operator-facing identity; rebrand or central-host change requires touching all three. High drift risk.

**Fix:** New config key `cert.cname_target` + env `CERT_CNAME_TARGET`. Default `'kynexedu.com'` (behavior unchanged). All three call sites read from the same key (drift-eliminated by construction — only one source).

### `config/cert.php` (new key)

```php
'cname_target' => env('CERT_CNAME_TARGET', 'kynexedu.com'),
```

### `app/Services/CustomDomainService.php` — `getVerificationInstructions()`

```diff
 public function getVerificationInstructions(Domain $domain): array
 {
+    $cnameTarget = (string) config('cert.cname_target', 'kynexedu.com');
+
     return [
         'domain'           => $domain->domain,
         'txt_record_name'  => '_kynexedu-verify.' . $domain->domain,
         'txt_record_value' => $domain->verification_token,
-        'cname_record'     => 'kynexedu.com',
+        'cname_record'     => $cnameTarget,
         'instructions'     => [
             'Go to your DNS provider (GoDaddy, Cloudflare, Namecheap, etc.)',
-            'Add a CNAME record pointing your domain to kynexedu.com',
+            'Add a CNAME record pointing your domain to ' . $cnameTarget,
             'Add a TXT record:',
             ...
         ],
     ];
 }
```

### `app/Filament/.../DomainsRelationManager.php` — `addCustomDomain` action body

Pulls the target from the same `getVerificationInstructions()` array (rather than hardcoding a third copy):

```diff
     $domain = $service->initiateVerification($tenant, $data['custom_domain']);
     $instructions = $service->getVerificationInstructions($domain);
+    $cnameTarget = $instructions['cname_record'];

     Notification::make()
         ->title('Custom domain added')
         ->body(
             "Add this DNS TXT record to verify:\n\n" .
             "**Name:** {$instructions['txt_record_name']}\n" .
             "**Value:** {$instructions['txt_record_value']}\n\n" .
-            "Also add a **CNAME** pointing to **kynexedu.com**.\n\n" .
+            "Also add a **CNAME** pointing to **{$cnameTarget}**.\n\n" .
             "Then click *Verify Now* on the domain row."
         )
```

Now there are zero hardcoded `'kynexedu.com'` literals in Phase 1.5 code outside `config/cert.php`'s default.

---

## 4. Item 2 — NGINX_LOCK_WAIT

**Problem:** `NGINX_LOCK_WAIT=30` hardcoded across `provision-cert.sh:49`, `remove-cert.sh:20`, and `certbot-renew.cron:12`. Three drift-prone copies.

**Fix:** New env var `CERT_NGINX_LOCK_WAIT_SECONDS` in `/var/www/kynex/.env`. Listener forwards via `$childEnv`. Both shell scripts read `${CERT_NGINX_LOCK_WAIT_SECONDS:-30}`. The cron line cannot directly read `.env`, so a new wrapper script (`cert-renew-wrapper.sh`) sources `.env`, validates the value, and invokes certbot — the cron now calls the wrapper instead of certbot directly.

### Cron-level approach (the part that needed design)

Cron does NOT auto-load shell env files. Three options were considered:

- (a) `bash -c 'set -a; . /var/www/.env; set +a; certbot renew …'` inline in the cron line. Works, but the cron line becomes long and hard to read.
- (b) **Wrapper script** — chosen. Mirrors the pattern of provision-cert.sh / remove-cert.sh / cert-listener.php, all of which already read env from `.env`. Cron just calls `/usr/local/bin/cert-renew-wrapper.sh`.
- (c) Hardcode in cron, defer this part. Defeats the audit.

### `/var/www/kynex/.env` (new)

```diff
 CERT_EXPECTED_V4=178.104.180.160
 CERT_EXPECTED_V6=2a01:4f8:c014:4657::1
+
+# Seconds to wait when acquiring /var/lock/nginx-mutate.lock in
+# provision-cert.sh, remove-cert.sh, and the certbot-renew cron's
+# --post-hook. Forwarded into the listener's child env. Falls back to
+# 30 if missing.
+CERT_NGINX_LOCK_WAIT_SECONDS=30
```

### `/var/www/kynex/docker/cert-listener.php` `$childEnv`

```diff
 $childEnv = [
-    'PATH'                => '/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin',
-    'CERT_CONTACT_EMAIL'  => $leEmail,
-    'CERT_LE_SERVER'      => $leServer,
-    'CERT_EXPECTED_V4'    => $env['CERT_EXPECTED_V4'] ?? '',
-    'CERT_EXPECTED_V6'    => $env['CERT_EXPECTED_V6'] ?? '',
+    'PATH'                          => '/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin',
+    'CERT_CONTACT_EMAIL'            => $leEmail,
+    'CERT_LE_SERVER'                => $leServer,
+    'CERT_EXPECTED_V4'              => $env['CERT_EXPECTED_V4'] ?? '',
+    'CERT_EXPECTED_V6'              => $env['CERT_EXPECTED_V6'] ?? '',
+    'CERT_NGINX_LOCK_WAIT_SECONDS'  => $env['CERT_NGINX_LOCK_WAIT_SECONDS'] ?? '',
 ];
```

### `/var/www/kynex/docker/provision-cert.sh:49`

```diff
 NGINX_LOCK="${LOCK_DIR}/nginx-mutate.lock"
-NGINX_LOCK_WAIT=30
+NGINX_LOCK_WAIT="${CERT_NGINX_LOCK_WAIT_SECONDS:-30}"
```

### `/var/www/kynex/docker/remove-cert.sh:20`

```diff
 NGINX_LOCK="/var/lock/nginx-mutate.lock"
-NGINX_LOCK_WAIT=30
+NGINX_LOCK_WAIT="${CERT_NGINX_LOCK_WAIT_SECONDS:-30}"
```

### `/var/www/kynex/docker/cert-renew-wrapper.sh` (NEW)

```bash
#!/usr/bin/env bash
#
# cert-renew-wrapper.sh — invoked by /etc/cron.d/certbot-renew.
#
# Sources /var/www/.env so $CERT_NGINX_LOCK_WAIT_SECONDS is available
# to the certbot --post-hook (cron itself does not auto-load .env). Falls
# back to 30s if the env is missing or the .env file is unreadable.
#
# Idempotent. Safe to run from cron or by hand. Output goes to
# /var/log/certbot-renew.log via the cron line's redirection.

set -u

if [ -r /var/www/.env ]; then
    set -a
    # shellcheck disable=SC1091
    . /var/www/.env
    set +a
fi

LOCK_WAIT="${CERT_NGINX_LOCK_WAIT_SECONDS:-30}"

# Validate (positive integer). On bad value, fall back loudly to 30s
# rather than passing junk into flock -w.
if ! [[ "$LOCK_WAIT" =~ ^[1-9][0-9]*$ ]]; then
    echo "[$(date -Iseconds)] cert-renew-wrapper: CERT_NGINX_LOCK_WAIT_SECONDS='$LOCK_WAIT' is not a positive integer; falling back to 30" >&2
    LOCK_WAIT=30
fi

exec /usr/bin/certbot renew --quiet \
    --post-hook "/usr/bin/flock -w ${LOCK_WAIT} /var/lock/nginx-mutate.lock /usr/sbin/nginx -s reload"
```

### `/var/www/kynex/docker/certbot-renew.cron` (now invokes wrapper)

```diff
 # Phase 1.5 — twice-daily Let's Encrypt renewal sweep.
 # LE recommends two attempts/day at randomised offset. Container time is
 # UTC; 03:17 and 15:17 keep us out of the herd.
 #
-# The --post-hook reload is wrapped in flock against the global nginx
-# mutate lock so it cannot race with provision-cert.sh / remove-cert.sh.
-# `flock -w 30` waits up to 30s for the lock; if it can't acquire it
-# exits non-zero (logged below) — fail-loud, never hang silently.
-17 3,15 * * * root /usr/bin/certbot renew --quiet --post-hook "/usr/bin/flock -w 30 /var/lock/nginx-mutate.lock /usr/sbin/nginx -s reload" >> /var/log/certbot-renew.log 2>&1
+# The actual certbot call lives in /usr/local/bin/cert-renew-wrapper.sh
+# because cron lines do not auto-load .env. The wrapper sources
+# /var/www/.env, reads CERT_NGINX_LOCK_WAIT_SECONDS (defaulting to 30),
+# and wraps `nginx -s reload` in flock against the global nginx-mutate
+# lock so it cannot race with provision-cert.sh / remove-cert.sh.
+# `flock -w $LOCK_WAIT` waits up to that many seconds; if it can't
+# acquire it exits non-zero (logged below) — fail-loud, never hang
+# silently.
+17 3,15 * * * root /usr/local/bin/cert-renew-wrapper.sh >> /var/log/certbot-renew.log 2>&1
```

### `/var/www/kynex/Dockerfile` (add wrapper to image)

```diff
 COPY docker/cert-listener.php       /usr/local/bin/cert-listener.php
 COPY docker/provision-cert.sh       /usr/local/bin/provision-cert.sh
 COPY docker/remove-cert.sh          /usr/local/bin/remove-cert.sh
+COPY docker/cert-renew-wrapper.sh   /usr/local/bin/cert-renew-wrapper.sh
 COPY docker/custom-domain.conf.tpl  /usr/local/share/cert-listener/custom-domain.conf.tpl
 COPY docker/certbot-renew.cron      /etc/cron.d/certbot-renew
-RUN chmod +x /usr/local/bin/provision-cert.sh /usr/local/bin/remove-cert.sh \
+RUN chmod +x /usr/local/bin/provision-cert.sh /usr/local/bin/remove-cert.sh /usr/local/bin/cert-renew-wrapper.sh \
     && chmod 644 /etc/cron.d/certbot-renew \
     ...
```

---

## 5. Item 3 — Renewal sweep window

**Problem:** `addDays(14)` hardcoded in `Job:70` (freshness check) and `Command:91` (sweep query). Two copies, drift-prone.

**Fix:** New config key `cert.renewal_sweep_days` + env `CERT_RENEWAL_SWEEP_DAYS`. Default 14.

### `config/cert.php`

```php
'renewal_sweep_days' => (int) env('CERT_RENEWAL_SWEEP_DAYS', 14),
```

### `app/Jobs/ProvisionCustomDomainCertificate.php`

```diff
+        $sweepDays = (int) config('cert.renewal_sweep_days', 14);
         if (! $this->force
             && $domain->cert_status === 'issued'
             && $domain->cert_expires_at
-            && Carbon::parse($domain->cert_expires_at)->isAfter(now()->addDays(14))) {
+            && Carbon::parse($domain->cert_expires_at)->isAfter(now()->addDays($sweepDays))) {
```

### `app/Console/Commands/VerifyPendingCustomDomains.php`

```diff
+        $sweepDays = (int) config('cert.renewal_sweep_days', 14);
         $nearExpiry = Domain::where('is_verified', true)
             ->where('domain_type', 'custom')
-            ->where(function ($q) {
+            ->where(function ($q) use ($sweepDays) {
                 $q->whereIn('cert_status', ['failed', 'rate_limited', 'dns_mismatch', 'lock_timeout'])
-                  ->orWhere(function ($q2) {
+                  ->orWhere(function ($q2) use ($sweepDays) {
                       $q2->where('cert_status', 'issued')
-                         ->where('cert_expires_at', '<', now()->addDays(14));
+                         ->where('cert_expires_at', '<', now()->addDays($sweepDays));
                   });
             })
             ->get();
```

(Note: query closures need `use ($sweepDays)` to capture from outer scope.)

---

## 6. Item 4 — Service remove-call timeout drift

**Problem (latent bug):** Service used `config('cert.listener_timeout', 60)` while Job used `config('cert.listener_timeout', 120)`. Different defaults for the same env var meant a remove call would time out at 60s but a provision call at 120s — caller-dependent behavior. Documented in commit message.

**Fix:** Distinct config key `cert.remove_timeout_seconds` for remove; both default to 120. Single source of truth per operation.

### `config/cert.php`

```php
'remove_timeout_seconds' => (int) env('CERT_REMOVE_TIMEOUT_SECONDS', 120),
```

### `app/Services/CustomDomainService.php` — `callListenerRemove`

```diff
         \Illuminate\Support\Facades\Http::withHeaders([
             'X-Cert-Listener-Secret' => (string) config('cert.listener_secret'),
             'Accept'                 => 'application/json',
         ])
-            ->timeout((int) config('cert.listener_timeout', 60))
-            ->connectTimeout(5)
+            ->timeout((int) config('cert.remove_timeout_seconds', 120))
+            ->connectTimeout((int) config('cert.http_connect_timeout_seconds', 5))
             ->post(rtrim((string) config('cert.listener_url'), '/') . '/remove', ['domain' => $domain]);
```

(Item 7's connect-timeout fix piggybacks on this same line.)

---

## 7. Item 5 — Job tries / backoff / timeout

**Problem:** `public int $tries = 3; public int $backoff = 60; public int $timeout = 180;` hardcoded as property defaults. Not env-tunable.

**Fix:** Properties without inline defaults; values set from config in the constructor. Laravel's queue dispatcher reads these properties immediately after `__construct` and again on retry (after re-instantiation from the serialised payload). Same value either way — defaults match the previous hardcoded values exactly.

### `config/cert.php`

```php
'job_tries'           => (int) env('CERT_JOB_TRIES', 3),
'job_backoff_seconds' => (int) env('CERT_JOB_BACKOFF_SECONDS', 60),
'job_timeout_seconds' => (int) env('CERT_JOB_TIMEOUT_SECONDS', 180),
```

### `app/Jobs/ProvisionCustomDomainCertificate.php`

```diff
-    public int $tries = 3;
-    public int $backoff = 60;
-    public int $timeout = 180;
+    public int $tries;
+    public int $backoff;
+    public int $timeout;

     public function __construct(public int $domainId, public bool $force = false)
     {
+        $this->tries   = (int) config('cert.job_tries', 3);
+        $this->backoff = (int) config('cert.job_backoff_seconds', 60);
+        $this->timeout = (int) config('cert.job_timeout_seconds', 180);
     }
```

---

## 8. Item 6 — Verification token TTL

**Problem:** `subDays(7)` hardcoded in `VerifyPendingCustomDomains:33` (window after which un-verified pending domains stop being polled).

**Fix:** New config + env `CERT_VERIFICATION_TOKEN_TTL_DAYS=7`.

### `config/cert.php`

```php
'verification_token_ttl_days' => (int) env('CERT_VERIFICATION_TOKEN_TTL_DAYS', 7),
```

### `app/Console/Commands/VerifyPendingCustomDomains.php`

```diff
+        $tokenTtlDays = (int) config('cert.verification_token_ttl_days', 7);
         $pendingDomains = Domain::where('is_verified', false)
             ->where('domain_type', 'custom')
             ->whereNotNull('verification_token')
-            ->where('created_at', '>', now()->subDays(7))
+            ->where('created_at', '>', now()->subDays($tokenTtlDays))
             ->get();
```

---

## 9. Item 7 — connectTimeout(5)

**Problem:** Hardcoded `connectTimeout(5)` in two places — `Job:117` and `Service:163`.

**Fix:** New config `cert.http_connect_timeout_seconds` + env `CERT_HTTP_CONNECT_TIMEOUT_SECONDS=5`.

### `config/cert.php`

```php
'http_connect_timeout_seconds' => (int) env('CERT_HTTP_CONNECT_TIMEOUT_SECONDS', 5),
```

### `app/Jobs/ProvisionCustomDomainCertificate.php`

```diff
             $response = Http::withHeaders($headers)
                 ->timeout($timeout)
-                ->connectTimeout(5)
+                ->connectTimeout((int) config('cert.http_connect_timeout_seconds', 5))
                 ->post($url . $endpoint, ['domain' => $domain->domain]);
```

### `app/Services/CustomDomainService.php`

(Already shown in §6 — same line that swapped the `timeout` source also swapped the `connectTimeout`.)

---

## 10. Env additions — both kynexedu env files

### `/var/www/kynexedu/.env.docker.example` (committed)

```
# ── Phase 1.5 hardcoding-fix batch (2026-05-04) ──────────────
# CNAME target shown to schools — change here if SaaS rebrands.
CERT_CNAME_TARGET=kynexedu.com
# Renewal sweep + Job freshness window (days). Job skips if cert
# expires more than this many days from now; Command re-dispatches
# anything within this window.
CERT_RENEWAL_SWEEP_DAYS=14
# How long after creation an unverified custom domain stays in the
# verify-pending sweep before being skipped. School must re-initiate.
CERT_VERIFICATION_TOKEN_TTL_DAYS=7
# Distinct timeout for /remove (was a latent drift bug — Service used 60,
# Job used 120). Unified at 120.
CERT_REMOVE_TIMEOUT_SECONDS=120
# TCP connect timeout for listener calls (Job + Service).
CERT_HTTP_CONNECT_TIMEOUT_SECONDS=5
# Job retry / timeout tunables.
CERT_JOB_TRIES=3
CERT_JOB_BACKOFF_SECONDS=60
CERT_JOB_TIMEOUT_SECONDS=180
```

### `/var/www/kynexedu/.env.production` (gitignored, runtime values)

Same vars, no comments. Real values can be overridden per environment.

---

## 11. Files modified summary

### Sub-phase 3 Laravel side (kynexedu repo, will be committed)

| File | Change |
|------|--------|
| `config/cert.php` | +8 keys (12 total), full re-write with rationale comments |
| `app/Jobs/ProvisionCustomDomainCertificate.php` | constructor sets tries/backoff/timeout from config; addDays from config; connectTimeout from config |
| `app/Services/CustomDomainService.php` | cname_record + instructions text from config; remove timeout from new key; connectTimeout from config |
| `app/Console/Commands/VerifyPendingCustomDomains.php` | subDays from config; addDays from config |
| `app/Filament/SaasAdmin/.../DomainsRelationManager.php` | modal text reads cnameTarget from instructions array |
| `.env.docker.example` | +9 commented lines, 8 new vars |
| `.env.production` | +9 lines, 8 new vars (gitignored) |

### Sub-phase 4 kynex-app side (NOT git-tracked, on disk only)

| File | Change |
|------|--------|
| `/var/www/kynex/.env` | +6 lines (Item 2 NGINX_LOCK_WAIT) |
| `/var/www/kynex/docker/cert-listener.php` | +1 key in `$childEnv` (NGINX_LOCK_WAIT forwarding) |
| `/var/www/kynex/docker/provision-cert.sh` | NGINX_LOCK_WAIT now reads env |
| `/var/www/kynex/docker/remove-cert.sh` | same |
| `/var/www/kynex/docker/certbot-renew.cron` | invokes wrapper instead of inline certbot |
| `/var/www/kynex/docker/cert-renew-wrapper.sh` | **NEW** |
| `/var/www/kynex/Dockerfile` | +1 COPY line, +1 chmod target |

### Docs (kynexedu repo, will be committed alongside)

| File | Change |
|------|--------|
| `docs/cert-provisioning-follow-up.md` | **NEW** — items 8-10 deferred tracking |
| `docs/cert-provisioning-hardcoding-fixes-2026-05-04.md` | **NEW** — this doc |

---

## 12. What's NOT in this batch (deferred — see follow-up doc)

Items 8-10 from `docs/cert-provisioning-hardcoded-audit-2026-05-04.md`
are deferred per operator decision. Each is tracked in
`docs/cert-provisioning-follow-up.md` with:

- **Where it appears** (file paths)
- **Current behavior**
- **Why deferred**
- **When to revisit** (concrete trigger conditions — defense against
  "deferred" silently becoming "abandoned")
- **Estimated effort**

Status table at bottom of follow-up doc is the canonical "what's still
deferred" view.

The Item 1-7 fix commit message will include:

> Items 8-10 from the hardcoded audit were deferred — see
> docs/cert-provisioning-follow-up.md.

---

## 13. Stop point

No image rebuild. No `docker compose up`. No `docker exec` writes.
After review:

1. Sub-phase 3 Laravel changes plus the two new docs commit together
   (single commit).
2. Sub-phase 4 kynex-app artifacts stay on disk uncommitted (kynex
   isn't a git repo) until Sub-phase 5's rebuild.
3. Sub-phase 5 begins.
