#!/usr/bin/env sh
set -eu

APP_URL="${APP_URL:-https://app.seudominio.com}"
WS_URL="${WS_URL:-https://ws.seudominio.com}"

echo "== docker compose ps =="
docker compose ps

echo "== app =="
curl -fsSI "$APP_URL" | sed -n '1,10p'

echo "== reverb endpoint =="
curl -fsSI "$WS_URL" | sed -n '1,10p' || true

echo "== laravel config smoke =="
docker compose exec -T app php artisan about --only=environment

echo "== queue smoke =="
docker compose exec -T app php artisan queue:monitor default --max=100 || true
