# Phase 1.5 — deferred items follow-up

**Created:** 2026-05-04
**Workstream:** Phase 1.5 — Automated TLS for custom school domains
**Predecessor docs:**
- docs/cert-provisioning-investigation-2026-05-04.md
- docs/cert-provisioning-design-2026-05-04.md (rev2)
- docs/cert-provisioning-subphase4-diffs.md
- docs/cert-provisioning-hardcoded-audit-2026-05-04.md
- docs/cert-provisioning-hardcoding-fixes-2026-05-04.md

This doc tracks Phase 1.5 work that was identified as desirable but
deliberately deferred during the Sub-phase 4-5 hardcoding audit. Each
item below was reviewed by the operator, judged correct-but-not-now,
and parked here for explicit future tracking.

The acceptable cost of deferring is: "we accept these are mildly
hardcoded for now."
The unacceptable cost is: "we forget about them and find them in 6
months when they bite."

This file is the defense against the second.

---

## Item 8 — HTTP listener port 9090

**Files where it appears:**
- /var/www/kynex/docker/cert-listener.php (the `php -S 0.0.0.0:9090` startup line)
- /var/www/kynex/docker/supervisor.conf ([program:cert-listener] command)
- /var/www/kynexedu/.env.production (CERT_LISTENER_URL=http://kynex-app:9090)
- /var/www/kynexedu/config/cert.php (listener_url default)

**Current behavior:** Port 9090 is the docker-internal service port for the cert listener. Not exposed to the host (not in compose `ports:`). Reachable only on the kynex_kynex docker network.

**Why deferred:** Configurability requires supervisord env-substitution and entrypoint env-sourcing, which complicates the kynex-app image. Port 9090 is genuinely an internal constant — no host collision risk, no operational reason it's currently 9090 vs anything else.

**When to revisit:**
- If a second internal HTTP service is added to kynex-app with similar listener pattern, build shared port-config infrastructure.
- If the deployment moves to multi-instance (multiple kynex-app replicas behind a load balancer), port collision becomes possible.
- If any tooling (monitoring, debug proxies) needs to assume a known-but-different port.

**Estimated effort:** 2-4 hours. supervisord conf templating, entrypoint env-source, env var addition on both sides, testing.

---

## Item 9 — Backend `proxy_pass http://kynexedu-app:80` in nginx template

**File:** /var/www/kynex/docker/custom-domain.conf.tpl (line 17 of the template)

**Current behavior:** The per-domain nginx server block hardcodes the upstream as `http://kynexedu-app:80`. This is the docker service hostname for the Laravel app container.

**Why deferred:** Configurability would require template substitution at provision time (replace a `__BACKEND__` placeholder via sed in provision-cert.sh, similar to `__DOMAIN__` and `__ISSUED_AT__`). Acceptable to defer because:
- Renaming the kynexedu-app service requires touching docker-compose.yml anyway, so the developer would see this dependency
- The kynexedu-app service hostname is stable across normal operations
- Adding a second backend (e.g., for canary deployments) is a larger architecture decision than a string substitution

**When to revisit:**
- If kynexedu-app is renamed (rename → service rename → all per-domain configs need regeneration)
- If a canary or blue-green deployment requires multiple backends
- If multi-region requires per-domain backend selection

**Estimated effort:** 1 hour. Add `__BACKEND__` placeholder, env var (CERT_BACKEND_UPSTREAM), substitution in provision-cert.sh, regenerate existing per-domain configs.

---

## Item 10 — Webroot path /var/www/certbot/www

**Files where it appears:**
- /var/www/kynex/docker/provision-cert.sh (WEBROOT constant, line 47)
- /var/www/kynex/Dockerfile (`mkdir -p /var/www/certbot/www/.well-known/acme-challenge`)
- /var/www/kynex/docker-compose.yml (`./certbot/www:/var/www/certbot/www` volume mount)
- /var/www/kynex/docker/nginx.conf — block (b) `location ^~ /.well-known/acme-challenge/ { root /var/www/certbot/www; }`
- Existing renewal configs: /etc/letsencrypt/renewal/sms.kynexsolutions.com.conf, aqmdigital.com.conf (after Sub-phase 6 sed migration)

**Current behavior:** Three-way pinning across provision script, Dockerfile, compose file. The path is required by certbot's webroot challenge handling and by nginx's block (b) which serves `/.well-known/acme-challenge/`.

**Why deferred:** Sub-phase 1's investigation surfaced a webroot path divergence between host certbot configs (referencing `/var/www/kynex/docker/certbot/www`) and in-container nginx (serving from `/var/www/certbot/www`). Sub-phase 6 addresses this divergence for existing certs via sed migration of renewal configs. But the underlying three-way pinning across script/Dockerfile/compose remains.

Changing the webroot path now would:
- Require migrating existing certs (sms.kynexsolutions.com, aqmdigital.com) AGAIN
- Require touching nginx.conf block (b) — the SACRED block we've kept untouched throughout Phase 1.5
- Add risk to a stable, working ACME challenge path

**When to revisit:**
- A dedicated "webroot path migration" workstream that addresses both the Sub-phase 1 divergence AND the three-way pinning together
- If a second TLS issuer (not Let's Encrypt) requires a different challenge path
- If the kynex-app filesystem layout changes for unrelated reasons

**Estimated effort:** 4-6 hours. Includes migrating existing certs, updating block (b) of nginx.conf, parallel-running new and old paths during cutover, validation that no in-flight ACME challenges are dropped.

---

## How items move out of this list

When work begins on any item:

1. Open a new design doc named `docs/cert-followup-item-N-design-2026-MM-DD.md` referencing this file
2. Mark the item below as IN PROGRESS with the date
3. After completion: mark DONE with date + commit SHA
4. Items stay in this file even after completion, as a historical record of what was deferred and when it was addressed

---

## Status

| # | Item | Status | Updated |
|---|------|--------|---------|
| 8 | HTTP listener port 9090 | DEFERRED | 2026-05-04 |
| 9 | Backend proxy_pass kynexedu-app:80 | DEFERRED | 2026-05-04 |
| 10 | Webroot path /var/www/certbot/www | DEFERRED | 2026-05-04 |
