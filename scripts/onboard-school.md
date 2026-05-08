# School Onboarding Runbook — Custom Domain + TLS

Operator guide for adding a new school tenant with a custom domain and a
production Let's Encrypt certificate. Assumes the SaaS admin panel is
reachable at **sms.kynexsolutions.com**.

---

## 1. Prerequisites

Before starting, confirm you have:

- The school's **custom domain** (e.g. `portal.acmeschool.edu`)
- Access to that domain's **DNS control panel** (school provides this, or
  you coordinate with their IT team)
- A SaaS admin account at `sms.kynexsolutions.com/saas/login`

---

## 2. DNS — point the domain at the KynexEdu server

In the school's DNS control panel, add:

| Type | Name | Value | TTL |
|------|------|-------|-----|
| A | `@` or `portal` (the subdomain they chose) | `178.104.180.160` | 300 |

**Critical:** if the domain is managed through Cloudflare, set the record to
**DNS only** (grey cloud). An orange-cloud (proxied) record prevents the
webroot challenge used for certificate issuance.

Propagation takes anywhere from a few minutes to a few hours depending on the
registrar and prior TTL. You can check propagation with:

```bash
dig +short portal.acmeschool.edu
# Must return: 178.104.180.160
```

Do not proceed to the cert step until DNS resolves correctly.

---

## 3. Create the tenant in the SaaS admin panel

1. Log in at `https://sms.kynexsolutions.com/saas/login`
2. Navigate to **Tenants → Create**
3. Fill in the school details (name, subdomain, plan, etc.)
4. In the **Custom Domain** field, enter the school's domain exactly as it
   will appear in the browser (e.g. `portal.acmeschool.edu`) — no `https://`
5. Save the tenant

---

## 4. Verify DNS and provision the certificate

In the tenant detail view:

1. Click **Verify Domain** — the panel checks that the A record resolves to
   `178.104.180.160`. If it fails, DNS hasn't propagated yet; wait and retry.
2. Once DNS is verified, click **Provision Cert Now**
3. The panel calls the cert provisioning automation. This takes **10–30
   seconds**. The `cert_status` field will cycle through:
   - `pending` → `issuing` → `issued`
4. Refresh the page to see the final status.

---

## 5. Verification

Once `cert_status = issued`:

```bash
# From any machine — ssl_verify_result must be 0
curl -s -o /dev/null -w '%{http_code} ssl_verify=%{ssl_verify_result}\n' \
  https://portal.acmeschool.edu/
```

Expected: any HTTP code (200, 302, etc.) with `ssl_verify=0`.

Also open `https://portal.acmeschool.edu/` in a browser — the padlock must
show a valid cert issued by **Let's Encrypt**.

---

## 6. Troubleshooting

**DNS verify fails ("domain does not resolve to this server")**
- DNS hasn't propagated yet. Wait 5–60 minutes and retry.
- Check: `dig +short portal.acmeschool.edu` — must return `178.104.180.160`.
- Cloudflare orange-cloud will return Cloudflare's IP, not ours — switch to
  grey cloud (DNS only).

**Cert provisioning fails / cert_status stays at `issuing`**
- Check the cert-listener log inside the container:
  ```bash
  docker exec kynex-app tail -50 /var/www/storage/logs/cert-listener.log
  ```
- Common causes:
  - DNS still not resolving (Let's Encrypt validates from their servers, not
    just yours)
  - Webroot challenge blocked — confirm nginx is serving `/.well-known/` for
    the domain
  - Rate limit hit — Let's Encrypt allows 5 duplicate certs per domain per
    week; wait if retrying repeatedly

**`ssl_verify_result=1` after provisioning**
- The nginx config for the domain may not have loaded. Check:
  ```bash
  docker exec kynex-app ls /etc/nginx/sites-enabled/
  docker exec kynex-app nginx -t && docker exec kynex-app nginx -s reload
  ```

**School already has a certificate from another provider**
- No action needed — provisioning will still issue a Let's Encrypt cert and
  the nginx config will serve it. The old cert is irrelevant once the A record
  points here.

---

## 7. Total time estimate

| Scenario | Time |
|----------|------|
| DNS already propagated | 5–10 minutes |
| DNS needs to propagate | 30–90 minutes (mostly waiting) |
| Cert issuance itself | 10–30 seconds |

---

## 8. Certificate renewal

Certificates auto-renew via the nightly cron inside `kynex-app`. No operator
action is required. Certs are valid for 90 days; renewal runs at 60 days
remaining.
