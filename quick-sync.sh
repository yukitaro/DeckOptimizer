#!/bin/bash
# quick-sync.sh - Fast sync for PHP changes only

UNRAID_IP="192.168.4.161"

echo "⚡ Quick syncing PHP changes..."

rsync -avz --progress \
  --exclude 'vendor' \
  --exclude 'node_modules' \
  --exclude '.git' \
  --exclude 'storage' \
  --exclude 'bootstrap/cache' \
  --exclude 'deckdb' \
  ./app/ root@${UNRAID_IP}:/mnt/user/appdata/deckoptimizer/app/

rsync -avz --progress \
  --exclude 'vendor' \
  ./routes/ root@${UNRAID_IP}:/mnt/user/appdata/deckoptimizer/routes/

echo "✅ PHP files synced! Changes are live."