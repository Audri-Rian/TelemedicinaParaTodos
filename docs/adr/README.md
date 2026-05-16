# Architecture Decision Records (ADR)

Registro de decisões arquiteturais do projeto **Telemedicina Para Todos**.

ADRs documentam **por que** uma decisão foi tomada — contexto, alternativas descartadas, trade-offs aceitos e consequências. Servem para humanos, IAs e agentes entenderem o projeto sem redescobrir o passado.

---

## Como usar

### Criar novo ADR

1. Copie o template abaixo
2. Nomeie o arquivo: `NNNN-titulo-kebab-case.md` (ex.: `0002-uso-rabbitmq-filas.md`)
3. Preencha todas as seções
4. Atualize a tabela de índice neste README

### Template

```markdown
# ADR-NNNN: Título

**Status:** proposed | accepted | deprecated | superseded  
**Data:** YYYY-MM-DD  
**Decisores:** (nomes ou papéis)  
**Supersede/Relacionado:** ADR-XXXX

## Contexto

[Problema ou força que motivou a decisão]

## Decisão

[O que foi decidido, em uma frase direta]

## Alternativas consideradas

| Alternativa | Por que descartada |
| ----------- | ------------------ |
| ...         | ...                |

## Consequências

**Positivas:**

- ...

**Negativas / trade-offs:**

- ...

**Riscos residuais:**

- ...
```

---

## Índice

| #                                                   | Título                                            | Status     | Data       |
| --------------------------------------------------- | ------------------------------------------------- | ---------- | ---------- |
| [0001](0001-pades-icp-brasil-assinatura-digital.md) | Assinatura digital PAdES com certificado A1 local | `accepted` | 2026-05-14 |
