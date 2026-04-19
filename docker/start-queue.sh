#!/usr/bin/env bash
set -euo pipefail

/usr/local/bin/docker-bootstrap

exec php artisan queue:work --tries=1 --timeout=0 --verbose
