# Claude Quality Pipeline — Kit Portátil (Laravel + Vue)

Kit pronto para replicar a pipeline de qualidade de código e os reviewers do Claude Code em projetos **Laravel 12 + Inertia + Vue 3** (ou equivalentes PHP/Node). Hooks Husky, skills, agents e slash commands ajustados ao ecossistema.

## O que tem aqui

```
claude-quality-pipeline/
├── install.sh          ← script que instala tudo no teu projeto
├── README.md           ← este arquivo
└── templates/          ← todos os arquivos que serão copiados
    ├── .husky/         ← git hooks (pre-commit, commit-msg, pre-push, post-commit)
    │                     pre-commit: Pint (PHP) + ESLint + lint-staged
    ├── .claude/
    │   ├── agents/     ← security-reviewer e performance-reviewer focados Laravel+Vue
    │   ├── commands/   ← /review-full, /review-security, /review-performance
    │   └── skills/     ← commit-message (pt-BR) e development-rules (placeholder)
    ├── .vscode/        ← settings + extensões recomendadas
    ├── .editorconfig   ← padrão Node (2 spaces); não sobrescreve se já existir
    ├── .prettierrc     ← padrão Node; não sobrescreve se já existir
    ├── .prettierignore
    ├── .lintstagedrc.json
    └── commitlint.config.cjs (ou .js em projetos CommonJS)
```

## Pré-requisitos

- Repositório git inicializado (`git init`)
- `package.json` e `composer.json` presentes (o kit detecta ambos)
- Node 18+ e PHP 8.2+ (se o projeto for Laravel)
- Um dos package managers: `pnpm`, `npm` ou `yarn`

## Como usar

### 1. Abre um terminal e vai pro teu projeto alvo

```bash
cd ~/Github/meu-projeto
```

### 2. Roda o install.sh apontando pra este kit

```bash
bash "/caminho/para/docs/claude-quality-pipeline/install.sh"
```

O script vai:

1. Checar se é um repo git
2. Checar se tem `package.json` (e detectar `composer.json` pra ativar Pint no pre-commit)
3. Detectar qual package manager usar (pnpm, npm ou yarn)
4. Instalar as dependências de dev necessárias
5. Copiar os arquivos de configuração — **sem sobrescrever** `.prettierrc`, `.editorconfig` ou `.prettierignore` existentes
6. Inicializar o Husky e dar permissão de execução nos hooks
7. Mostrar os próximos passos

### 3. Depois da instalação — passos manuais

- [ ] Preenche `.claude/skills/development-rules/SKILL.md` com a stack e regras do teu projeto (ou peça ao Claude Code pra fazer isso lendo teu código)
- [ ] Revisa `.claude/agents/*.md` — os checklists do kit já são Laravel+Vue; remova/adapte o que não se aplicar
- [ ] Adiciona ao teu `package.json` os scripts que os hooks esperam:
    ```json
    "scripts": {
      "lint": "eslint . --cache",
      "lint:fix": "eslint . --fix --cache",
      "lint:fix-safe": "eslint . --fix --fix-type directive --cache",
      "format": "prettier --write .",
      "format:check": "prettier --check ."
    }
    ```
- [ ] Se teu projeto tiver build, já está contemplado — se não, edite ou remova `.husky/pre-push`
- [ ] Reinicia o VS Code pra carregar as extensões recomendadas
- [ ] No Claude Code, rode `/review-security` e `/review-performance` nos arquivos staged antes do push

## O que é Laravel+Vue nos agents

Os reviewers deste kit cobrem checklist específico do ecossistema:

- **security-reviewer**: mass assignment, Policies/Gates, FormRequest, CSRF (web vs api), XSS em Blade e Vue, uploads via Storage, webhooks com HMAC, LGPD (audit logs de prontuário)
- **performance-reviewer**: N+1 Eloquent, `with()`/`select()`/`chunk()`, cache Redis, ShouldQueue, Inertia props pesadas, Vue re-renders, Reverb broadcast, Http::pool em integrations

Se teu projeto for puro Node (Next.js, Remix, etc.), os checklists ainda ajudam em 70% — mas vale editar a seção correspondente.

## Arquivos já prontos pra usar

- **`commit-message` skill** — Conventional Commits em pt-BR, aceita inicial maiúscula OU minúscula (consistente com `commitlint.config.cjs (ou .js em projetos CommonJS)`)
- **`security-reviewer` agent** — Laravel + Vue, OWASP adaptado
- **`performance-reviewer` agent** — Eloquent, cache, queues, Inertia, Vue
- **`/review-full`, `/review-security`, `/review-performance`** — slash commands prontos

## Arquivos que precisam customização

- **`development-rules` skill** — placeholders `{{...}}`. Preencher manualmente ou pedir ao Claude Code.
- **Agents** — checklist Laravel+Vue é bom default; se tua stack é diferente, revise.

## Fluxo de uso depois de instalado

```
1. Implementa feature (skills carregam contexto sozinhas)
2. git add + git commit
   ├─ pre-commit: Pint (PHP) + ESLint + Prettier staged
   └─ commit-msg: valida Conventional Commits em pt-BR
3. No Claude Code: /review-full  ← antes do push
4. Corrige o que apontou
5. git push → pre-push: build
```

## Por que `.prettierrc` e `.editorconfig` não são sobrescritos

Projetos Laravel já têm convenções próprias (tab 4, printWidth 150, plugins Vue/Tailwind). O kit **preserva o que já existe** pra não quebrar formatação de milhares de arquivos. Se o teu projeto não tem esses arquivos, o kit copia o default dele (padrão Node: 2 spaces, 100 cols).

---

**Gerado automaticamente a partir do projeto backend PassKey e adaptado para Laravel + Vue no projeto Telemedicina Para Todos.**
