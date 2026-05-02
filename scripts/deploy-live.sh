#!/usr/bin/env bash
#
# deploy-live.sh — push local source to the live KynexEdu server, rebuild
# the Docker image, and restart containers with zero data loss.
#
# Run from the project root on your dev machine:
#   ./scripts/deploy-live.sh
#
# Configuration: override via env vars if needed.
set -euo pipefail

REMOTE_USER="${KYNEX_DEPLOY_USER:-root}"
REMOTE_HOST="${KYNEX_DEPLOY_HOST:-178.104.180.160}"
REMOTE_PATH="${KYNEX_DEPLOY_PATH:-/var/www/kynexedu}"
SSH_KEY="${KYNEX_SSH_KEY:-$HOME/.ssh/id_ed25519}"
COMPOSE_FILE="${KYNEX_COMPOSE_FILE:-docker-compose.prod.yml}"

ssh_run() { ssh -i "$SSH_KEY" -o StrictHostKeyChecking=no "${REMOTE_USER}@${REMOTE_HOST}" "$@"; }

echo "▶ Building deploy tarball (excluding vendor / node_modules / storage / .git)..."
TAR=$(mktemp /tmp/kynex-deploy.XXXXXX.tar.gz)
EXTRA=()
[[ -f docker-compose.prod.yml ]] && EXTRA+=("docker-compose.prod.yml")
[[ -f docker-compose.yml ]] && EXTRA+=("docker-compose.yml")

tar --exclude='./vendor' \
    --exclude='./node_modules' \
    --exclude='./storage/logs/*' \
    --exclude='./storage/framework/cache/*' \
    --exclude='./storage/framework/sessions/*' \
    --exclude='./storage/framework/views/*' \
    --exclude='./.git' \
    --exclude='./.claude' \
    --exclude='./public/build' \
    --exclude='./tests' \
    -czf "$TAR" \
    app bootstrap config database public resources routes scripts \
    artisan composer.json composer.lock package.json \
    Dockerfile docker "${EXTRA[@]}"

echo "▶ Uploading $(du -h "$TAR" | cut -f1) to ${REMOTE_HOST}:${REMOTE_PATH}/..."
scp -i "$SSH_KEY" -o StrictHostKeyChecking=no "$TAR" "${REMOTE_USER}@${REMOTE_HOST}:/tmp/kynex-deploy.tar.gz"
rm -f "$TAR"

echo "▶ Extracting on remote..."
ssh_run "cd ${REMOTE_PATH} && tar xzf /tmp/kynex-deploy.tar.gz && rm -f /tmp/kynex-deploy.tar.gz"

echo "▶ Rebuilding Docker image (this may take a minute)..."
ssh_run "cd ${REMOTE_PATH} && docker compose -f ${COMPOSE_FILE} build app 2>&1 | tail -5"

echo "▶ Recreating containers..."
ssh_run "cd ${REMOTE_PATH} && docker compose -f ${COMPOSE_FILE} up -d --force-recreate 2>&1 | tail -10"

echo "▶ Waiting for app container to become healthy..."
sleep 8

echo "▶ Running post-deploy artisan tasks..."
ssh_run "docker exec kynexedu-app php /var/www/html/artisan optimize:clear"
ssh_run "docker exec kynexedu-app php /var/www/html/artisan filament:cache-components"
ssh_run "docker exec kynexedu-app php /var/www/html/artisan config:cache"
ssh_run "docker exec kynexedu-app php /var/www/html/artisan route:cache"
ssh_run "docker exec kynexedu-app php /var/www/html/artisan view:cache"

echo "▶ Smoke-testing https://sms.kynexsolutions.com/login..."
HTTP_CODE=$(curl -s -o /dev/null -w '%{http_code}' https://sms.kynexsolutions.com/login)
if [[ "$HTTP_CODE" != "200" ]]; then
    echo "✗ Smoke test failed: got HTTP ${HTTP_CODE} (expected 200)"
    echo "  Check logs: ssh ${REMOTE_USER}@${REMOTE_HOST} 'docker exec kynexedu-app tail -50 /var/www/html/storage/logs/laravel.log'"
    exit 1
fi

echo "✓ Deploy complete. Live at https://sms.kynexsolutions.com/"
