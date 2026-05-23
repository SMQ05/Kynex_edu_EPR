# Deploying KynexEdu ERP on Coolify (v4.x) — First-Timer's Guide

Written against the **actual Coolify v4 UI**. Field names below match what you
see on screen. Where a label might differ slightly by version, it's flagged with
"⌖ on your screen".

> ⚠️ **If a field on your screen doesn't match this guide, tell me and I'll fix
> the guide.** This is meant to be exact, not approximate.

> ℹ️ This supersedes the older [`COOLIFY_SETUP.md`](COOLIFY_SETUP.md). That file is
> still correct in outline but skips the two things that bite first-timers most:
> **wildcard SSL** and the **`central_domains` code dependency**. Both are covered
> here.

---

## The single most important rule

KynexEdu is **one Docker image** run as **three services** that share **one
Postgres database**, and it is **multi-tenant** (one app instance serves the
central portal *and* every school's subdomain, each on its own database):

```
your VPS (Coolify)
├── 🗄️  Postgres                        ← central DB + one DB per school tenant
├── 🌐 app        (Apache, port 80)     ← serves the portal + every *.edu subdomain
├── ⚙️  queue      (queue:work)          ← background jobs (billing, approvals, syncs)
└── ⏰ scheduler  (schedule:run loop)   ← cron tasks (invoices, demo reset, cert sweep)
```

All three services run **the same image** (the single `Dockerfile` at the repo
root). They differ only in their **start command** and one env flag. Because they
are the same app wearing three hats, they **must share three things or it breaks**:

1. **The same `APP_KEY`** — or you get `DecryptException` and random logouts (the
   key signs sessions and encrypts queued payloads).
2. **The same database credentials** — all three talk to the one Postgres.
3. **The same `storage/` volume** — uploaded files (logos, student docs, the
   `public` disk symlink) must be visible to all three.

> ## ✅ Deploy order is fixed: **Postgres (healthy) → app → queue + scheduler**
> The `app` service runs the database migrations and seeders on first boot; the
> queue and scheduler must not start until the schema exists. If you use the
> bundled Compose file (recommended below), Coolify enforces this for you via
> `depends_on` + healthchecks.

### The multi-tenant part you cannot skip

Schools are reached at **`{slug}.edu.kynexsolutions.com`** (e.g.
`demo.edu.kynexsolutions.com`). New schools are created **at runtime** from the
SaaS admin panel — you do **not** know their subdomains in advance. That forces
two requirements that a normal single-site deploy doesn't have:

- **Wildcard DNS**: `*.edu.kynexsolutions.com → VPS IP` (Part 2).
- **Wildcard SSL**: a `*.edu.kynexsolutions.com` certificate, which Let's Encrypt
  can only issue via a **DNS challenge** (Part 5). This is the single trickiest
  step — read Part 5 before you launch schools.

> ❗ **Code dependency, not just Coolify config:** the host you serve on **must be
> listed in [`config/tenancy.php`](../config/tenancy.php) → `central_domains`**.
> It currently contains `kynexedu.com`, `kynexsolutions.com`, `sms.kynexsolutions.com`,
> and `edu.kynexsolutions.com`. If you deploy on a different apex, **add it there,
> commit, and redeploy** — otherwise the portal host is mistaken for a tenant slug
> and every page 404s with "School not found." This guide assumes
> `edu.kynexsolutions.com`.

---

## How Coolify is laid out (so the steps make sense)

You create **Resources** inside a **Project → Environment**. We'll use
**`KynexEdu ERP → production`** as the example breadcrumb. You create resources
with the **`+ New`** button there.

There are **two ways** to deploy KynexEdu, and this guide leads with the first:

- **A. Docker Compose (recommended)** — paste one Compose file; Coolify builds the
  image once and runs `app` + `queue` + `scheduler` + `db` as a single stack on a
  private network, with the shared volume and deploy-ordering handled for you. The
  repo ships [`docker-compose.coolify.yml`](../docker-compose.coolify.yml) for
  exactly this.
- **B. Four separate resources (advanced)** — a Coolify-managed Postgres plus three
  Application resources from the same repo. More moving parts; only pick this if
  you specifically want Coolify's managed-database backups/UI. Covered in
  [Appendix B](#appendix-b--four-separate-resources-advanced).

A Docker Compose resource has these **tabs across the top**:

`Configuration` · `Deployments` · `Logs` · `Terminal` · `Links` · **`Deploy`** (button)

Inside **Configuration**, the left sub-menu you'll use:

- **General** — repo, branch, build settings, the Compose file
- **Environment Variables** — secrets and config (the values the Compose file reads)
- **Persistent Storage** — volumes (already declared in the Compose file)
- **Domains** (per service) — where you attach the portal + wildcard domain
- **Webhooks** — auto-deploy URL for `git push`
- **Danger Zone** — delete

---

# Part 1 — Before you touch Coolify

## 1.1 Generate your secrets (do this on your laptop now)

You'll paste these into Coolify in Part 3. Generate them once and keep them.

```bash
# Laravel app key — signs sessions, encrypts queued jobs. ALL THREE services
# must share this exact value. Generate ONCE; changing it logs everyone out.
php artisan key:generate --show
# → base64:xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx=

# If you don't have PHP handy, this is equivalent:
echo "base64:$(openssl rand -base64 32)"

# Postgres password — used by the DB and by all three app services.
openssl rand -hex 24
```

> 🔑 The `APP_KEY` is the #1 cause of "it deploys but everyone's logged out / jobs
> fail to decrypt." Generate it **here**, paste the **same** value for the whole
> stack, and never rotate it casually.

## 1.2 DNS — you need a wildcard record

KynexEdu serves every school on a subdomain, created at runtime. So a single A
record isn't enough. At your DNS provider, create:

| Type | Name | Value | Purpose |
|---|---|---|---|
| `A` | `edu` | *your VPS IP* | the central portal `edu.kynexsolutions.com` |
| `A` | `*.edu` | *your VPS IP* | every school subdomain `*.edu.kynexsolutions.com` |

Verify before continuing:

```bash
dig +short edu.kynexsolutions.com          # → VPS IP
dig +short demo.edu.kynexsolutions.com     # → VPS IP (proves the wildcard works)
```

> If you intend to issue a **wildcard SSL cert** (you do — Part 5), your DNS
> provider also needs an **API token** so Let's Encrypt can solve the DNS
> challenge. Cloudflare is the easiest. Grab that token now if you can.

## 1.3 Confirm your domain is a "central domain" in code

Open [`config/tenancy.php`](../config/tenancy.php). The `central_domains` array
**must** contain the apex you're deploying on:

```php
'central_domains' => [
    '127.0.0.1',
    'localhost',
    'kynexedu.com',
    'kynexsolutions.com',
    'sms.kynexsolutions.com',
    'edu.kynexsolutions.com',   // ← the portal host this guide uses
],
```

If your portal host isn't there, add it, commit, and push **before** you deploy.
This is read by the tenant-resolution middleware
([`InitializeTenancyBySubdomainOrDomain`](../app/Http/Middleware/InitializeTenancyBySubdomainOrDomain.php)):
a host *in* the list is treated as the central portal; a host *ending in* `.{central}`
is treated as a school slug; anything else falls through. Get this wrong and the
portal itself is read as a (non-existent) tenant.

---

# Part 2 — Create the Docker Compose resource

## 2.1 New resource

1. In **`KynexEdu ERP → production`**, click **`+ New`**.
2. Choose **Docker Compose** (⌖ may read **"Docker Compose Empty"** or
   **"Based on a Git repository"** — pick the **Git repository** option so Coolify
   builds your image from the repo).
3. Connect the repo:
   - **Public Repository**: paste the repo URL.
   - or **Private Repository (GitHub App)** if you connected one.
4. **Branch:** `main`. Continue. Coolify drops you on the **General** page.

## 2.2 General — point Coolify at the Compose file

| Field (⌖ on your screen) | Set to | Notes |
|---|---|---|
| **Base Directory** | `/` | Build context = repo root. The Dockerfile copies workspace files from root. |
| **Docker Compose Location** | `/docker-compose.coolify.yml` | ❗ Not the default `/docker-compose.yml` (that one is for local dev). |

Coolify will read the Compose file and discover four services: `app`, `queue`,
`scheduler`, `db`. **Save.** Don't deploy yet — env vars first.

> ⌖ Some versions show a **"Docker Compose Raw"** editor instead of a file path.
> If so, paste the contents of
> [`docker-compose.coolify.yml`](../docker-compose.coolify.yml) verbatim. The file
> already wires the shared volume, the private network, the healthcheck, and the
> three start commands — don't hand-edit it.

## 2.3 What the Compose file already does for you (so you don't re-add it)

You do **not** need to configure these in the UI — they're baked into
[`docker-compose.coolify.yml`](../docker-compose.coolify.yml):

- **One build, three roles.** `app` runs Apache (`apache2-foreground`), `queue`
  runs `start-queue` (`php artisan queue:work`), `scheduler` runs `start-scheduler`
  (a `schedule:run` loop). All from `image: kynexedu-erp:coolify`.
- **Migrations/seed on first boot.** Only `app` has `RUN_MIGRATIONS_AND_SEED:
  "true"`; `queue` and `scheduler` are `"false"`. (See [§4.1](#41-what-happens-on-first-boot).)
- **Shared storage.** All three mount `kynexedu_storage:/var/www/html/storage`.
- **Private DB network.** `db` is **not** published to the internet; the app
  reaches it at host `db:5432` on `kynexedu_internal`.
- **Healthcheck.** `app` is healthy when `curl http://localhost/up` returns 200
  (Laravel's [health route](../bootstrap/app.php)).
- **Deploy ordering.** `queue`/`scheduler` wait for `app`; everything waits for
  `db` to be healthy.

---

# Part 3 — Environment Variables

Open **Configuration → Environment Variables**. The Compose file reads these by
name (e.g. `${APP_KEY}`, `${POSTGRES_PASSWORD}`), so you set the **source** values
here and Coolify injects them into all the right services. Add each row (there's a
**+ Add** button; many versions have a **Developer view** to paste them all at once).
Turn on the **lock/secret** toggle for anything marked 🔒.

## 3.1 Required

| Key | Value | Lock | Notes |
|---|---|---|---|
| `APP_KEY` | *(the `base64:…` from §1.1)* | 🔒 | Shared by all three services — that's the point. |
| `APP_URL` | `https://edu.kynexsolutions.com` | — | The portal's own address. |
| `APP_ENV` | `production` | — | |
| `APP_DEBUG` | `false` | — | Never `true` in production. |
| `POSTGRES_PASSWORD` | *(the password from §1.1)* | 🔒 | Used by the `db` container **and** the app. |
| `DB_PASSWORD` | *(same as `POSTGRES_PASSWORD`)* | 🔒 | Must match exactly. |
| `TENANCY_DB_PASSWORD` | *(same as `POSTGRES_PASSWORD`)* | 🔒 | Must match — used when creating tenant DBs. |
| `SAAS_ADMIN_EMAIL` | `admin@kynexedu.com` | — | Seeds the first super-admin login. |
| `SAAS_ADMIN_PASSWORD` | *(a strong password)* | 🔒 | You log into `/saas` with this. Change it after first login. |
| `SESSION_SECURE_COOKIE` | `true` | — | Cookies HTTPS-only (you're behind Coolify's TLS proxy). |

> The Compose file defaults `POSTGRES_DB=kynexedu_central` and
> `POSTGRES_USER=kynexedu`. Leave those unless you have a reason to change them; if
> you do, set `POSTGRES_DB` / `POSTGRES_USER` here too and they'll flow through to
> `DB_DATABASE` / `DB_USERNAME` automatically.

## 3.2 Recommended

| Key | Value | Notes |
|---|---|---|
| `QUEUE_CONNECTION` | `database` | ❗ Default in the Compose file. Leave it `database` so the **queue** container actually processes jobs. If you set it to `sync`, jobs run inline in web requests and the queue container does nothing — only do that deliberately. |
| `MAIL_MAILER` | `resend` | Transactional email (onboarding, notifications). |
| `RESEND_API_KEY` | `re_…` | 🔒 Required if `MAIL_MAILER=resend`. Get it at resend.com. |
| `MAIL_FROM_ADDRESS` | `noreply@kynexsolutions.com` | Must be a domain verified in Resend. |
| `RUN_MIGRATIONS_AND_SEED` | `true` for the first deploy | See [§4.1](#41-what-happens-on-first-boot) — flip to `false` after the first successful boot. |

## 3.3 Optional integrations (safe to leave unset)

These are **fallback** values — for most of them the live config is managed in the
SaaS Admin Panel → **Settings → API Settings** (stored in the DB), so you usually
don't set them here at all. Set them only if you want a boot-time default:

| Key | What it enables |
|---|---|
| `SMS_GATEWAY_ENABLED` / `SMS_GATEWAY_URL` / `SMS_GATEWAY_LOGIN` / `SMS_GATEWAY_PASSWORD` | Android SMS Gateway (capcom6) |
| `WHATSAPP_ENABLED` / `WHATSAPP_EVOLUTION_URL` / `WHATSAPP_EVOLUTION_TOKEN` / `WHATSAPP_EVOLUTION_INSTANCE` | WhatsApp via self-hosted Evolution API |
| `AI_ENABLED` / `OPENROUTER_API_KEY` / `OPENROUTER_DEFAULT_MODEL` | AI features via OpenRouter 🔒 |
| `JAZZCASH_*` / `EASYPAISA_*` | Pakistani payment gateways 🔒 |
| `CERT_*` (see [§5.3](#53-optional-per-school-custom-domains)) | Per-school *custom* domain SSL provisioning |

> Generate any secret value with `openssl rand -hex 32`. Lock 🔒 every credential.

---

# Part 4 — Deploy and first boot

## 4.1 What happens on first boot

Click **Deploy** (top right). On the **first** run the `app` container's entrypoint
([`docker/bootstrap.sh`](../docker/bootstrap.sh)) will:

1. Wait until Postgres accepts connections.
2. Generate an `APP_KEY` **only if one isn't set** (you set it, so it won't).
3. `php artisan migrate --force` — creates the **central** schema.
4. `php artisan db:seed --force` — seeds RBAC permissions, plans, and the
   **super-admin** from `SAAS_ADMIN_EMAIL` / `SAAS_ADMIN_PASSWORD`.
5. `php artisan kynex:ensure-dev-demo` and `php artisan storage:link`.

This is gated by `RUN_MIGRATIONS_AND_SEED=true` (set only on `app`). The first
build takes several minutes (it installs Composer deps, builds Vite assets, and
installs Playwright/Chromium for the audit crawler).

> 🔁 **After the first successful boot, set `RUN_MIGRATIONS_AND_SEED=false` and
> redeploy.** Migrations are idempotent, but re-running the seeders on every boot
> is wasteful and can re-insert demo data. Leave it `false` for normal operation;
> flip it back to `true` only when a release adds central migrations (or run them
> manually — see [§6.2](#62-running-migrations-by-hand)).

Watch progress on the **Deployments** tab (build + boot logs) and the **Logs** tab
(runtime). The `app` service goes green once `/up` returns 200.

## 4.2 Attach the domains (the central portal)

Once the stack is up, give the `app` service its public hostname.

1. In the Compose resource, find the **`app`** service (⌖ Coolify lists each
   service; there's a **Domains** field per service, or a **`SERVICE_FQDN_APP`**
   magic env).
2. Set the domain to:
   ```
   https://edu.kynexsolutions.com
   ```
3. **Save**, then **Redeploy** (or **Restart**) so Traefik picks up the route.

> Do **not** put a domain on `queue`, `scheduler`, or `db` — they have no web
> listener. Only `app` is web-facing.

> ⌖ For Docker Compose resources, Coolify routes Traefik to the service's exposed
> port (the Compose file exposes `80`). If your version asks for a port alongside
> the domain, it's **80**.

## 4.3 Verify the central portal

```bash
curl -I https://edu.kynexsolutions.com/up      # → HTTP/2 200
```

Then in a browser:

- **`https://edu.kynexsolutions.com/`** — the public landing/portal loads with a
  valid padlock.
- **`https://edu.kynexsolutions.com/saas/login`** — the **SaaS super-admin** login.
  Sign in with `SAAS_ADMIN_EMAIL` / `SAAS_ADMIN_PASSWORD`. ✅ Portal is live.

> The three Filament panels live at fixed paths:
> `/saas` (super-admin, **central** host only), `/admin` (school admin, **tenant
> subdomain**), `/parent` (parent portal, **tenant subdomain**).

---

# Part 5 — Wildcard SSL (the trickiest step — read before launching schools)

The portal at `edu.kynexsolutions.com` got its certificate automatically (Let's
Encrypt HTTP-01). **School subdomains are different.** They're created at runtime,
so you can't list each one in Coolify, and **Let's Encrypt's HTTP-01 challenge
cannot issue a `*.domain` wildcard certificate.** You have two honest options.

## 5.1 Option A — Wildcard cert via DNS challenge (recommended for production)

A wildcard cert (`*.edu.kynexsolutions.com`) requires Let's Encrypt's **DNS-01**
challenge, which means giving Traefik an **API token** for your DNS provider so it
can create the validation TXT record.

1. Create a scoped DNS API token at your provider (Cloudflare: a token with
   **Zone → DNS → Edit** on that zone).
2. In Coolify, configure the DNS challenge for the wildcard. ⌖ In v4 this is set on
   the **app service's Domains** as a wildcard plus a DNS-challenge resolver, or via
   Traefik environment for the proxy (e.g. `CF_DNS_API_TOKEN`). Add the domain:
   ```
   https://*.edu.kynexsolutions.com
   ```
   alongside the existing `https://edu.kynexsolutions.com`.
3. Redeploy. Traefik requests `*.edu.kynexsolutions.com` from Let's Encrypt over
   DNS-01 — one cert that covers **every** school subdomain, present and future.

> ⌖ Coolify's exact wildcard/DNS-challenge UI moves between v4 minor versions. If
> you don't see a DNS-challenge option on the service, tell me your exact version
> and what the **Domains** / **proxy** settings show, and I'll give you the precise
> clicks. The principle is fixed: **wildcard ⇒ DNS-01 ⇒ a provider API token.**

## 5.2 Option B — Per-subdomain certs (fine for a handful of schools)

Skip wildcards: each time you onboard a school, add its concrete domain
(`school1.edu.kynexsolutions.com`) to the `app` service's **Domains** list and
redeploy. Traefik then mints a normal per-host cert via HTTP-01 (no DNS token
needed). Works, but it's manual and doesn't scale to many schools — and a school's
first visitor before you've added the domain will hit a TLS warning. Use Option A
once you have more than a few tenants.

## 5.3 (Optional) Per-school *custom* domains

Schools can map their **own** domain (e.g. `portal.greenfield.edu.pk`) to their
tenant. That's a separate feature with its own certificate-provisioning listener,
configured by the `CERT_*` env vars and documented in
[`docs/custom-domains-ssl.md`](custom-domains-ssl.md). It's off by default
(`CERT_STUB_MODE`) — leave it alone until you actually need custom domains.

---

# Part 6 — Create your first school (tenant)

With the portal live and wildcard SSL in place:

1. Log into **`https://edu.kynexsolutions.com/saas/login`** as super-admin.
2. Create a new **School / Tenant**. Give it a slug, e.g. `demo`. KynexEdu
   **automatically creates and migrates a dedicated database** for it
   (`kynexedu_tenant_demo`). Give it ~10–30 seconds.
3. Visit **`https://demo.edu.kynexsolutions.com/admin`** → the school-admin panel
   loads over HTTPS with no "School not found" and no decryption error. ✅

> ❗ **The DB role must be allowed to create databases.** Tenant onboarding runs
> `CREATE DATABASE kynexedu_tenant_…`. In the bundled Compose stack the
> `POSTGRES_USER` owns the cluster, so it can. If you ever point KynexEdu at an
> externally managed Postgres, the role you use **must have the `CREATEDB`
> privilege** or tenant creation fails.

## 6.2 Running migrations by hand

When a release adds **central** migrations and you keep `RUN_MIGRATIONS_AND_SEED=false`,
apply them from the `app` service's **Terminal** tab:

```bash
cd /var/www/html
php artisan migrate --force          # central schema
php artisan tenants:migrate --force  # all tenant databases
```

`tenants:migrate` loops every tenant and applies the tenant migrations
(`database/migrations/tenant`). Run it after any release that changes tenant tables.

---

# Part 7 — Auto-deploy on `git push`

1. Compose resource → **Webhooks** → copy the **deploy webhook URL**.
2. Add it to your Git provider (GitHub: repo → Settings → Webhooks → `push` event),
   **or** connect the GitHub App source, which auto-deploys without a manual webhook.
3. Push to `main` → Coolify rebuilds the image and recreates `app`, `queue`,
   `scheduler` together (they share the image, so they stay in lockstep).

---

## Environment variables — full reference

Set on the **Compose resource** (the file maps them into the right services):

| Key | Example | Lock | Notes |
|---|---|---|---|
| `APP_KEY` | `base64:…` (`php artisan key:generate --show`) | 🔒 | Shared across app/queue/scheduler. Generate once. |
| `APP_URL` | `https://edu.kynexsolutions.com` | — | Portal host. Must be in `central_domains`. |
| `APP_ENV` | `production` | — | |
| `APP_DEBUG` | `false` | — | |
| `POSTGRES_PASSWORD` | `…` | 🔒 | DB container password. |
| `DB_PASSWORD` | *(= `POSTGRES_PASSWORD`)* | 🔒 | App → DB. Must match. |
| `TENANCY_DB_PASSWORD` | *(= `POSTGRES_PASSWORD`)* | 🔒 | Tenant DB creation. Must match. |
| `POSTGRES_DB` | `kynexedu_central` | — | Default; change only deliberately. |
| `POSTGRES_USER` | `kynexedu` | — | Default. Needs `CREATEDB`. |
| `SAAS_ADMIN_EMAIL` | `admin@kynexedu.com` | — | First super-admin. |
| `SAAS_ADMIN_PASSWORD` | `…` | 🔒 | Change after first login. |
| `SESSION_SECURE_COOKIE` | `true` | — | HTTPS-only cookies. |
| `QUEUE_CONNECTION` | `database` | — | Keep `database` so the queue container works. |
| `RUN_MIGRATIONS_AND_SEED` | `true` (first deploy) → `false` | — | Only the `app` service consumes it. |
| `MAIL_MAILER` | `resend` | — | |
| `RESEND_API_KEY` | `re_…` | 🔒 | If using Resend. |
| `MAIL_FROM_ADDRESS` | `noreply@kynexsolutions.com` | — | Resend-verified domain. |
| `TENANCY_DB_PREFIX` | `kynexedu_tenant_` | — | Default tenant DB name prefix. |

---

## Go-live checklist

- [ ] `central_domains` in `config/tenancy.php` includes your portal host (committed)
- [ ] DNS: `A edu → VPS IP` **and** `A *.edu → VPS IP` (both resolve via `dig`)
- [ ] `APP_KEY` + `POSTGRES_PASSWORD` generated and saved
- [ ] Compose resource: **Base Dir `/`**, **Compose file `/docker-compose.coolify.yml`**
- [ ] Env vars set: `APP_KEY`, `APP_URL`, `POSTGRES_PASSWORD`, `DB_PASSWORD`, `TENANCY_DB_PASSWORD`, `SAAS_ADMIN_*`, `SESSION_SECURE_COOKIE=true`, `QUEUE_CONNECTION=database`
- [ ] First deploy with `RUN_MIGRATIONS_AND_SEED=true`; `app` goes green; `/up` = 200
- [ ] Domain `https://edu.kynexsolutions.com` attached to the **`app`** service
- [ ] `/saas/login` works with the seeded super-admin
- [ ] Wildcard SSL for `*.edu.kynexsolutions.com` (Part 5)
- [ ] Created a test school; `https://demo.edu.kynexsolutions.com/admin` loads w/ SSL
- [ ] Flipped `RUN_MIGRATIONS_AND_SEED=false` and redeployed
- [ ] Queue + scheduler containers green (check **Logs**)
- [ ] Postgres backups scheduled

---

## Troubleshooting

**Build fails immediately / "no such file" copying `composer.json`**
Base Directory isn't `/`. The Dockerfile builds from the repo root. Set **Base
Directory = `/`** and redeploy.

**Coolify deployed `docker-compose.yml` (dev) instead of the prod stack**
Set **Docker Compose Location = `/docker-compose.coolify.yml`**. The plain
`docker-compose.yml` is for local development.

**Portal 404s with "School not found" on every page**
Your portal host isn't in `central_domains` (so it's read as a tenant slug). Add it
to [`config/tenancy.php`](../config/tenancy.php), commit, redeploy. See [§1.3](#13-confirm-your-domain-is-a-central-domain-in-code).

**Everyone gets logged out / `DecryptException` / queued jobs fail to decrypt**
The three services don't share one `APP_KEY`. Set a single `APP_KEY` env var on the
Compose resource (the file injects it into all three) and redeploy. Don't let the
entrypoint auto-generate it per-container.

**`app` won't go healthy / `ECONNREFUSED` to the DB**
The `app` reaches Postgres at host `db:5432` on the internal network. Confirm the
`db` service is **green** first (check its **Logs**), and that `DB_PASSWORD` /
`TENANCY_DB_PASSWORD` / `POSTGRES_PASSWORD` are **identical**.

**School subdomains show a TLS warning**
Wildcard SSL isn't issued. HTTP-01 can't make a `*.domain` cert — set up the
**DNS-01 challenge** (Part 5.1) or add each subdomain explicitly (Part 5.2).

**Creating a school fails with a permission/`CREATE DATABASE` error**
The Postgres role can't create databases. The bundled stack's `POSTGRES_USER` can;
an external Postgres role needs the **`CREATEDB`** privilege. See [§6](#part-6--create-your-first-school-tenant).

**Background jobs (billing, approvals) never run**
Either `QUEUE_CONNECTION` isn't `database` (so the queue container idles), or the
**queue** service isn't running. Check **queue** Logs; set `QUEUE_CONNECTION=database`.

**Scheduled tasks (invoices, demo reset, cert sweep) don't fire**
The **scheduler** service isn't running. It loops `php artisan schedule:run` every
60s — check its **Logs**. Tasks live in [`routes/console.php`](../routes/console.php).

**Uploaded files / logos vanish after redeploy**
The `storage` volume isn't shared or wasn't persisted. The Compose file mounts
`kynexedu_storage:/var/www/html/storage` on all three services — confirm it under
**Persistent Storage** and that you didn't override the mount.

**"You are not allowed…" / CSRF / mixed-content over HTTPS**
Set `SESSION_SECURE_COOKIE=true` and `APP_URL=https://…` and redeploy. Coolify's
Traefik terminates TLS and forwards `X-Forwarded-Proto`; the app needs `APP_URL` on
`https` to build correct, same-origin URLs.

**Where are the logs?** Each resource: **Deployments** tab = build/boot logs,
**Logs** tab = runtime (pick the service: `app`, `queue`, `scheduler`, or `db`).
**Terminal** tab = a shell in the chosen container.

---

## Day-2 operations

- **Deploy code changes:** push to `main` (auto-deploy via webhook/GitHub App), or
  click **Deploy**. All three app services rebuild from one image and stay in sync.
- **Apply new migrations:** with `RUN_MIGRATIONS_AND_SEED=false`, run them from the
  `app` Terminal — `php artisan migrate --force` then `php artisan tenants:migrate
  --force` ([§6.2](#62-running-migrations-by-hand)).
- **Onboard a school:** SaaS panel → create tenant; its DB is auto-created and
  migrated; reach it at `{slug}.edu.kynexsolutions.com/admin`.
- **Backups:** back up Postgres regularly. The bundled `db` is a Compose service —
  schedule `pg_dump` (or switch to [Appendix B](#appendix-b--four-separate-resources-advanced)
  to use Coolify's managed-database backups). Also back up the `kynexedu_storage`
  volume if uploaded files matter.
- **Rotate the super-admin password:** log into `/saas`, change it, then update
  `SAAS_ADMIN_PASSWORD` so a future re-seed doesn't reset it.

---

## Notes

- **Why one image, three services?** `app` serves HTTP, `queue` drains the job
  table, `scheduler` runs cron. Same code, three start commands
  ([`docker/start-container.sh`](../docker/start-container.sh),
  [`start-queue.sh`](../docker/start-queue.sh),
  [`start-scheduler.sh`](../docker/start-scheduler.sh)). Splitting them lets long
  jobs and cron run without blocking web requests.
- **PostgreSQL 16** is bundled (`postgres:16-alpine`). Major-version upgrades later
  need `pg_dump`/restore, not just an image-tag bump.
- **PgBouncer:** optional and **not** required. If you add it, use **session**
  pooling — KynexEdu switches databases per request (tenant-per-DB), which
  transaction pooling breaks. See [`docs/pgbouncer-setup.md`](pgbouncer-setup.md).
- **Sizing:** 2 GB / 1 vCPU runs the portal + a few tenants; the image bundles
  Playwright/Chromium (for the audit crawler), so give it **4 GB** if you'll run
  audits or host many active schools.
- **SSL:** automatic for the portal (HTTP-01). Wildcard for schools needs DNS-01 —
  the one manual SSL step (Part 5).

---

## Appendix B — Four separate resources (advanced)

Prefer Coolify's managed Postgres (backups UI, point-in-time) over the bundled
`db`? Run four resources instead of the Compose stack. All three app resources use
the **same repo**, **Build Pack = `Dockerfile`**, **Dockerfile Location =
`/Dockerfile`**, **Base Directory = `/`**, and the **same** `APP_KEY` + DB creds +
a shared `kynexedu_storage` volume at `/var/www/html/storage`.

1. **Postgres** — `+ New → Database → PostgreSQL`. Deploy. Copy its **internal**
   connection string; set the app resources' `DB_HOST`/`DB_PORT`/`DB_DATABASE`/
   `DB_USERNAME`/`DB_PASSWORD` (and the matching `TENANCY_DB_*`) from it. Enable
   **Connect to Predefined Network** on the DB and each app resource so they share
   a network. Ensure the role has **`CREATEDB`**.
2. **`kynexedu-app`** (web) — port **80**, domain `https://edu.kynexsolutions.com`
   (+ wildcard per Part 5), `RUN_MIGRATIONS_AND_SEED=true` (first boot, then false).
3. **`kynexedu-queue`** — **Custom Start Command** `/usr/local/bin/start-queue`,
   `RUN_MIGRATIONS_AND_SEED=false`, no domain.
4. **`kynexedu-scheduler`** — **Custom Start Command** `/usr/local/bin/start-scheduler`,
   `RUN_MIGRATIONS_AND_SEED=false`, no domain.

The trade-off: more resources to keep in sync (especially `APP_KEY`, DB creds, and
the storage volume). The Compose path in Parts 2–4 does that wiring for you, which
is why it's the default recommendation.
```
