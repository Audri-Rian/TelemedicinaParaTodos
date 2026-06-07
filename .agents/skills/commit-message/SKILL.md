---
name: commit-message
description: Padrão de mensagens de commit (Conventional Commits em pt-BR). Use ao final de QUALQUER implementação para gerar a mensagem de commit que o dev vai copiar/colar. Valida tipo, escopo, descrição em português e formato do corpo. Aceita descrição com inicial minúscula ou maiúscula (o projeto pode escolher — mantém consistência com o commitlint.config.js).
---

# Padrão de Mensagens de Commit

## Formato

```
<tipo>(<escopo>): <descrição>

<corpo opcional>
```

## Tipos Válidos

| Tipo       | Uso                               | Exemplo                                  |
| ---------- | --------------------------------- | ---------------------------------------- |
| `feat`     | Nova funcionalidade               | `feat(auth): adiciona login com Google`  |
| `fix`      | Correção de bug                   | `fix(api): corrige timeout em /users`    |
| `refactor` | Mudança sem alterar comportamento | `refactor(utils): simplifica formatação` |
| `perf`     | Melhoria de performance           | `perf(list): memoriza render de itens`   |
| `style`    | Formatação, sem lógica            | `style: ajusta indentação`               |
| `test`     | Adicionar/alterar testes          | `test(auth): cria testes de login`       |
| `docs`     | Documentação                      | `docs: atualiza README`                  |
| `chore`    | Dependências, configuração        | `chore: atualiza dependências`           |
| `ci`       | CI/CD                             | `ci: ajusta workflow de deploy`          |
| `build`    | Sistema de build                  | `build: migra para Vite 6`               |
| `revert`   | Reverter commit anterior          | `revert: desfaz feat(auth)`              |

## Escopo

Parte do código modificada. Use pasta ou módulo (ex: `auth`, `api`, `integrations`, `doctor`, `patient`, `lgpd`, `ui`, `db`).

## Descrição (primeira linha)

**Obrigatório**:

- Verbo no **imperativo**: `adiciona`, `implementa`, `corrige`, `remove`, `refatora`, `atualiza`
- **Português brasileiro**
- **Sem ponto final**
- **Máximo 50 caracteres** (soft limit; 72 é o teto duro)

**Opcional** (siga o padrão já estabelecido no repo — olhe `git log`):

- Inicial **minúscula** (`feat(auth): adiciona refresh token`) — estilo Conventional Commits "clássico"
- Inicial **maiúscula** (`feat(auth): Adiciona refresh token`) — estilo adotado em alguns repositórios brasileiros

Se o repositório já mistura os dois, mantenha o que estiver mais recente. O `commitlint.config.js` deste kit tolera ambos.

### Corretos

```
feat(auth): implementa refresh token
feat(auth): Implementa refresh token
fix(appointments): corrige ordenação por data
refactor(services): extrai lógica de notificação
```

### Incorretos

```
feat(auth): Implementação de refresh token.   ❌ substantivo (não imperativo), ponto final
fix(list): Fixed sort issue                   ❌ inglês
refactor(hooks): refatorado o fetch           ❌ particípio, não imperativo
```

## Corpo (opcional)

Separado da descrição por **uma linha em branco**.

- Máximo **5 linhas** (itens com hífen)
- Máximo **72 caracteres** por linha (soft; regra `body-max-line-length` está desligada no kit)
- Explicar **POR QUÊ**, não **O QUÊ**
- Usar **aspas simples** em referências a código

## Output esperado ao final de uma task

Sempre termine retornando um bloco assim:

```
## Mensagem de commit

<bloco de código com a mensagem>

## Comando

git add <arquivos>
git commit -m "<mensagem>"
```

Se o corpo tiver múltiplas linhas, use HEREDOC:

```
git commit -m "$(cat <<'EOF'
feat(integrations): adiciona suporte para webhooks FHIR

- valida assinatura HMAC antes de processar payload
- enfileira job idempotente para evitar duplicação
- registra evento em IntegrationEvent para auditoria
EOF
)"
```
