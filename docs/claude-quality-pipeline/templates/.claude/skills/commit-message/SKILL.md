---
name: commit-message
description: Padrão de mensagens de commit (Conventional Commits em pt-BR). Use ao final de QUALQUER implementação para gerar a mensagem de commit que o dev vai copiar/colar. Valida tipo, escopo, descrição no imperativo em português e formato do corpo.
---

# Padrão de Mensagens de Commit

## Formato

```
<tipo>(<escopo>): <descrição>

<corpo opcional>
```

## Tipos Válidos

| Tipo       | Uso                               | Exemplo                                    |
| ---------- | --------------------------------- | ------------------------------------------ |
| `feat`     | Nova funcionalidade               | `feat(auth): adiciona login com Google`    |
| `fix`      | Correção de bug                   | `fix(api): corrige timeout em /users`      |
| `refactor` | Mudança sem alterar comportamento | `refactor(utils): simplifica formatação`   |
| `perf`     | Melhoria de performance           | `perf(list): memoriza render de itens`     |
| `style`    | Formatação, sem lógica            | `style: ajusta indentação`                 |
| `test`     | Adicionar/alterar testes          | `test(auth): cria testes de login`         |
| `docs`     | Documentação                      | `docs: atualiza README`                    |
| `chore`    | Dependências, configuração        | `chore: atualiza dependências`             |
| `ci`       | CI/CD                             | `ci: ajusta workflow de deploy`            |
| `build`    | Sistema de build                  | `build: migra para Vite 6`                 |
| `revert`   | Reverter commit anterior          | `revert: desfaz feat(auth)`                |

## Escopo

Parte do código modificada. Use pasta ou módulo (ex: `auth`, `api`, `ui`, `db`, `utils`).

## Descrição (primeira linha)

**Obrigatório**:
- Verbo no **imperativo**: `adiciona`, `implementa`, `corrige`, `remove`, `refatora`
- **Português brasileiro**
- **Sem ponto final**
- **Máximo 50 caracteres** (soft limit)
- **Letra minúscula** no início

### Corretos

```
feat(auth): implementa refresh token
fix(list): corrige ordenação por data
refactor(hooks): extrai lógica de fetch
```

### Incorretos

```
feat(auth): Implementação de refresh token.   ❌ maiúscula, ponto
fix(list): Fixed sort issue                   ❌ inglês
refactor(hooks): refatorado o fetch           ❌ particípio, não imperativo
```

## Corpo (opcional)

Separado da descrição por **uma linha em branco**.

- Máximo **5 linhas** (itens com hífen)
- Máximo **72 caracteres** por linha
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
