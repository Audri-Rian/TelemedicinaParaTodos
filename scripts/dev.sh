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

log_ok()  { echo -e "${OK} $*"; }
log_err() { echo -e "${FAIL} $*" >&2; }
log_info() { echo -e "${YELLOW}→${NC} $*"; }

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
      if docker_compose_cmd exec -T mysql mysqladmin ping -h localhost -u root -proot_secret >/dev/null 2>&1; then
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
  echo ""
  exec npx concurrently -c "#93c5fd,#c4b5fd,#fdba74,#bbf7d0" \
    "php artisan serve" \
    "php artisan queue:listen --tries=1" \
    "npm run dev" \
    "php artisan reverb:start" \
    --names='server,queue,vite,reverb'
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
