#!/usr/bin/env bash
set -euo pipefail

COMPOSE_FILE="docker-compose.prod.yml"

# Rebuild WordPress image from local sources without cache to avoid stale theme/plugin code
# then restart the stack with the freshly built image.
docker compose -f "$COMPOSE_FILE" build --no-cache wordpress
docker compose -f "$COMPOSE_FILE" up -d
