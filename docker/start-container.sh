#!/usr/bin/env bash
set -euo pipefail

/usr/local/bin/docker-bootstrap

if [ $# -gt 0 ]; then
    exec "$@"
else
    exec apache2-foreground
fi

