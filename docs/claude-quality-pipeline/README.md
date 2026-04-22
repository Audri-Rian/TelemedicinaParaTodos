# Claude Quality Pipeline — Kit Portátil

Kit pronto para replicar a pipeline de qualidade do projeto PassKey em qualquer projeto pessoal.

## O que tem aqui

```
claude-quality-pipeline/
├── install.sh          ← script que instala tudo no teu projeto
├── README.md           ← este arquivo
└── templates/          ← todos os arquivos que serão copiados
    ├── .husky/         ← git hooks (pre-commit, commit-msg, pre-push, post-commit)
    ├── .claude/        ← skills, agents, commands do Claude Code
    ├── .vscode/        ← settings + extensões recomendadas
    ├── .editorconfig
    ├── .prettierrc
    ├── .prettierignore
    ├── .lintstagedrc.json
    └── commitlint.config.js
```

## Como usar

### 1. Abre um terminal no WSL e vai pro teu projeto alvo

```bash
cd ~/Github/meu-projeto-pessoal
```

### 2. Roda o install.sh apontando pra este kit

```bash
bash "/mnt/c/Users/Audri/Desktop/claude-quality-pipeline/install.sh"
```

O script vai:

1. Checar se é um repo git
2. Detectar qual package manager tu usa (pnpm, npm ou yarn)
3. Instalar as dependências de dev necessárias
4. Copiar todos os arquivos de configuração
5. Inicializar o Husky e dar permissão de execução nos hooks
6. Mostrar os próximos passos

### 3. Depois da instalação — passos manuais

- [ ] Abre `.claude/skills/development-rules/SKILL.md` e preencha com a stack e regras do teu projeto
- [ ] Revisa `.claude/agents/*.md` — ajusta as referências de stack onde fizer sentido
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
- [ ] Se teu projeto tiver build, adiciona `"build": "..."` — ou remove o hook `pre-push` se não quiser build no push
- [ ] Reinicia o VS Code pra carregar as extensões recomendadas
- [ ] Abre o Claude Code no projeto e pede:
  > "Lê o meu código e preenche o `.claude/skills/development-rules/SKILL.md` com a stack, arquitetura e regras deste projeto."

## Arquivos já prontos pra usar

- **`commit-message` skill** — universal, já funciona
- **`security-reviewer` agent** — OWASP Top 10, genérico
- **`performance-reviewer` agent** — gargalos comuns em backend/frontend Node
- **`/review-full`, `/review-security`, `/review-performance`** — slash commands prontos

## Arquivos que precisam customização

- **`development-rules` skill** — está com placeholders. Precisa descrever teu projeto.
- **Agents** — revisar a seção "Stack" de cada um pra refletir teu projeto.

## Fluxo de uso depois de instalado

```
1. Implementa feature (skills carregam contexto sozinhas)
2. git add + git commit   ← hooks validam lint/format/mensagem
3. Repete commits até terminar
4. No Claude Code: /review-full
5. Corrige o que apontou
6. git push
```

---

**Gerado automaticamente a partir do projeto backend PassKey.**
