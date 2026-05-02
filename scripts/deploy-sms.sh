#!/usr/bin/env bash
# Deploy sms.kynexsolutions.com as a separate instance
# Must be run from the KynexEdu-ERP repo root on the target server
#
# IMPORTANT: Edit DB_PASSWORD, TENANCY_DB_PASSWORD, and POSTGRES_PASSWORD below
# before running. The script refuses to run while CHANGE_ME_BEFORE_RUNNING
# placeholders are still present. APP_KEY can be left empty — the container
# entrypoint runs `php artisan key:generate` on first boot when missing.
set -euo pipefail

if grep -qE 'CHANGE_ME_[B]EFORE_RUNNING' "$0"; then
    echo "ERROR: edit $0 — replace CHANGE_ME_BEFORE_RUNNING placeholders before running" >&2
    exit 1
fi

APP_DIR="/var/www/sms"
NGINX_CONF="/etc/nginx/sites-available/sms.kynexsolutions.com"
NGINX_LINK="/etc/nginx/sites-enabled/sms.kynexsolutions.com"
CERT_EMAIL="admin@kynexsolutions.com"

echo "=== Step 1: Create app directory ==="
mkdir -p "$APP_DIR"
cp -r . "$APP_DIR"
cd "$APP_DIR"

echo "=== Step 2: Write sms-specific .env ==="
cat > .env.docker <<'ENVEOF'
APP_NAME="KynexEdu SMS"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=https://sms.kynexsolutions.com

LOG_CHANNEL=stack
LOG_LEVEL=debug

DB_CONNECTION=pgsql
DB_HOST=db-sms
DB_PORT=5432
DB_DATABASE=kynexedu_sms_central
DB_USERNAME=kynexedu_sms
DB_PASSWORD=CHANGE_ME_BEFORE_RUNNING

TENANCY_DB_PREFIX=kynexedu_sms_tenant_
TENANCY_DB_HOST=db-sms
TENANCY_DB_PORT=5432
TENANCY_DB_USERNAME=kynexedu_sms
TENANCY_DB_PASSWORD=CHANGE_ME_BEFORE_RUNNING

BROADCAST_CONNECTION=log
CACHE_STORE=database
FILESYSTEM_DISK=local
QUEUE_CONNECTION=database
SESSION_DRIVER=database
SESSION_LIFETIME=120

MAIL_MAILER=log
MAIL_FROM_ADDRESS="noreply@kynexedu.com"
MAIL_FROM_NAME="${APP_NAME}"

SAAS_ADMIN_EMAIL=admin@kynexedu.com
SAAS_ADMIN_PASSWORD=password

FILAMENT_FILESYSTEM_DISK=public
VITE_APP_NAME="${APP_NAME}"

DEMO_TENANT_ID=demo
ENVEOF

echo "=== Step 3: Write separate docker-compose ==="
cat > docker-compose.sms.yml <<'COMPOSEEOF'
services:
  app:
    build:
      context: .
      dockerfile: Dockerfile
    image: kynexedu-erp-sms:local
    container_name: kynexedu-sms-app
    restart: unless-stopped
    env_file:
      - .env.docker
    ports:
      - "8001:80"
    environment:
      RUN_MIGRATIONS_AND_SEED: "true"
    depends_on:
      db:
        condition: service_healthy
    volumes:
      - sms_app_storage:/var/www/html/storage
    networks:
      - sms_net

  queue:
    image: kynexedu-erp-sms:local
    container_name: kynexedu-sms-queue
    restart: unless-stopped
    command: ["/usr/local/bin/start-queue"]
    env_file:
      - .env.docker
    environment:
      RUN_MIGRATIONS_AND_SEED: "false"
    depends_on:
      db:
        condition: service_healthy
      app:
        condition: service_started
    volumes:
      - sms_app_storage:/var/www/html/storage
    networks:
      - sms_net

  scheduler:
    image: kynexedu-erp-sms:local
    container_name: kynexedu-sms-scheduler
    restart: unless-stopped
    command: ["/usr/local/bin/start-scheduler"]
    env_file:
      - .env.docker
    environment:
      RUN_MIGRATIONS_AND_SEED: "false"
    depends_on:
      db:
        condition: service_healthy
      app:
        condition: service_started
    volumes:
      - sms_app_storage:/var/www/html/storage
    networks:
      - sms_net

  db:
    image: postgres:16-alpine
    container_name: kynexedu-sms-db
    restart: unless-stopped
    environment:
      POSTGRES_DB: kynexedu_sms_central
      POSTGRES_USER: kynexedu_sms
      POSTGRES_PASSWORD: CHANGE_ME_BEFORE_RUNNING
    ports:
      - "5433:5432"
    healthcheck:
      test: ["CMD-SHELL", "pg_isready -U kynexedu_sms -d kynexedu_sms_central"]
      interval: 5s
      timeout: 5s
      retries: 20
    volumes:
      - sms_db_data:/var/lib/postgresql/data
    networks:
      - sms_net

volumes:
  sms_app_storage:
  sms_db_data:

networks:
  sms_net:
    driver: bridge
COMPOSEEOF

echo "=== Step 4: Build and start SMS containers ==="
docker compose -f docker-compose.sms.yml build
docker compose -f docker-compose.sms.yml up -d

echo "=== Step 5: Configure nginx for sms.kynexsolutions.com ==="
cat > "$NGINX_CONF" <<'NGINXEOF'
server {
    listen 80;
    listen [::]:80;
    server_name sms.kynexsolutions.com;

    location / {
        proxy_pass http://127.0.0.1:8001;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_set_header X-Forwarded-Host $host;
        proxy_set_header X-Forwarded-Port $server_port;
        proxy_read_timeout 120;
        proxy_connect_timeout 120;
    }

    location /health {
        access_log off;
        return 200 "OK";
    }
}
NGINXEOF

ln -sf "$NGINX_CONF" "$NGINX_LINK"

echo "=== Step 6: Test nginx config and reload ==="
nginx -t && systemctl reload nginx

echo "=== Step 7: Run certbot for SSL ==="
certbot --nginx -d sms.kynexsolutions.com --non-interactive --agree-tos -m "$CERT_EMAIL" 2>/dev/null || {
    echo "certbot may have failed or is not installed; check manually"
}

echo "=== Step 8: Verify ==="
echo "--- SMS instance ---"
curl -so /dev/null -w "%{http_code}" http://sms.kynexsolutions.com/ || true
echo ""
echo "--- AI instance (should return 301/302) ---"
curl -so /dev/null -w "%{http_code}" http://ai.kynexsolutions.com/ || true
echo ""

echo "=== DONE ==="
echo "SMS containers:"
docker ps --filter "name=kynexedu-sms" --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}"
echo ""
echo "AI containers (untouched):"
docker ps --filter "name=kynexedu-" --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}"
