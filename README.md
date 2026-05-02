# KynexEdu ERP

Multi-tenant Laravel 13 + Filament ERP for schools, with a SaaS admin panel, tenant provisioning, queue jobs, and Vite-built frontend assets.

## Docker Quick Start

1. Build and start the full stack:

```bash
docker compose up --build -d
```

2. Open the app:

```text
http://localhost:8000
```

The containers automatically:

- wait for PostgreSQL
- include `composer`, `node`, `npm`, and `psql` inside the app container
- generate `APP_KEY` when needed
- run migrations
- seed the SaaS admin and base data
- ensure a demo school tenant exists for portal login testing
- refresh the demo school admin password on startup so `/login` stays usable
- create the storage symlink
- start the web app, queue worker, and scheduler

## Default Docker Login

These are development defaults from [.env.docker](.env.docker):

- SaaS admin URL: `http://localhost:8000/saas/login`
- Email: `admin@kynexedu.com`
- Password: `password`
- School portal URL: `http://localhost:8000/login`
- Demo school email: `admin@demo.kynexedu.com`
- Demo school password: `password`

If another user pulls the repository and starts it with the same Docker env values, they will log in with the same seeded SaaS admin credentials. The account is recreated or updated on startup by `DatabaseSeeder`, so the login stays consistent for fresh Docker databases.

## Services

- `app`: Apache + PHP 8.3 + built Laravel app
- `db`: PostgreSQL 16
- `queue`: Laravel queue worker
- `scheduler`: Laravel scheduler loop

## Common Commands

```bash
docker compose up --build -d
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

Use `docker compose down -v` only if you want to remove the PostgreSQL volume and start from a fresh database.

## Docker Recovery Checklist

If login or seeded data does not work, run:

```bash
docker compose down
docker compose up --build -d
docker compose exec app php artisan migrate --force
docker compose exec app php artisan db:seed --force
docker compose exec app php artisan kynex:ensure-dev-demo
docker compose logs -f app
```

If the database volume is corrupted or you want a full reset:

```bash
docker compose down -v
docker compose up --build -d
```

## Notes

- This Docker setup is for local/shared development.
- Do not use the default `admin@kynexedu.com` / `password` credentials in production.
- Do not use the default `admin@demo.kynexedu.com` / `password` credentials in production.
- If you want teammates to use a different login, edit `.env.docker` before first boot.
