#!/usr/bin/env bash
# Claude Quality Pipeline — Installer
# Copia templates de qualidade de código e Claude Code para o projeto atual.

set -e

# Cores
BOLD='\033[1m'
CYAN='\033[36m'
YELLOW='\033[33m'
GREEN='\033[32m'
RED='\033[31m'
DIM='\033[2m'
RESET='\033[0m'

# Diretório do kit (onde este script está)
KIT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
TEMPLATES_DIR="$KIT_DIR/templates"

# Diretório alvo = CWD (onde o usuário rodou o script)
TARGET_DIR="$(pwd)"

printf "\n${BOLD}${CYAN}═══════════════════════════════════════════════════════════════${RESET}\n"
printf "${BOLD}${CYAN}  Claude Quality Pipeline — Installer${RESET}\n"
printf "${BOLD}${CYAN}═══════════════════════════════════════════════════════════════${RESET}\n\n"

printf "${DIM}Kit:    $KIT_DIR${RESET}\n"
printf "${DIM}Alvo:   $TARGET_DIR${RESET}\n\n"

# 1. Valida que é um repo git
if [ ! -d "$TARGET_DIR/.git" ]; then
  printf "${RED}✗ Este diretório não é um repositório git.${RESET}\n"
  printf "  Rode ${BOLD}git init${RESET} primeiro.\n\n"
  exit 1
fi
printf "${GREEN}✓${RESET} Repositório git detectado\n"

# 2. Valida que tem package.json
if [ ! -f "$TARGET_DIR/package.json" ]; then
  printf "${RED}✗ Não encontrei package.json neste diretório.${RESET}\n"
  printf "  Rode ${BOLD}npm init -y${RESET} primeiro.\n\n"
  exit 1
fi
printf "${GREEN}✓${RESET} package.json encontrado\n"

# 3. Detecta package manager
PM=""
if [ -f "$TARGET_DIR/pnpm-lock.yaml" ]; then
  PM="pnpm"
elif [ -f "$TARGET_DIR/yarn.lock" ]; then
  PM="yarn"
elif [ -f "$TARGET_DIR/package-lock.json" ]; then
  PM="npm"
else
  # Nenhum lockfile — pergunta
  printf "\n${YELLOW}Nenhum lockfile encontrado. Qual package manager usar?${RESET}\n"
  printf "  1) pnpm ${DIM}(recomendado)${RESET}\n"
  printf "  2) npm\n"
  printf "  3) yarn\n"
  printf "Escolha [1]: "
  read -r choice
  case "${choice:-1}" in
    1) PM="pnpm" ;;
    2) PM="npm" ;;
    3) PM="yarn" ;;
    *) PM="pnpm" ;;
  esac
fi
printf "${GREEN}✓${RESET} Package manager: ${BOLD}$PM${RESET}\n"

# 4. Confirmação
printf "\n${YELLOW}Este script vai:${RESET}\n"
printf "  • Instalar: husky, lint-staged, prettier, eslint, @commitlint/cli, @commitlint/config-conventional\n"
printf "  • Copiar arquivos de config (sobrescreve se já existir)\n"
printf "  • Copiar pasta .claude/ e .husky/\n"
printf "  • Rodar ${BOLD}$PM exec husky init${RESET}\n\n"
printf "Continuar? [y/N]: "
read -r confirm
if [ "$confirm" != "y" ] && [ "$confirm" != "Y" ]; then
  printf "${RED}Cancelado.${RESET}\n\n"
  exit 0
fi

# 5. Instala dependências
printf "\n${BOLD}${CYAN}[1/4] Instalando dependências...${RESET}\n"
case "$PM" in
  pnpm)
    pnpm add -D husky lint-staged prettier eslint @commitlint/cli @commitlint/config-conventional
    ;;
  npm)
    npm install -D husky lint-staged prettier eslint @commitlint/cli @commitlint/config-conventional
    ;;
  yarn)
    yarn add -D husky lint-staged prettier eslint @commitlint/cli @commitlint/config-conventional
    ;;
esac
printf "${GREEN}✓${RESET} Dependências instaladas\n"

# 6. Inicia Husky
printf "\n${BOLD}${CYAN}[2/4] Inicializando Husky...${RESET}\n"
$PM exec husky init > /dev/null 2>&1 || true
printf "${GREEN}✓${RESET} Husky inicializado\n"

# 7. Copia templates
printf "\n${BOLD}${CYAN}[3/4] Copiando arquivos...${RESET}\n"

copy_file() {
  local src="$1"
  local dest="$2"
  mkdir -p "$(dirname "$dest")"
  cp "$src" "$dest"
  printf "  ${GREEN}+${RESET} $(echo "$dest" | sed "s|$TARGET_DIR/||")\n"
}

copy_dir() {
  local src="$1"
  local dest="$2"
  mkdir -p "$dest"
  cp -r "$src"/. "$dest"/
  printf "  ${GREEN}+${RESET} $(echo "$dest" | sed "s|$TARGET_DIR/||")/ (pasta)\n"
}

# Configs raiz
copy_file "$TEMPLATES_DIR/.editorconfig" "$TARGET_DIR/.editorconfig"
copy_file "$TEMPLATES_DIR/.prettierrc" "$TARGET_DIR/.prettierrc"
copy_file "$TEMPLATES_DIR/.prettierignore" "$TARGET_DIR/.prettierignore"
copy_file "$TEMPLATES_DIR/.lintstagedrc.json" "$TARGET_DIR/.lintstagedrc.json"
copy_file "$TEMPLATES_DIR/commitlint.config.js" "$TARGET_DIR/commitlint.config.js"

# .vscode
copy_dir "$TEMPLATES_DIR/.vscode" "$TARGET_DIR/.vscode"

# .claude
copy_dir "$TEMPLATES_DIR/.claude" "$TARGET_DIR/.claude"

# .husky (hooks)
mkdir -p "$TARGET_DIR/.husky"
for hook in pre-commit commit-msg pre-push post-commit; do
  cp "$TEMPLATES_DIR/.husky/$hook" "$TARGET_DIR/.husky/$hook"
  chmod +x "$TARGET_DIR/.husky/$hook"
  # Substitui placeholder {{PM}} pelo package manager detectado
  sed -i "s/{{PM}}/$PM/g" "$TARGET_DIR/.husky/$hook"
  printf "  ${GREEN}+${RESET} .husky/$hook ${DIM}(exec)${RESET}\n"
done

# 8. Atualiza .gitignore
printf "\n${BOLD}${CYAN}[4/4] Atualizando .gitignore...${RESET}\n"
touch "$TARGET_DIR/.gitignore"
for entry in ".eslintcache" ".claude/settings.local.json" ".claude/projects/"; do
  if ! grep -qxF "$entry" "$TARGET_DIR/.gitignore" 2>/dev/null; then
    echo "$entry" >> "$TARGET_DIR/.gitignore"
    printf "  ${GREEN}+${RESET} $entry\n"
  fi
done

# 9. Sucesso
printf "\n${BOLD}${GREEN}═══════════════════════════════════════════════════════════════${RESET}\n"
printf "${BOLD}${GREEN}  ✓ Instalação concluída${RESET}\n"
printf "${BOLD}${GREEN}═══════════════════════════════════════════════════════════════${RESET}\n\n"

printf "${BOLD}Próximos passos manuais:${RESET}\n\n"
printf "1. ${YELLOW}Adiciona os scripts no package.json${RESET}:\n"
printf "   ${DIM}\"lint\": \"eslint . --cache\",${RESET}\n"
printf "   ${DIM}\"lint:fix\": \"eslint . --fix --cache\",${RESET}\n"
printf "   ${DIM}\"lint:fix-safe\": \"eslint . --fix --fix-type directive --cache\",${RESET}\n"
printf "   ${DIM}\"format\": \"prettier --write .\",${RESET}\n"
printf "   ${DIM}\"format:check\": \"prettier --check .\"${RESET}\n\n"

printf "2. ${YELLOW}Cria o eslint.config.mjs${RESET} (ESLint 9 flat config):\n"
printf "   ${DIM}https://eslint.org/docs/latest/use/configure/configuration-files${RESET}\n\n"

printf "3. ${YELLOW}Customiza .claude/skills/development-rules/SKILL.md${RESET}\n"
printf "   ${DIM}Abre no Claude Code e pede pra ele preencher com base no teu código:${RESET}\n"
printf "   ${DIM}\"Lê meu código e preenche o development-rules com stack e arquitetura\"${RESET}\n\n"

printf "4. ${YELLOW}Se teu projeto não tiver build${RESET}, edita ou remove ${BOLD}.husky/pre-push${RESET}\n\n"

printf "${BOLD}Teste rápido:${RESET}\n"
printf "  ${DIM}git commit --allow-empty -m \"chore: testa hooks\"${RESET}\n\n"
