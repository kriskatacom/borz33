#!/usr/bin/env bash
set -euo pipefail

SERVER="borz33-vps"
REMOTE_DIR="/home/almalinux/borz33"
COMPOSE="docker compose --env-file .env.production -f docker-compose.production.yml"

UPLOAD_ENV=false

if [[ "${1:-}" == "--env" ]]; then
  UPLOAD_ENV=true
fi

echo "==> Uploading project..."

rsync -az --delete \
  --exclude='.git/' \
  --exclude='.env' \
  --exclude='.env.production' \
  --exclude='node_modules/' \
  --exclude='admin/node_modules/' \
  --exclude='web/node_modules/' \
  --exclude='vendor/' \
  ./ "$SERVER:$REMOTE_DIR/"

if [ "$UPLOAD_ENV" = true ]; then
  echo "==> Uploading production environment..."

  scp .env.production "$SERVER:$REMOTE_DIR/.env.production"

  ssh "$SERVER" "
    chmod 600 '$REMOTE_DIR/.env.production'
  "
fi

echo "==> Building admin..."

ssh "$SERVER" "
  set -e
  cd '$REMOTE_DIR'
  $COMPOSE up \
    --force-recreate \
    --abort-on-container-exit \
    --exit-code-from admin-build \
    admin-build
"

echo "==> Building storefront..."

ssh "$SERVER" "
  set -e
  cd '$REMOTE_DIR'
  $COMPOSE up \
    --force-recreate \
    --abort-on-container-exit \
    --exit-code-from store-build \
    store-build
"

echo "==> Ensuring PHP is running..."

ssh "$SERVER" "
  set -e
  cd '$REMOTE_DIR'
  $COMPOSE up -d --force-recreate --no-deps php
"

echo "==> Running database migrations..."

ssh "$SERVER" "
  set -e
  cd '$REMOTE_DIR'
  $COMPOSE exec -T php ./vendor/bin/phinx migrate
"

echo "==> Updating Nginx..."

ssh "$SERVER" "
  set -e
  cd '$REMOTE_DIR'
  $COMPOSE up -d --force-recreate --no-deps nginx
"

echo "==> Checking containers..."

ssh "$SERVER" "
  set -e
  cd '$REMOTE_DIR'
  $COMPOSE ps
"

echo "==> Checking storefront..."

ssh "$SERVER" \
  "curl -fsS -o /dev/null -H 'Host: borz33.com' http://127.0.0.1/"

echo "==> Checking admin..."

ssh "$SERVER" \
  "curl -fsS -o /dev/null -H 'Host: borz33.com' http://127.0.0.1/admin/"

echo
echo "======================================"
echo " DEPLOYMENT COMPLETED SUCCESSFULLY"
echo "======================================"
