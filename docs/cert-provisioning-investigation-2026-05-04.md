# Cert Provisioning — Sub-phase 1 Investigation Report

**Date:** 2026-05-04
**Workstream:** Phase 1.5 — Automated TLS for custom school domains
**Scope:** Read-only investigation. No code, infra, or compose changes were made.

This report captures the *as-found* state of the systems involved, the gaps
between today's manual workflow and the approved Q5/Q6 design, and the
concrete decisions that Sub-phase 2 will need to lock in.

A handful of new findings — not previously documented — are flagged with
**FINDING** so they can be discussed before being baked into the design doc.

---

## 1. kynex-app Dockerfile (`/var/www/kynex/Dockerfile`)

Image: `php:8.4-fpm`. Single-stage build. Composer 2 copied from upstream.
The whole `/var/www/kynex` repo is `COPY . /var/www`'d into the image.

### What is already installed (apt)

```
libpq-dev libzip-dev libpng-dev libjpeg-dev libfreetype6-dev libicu-dev
unzip git supervisor nginx cron
```

PHP extensions: `pdo_pgsql pgsql zip gd bcmath opcache pcntl posix intl`
plus `redis` via PECL.

Probed via `docker exec kynex-app`:

| Tool        | Status            | Note                            |
|-------------|-------------------|---------------------------------|
| `nginx`     | installed (1.26.3)| reverse-proxy front door        |
| `supervisord` | installed       | already running 4 programs      |
| `cron`      | installed         | **not started** by supervisor   |
| `php` 8.4   | installed         | CLI available for the listener  |
| `python3`   | installed         | available, not currently used   |
| `certbot`   | **NOT installed** | ← Phase 1.5 must add            |

### Already-running supervisord programs (`docker/supervisor.conf`)

`php-fpm`, `nginx`, `kynex-horizon`, `kynex-queue` (numprocs=4).
The cron daemon is **not** under supervisord — needs adding for renewals.

### Entrypoint (`docker/entrypoint.sh`)

Wait for postgres → wait for redis → ensure storage dirs → `php artisan
migrate --force` → `php artisan optimize` → fix permissions → `exec
supervisord`. No cert-related tasks.

### What Phase 1.5 must add to the Dockerfile

1. `certbot` package (`apt-get install -y certbot`). No nginx plugin needed
   — we'll use webroot.
2. The `provision-cert.sh` script (COPY into `/usr/local/bin/`).
3. The HTTP listener source (one PHP file, COPY into `/usr/local/bin/`).
4. A supervisord program block for the listener (`php -S 0.0.0.0:9090 ...`).
5. A supervisord program block for `cron -f` (so the renewal cron runs).
6. `/etc/cron.d/certbot-renew` cron file.
7. Per-domain nginx template (COPY into `/usr/local/share/cert-listener/`).

These are all additions; nothing existing is removed or modified.

---

## 2. kynex-app docker-compose (`/var/www/kynex/docker-compose.yml`)

```yaml
services:
  app:
    image: kynex-app
    container_name: kynex-app
    restart: unless-stopped
    ports: [80:80, 443:443]
    networks: [kynex]                       # → docker network kynex_kynex
    volumes:
      - app-storage:/var/www/storage
      - ./.env:/var/www/.env
      - /etc/letsencrypt:/etc/letsencrypt:ro    # ← read-only — see §2a
      - ./certbot/www:/var/www/certbot/www      # ← path mismatch — see §2b
    depends_on: [postgres (healthy), redis (healthy)]
```

Networks (verified via `docker network ls` + `docker inspect`):

| Container       | Networks                                              |
|-----------------|-------------------------------------------------------|
| `kynex-app`     | `kynex_kynex` only                                    |
| `kynexedu-app`  | `kynex_kynex` AND `kynexedu_kynexedu_internal`        |

`kynexedu-app` can reach `kynex-app` over `kynex_kynex` at the hostname
`kynex-app` (and IP 172.18.0.3). Restart policy is `unless-stopped`.

### 2a. **FINDING — `/etc/letsencrypt` is mounted read-only**

```
$ docker exec kynex-app sh -c 'mount | grep letsencrypt'
/dev/sda1 on /etc/letsencrypt type ext4 (ro,relatime)

$ docker exec kynex-app sh -c 'touch /etc/letsencrypt/_test'
touch: cannot touch '/etc/letsencrypt/_test': Read-only file system
```

This blocks the entire design point #3c (`certbot certonly` inside the
container). To run certbot in-container we must change `:ro` → `:rw` (or
just drop the `:ro` flag) on the `/etc/letsencrypt` mount.

This is a one-line `docker-compose.yml` change. Combined with the Dockerfile
additions, it requires a kynex-app rebuild + recreate (already authorised
in Sub-phase 5).

### 2b. **FINDING — webroot path divergence between host certbot and in-container nginx**

The currently-deployed setup splits the ACME webroot across two paths:

| Side                              | Path                                       | Source of truth        |
|-----------------------------------|--------------------------------------------|------------------------|
| Host certbot (`renewal/*.conf`)   | `/var/www/kynex/docker/certbot/www`        | exists; writable by host |
| Compose bind-mount source         | `./certbot/www` → `/var/www/kynex/certbot/www` | does not exist on host (docker auto-creates empty mountpoint) |
| In-container nginx `root` for `.well-known/acme-challenge/` | `/var/www/certbot/www`                     | bind-mount target inside container |

Concretely, the host-side certbot writes the HTTP-01 challenge into
`/var/www/kynex/docker/certbot/www/.well-known/acme-challenge/<token>`,
but the container's nginx reads challenges from a *different* host
directory. As of today none of the three live certs is due for renewal
(`sms` 2026-07-27, `aqm` 2026-07-31, `ai` 2026-07-23 — verified in
`/var/log/letsencrypt/letsencrypt.log`), so the divergence has not yet
broken anything — but webroot-style host renewals **will** silently fail
the next time `sms` or `aqm` is due.

Phase 1.5 should converge on a single canonical webroot. Recommendation
for Sub-phase 2:

> Make the in-container path `/var/www/certbot/www` the single canonical
> webroot. Mount it from a host directory we control (e.g.
> `./certbot/www` if we create the dir, or a named volume). The in-container
> certbot writes its challenges there; the in-container nginx's existing
> `.well-known/acme-challenge/` blocks already serve from there. New
> renewal configs written by Phase 1.5 will reference the in-container
> path and renewals will succeed end-to-end.

### 2c. **FINDING — DNS alias collision on `kynex_kynex`**

Both containers register the alias `app` on the shared network:

```
$ docker exec kynex-app sh -c 'getent hosts app'
172.18.0.3      app          # resolves to kynex-app itself
```

`app` is therefore ambiguous. Phase 1.5 code must always use the
unambiguous hostname `kynex-app` (which is also a registered alias)
when calling the listener from `kynexedu-app`. **Never `app`.**

---

## 3. Existing nginx config (`docker/nginx.conf`, mounted as `/etc/nginx/sites-enabled/kynex.conf`)

Four server blocks (matching memory's labelling, with one correction):

| # | listen        | server_name                        | role                                  |
|---|---------------|------------------------------------|---------------------------------------|
| a | 80            | sms.kynexsolutions.com             | HTTP→HTTPS + ACME `.well-known`       |
| b | 80 default_server | ai.kynexsolutions.com `_`      | HTTP→HTTPS catch-all + ACME `.well-known` |
| c | 443 ssl       | sms.kynexsolutions.com             | proxies to `kynexedu-app:80`          |
| d | 443 ssl default_server | ai.kynexsolutions.com `_`  | local PHP-FPM (the AI app — **SACRED**) |

There is **no separate snakeoil HTTPS catch-all**. Block (d) handles both
`ai.kynexsolutions.com` and any unknown HTTPS request via SNI mismatch
(browsers see the AI app's cert; if accepted, they reach the AI app's
docroot). Memory line 12 of `project_kynexedu_topology.md` should be
corrected on this point.

### Glob-include is in place

```
$ docker exec kynex-app cat /etc/nginx/nginx.conf | grep include
    include /etc/nginx/sites-enabled/*;
```

So per-domain `custom-<domain>.conf` files dropped into
`/etc/nginx/sites-enabled/` are picked up by `nginx -s reload` without
editing `kynex.conf`. Confirmed by the existing
`custom-aqmdigital.com.conf` (live in the writable layer).

### Existing `custom-aqmdigital.com.conf` (template ground truth)

```
server {
    listen 443 ssl;
    listen [::]:443 ssl;
    server_name aqmdigital.com;

    ssl_certificate     /etc/letsencrypt/live/aqmdigital.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/aqmdigital.com/privkey.pem;
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

This block is the de-facto template. Phase 1.5's per-domain template will
be a verbatim copy of this with `aqmdigital.com` replaced by the new
domain. **There is no HTTP→HTTPS redirect block per-domain** in the
existing file — block (b)'s catch-all handles `_`, so any HTTP request
gets a 301 to HTTPS. The same will hold for new custom domains.

> Open question for Sub-phase 2: do we want an explicit per-domain HTTP
> server block too (mirroring block (a) for sms)? Pros: cleaner logs, the
> ACME challenge is on a known-good path. Cons: more code, more state
> to clean up on remove. The catch-all (b) already covers ACME for *any*
> hostname pointing here, so I lean **no — keep per-domain config to one
> HTTPS block only**. To be confirmed.

---

## 4. Existing certbot ground truth

### Host-side certbot (`/usr/bin/certbot 2.9.0`)

Three live certs, all on the *production* Let's Encrypt API
(`https://acme-v02.api.letsencrypt.org/directory`), all `key_type = ecdsa`,
all using the same account `1b1cc581fbd055c7cd7628453417e179`.

| Domain                     | Authenticator | webroot_path                            | Issued     | Expires    |
|----------------------------|---------------|-----------------------------------------|------------|------------|
| `sms.kynexsolutions.com`   | webroot       | `/var/www/kynex/docker/certbot/www`     | 2026-04-28 | 2026-07-27 |
| `aqmdigital.com`           | webroot       | `/var/www/kynex/docker/certbot/www`     | 2026-05-02 | 2026-07-31 |
| `ai.kynexsolutions.com`    | nginx (plugin) | n/a                                    | 2026-04-24 | 2026-07-23 |

Renewal: `certbot.timer` (systemd) — `Active: active (waiting) since
2026-04-28 12:45:51`, fires twice daily, last run today 14:57 UTC,
"No renewals were attempted" (none due).

The `ai.kynexsolutions.com` cert uses certbot's **nginx plugin** rather
than webroot. That makes it special: it can only be renewed by a certbot
that has access to the in-container nginx config — i.e. it has to run
*inside* kynex-app, OR continue to be renewed by host certbot if/when
host certbot can reach the container's nginx (which it currently cannot).
Today nothing is renewing `ai.kynexsolutions.com` end-to-end; it just
hasn't expired yet. **Out of scope for Phase 1.5** but worth flagging
as latent risk.

### Email/contact

Not stored in the renewal `.conf` files. Likely captured at issuance time
under `~/.config/letsencrypt` on host. Sub-phase 2 should explicitly set
`--email <addr>` and `--agree-tos` in `provision-cert.sh` so we don't
depend on cached host config. Suggest: `ops@kynexsolutions.com` —
**confirm with user before locking in**.

### Canonical certbot invocation Phase 1.5 will use

```
certbot certonly \
    --webroot --webroot-path /var/www/certbot/www \
    --domain <DOMAIN> \
    --email ops@kynexsolutions.com \
    --agree-tos --no-eff-email \
    --key-type ecdsa \
    --keep-until-expiring \
    --non-interactive \
    --server https://acme-v02.api.letsencrypt.org/directory   # or staging
```

`--keep-until-expiring` makes the call idempotent (constraint **g**).
For Sub-phase 5 testing we'll point `--server` at LE *staging*
(`https://acme-staging-v02.api.letsencrypt.org/directory`) to avoid
chewing into the prod rate-limit budget.

---

## 5. Laravel side — current state

### `app/Services/CustomDomainService.php` (Phase 15C.3)

Public methods today:

- `initiateVerification(Tenant $tenant, string $customDomain): Domain`
- `verifyDomain(Domain $domain): bool` — flips `is_verified`, clears
  token. **Does NOT trigger any cert provisioning.**
- `removeDomain(Domain $domain): void` — currently `$domain->delete()`
  only. **No nginx cleanup, no `certbot delete`.**
- `getVerificationInstructions(Domain $domain): array`

Private helpers: `sanitizeDomain`, `validateDomainFormat`,
`ensureDomainAvailable`. Central-domain blocklist already wired.

### `app/Console/Commands/VerifyPendingCustomDomains.php` (Phase 15C.5)

**Signature:** `kynex:verify-pending-domains` (note: prompt called this
`kynex:verify-pending-custom-domains` — the actual signature is shorter;
will keep the existing one).

Wired in `routes/console.php:244` via `Schedule::command(...)`.

Per-iteration: calls `$service->verifyDomain($domain)`. On success
notifies SCHOOL_ADMIN role inside `$tenant->run(...)`. **No cert job
dispatch yet.**

### `app/Filament/SaasAdmin/Resources/TenantResource/RelationManagers/DomainsRelationManager.php` (Phase 15C.4)

Existing actions:

- **headerActions:** `addCustomDomain` (form + DNS instructions notification)
- **per-row actions:** `verifyNow` (visible if !verified && custom),
  `showInstructions` (visible if !verified && custom),
  `removeDomain` (visible if !primary)

Existing columns: `domain`, `domain_type` (badge), `is_verified` (icon),
`is_primary` (icon), `verified_at`, `created_at`.

Phase 1.5 additions: `cert_status` column (badge), `cert_expires_at`
column, plus two row actions: `provisionCertNow` and `reissueCertificate`
(the latter visible only to verified rows).

### Domains table — current schema

Migration history:

1. `2019_09_15_000020_create_domains_table.php` (Stancl stock):
   `id, domain (unique), tenant_id, timestamps`.
2. `2026_04_06_000001_add_custom_domain_fields_to_domains_table.php`
   (Phase 15C.1): adds `is_primary`, `is_verified`, `verification_token`,
   `verified_at`, `domain_type`. Back-fills existing subdomain rows.

Phase 1.5 migration (Sub-phase 3) will add five more columns:

```
cert_status         varchar(20) default 'pending' nullable
cert_issued_at      timestamp nullable
cert_expires_at     timestamp nullable
cert_last_error     text nullable
cert_attempt_count  unsigned integer default 0
```

Allowed `cert_status` values:
`pending|issuing|issued|failed|rate_limited|dns_mismatch`.

### Confirmed absent

- **`ProvisionCustomDomainCertificate` job class** does not exist.
  `find app -iname '*ProvisionCustomDomain*'` returned nothing.
- **No env vars** matching `CERT_*` or `LISTENER_*` in `.env.example`.
- **`kynexedu` app has no host `.env` file.** Env is loaded by
  `docker-compose.prod.yml` from `.env.production` (and there's also
  `.env.docker.example` for documentation). New env additions for
  Phase 1.5 must land in `.env.production` AND `.env.docker.example`.

---

## 6. Listener runtime — recommendation

### Constraints (re-stated for the decision)

- Bind to docker network only (NOT host-exposed)
- Authenticate every request with a shared secret
- Run as separate process (NOT embedded in nginx)
- Supervised, restarts on crash
- Clear logs

### Options considered

| Option | Pros | Cons |
|--------|------|------|
| **A. PHP CLI built-in server + single index.php** | PHP 8.4 already in image. One file, ~80 LOC. Easy for the user (PHP shop) to read/audit. Process supervised easily by adding one supervisord block. | Single-threaded; not "production grade" — fine here because we expect ≤ a few requests per day. |
| B. Python http.server + handler | Python 3 already in image. | More verbose handler code; another language in the operational surface. |
| C. Go binary | Tiny, statically linked, robust. | Requires multi-stage Docker build with go toolchain. Adds significant build complexity and a new toolchain to keep up to date. Overkill for ~1 request per onboarding. |

### Recommendation: **Option A (PHP)**

Single file at `/usr/local/bin/cert-listener.php`. Listens on
`0.0.0.0:9090` (port not in compose `ports:` → not host-exposed → only
reachable from `kynex_kynex` network). Validates a constant-time
comparison of the `X-Cert-Listener-Secret` header against
`getenv('SHARED_CERT_LISTENER_SECRET')`. On match, dispatches to one of
two endpoints:

- `POST /provision` body `{"domain":"..."}` → execs `provision-cert.sh
  <domain>`, returns `{"status":"issued|failed|...","stderr":"...","exit_code":N}`
- `POST /remove` body `{"domain":"..."}` → execs cleanup (rm conf file,
  reload nginx, `certbot delete --cert-name`)

Supervised by adding to `docker/supervisor.conf`:

```ini
[program:cert-listener]
command=php -S 0.0.0.0:9090 /usr/local/bin/cert-listener.php
autostart=true
autorestart=true
stdout_logfile=/dev/stdout
stdout_logfile_maxbytes=0
stderr_logfile=/dev/stderr
stderr_logfile_maxbytes=0
```

Logs go to stdout/stderr → captured by docker logs (consistent with
existing programs).

Laravel side calls via Guzzle:

```
POST http://kynex-app:9090/provision
X-Cert-Listener-Secret: <secret>
```

---

## 7. Renewal cron — what to add

`/etc/cron.d/certbot-renew` (baked into image):

```
SHELL=/bin/bash
PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin

# Run twice daily at offset minutes per LE recommendation.
17  3 * * * root /usr/bin/certbot renew --quiet --post-hook "/usr/sbin/nginx -s reload" >> /var/log/certbot-renew.log 2>&1
17 15 * * * root /usr/bin/certbot renew --quiet --post-hook "/usr/sbin/nginx -s reload" >> /var/log/certbot-renew.log 2>&1
```

Plus a supervisord block to run `cron -f`:

```ini
[program:cron]
command=cron -f
autostart=true
autorestart=true
stdout_logfile=/dev/stdout
stderr_logfile=/dev/stderr
```

### Belt-and-suspenders inside Laravel

`VerifyPendingCustomDomains` should — in addition to its current verify
loop — re-dispatch `ProvisionCustomDomainCertificate` for any verified
domain whose `cert_expires_at < now()->addDays(14)` OR whose
`cert_status IN ('failed','rate_limited','dns_mismatch')`. Pulls in the
recovery path automatically.

### Coexistence with host `certbot.timer`

The host's `certbot.timer` will continue to exist after Phase 1.5 ships.
Both certbot processes (host + in-container) will *try* to renew the
same `/etc/letsencrypt` set. Certbot uses file-based locking, so they
won't race destructively, but it does mean log output appears in two
places.

For the existing 3 certs, host renewal will keep working only if §2b's
path divergence is resolved (which Phase 1.5 implicitly does, by
standardising the in-container webroot — but only for *new* certs
written by Phase 1.5 with new renewal configs). The existing 3 renewal
configs still reference the host-side path. Options:

- (1) Leave host `certbot.timer` enabled; let it renew the existing 3
      via the host webroot (which won't reach the container nginx —
      BROKEN today regardless of our work).
- (2) Disable host `certbot.timer` in Sub-phase 6 and migrate the 3
      existing renewal `.conf` files' webroot path to
      `/var/www/certbot/www`, so in-container certbot handles them too.
      Higher risk: if we mis-edit, the AI app's cert stops renewing
      silently.

Recommendation: **defer this question to Sub-phase 6**. Sub-phase 5
proves in-container renewal for *new* domains works; Sub-phase 6
optionally migrates the existing 3 to the same mechanism after we have
operational confidence.

---

## 8. Shared secret — placement

Symmetric secret read by both sides, never in git.

- **Laravel side**: env var `SHARED_CERT_LISTENER_SECRET` in
  `.env.production`. New row in `.env.docker.example` showing the var
  name + placeholder. Read in `config/services.php` (or a new
  `config/cert.php`) and dereferenced via `config('cert.listener_secret')`.
- **kynex-app side**: env var `SHARED_CERT_LISTENER_SECRET` in
  `/var/www/kynex/.env` (already mounted into kynex-app via compose
  `./.env:/var/www/.env`). Read directly via `getenv()` in
  `cert-listener.php`.

Initial value generation: `openssl rand -hex 32` (64 hex chars). Manually
copy into both `.env` files at Sub-phase 5 cutover. Rotation strategy
for later: write the new value to *both* env files in the same operation,
then `docker exec kynex-app supervisorctl restart cert-listener`. Brief
gap (~1s) but no provisioning is in flight at any given moment, so
acceptable. Document this in Sub-phase 6's onboarding doc.

Also-needed Laravel env: `CERT_LISTENER_URL=http://kynex-app:9090`.

---

## 9. DNS pre-check — server IPs

Phase 1.5's `provision-cert.sh` step (b) refuses to call certbot if the
domain's DNS doesn't resolve to this server. The server has both:

- IPv4: `178.104.180.160`
- IPv6: `2a01:4f8:c014:4657::1`

Pre-check should accept a domain whose DNS resolves to **at least one**
of those (A → v4 OR AAAA → v6). This avoids false negatives for IPv6-only
records or IPv4-only records.

Implementation sketch (in `provision-cert.sh`):

```bash
EXPECTED_V4="178.104.180.160"
EXPECTED_V6="2a01:4f8:c014:4657::1"
A_RECORDS=$(dig +short A "$DOMAIN" 2>/dev/null)
AAAA_RECORDS=$(dig +short AAAA "$DOMAIN" 2>/dev/null)
if ! echo "$A_RECORDS" | grep -qx "$EXPECTED_V4" \
   && ! echo "$AAAA_RECORDS" | grep -qx "$EXPECTED_V6"; then
    exit_with_status dns_mismatch
fi
```

`dig` requires `dnsutils` apt package. **Add `dnsutils` to the Dockerfile
apt list.**

---

## 10. Sub-phase 1 deliverables — open questions for Sub-phase 2

These need user decision before Sub-phase 2's design doc:

1. **Email for `--email` flag in certbot.** Suggested:
   `ops@kynexsolutions.com`. Confirm or supply alternative.
2. **`/etc/letsencrypt` mount: flip `:ro` → `:rw`** — confirm OK to
   change `/var/www/kynex/docker-compose.yml`. Single-line diff. (User
   has authorised Dockerfile + recreate; the compose change is in the
   same blast radius.)
3. **Per-domain HTTP server block: include or skip?** Recommendation:
   **skip** (catch-all (b) already handles ACME for any host pointing
   here). Confirm.
4. **Existing 3 certs (`sms`, `aqm`, `ai`) — migrate renewal to
   in-container or leave host certbot.timer in charge?** Recommendation:
   **defer to Sub-phase 6**. Confirm.
5. **Listener port — `:9090`?** Or another internal port? `:9090` is
   unused on `kynex_kynex` today (kynex-app uses :9000 internally for
   PHP-FPM but it's not network-exposed). Confirm.
6. **Initial secret rotation cadence** — none required, manual rotate
   as needed? Confirm.

---

## 11. Sub-phase 2 design doc will contain

(For reference; not produced yet.)

- Laravel: file paths for new Migration, Job, Service edits, Filament
  action additions, env var additions in `.env.production` and
  `.env.docker.example`, `config/cert.php` (or `services.php` extension).
- kynex-app: `/var/www/kynex/Dockerfile` diff (ADD certbot, dnsutils,
  COPY listener + script, COPY cron file), `docker/supervisor.conf`
  diff (ADD `cron` and `cert-listener` programs),
  `docker/docker-compose.yml` diff (flip `:ro` → `:rw`),
  full source of `provision-cert.sh`, full source of
  `cert-listener.php`, the per-domain nginx template, the cron file.
- Sequence diagrams: happy path, DNS mismatch, rate-limit, nginx-test
  failure, renewal failure.
- Test plan for Sub-phases 3, 4, 5 (using LE staging).
- Documentation placeholder for Sub-phase 6's `scripts/onboard-school.md`.

---

## 12. Constraints check (back-reference to user prompt)

| Constraint | Status |
|------------|--------|
| (a) blocks (a)-(e) of `kynex.conf` not modified | ✓ — only additions via per-domain `custom-<domain>.conf` files |
| (b) `ai.kynexsolutions.com` block sacred | ✓ — not touched. Note `ai`'s own renewal is latently broken regardless of our work. |
| (c) `/var/www/kynex/Dockerfile` editable | ✓ — additions only, will diff in Sub-phase 2 |
| (d) kynex-app rebuild + ~30s downtime authorised | ✓ — happens in Sub-phase 5 |
| (e) `/var/www/kynexedu` primary scope | ✓ |
| (f) listener: docker-network-only, secret-auth, separate process, supervised, logged | ✓ — design above satisfies |
| (g) `provision-cert.sh` idempotent | ✓ — `--keep-until-expiring` + safe nginx-block write |
| (h) no secrets in git | ✓ — env vars only |
| (i) no `docker compose build`/recreate yet | ✓ — Sub-phase 1 was strictly read-only |

Plus the durable rules from memory:

- **Scoping & safety:** kept inside `/var/www/kynexedu` for Laravel +
  `/var/www/kynex/{Dockerfile,docker-compose.yml,docker/}` for the
  kynex-app additions. Nothing else touched.
- **No school-side domain UI:** all Phase 1.5 Filament additions go in
  `app/Filament/SaasAdmin/...`. Nothing in `app/Filament/SchoolAdmin/`.
- **Ephemeral aqmdigital conf:** the existing
  `custom-aqmdigital.com.conf` will be wiped by the kynex-app recreate
  in Sub-phase 5. Mitigations to capture in Sub-phase 5's plan:
  (1) re-dispatch the provisioning job for `aqmdigital.com` after
  recreate to re-create the conf from the new template, OR
  (2) `docker cp` the existing conf out before recreate and back in
  after. Recommendation: **(1)**, since it exercises the new code path
  and proves it works on a real domain.

---

## 13. End of Sub-phase 1

No code, no infra changes were made. Awaiting user review of this
report and answers to §10 before producing the Sub-phase 2 design doc
at `docs/cert-provisioning-design-2026-05-04.md`.
