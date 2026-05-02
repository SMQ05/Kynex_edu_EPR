# PgBouncer Setup for KynexEdu

## Why PgBouncer

At 50+ concurrent tenants, PostgreSQL's `max_connections` (default 100) becomes a bottleneck. PgBouncer pools connections, allowing 1000+ clients to share 20 actual database connections.

## RLS + PgBouncer Compatibility

KynexEdu uses Row Level Security (RLS) with `SET LOCAL app.user_role` to enforce role-based access at the database level.

- `SET LOCAL` resets the variable at the end of each transaction
- While this is technically safe with transaction pooling, we use **session pooling** as a defensive measure
- If `SET LOCAL` were ever accidentally changed to `SET`, transaction pooling would silently leak roles between requests — session mode prevents this
- The throughput trade-off is acceptable for a school SaaS workload

## Installation (Ubuntu/Debian)

```bash
sudo apt install pgbouncer
sudo cp pgbouncer/pgbouncer.ini /etc/pgbouncer/pgbouncer.ini
sudo cp pgbouncer/userlist.txt.example /etc/pgbouncer/userlist.txt
# Edit userlist.txt with actual credentials
sudo nano /etc/pgbouncer/userlist.txt
sudo systemctl enable pgbouncer
sudo systemctl start pgbouncer
```

## Laravel Configuration

Set in `.env`:

```env
PGBOUNCER_HOST=127.0.0.1
PGBOUNCER_PORT=6432
DB_HOST=127.0.0.1
DB_PORT=6432
```

Note: Point `DB_PORT` at PgBouncer (6432), not PostgreSQL directly (5432).

## Verify Connection Pooling

```bash
# Connect to PgBouncer admin console
psql -h 127.0.0.1 -p 6432 -U kynexedu_app -d pgbouncer -c "SHOW POOLS;"

# Check active connections
psql -h 127.0.0.1 -p 6432 -U kynexedu_app -d pgbouncer -c "SHOW STATS;"
```

## Pool Mode Reference

| Mode | Behavior | Safe with SET LOCAL? |
|------|----------|---------------------|
| `transaction` | Connection returned to pool after each transaction | Yes |
| `session` | Connection held for entire client session | Yes |
| `statement` | Connection returned after each statement | No (breaks transactions) |

KynexEdu uses `session` mode for maximum safety with RLS.
