#!/usr/bin/env bash
set -euo pipefail

/usr/local/bin/docker-bootstrap

while true; do
    php artisan schedule:run --verbose --no-interaction
    sleep 60
done
