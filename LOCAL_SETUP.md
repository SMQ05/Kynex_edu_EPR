# KynexEdu ERP — Local Development Setup

## Docker Setup

If you want a fully containerized local environment, use the Docker stack in this repo.

### Quick start

```bash
cd /home/Kynex_Solutions/Pictures/KynexSolution.com/KynexEdu-ERP
docker compose up --build -d
```

Open: **http://localhost:8000**

### Docker login credentials

| Field | Value |
|------|-------|
| URL | http://localhost:8000/saas/login |
| Email | `admin@kynexedu.com` |
| Password | `password` |

Demo school portal login is also prepared automatically:

| Field | Value |
|------|-------|
| URL | http://localhost:8000/login |
| Email | `admin@demo.kynexedu.com` |
| Password | `password` |

These credentials come from `SAAS_ADMIN_EMAIL` and `SAAS_ADMIN_PASSWORD`. Because the Docker startup runs migrations and `db:seed`, another user can pull the repo, start the same stack, and log in with the same credentials as long as they keep those env values unchanged.

### Docker services

| Service | Purpose |
|---------|---------|
| `app` | Apache + PHP web app, plus `composer`, `node`, `npm`, and `psql` for in-container development |
| `queue` | Laravel queue worker |
| `scheduler` | Laravel scheduler |
| `db` | PostgreSQL database |

### Useful Docker commands

```bash
docker compose logs -f app
docker compose exec app php artisan migrate --force
docker compose exec app php artisan db:seed --force
docker compose exec app php artisan kynex:ensure-dev-demo
docker compose exec app npm --version
docker compose exec app psql --version
docker compose exec app php artisan test
docker compose down
docker compose down -v
```

> `docker compose down -v` removes the database volume and resets seeded data.

### Docker recovery for broken login or missing setup

If `/saas/login` or `/login` does not work, or if the app container looks like it is missing database/frontend tooling, run:

```bash
docker compose down
docker compose up --build -d
docker compose exec app php artisan migrate --force
docker compose exec app php artisan db:seed --force
docker compose exec app php artisan kynex:ensure-dev-demo
docker compose logs -f app
```

If you want a guaranteed clean database:

```bash
docker compose down -v
docker compose up --build -d
```

## Prerequisites

| Tool | Required Version | Check |
|------|-----------------|-------|
| PHP | 8.3+ | `php --version` |
| Composer | 2.x | `composer --version` |
| Node.js | 18+ | `node --version` |
| npm | 9+ | `npm --version` |
| PostgreSQL | 14+ | `psql --version` |

> **Current environment:** PHP 8.4, Composer 2.9, Node 20, PostgreSQL 18 — all already installed.

---

## PostgreSQL Setup

PostgreSQL is already installed and running on this machine. The database user and central database have already been created.

### Verify connection

```bash
PGPASSWORD=kynexedu123 psql -U kynexedu -h 127.0.0.1 -d kynexedu_central -c "\l"
```

You should see `kynexedu_central` in the list. If this fails, see **Fresh PostgreSQL Setup** below.

### Fresh PostgreSQL Setup (if needed)

If starting on a new machine, run these commands as the `postgres` superuser:

```bash
sudo -u postgres psql <<'SQL'
CREATE USER kynexedu WITH PASSWORD 'kynexedu123' CREATEDB;
CREATE DATABASE kynexedu_central OWNER kynexedu ENCODING 'UTF8';
GRANT ALL PRIVILEGES ON DATABASE kynexedu_central TO kynexedu;
SQL
```

Then make sure PostgreSQL is running:

```bash
sudo systemctl enable --now postgresql
```

---

## Project Setup

### 1. Install PHP dependencies

```bash
cd /home/Kynex_Solutions/Pictures/KynexSolution.com/KynexEdu-ERP
composer install
```

### 2. Install Node dependencies

```bash
npm install
```

### 3. Environment file

The `.env` file is already configured for local development. If you ever need to reset it:

```bash
cp .env.example .env
php artisan key:generate
```

Key values in `.env`:

```
APP_URL=http://127.0.0.1:8000
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=kynexedu_central
DB_USERNAME=kynexedu
DB_PASSWORD=kynexedu123
```

### 4. Run migrations

Migrations are already applied. To re-run or after pulling new changes:

```bash
php artisan migrate
```

### 5. Create storage symlink

```bash
php artisan storage:link
```

---

## Starting the Development Servers

You need **two terminal windows** running simultaneously.

### Terminal 1 — Laravel backend

```bash
cd /home/Kynex_Solutions/Pictures/KynexSolution.com/KynexEdu-ERP
php artisan serve
```

Server starts at: **http://127.0.0.1:8000**

### Terminal 2 — Vite frontend (hot reload)

```bash
cd /home/Kynex_Solutions/Pictures/KynexSolution.com/KynexEdu-ERP
npm run dev
```

Vite starts at: **http://localhost:5173** (assets are proxied through Laravel — you still open port 8000)

### Optional: Queue worker (for jobs/emails/notifications)

```bash
php artisan queue:listen
```

### All-in-one (using composer script)

```bash
composer dev
```

This runs Laravel server + queue worker + log viewer + Vite concurrently in one terminal.

---

## Dashboard URLs & Login Credentials

### SaaS / Platform Admin Panel

> Manage all schools, tenants, billing, and platform settings.

| Field | Value |
|-------|-------|
| URL | http://127.0.0.1:8000/saas/login |
| Email | `admin@kynexedu.com` |
| Password | `password` |
| Role | `superadmin` |

**Dashboard:** http://127.0.0.1:8000/saas

Key sections:
- `/saas/tenants` — All registered schools
- `/saas/subscription-plans` — Billing plans
- `/saas/invoices` — Invoice management
- `/saas/tenant-signups` — New school signup requests
- `/saas/approval-requests` — Pending approvals
- `/saas/api-settings` — Platform API keys

---

### School Admin Panel (Tenant)

> Each school has its own admin panel accessed via subdomain or the central URL when running locally.

When a tenant school exists (e.g., ID `testschool`), access its admin panel at:

```
http://testschool.localhost:8000/admin
```

Or if you have added the subdomain to `/etc/hosts`:

```
http://testschool.127.0.0.1.nip.io:8000/admin
```

> **Local subdomain tip:** Add entries to `/etc/hosts` for each test tenant:
> ```
> 127.0.0.1   testschool.localhost
> ```

School admin login credentials are set per tenant during seeding or manual creation via the SaaS admin panel.

---

## Port Reference

| Service | Port | URL |
|---------|------|-----|
| Laravel (backend) | 8000 | http://127.0.0.1:8000 |
| Vite (HMR assets) | 5173 | internal only |
| PostgreSQL | 5432 | 127.0.0.1:5432 |

---

## Common Artisan Commands

```bash
# Clear all caches
php artisan optimize:clear

# List all routes
php artisan route:list

# Run tests
php artisan test

# Check migration status
php artisan migrate:status

# Tail logs
php artisan pail

# Create a new SaaS admin
php artisan tinker
>>> \App\Models\SaasAdmin::create([
...     'name' => 'New Admin',
...     'email' => 'newadmin@kynexedu.com',
...     'password' => bcrypt('yourpassword'),
...     'role' => 'superadmin',
... ]);
```

---

## Troubleshooting

### "Do not open port 5173 in the browser"
Port 5173 is the Vite **asset server only** — it serves JS/CSS hot-reload and shows a placeholder page, not the app.
Always open **http://127.0.0.1:8000** (the Laravel backend).

### "No application encryption key" error
```bash
php artisan key:generate
```

### Assets not loading / 404 on CSS/JS
Make sure Vite is running (`npm run dev`) or build assets first:
```bash
npm run build
```

### Database connection refused
```bash
systemctl status postgresql
sudo systemctl start postgresql
```

### Migration errors on tenant databases
```bash
php artisan tenants:migrate
```

### Permissions error on storage
```bash
chmod -R 775 storage bootstrap/cache
```
