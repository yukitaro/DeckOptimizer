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
  --exclude 'storage/logs/'
  --exclude 'storage/logs/**'
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

ssh -i "${SSH_KEY}" "${UNRAID_USER}@${UNRAID_IP}" bash -s <<'EOF'
# ... (Remote script block inside ssh -i "${SSH_KEY}" ...)
set -euo pipefail
DEST_PATH="/mnt/user/appdata/deckoptimizer"
REBUILD="false"
SAIL_UID=1337
SAIL_GID=1000

cd "$DEST_PATH" || { echo "cd $DEST_PATH failed"; exit 1; }

# 1. Prepare runtime dirs (MUST happen before chown)
mkdir -p storage/framework/{cache,sessions,views} storage/logs bootstrap/cache supervisor
echo "✅ Prepared runtime directories."

# 2. Stop containers safely
docker-compose -f docker-compose.prod.yml down --remove-orphans || true
echo "✅ Containers stopped."

# 3. Attempt to fix ownership/permissions on host directories (MUST run before composer install)
# This prevents permission denied errors during runtime/composer install if Unraid's mask is wrong.
chown -R ${SAIL_UID}:${SAIL_GID} bootstrap/cache storage supervisor || true
chmod -R 775 bootstrap/cache storage supervisor || true
find storage -type d -exec chmod g+s {} + || true
echo "✅ Initial permissions set."

# 4. Ensure .env exists
if [ ! -f .env ]; then
  if [ -f .env.production ]; then
    cp .env.production .env
    echo "✅ Created .env from .env.production"
  else
    echo "ERROR: no .env and no .env.production" >&2
    exit 1
  fi
fi

# 5. Run composer
docker-compose -f docker-compose.prod.yml run --rm backend \
  composer install --no-dev --optimize-autoloader --no-interaction --no-scripts
echo "✅ Composer dependencies installed."

# 6. Verify vendor (Optional but good check)
if [ ! -d vendor/laravel ]; then
  echo "❌ vendor missing after composer install"; exit 1
fi
echo "✅ Composer vendor present"


# 7. Optionally rebuild (no-cache) then start
if [ "${REBUILD}" = "true" ]; then
  docker-compose -f docker-compose.prod.yml build --no-cache backend
fi
docker-compose -f docker-compose.prod.yml up -d
echo "✅ Containers started."

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
EOF

echo ""
echo "🎉 DeckOptimizer deployed successfully!"
echo ""
echo "Access your application:"
echo "  Frontend: http://${UNRAID_IP}:8284"
echo "  Backend:  http://${UNRAID_IP}:8765"
