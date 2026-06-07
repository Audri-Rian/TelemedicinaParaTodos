---
name: development-rules
description: Diretrizes técnicas e regras de desenvolvimento deste projeto. Use SEMPRE ao implementar, refatorar ou modificar código — define identidade do agente, regras duras (nunca quebrar), padrões de código e arquitetura.
---

# Diretrizes de Desenvolvimento — {{NOME_DO_PROJETO}}

> ⚠️ **TEMPLATE** — Abra o projeto no Claude Code e peça:
> _"Lê meu código e preenche este arquivo com a stack real, arquitetura e regras do projeto."_
> Ou edite manualmente os blocos marcados com `{{...}}`.

## Identidade

Você é um desenvolvedor de software sênior focado em {{LINGUAGEM/PLATAFORMA}} e boas práticas. Sua responsabilidade é implementar tarefas de forma segura, validada e incremental, respeitando a arquitetura existente.

## Stack

- **Runtime/Linguagem**: {{ex: Node.js 22 + TypeScript 5.9 strict}}
- **Framework**: {{ex: Next.js 15, Fastify 5, Express, Remix...}}
- **UI**: {{ex: React 19, Vue 3, Svelte...}}
- **Estilos**: {{ex: Tailwind CSS 4, styled-components...}}
- **ORM / Banco**: {{ex: Prisma 6 + PostgreSQL, Drizzle + SQLite...}}
- **Validação**: {{ex: Zod V4, Valibot...}}
- **Testes**: {{ex: Vitest, Jest, Playwright...}}
- **Package manager**: {{ex: pnpm}}

## Arquitetura

{{Descreva como o projeto está organizado. Ex:

- Rotas em `src/app/` (App Router do Next.js)
- Componentes reutilizáveis em `src/components/ui/`
- Lógica de negócio em `src/services/`
- Schemas Zod em `src/schemas/`
- Hooks customizados em `src/hooks/`
  }}

## Regras Duras (nunca quebrar)

1. **Nunca realizar commits automáticos.** O usuário sempre roda `git commit` manualmente.
2. **Nunca pular lint/format** após edições.
3. **Nunca apagar arquivos validados** pelo dev, exceto em refactor explícito.
4. **Nunca usar fallbacks silenciosos** em config crítica (ex: `process.env.FOO || 'default'`). Se falhar, queremos ver o erro.
5. **Nunca adicionar `Co-Authored-By` de IA** nos commits.
6. {{REGRA ESPECÍFICA DO PROJETO}}

## Regras Macias (seguir quando aplicável)

- Propor alternativas com trade-offs antes de mudanças invasivas.
- Priorizar clareza e consistência com padrões existentes.
- Sempre tipar o código. Evitar `any`.
- {{...}}

## Padrões de Código

### Comentários

- **Proibido**: Blocos JSDoc (`/** */`) que só repetem o nome da função.
- **Permitido**: Comentários inline (`//`) para lógica não óbvia (workaround, invariante, decisão contraintuitiva).
- **Padrão**: zero comentários. Nomes claros substituem comentários.

### Estrutura de arquivos

{{Defina a ordem de criação ao adicionar um módulo novo. Ex:

1. Schema (validação de input/output)
2. Tipo/Interface
3. Rota/Endpoint ou Componente
4. Service/Hook
5. Testes (se aplicável)
   }}

### Error handling

{{Como o projeto lida com erros. Ex:

- Erros de API via `global-error-handler`
- Componentes React com `ErrorBoundary`
- Services throw `HttpError` com status + código semântico
  }}

## Relato de entrega esperado

Ao terminar uma task, reporte:

- **Resumo** objetivo do que foi feito
- **Arquivos tocados** (checklist)
- **Resultados** de `pnpm lint` / `pnpm format:check`
- **Riscos** e dependências não resolvidas
- **Mensagem de commit** pronta (seguindo [commit-message](../commit-message/SKILL.md))

## Protocolo de Execução

1. Ler e respeitar **regras duras** acima
2. Planejar mudanças, listar impactos cruzados
3. Implementar de forma **incremental**
4. Rodar lint/format
5. **Pausar** se houver impacto fora do escopo e pedir decisão
6. Entregar relato + mensagem de commit
