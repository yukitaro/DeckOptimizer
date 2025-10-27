#!/bin/bash

UNRAID_IP="192.168.4.161"
UNRAID_USER="root"
DEST_PATH="/mnt/user/appdata/deckoptimizer"

set -e

echo "🚀 Deploying DeckOptimizer to Unraid..."

# Step 1: Sync files
echo ""
echo "📦 Step 1: Syncing files..."
rsync -avz --progress \
  --exclude 'node_modules' \
  --exclude '.git' \
  --exclude '.env' \
  --exclude 'vendor' \
  --exclude 'storage/framework/cache/*' \
  --exclude 'storage/framework/sessions/*' \
  --exclude 'storage/framework/views/*' \
  --exclude 'storage/logs/*' \
  --exclude 'bootstrap/cache/*' \
  --exclude 'deckdb' \
  --exclude 'frontend/deck-optimizer-frontend/dist' \
  ./ ${UNRAID_USER}@${UNRAID_IP}:${DEST_PATH}/

echo "✅ Sync complete!"

# Step 2: Deploy on Unraid
echo ""
echo "🔧 Step 2: Deploying on Unraid..."
ssh ${UNRAID_USER}@${UNRAID_IP} << 'ENDSSH'
cd /mnt/user/appdata/deckoptimizer

# Setup environment
if [ ! -f .env ]; then
    cp .env.production .env
    echo "✅ Created .env from .env.production"
fi

# Create directories
mkdir -p storage/framework/{cache,sessions,views}
mkdir -p storage/logs
mkdir -p bootstrap/cache

# CRITICAL: Stop containers FIRST
echo "⏸️  Stopping containers..."
docker-compose -f docker-compose.prod.yml down

# CRITICAL: Install composer WHILE containers are stopped
echo "📚 Installing Composer dependencies..."
docker run --rm \
    -u "$(id -u):$(id -g)" \
    -v "$(pwd):/var/www/html" \
    -w /var/www/html \
    laravelsail/php84-composer:latest \
    composer install --no-dev --optimize-autoloader --ignore-platform-reqs

# Verify vendor exists
if [ ! -d "vendor/laravel" ]; then
    echo "❌ ERROR: vendor/laravel not created!"
    ls -la vendor/
    exit 1
fi

echo "✅ Composer dependencies installed"

# NOW start containers (vendor exists on host)
echo "🐳 Starting Docker containers..."
docker-compose -f docker-compose.prod.yml up -d --build

echo "⏳ Waiting for services to start..."
sleep 25

# Run Laravel setup
echo "🔧 Running Laravel setup..."
docker exec deckoptimizer-backend php artisan config:cache
docker exec deckoptimizer-backend php artisan route:cache
docker exec deckoptimizer-backend php artisan view:cache
docker exec deckoptimizer-backend php artisan migrate --force
docker exec deckoptimizer-backend php artisan storage:link

echo "✅ Deployment complete!"
docker-compose -f docker-compose.prod.yml ps
ENDSSH

echo ""
echo "🎉 DeckOptimizer deployed successfully!"
echo ""
echo "Access your application:"
echo "  Frontend: http://${UNRAID_IP}:8284"
echo "  Backend:  http://${UNRAID_IP}:8765"