---
description: Executa TODOS os reviewers em paralelo (security, performance) nos arquivos staged
argument-hint: [arquivos opcionais]
---

Execute **todos os subagents de review** em paralelo nos arquivos staged.

**Escopo**:

- Se houver argumentos em `$ARGUMENTS`, use esses arquivos.
- Caso contrário, rode `git diff --cached --name-only` para pegar os staged.
- Se não houver staged, rode `git diff --name-only HEAD`.

**Instruções**:

1. Liste os arquivos que serão analisados
2. Invoque os subagents em paralelo (um único message com múltiplos tool uses):
   - `security-reviewer`
   - `performance-reviewer`
3. Agregue os relatórios num único output:

```markdown
# 🔍 Full Review — <resumo do escopo>

Arquivos analisados:

- <lista>

---

## 🔒 Security

<relatório>

---

## ⚡ Performance

<relatório>

---

## 📊 Resumo consolidado

| Reviewer    | Critical | High | Medium | Low |
| ----------- | -------- | ---- | ------ | --- |
| Security    | X        | X    | X      | X   |
| Performance | X        | X    | X      | X   |

## Veredito

<ação recomendada: 'pronto para commit', 'corrigir críticos', 'reavaliar'>
```
