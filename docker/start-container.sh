#!/usr/bin/env bash
set -euo pipefail

/usr/local/bin/docker-bootstrap

exec apache2-foreground
