#!/usr/bin/env bash
set -euo pipefail

UNRAID_IP="192.168.4.161"
UNRAID_USER="root"
SSH_KEY="${HOME}/.ssh/id_ed25519FS"
DEST_PATH="/mnt/user/appdata/deckoptimizer"

RSYNC_OPTS=(-az --progress --delete --owner --group --links --chmod=ugo=rwX -e "ssh -i ${SSH_KEY}")
EXCLUDES=(
  --exclude node_modules
  --exclude .git
  --exclude .env
  --exclude mysql
  --exclude mysql-backup.sql
  --exclude vendor
  --exclude storage/framework/cache/*
  --exclude storage/framework/sessions/*
  --exclude storage/framework/views/*
  --exclude storage/logs/*
  --exclude bootstrap/cache/*
  --exclude bootstrap-cache
  --exclude deckdb
  --exclude deckoptimizer-dev.sql
  --exclude frontend/deck-optimizer-frontend/dist
)

echo "🚀 Deploying DeckOptimizer to Unraid..."
echo ""
echo "📦 Step 1: Syncing files..."
rsync "${RSYNC_OPTS[@]}" "${EXCLUDES[@]}" ./ "${UNRAID_USER}@${UNRAID_IP}:${DEST_PATH}/"

echo "📦 Syncing scraper files..."
rsync "${RSYNC_OPTS[@]}" DeckOptimizer-mtg-scrapers/ \
  "${UNRAID_USER}@${UNRAID_IP}:${DEST_PATH}/DeckOptimizer-mtg-scrapers/"
echo "✅ Sync complete!"

ssh -i "${SSH_KEY}" "${UNRAID_USER}@${UNRAID_IP}" bash -lc '
set -euo pipefail
DEST_PATH="'"${DEST_PATH}"'"
REBUILD="'"${REBUILD:-false}"'"
SAIL_UID=1337
SAIL_GID=1000

cd "${DEST_PATH}" || { echo "cd ${DEST_PATH} failed"; exit 1; }

# Ensure .env exists
if [ ! -f .env ]; then
  if [ -f .env.production ]; then
    cp .env.production .env
    echo "✅ Created .env from .env.production"
  else
    echo "ERROR: no .env and no .env.production" >&2
    exit 1
  fi
fi

# Prepare runtime dirs
mkdir -p storage/framework/{cache,sessions,views} storage/logs bootstrap/cache bootstrap-cache supervisor
mkdir -p bootstrap-cache  # host bind mount for bootstrap/cache


# Stop containers safely
docker-compose -f docker-compose.prod.yml down || true

# Run composer inside official composer:2 container (deterministic)
docker run --rm \
  -u "$(id -u):$(id -g)" \
  -v "$(pwd):/app" \
  -w /app \
  composer:2 \
  composer install --no-dev --optimize-autoloader --ignore-platform-reqs --no-interaction

# Verify vendor
if [ ! -d vendor/laravel ]; then
  echo "❌ vendor missing after composer install"; ls -la vendor || true; exit 1
fi
echo "✅ Composer vendor present"

# Attempt to fix ownership/permissions for Unraid mounts (best-effort)
chown -R ${SAIL_UID}:${SAIL_GID} bootstrap/cache bootstrap-cache storage supervisor || true
chmod -R 775 bootstrap/cache bootstrap-cache storage supervisor || truefind storage -type d -exec chmod g+s {} + || true

# Optionally rebuild (no-cache) then start
if [ "${REBUILD}" = "true" ]; then
  docker-compose -f docker-compose.prod.yml build --no-cache backend
fi
docker-compose -f docker-compose.prod.yml up -d

# Wait for backend health
for i in $(seq 1 60); do
  STATUS=$(docker inspect --format "{{.State.Health.Status}}" deckoptimizer-backend 2>/dev/null || echo "none")
  if [ "${STATUS}" = "healthy" ]; then
    echo "✅ backend healthy"
    break
  fi
  if [ "${STATUS}" = "none" ]; then
    RUNNING=$(docker inspect --format "{{.State.Running}}" deckoptimizer-backend 2>/dev/null || echo "false")
    if [ "${RUNNING}" = "true" ]; then
      docker exec deckoptimizer-backend sh -c "php -r '\''echo function_exists(\"pdo_mysql\")?\"pdo_ok\":\"pdo_missing\";'\''" >/tmp/pdo_check 2>&1 || true
      if grep -q "pdo_ok" /tmp/pdo_check 2>/dev/null; then
        echo "✅ backend running and pdo_mysql present"
        break
      fi
    fi
  fi
  if [ "$i" -eq 60 ]; then
    echo "❌ backend did not become healthy; showing last logs"
    docker-compose -f docker-compose.prod.yml ps || true
    docker-compose -f docker-compose.prod.yml logs --no-color backend | tail -n 300
    exit 2
  fi
  sleep 2
done

# Laravel housekeeping
if docker ps --format "{{.Names}}" | grep -q deckoptimizer-backend; then
  docker exec deckoptimizer-backend php artisan optimize:clear || true
fi

echo "✅ Remote deploy completed"
docker-compose -f docker-compose.prod.yml ps
'

echo ""
echo "🎉 DeckOptimizer deployed successfully!"
echo ""
echo "Access your application:"
echo "  Frontend: http://${UNRAID_IP}:8284"
echo "  Backend:  http://${UNRAID_IP}:8765"
