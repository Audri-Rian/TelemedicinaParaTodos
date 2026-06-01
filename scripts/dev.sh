#!/usr/bin/env bash
# Script de automação do ambiente de desenvolvimento.
# Uso: composer run dev (ou ./scripts/dev.sh)
# Cria .env se não existir, sobe infra Docker, instala deps, migra e inicia serve + vite + queue + reverb.

set -e

# Cores e símbolos para logs
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'
OK="${GREEN}✔${NC}"
FAIL="${RED}✖${NC}"

# Diretório do projeto (raiz)
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
cd "$PROJECT_ROOT"

# Usar compose de dev para não conflitar com outros projetos (ex.: outro repo telemedicina)
export COMPOSE_FILE="${PROJECT_ROOT}/docker-compose.dev.yml"

log_ok()  { echo -e "${OK} $*"; }
log_err() { echo -e "${FAIL} $*" >&2; }
log_info() { echo -e "${YELLOW}→${NC} $*"; }

# Filtro de ruído dos logs no terminal (pode desligar com DEV_LOG_FILTER=0)
DEV_LOG_FILTER="${DEV_LOG_FILTER:-1}"

# --- 1. Verificar dependências do ambiente ---
check_requirements() {
  log_info "Verificando dependências do ambiente..."
  local missing=()

  command -v docker >/dev/null 2>&1 || missing+=("Docker (https://docs.docker.com/get-docker/)")
  if ! docker compose version >/dev/null 2>&1 && ! command -v docker-compose >/dev/null 2>&1; then
    missing+=("Docker Compose (plugin ou docker-compose)")
  fi
  command -v php >/dev/null 2>&1 || missing+=("PHP (https://www.php.net/downloads)")
  command -v composer >/dev/null 2>&1 || missing+=("Composer (https://getcomposer.org)")
  command -v node >/dev/null 2>&1 || missing+=("Node.js (https://nodejs.org)")
  command -v npm >/dev/null 2>&1 || missing+=("npm (vem com Node.js)")

  if [ ${#missing[@]} -gt 0 ]; then
    log_err "Faltam ferramentas necessárias:"
    for m in "${missing[@]}"; do echo "  - $m"; done
    echo ""
    echo "Instale os itens acima e tente novamente."
    exit 1
  fi
  log_ok "Dependências do ambiente OK."
}

# Comando Docker Compose (plugin ou binário)
docker_compose_cmd() {
  if docker compose version >/dev/null 2>&1; then
    docker compose "$@"
  else
    docker-compose "$@"
  fi
}

env_value() {
  local key="$1"
  local value
  value="$(printenv "$key" 2>/dev/null || true)"
  if [ -n "$value" ]; then
    printf '%s' "$value"
    return 0
  fi

  if [ -f .env ]; then
    value="$(grep -E "^${key}=" .env | head -n1 | cut -d= -f2- | sed -e 's/^"//' -e 's/"$//' -e "s/^'//" -e "s/'$//")"
    printf '%s' "$value"
  fi
}

# --- 2. .env a partir de .env.example ---
ensure_env() {
  if [ ! -f .env ]; then
    log_info "Criando .env a partir de .env.example..."
    cp .env.example .env
    log_ok ".env criado."
  else
    log_ok ".env já existe."
  fi
}

# --- 3. composer install (necessário para key:generate e artisan) ---
install_php_deps() {
  local need_install=
  if [ ! -f vendor/autoload.php ]; then
    need_install=1
  elif [ -f composer.lock ] && [ composer.lock -nt vendor/autoload.php ]; then
    need_install=1
  fi
  if [ -n "$need_install" ]; then
    log_info "Instalando dependências PHP..."
    composer install --no-interaction --prefer-dist
    log_ok "Dependências PHP instaladas."
  else
    log_ok "Dependências PHP já instaladas."
  fi
}

# --- 4. APP_KEY ---
ensure_app_key() {
  if ! grep -q '^APP_KEY=base64:' .env 2>/dev/null; then
    log_info "Gerando APP_KEY..."
    php artisan key:generate --ansi --force
    log_ok "APP_KEY gerado."
  else
    log_ok "APP_KEY já configurado."
  fi
}

# --- 5. Subir containers de infraestrutura ---
start_infra() {
  local services="postgres redis rabbitmq"
  if [ -f .env ] && grep -qE '^DB_CONNECTION=mysql' .env; then
    services="mysql redis rabbitmq"
  fi
  log_info "Iniciando containers Docker ($services)..."
  docker_compose_cmd up -d $services
  log_ok "Containers Docker em execução."
}

# --- 6. Aguardar banco ficar saudável ---
wait_for_db() {
  local max=60
  local i=0
  local db_connection="pgsql"
  [ -f .env ] && db_connection=$(grep -E '^DB_CONNECTION=' .env | cut -d= -f2 | tr -d '"' | tr -d "'" || true)
  [ -z "$db_connection" ] && db_connection="pgsql"

  log_info "Aguardando banco de dados ($db_connection) ficar pronto (até ${max}s)..."

  while [ $i -lt $max ]; do
    if [ "$db_connection" = "pgsql" ]; then
      if docker_compose_cmd exec -T postgres pg_isready -h localhost -U postgres >/dev/null 2>&1; then
        log_ok "PostgreSQL pronto."
        return 0
      fi
    elif [ "$db_connection" = "mysql" ]; then
      local db_password
      db_password="$(env_value DB_PASSWORD)"
      if [ -n "$db_password" ]; then
        if docker_compose_cmd exec -T mysql mysqladmin ping -h localhost -u root -p"$db_password" >/dev/null 2>&1; then
          log_ok "MySQL pronto."
          return 0
        fi
      elif docker_compose_cmd exec -T mysql mysqladmin ping -h localhost -u root >/dev/null 2>&1; then
        log_ok "MySQL pronto."
        return 0
      fi
    fi
    sleep 1
    i=$((i + 1))
  done

  log_err "Timeout: banco de dados não respondeu em ${max}s."
  echo "  Verifique se os containers estão rodando: docker compose ps"
  echo "  Para PostgreSQL: docker compose exec postgres pg_isready -U postgres"
  exit 1
}

# --- 7. npm install ---
install_node_deps() {
  local need_install=
  if [ ! -d node_modules ]; then
    need_install=1
  elif [ -f package-lock.json ] && [ package-lock.json -nt node_modules ]; then
    need_install=1
  fi
  if [ -n "$need_install" ]; then
    log_info "Instalando dependências Node..."
    npm install
    log_ok "Dependências Node instaladas."
  else
    log_ok "Dependências Node já instaladas."
  fi
}

# --- 8. Migrações ---
run_migrations() {
  local app_env
  app_env="$(env_value APP_ENV)"
  if [ "$app_env" = "production" ]; then
    log_err "APP_ENV=production detectado. Abortando para evitar dano ao banco de produção."
    exit 1
  fi

  log_info "Rodando migrações..."
  if ! php artisan migrate --force --ansi; then
    log_err "Falha ao rodar migrações."
    echo "  Verifique DB_* no .env e se o container do banco está saudável."
    exit 1
  fi
  log_ok "Migrações concluídas."
}

# --- 9. Seed (opcional) ---
run_seed_if_requested() {
  if [ -n "$RUN_SEED" ] && [ "$RUN_SEED" != "0" ]; then
    log_info "Rodando seeders..."
    php artisan db:seed --force --ansi
    log_ok "Seed concluído."
    return 0
  fi
  for arg in "$@"; do
    if [ "$arg" = "--seed" ]; then
      log_info "Rodando seeders (--seed)..."
      php artisan db:seed --force --ansi
      log_ok "Seed concluído."
      return 0
    fi
  done
}

# --- 10. Iniciar servidores (concurrently) ---
start_servers() {
  echo ""
  log_ok "Ambiente pronto!"
  if [ "$DEV_LOG_FILTER" = "1" ]; then
    log_info "Filtro de logs ativo (DEV_LOG_FILTER=0 para desativar)."
  else
    log_info "Filtro de logs desativado."
  fi
  echo ""
  local server_cmd
  local queue_cmd
  local vite_cmd
  local reverb_cmd
  local scheduler_cmd

  if [ "$DEV_LOG_FILTER" = "1" ]; then
    server_cmd="php artisan serve 2>&1 | stdbuf -oL -eL awk '{
      if (\$0 ~ /\\.(png|jpe?g|gif|webp|svg|ico|css|js|map|woff2?|ttf)(\\?|$)/) next;
      if (\$0 ~ /\\/(storage|build|images|favicon\\.ico)(\\?|$)/) next;
      print \$0;
      fflush();
    }'"
    queue_cmd="php artisan queue:work --tries=1 --quiet"
    vite_cmd="npm run dev -- --clearScreen false --logLevel warn"
    reverb_cmd="php artisan reverb:start"
    scheduler_cmd="php artisan schedule:work 2>&1 | stdbuf -oL -eL awk '!/No scheduled commands are ready to run/ { print; fflush(); }'"
  else
    server_cmd="php artisan serve"
    queue_cmd="php artisan queue:listen --tries=1"
    vite_cmd="npm run dev -- --clearScreen false"
    reverb_cmd="php artisan reverb:start"
    scheduler_cmd="php artisan schedule:work"
  fi

  exec npx concurrently \
    --prefix "[{time}][{name}]" \
    --timestamp-format "HH:mm:ss" \
    -c "#93c5fd,#c4b5fd,#fdba74,#bbf7d0,#fde68a" \
    "$server_cmd" \
    "$queue_cmd" \
    "$vite_cmd" \
    "$reverb_cmd" \
    "$scheduler_cmd" \
    --names='server,queue,vite,reverb,scheduler'
}

# --- Main ---
main() {
  check_requirements
  ensure_env
  install_php_deps
  ensure_app_key
  start_infra
  wait_for_db
  install_node_deps
  run_migrations
  run_seed_if_requested "$@"
  start_servers
}

main "$@"
