#!/bin/bash
# Run this once as root to complete PgBouncer setup:
# sudo bash scripts/setup-pgbouncer.sh

set -e

echo "Setting up PgBouncer directories..."
mkdir -p /var/log/pgbouncer /var/run/pgbouncer
chown pgbouncer:pgbouncer /var/log/pgbouncer /var/run/pgbouncer

echo "Starting PgBouncer..."
systemctl enable --now pgbouncer

echo "Waiting for PgBouncer to be ready..."
sleep 2

# Test the connection
if PGPASSWORD=kynexedu123 psql -U kynexedu -h 127.0.0.1 -p 6432 -d kynexedu_central -c "SELECT 1" > /dev/null 2>&1; then
    echo "PgBouncer is working! Connection on port 6432 OK."
else
    echo "PgBouncer started but connection test failed. Check: journalctl -u pgbouncer"
fi

systemctl status pgbouncer --no-pager
