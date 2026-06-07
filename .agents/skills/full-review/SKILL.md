---
name: full-review
description: Executa review completo de segurança e performance nos arquivos staged, modificados ou passados explicitamente. Use quando o usuário disser "/review-full", "review full", "revisão completa", "rode todos os reviewers" ou pedir security + performance antes de commit.
---

# Full Review — Security + Performance

Run a consolidated review over the requested scope, combining the local `security-reviewer` and `performance-reviewer` instructions mirrored from `.claude/agents/`.

## Scope Resolution

1. If the user provided explicit files, analyze only those files.
2. Otherwise run `git diff --cached --name-only`.
3. If there are no staged files, run `git diff --name-only HEAD`.
4. If no files are found, say there is nothing to review.

## Review Process

1. List the files that will be analyzed.
2. Apply the `security-reviewer` checklist to the same scope.
3. Apply the `performance-reviewer` checklist to the same scope.
4. Prioritize real issues only. Do not scan the entire project unless a touched file requires a specific supporting lookup.

## Output Format

```markdown
# Full Review — <scope summary>

Arquivos analisados:

- <file>

---

## Security

<security findings or "Sem problemas encontrados">

---

## Performance

<performance findings or "Sem problemas encontrados">

---

## Resumo consolidado

| Reviewer    | Critical | High | Medium | Low |
| ----------- | -------: | ---: | -----: | --: |
| Security    |        X |    X |      X |   X |
| Performance |        X |    X |      X |   X |

## Veredito

<pronto para commit / corrigir críticos / reavaliar>
```
