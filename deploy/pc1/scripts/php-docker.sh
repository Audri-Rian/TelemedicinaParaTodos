#!/usr/bin/env bash
# Wrapper para permitir `npm run build` no host sem PHP instalado.
# O plugin Wayfinder do Vite invoca `php artisan wayfinder:generate`.
set -euo pipefail
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../../.." && pwd)"
cd "$ROOT/deploy/pc1"
exec docker compose --env-file .env run --rm --no-deps -T app php "$@"
