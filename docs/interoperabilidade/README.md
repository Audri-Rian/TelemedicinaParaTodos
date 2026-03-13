# Interoperabilidade — Ecossistema de documentação

Esta pasta concentra toda a documentação da **feature de Interoperabilidade** do Telemedicina para Todos: propósito, níveis de maturidade, UX, produto/MVP, arquitetura e métricas.

---

## O que é Interoperabilidade aqui?

**Interoperabilidade** é a capacidade do sistema de **consumir e expor serviços** para parceiros externos (hospitais, laboratórios, farmácias, convênios) por meio de **protocolos padronizados** (APIs REST documentadas, eventualmente FHIR/HL7).

O **propósito real** não é só “ter uma API”, e sim **transformar o sistema em um hub de saúde conectado** — parte do ecossistema de saúde, e não uma aplicação isolada.

| Sem interoperabilidade     | Com interoperabilidade                                      |
|----------------------------|-------------------------------------------------------------|
| Paciente → Telemedicina → Fim | Paciente → Telemedicina → Lab → Farmácia → Plano → Hospital |

---

## Como navegar nesta documentação

### Por objetivo

| Se você quer… | Documento |
|---------------|-----------|
| Entender **por que** e **para quem** (propósito, personas, casos de uso) | [Análise de propósito, UX e personas](TASK_INTEROPERABILIDADE_ANALISE_UX_PERSONAS.md) |
| Ver a **intenção oficial** da feature (task, escopo, status) | [Task — Intenção da feature](TASK_INTEROPERABILIDADE_FEATURE.md) |
| Saber **em que nível** estamos e para onde evoluir | [Níveis de maturidade](NiveisMaturidade.md) |
| Definir **o que construir primeiro** (fluxo, MVP) | [Produto, MVP e roadmap](Produto-MVP-Roadmap.md) |
| Desenhar **interface e fluxos** da gestão de integrações | [UX da feature de integrações](UX-Integracoes.md) |
| Pensar **arquitetura** (camadas, adapters, eventos, Laravel) | [Arquitetura](Arquitetura.md) |
| Definir **como medir** sucesso da feature | [Métricas e KPIs](Metricas.md) |

### Por tipo de conteúdo

| Tipo | Documentos |
|------|------------|
| **Visão e produto** | [Task — Intenção](TASK_INTEROPERABILIDADE_FEATURE.md), [Análise UX e personas](TASK_INTEROPERABILIDADE_ANALISE_UX_PERSONAS.md), [Produto-MVP-Roadmap](Produto-MVP-Roadmap.md), [Níveis de maturidade](NiveisMaturidade.md) |
| **Experiência de uso** | [Análise UX e personas](TASK_INTEROPERABILIDADE_ANALISE_UX_PERSONAS.md), [UX da feature de integrações](UX-Integracoes.md) |
| **Implementação e operação** | [Arquitetura](Arquitetura.md), [Métricas](Metricas.md) |

---

## Documentos desta pasta

| Documento | Conteúdo |
|-----------|----------|
| **[README.md](README.md)** (este arquivo) | Índice do ecossistema e navegação. |
| **[TASK_INTEROPERABILIDADE_FEATURE.md](TASK_INTEROPERABILIDADE_FEATURE.md)** | Objetivo, motivação, escopo de alto nível e status da feature. |
| **[TASK_INTEROPERABILIDADE_ANALISE_UX_PERSONAS.md](TASK_INTEROPERABILIDADE_ANALISE_UX_PERSONAS.md)** | Propósito real, problemas que resolve, personas, casos de uso, UX para usuário final, UX da gestão de integrações, riscos e evolução estratégica. |
| **[NiveisMaturidade.md](NiveisMaturidade.md)** | Níveis 1 a 4 (integração técnica → operacional → plataforma → ecossistema); referências Stripe, Slack, Epic/HL7-FHIR. |
| **[UX-Integracoes.md](UX-Integracoes.md)** | Hub de integrações, cards, fluxo de conexão (tipo OAuth), logs; modelo mental do usuário. |
| **[Produto-MVP-Roadmap.md](Produto-MVP-Roadmap.md)** | Pergunta de produto (“qual fluxo primeiro?”), MVP 1 (laboratório), MVP 2 (farmácia), MVP 3 (exportação hospitalar), impacto estratégico. |
| **[Arquitetura.md](Arquitetura.md)** | Camada de interoperabilidade, adapters, eventos; estrutura Laravel sugerida (Contracts, Services, Adapters, DTOs, Events). |
| **[Metricas.md](Metricas.md)** | KPIs: integrações ativas, fluxos automatizados, redução de uploads, tempo em consulta, erros. |

---

## Onde a Interoperabilidade aparece na documentação geral

- **Visão geral do projeto:** [index/VisaoGeral.md](../index/VisaoGeral.md) — seção Estrutura da Documentação.
- **Índice central:** [index/README.md](../index/README.md).

---

*Última atualização: março/2025.*
