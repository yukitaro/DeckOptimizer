#!/bin/bash

# Files that should trigger a rebuild if changed
REBUILD_FILES=(
  docker-compose.prod.yml
  docker/8.4/Dockerfile
  docker/8.4/php.ini
  docker/8.4/supervisord.conf
  docker/8.4/start-container
  composer.json
  composer.lock
  .env.production
)

REBUILD=false

echo "🔍 Checking for rebuild-triggering changes..."

for file in "${REBUILD_FILES[@]}"; do
  if git diff --quiet HEAD -- "$file"; then
    # No committed changes
    :
  else
    echo "⚠️  Committed change detected in $file"
    REBUILD=true
  fi

  if git status --porcelain | grep -q "$file"; then
    echo "⚠️  Uncommitted change detected in $file"
    REBUILD=true
  fi
done

if [ "$REBUILD" = true ]; then
  echo "✅ Rebuild required"
  export REBUILD=true
else
  echo "✅ No rebuild needed"
  export REBUILD=false
fi