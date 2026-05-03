# TASK — Interoperabilidade: API para parceiros e integrações externas

## Intenção da feature

Este documento descreve **apenas o propósito** da nova feature de interoperabilidade. A implementação detalhada (arquitetura, UX, fluxos e priorização) será definida e estudada em momento posterior.

---

## Objetivo

Implementar **interoperabilidade** no projeto Telemedicina para Todos, no sentido definido pela validação corporativa:

> _Capacidade de consumir e expor serviços para sistemas legados ou parceiros externos via protocolos padronizados._

Ou seja:

- **Expor**: permitir que outros sistemas (hospitais, laboratórios, farmácias, seguradoras, etc.) consumam dados e funcionalidades do nosso sistema de forma segura e padronizada.
- **Consumir**: permitir que o nosso sistema integre dados e serviços de parceiros externos (ex.: resultados de laboratório, validação de cobertura, prescrições digitais).

Tudo isso por meio de **protocolos padronizados** (APIs REST documentadas, eventualmente padrões de saúde como FHIR/HL7, conforme requisitos futuros).

---

## Motivação

- **Validação corporativa** ([ValidaçãoCorp.md](../../ValidaçãoCorp.md)) exige interoperabilidade como um dos requisitos.
- Hoje o projeto possui APIs internas (rotas em `/api/*`) voltadas ao frontend (Inertia); **não** existe hoje uma API pública, documentada e preparada para integradores externos.
- Esta feature é tratada como **nova** em relação ao que já está implementado.

---

## Escopo (visão de alto nível)

- Definir e expor uma **API pública** pensada para parceiros (separada do uso interno do frontend).
- Oferecer **autenticação e autorização** adequadas para aplicações de terceiros (ex.: tokens, OAuth2, conforme decisão futura).
- **Documentar** a API (ex.: OpenAPI/Swagger) para facilitar integração por parceiros.
- Avaliar **padrões de saúde** (ex.: FHIR) para troca de dados com hospitais e laboratórios, conforme necessidade.
- Avaliar **consumo de serviços externos** (laboratórios, farmácias, etc.) e definir pontos de integração.

Detalhes de como isso será feito (tecnologia, UX de gestão de integrações, fluxos, segurança e priorização) ficam para o estudo e o planejamento futuros.

---

## Status

- **Estado**: MVP 1 (laboratório) implementado e funcional. Nível 1→2 de maturidade atingido.

### O que está implementado

| Escopo                                                       | Status          |
| ------------------------------------------------------------ | --------------- |
| API pública separada do frontend (`/api/v1/public/`)         | ✅ Implementado |
| Autenticação OAuth2 Client Credentials para parceiros        | ✅ Implementado |
| Documentação OpenAPI dos endpoints públicos                  | ✅ Implementado |
| Fluxo laboratório: pedido de exame → resultado no prontuário | ✅ Implementado |
| Adapter FHIR R4 genérico para laboratórios                   | ✅ Implementado |
| Webhook de resultado (inbound) com validação HMAC            | ✅ Implementado |
| Pull de resultados via cron (sync)                           | ✅ Implementado |
| Circuit breaker + fila de retry                              | ✅ Implementado |
| Padrão FHIR (mappers, DTOs, Bundle)                          | ✅ Implementado |

### O que ainda falta

| Escopo                                                     | Status                                              |
| ---------------------------------------------------------- | --------------------------------------------------- |
| MVP 2 — Integração com farmácia (receita digital)          | ⏳ Não iniciado                                     |
| MVP 3 — Exportação de dados para hospital                  | ⏳ Não iniciado                                     |
| Hub de integrações (catálogo, admin "só clica conectar")   | ⏳ Não iniciado — Nível 3                           |
| Integração com RNDS (listener registrado, envio não ativo) | ⏳ Aguarda `RNDS_ENABLED=true` e certificado e-CNPJ |

---

## Documentos relacionados

- **[README — Ecossistema de documentação](README.md)** — Índice completo: navegação por objetivo e por tipo de conteúdo.
- **[Análise de propósito, UX e personas](TASK_INTEROPERABILIDADE_ANALISE_UX_PERSONAS.md)** — Propósito além do corporativo, benefício ao usuário, UX e personas.
- **[Níveis de maturidade](NiveisMaturidade.md)** — Integração técnica → operacional → plataforma → ecossistema.
- **[Produto, MVP e roadmap](Produto-MVP-Roadmap.md)** — Qual fluxo automatizar primeiro; MVPs laboratório, farmácia, exportação.
- **[Arquitetura](Arquitetura.md)** — Camada de interoperabilidade, adapters, eventos, estrutura Laravel sugerida.
- **[Métricas e KPIs](Metricas.md)** — Como medir sucesso da feature.

---

_Última atualização: maio/2026._
