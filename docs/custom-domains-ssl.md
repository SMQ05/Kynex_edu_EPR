# Custom Domain SSL Setup

KynexEdu supports custom domains for schools (e.g. `portal.brightfuture.com`).
This document covers SSL certificate provisioning for those domains.

---

## Option A: Caddy (Recommended — Automatic SSL)

Caddy automatically provisions Let's Encrypt certificates for any domain
that resolves to your server. No manual certificate management needed.

### 1. Caddyfile Configuration

```caddyfile
# Global options
{
    # On-Demand TLS: Caddy will auto-provision certs for any domain
    # that passes the "ask" check below.
    on_demand_tls {
        ask http://localhost:8000/caddy/check-domain
    }
}

# ── Wildcard for all KynexEdu subdomains ─────────────────────
*.kynexedu.com {
    tls {
        dns cloudflare {env.CLOUDFLARE_API_TOKEN}
    }
    reverse_proxy localhost:8000
}

# ── Bare domain (central app) ───────────────────────────────
kynexedu.com {
    tls {
        dns cloudflare {env.CLOUDFLARE_API_TOKEN}
    }
    reverse_proxy localhost:8000
}

# ── Custom domains (auto-SSL via On-Demand TLS) ─────────────
# No explicit config needed per domain — Caddy uses On-Demand TLS.
# When a request arrives for an unknown domain, Caddy calls the
# /caddy/check-domain endpoint. If it returns 200, Caddy provisions
# a certificate automatically.
:443 {
    tls {
        on_demand
    }
    reverse_proxy localhost:8000
}
```

### 2. How It Works

1. School adds custom domain in SaaS admin panel
2. School configures DNS: CNAME → `kynexedu.com`
3. School adds TXT record for verification
4. SaaS admin (or scheduled job) verifies the domain
5. When first HTTPS request arrives at Caddy for the custom domain:
   - Caddy calls `GET /caddy/check-domain?domain=portal.brightfuture.com`
   - KynexEdu checks the `domains` table for a verified record
   - Returns `200` → Caddy provisions SSL via Let's Encrypt
   - Returns `404` → Caddy refuses the connection

### 3. Environment Variables

```env
CLOUDFLARE_API_TOKEN=your-cloudflare-api-token
```

The Cloudflare token is only needed for the wildcard `*.kynexedu.com` cert
(DNS challenge). Custom domains use HTTP challenge automatically.

---

## Option B: Nginx + Certbot (Manual)

For each new verified custom domain, an admin must manually provision the
certificate and configure Nginx.

### 1. Provision Certificate

```bash
# After the domain is verified in the SaaS admin panel:
sudo certbot --nginx -d portal.brightfuture.com
```

### 2. Nginx Server Block

Add to `/etc/nginx/sites-available/portal.brightfuture.com`:

```nginx
server {
    listen 80;
    server_name portal.brightfuture.com;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl;
    server_name portal.brightfuture.com;

    # Certbot will add these lines automatically:
    # ssl_certificate /etc/letsencrypt/live/portal.brightfuture.com/fullchain.pem;
    # ssl_certificate_key /etc/letsencrypt/live/portal.brightfuture.com/privkey.pem;

    location / {
        proxy_pass http://localhost:8000;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
```

```bash
sudo ln -s /etc/nginx/sites-available/portal.brightfuture.com \
           /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx
```

### 3. Auto-Renewal

Certbot sets up a systemd timer automatically. Verify with:

```bash
sudo certbot renew --dry-run
```

---

## DNS Requirements for Schools

When a SaaS admin adds a custom domain for a school, the school must
configure two DNS records at their domain registrar:

### Record 1: CNAME (Route traffic to KynexEdu)

| Field | Value                      |
|-------|----------------------------|
| Type  | CNAME                      |
| Name  | `portal` (or `@` for apex) |
| Value | `kynexedu.com`             |
| TTL   | 300                        |

### Record 2: TXT (Domain verification)

| Field | Value                                      |
|-------|--------------------------------------------|
| Type  | TXT                                        |
| Name  | `_kynexedu-verify.portal.brightfuture.com` |
| Value | *(verification token shown in admin panel)* |
| TTL   | 300                                        |

> **Note:** DNS propagation may take up to 10 minutes. The scheduled
> `kynex:verify-pending-domains` command checks every 15 minutes.

---

## The `/caddy/check-domain` Endpoint

This route is registered in `routes/web.php` and responds to Caddy's
On-Demand TLS "ask" request:

- `GET /caddy/check-domain?domain=portal.brightfuture.com`
- Returns `200` if the domain exists in the `domains` table and is verified
- Returns `404` otherwise

This prevents Caddy from provisioning certificates for random domains
pointed at your server.
