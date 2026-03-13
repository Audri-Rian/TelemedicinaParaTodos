# Níveis de maturidade da interoperabilidade

Nem toda interoperabilidade é igual. Este documento descreve **níveis de evolução** que ajudam a decidir o que construir agora e como evoluir a feature ao longo do tempo.

---

## Visão geral

| Nível | Nome | Foco | Valor percebido pelo usuário final |
|-------|------|------|-----------------------------------|
| 1 | Integração técnica | API básica | ⚠️ Quase nenhum |
| 2 | Integração operacional | Fluxos reais conectados | ✔ Fluxos automáticos, menos trabalho manual |
| 3 | Plataforma conectada | Hub + catálogo de integrações | ✔ Admin “só clica conectar” |
| 4 | Ecossistema | Terceiros criam apps na plataforma | ✔ Novos serviços (IA, dispositivos, etc.) |

---

## Nível 1 — Integração técnica (API básica)

O sistema apenas:

- **Expõe** endpoints (ex.: `GET /api/exams`, `POST /api/prescriptions`)
- **Consome** APIs externas quando chamado

**Uso típico:** parceiro integra manualmente; desenvolvedor depende da documentação (OpenAPI/Swagger).

**Problema:** o usuário final (médico, paciente, admin) **quase não percebe valor**. A capacidade existe, mas sem fluxos automatizados ou interface de gestão o benefício fica invisível.

**Quando faz sentido:** como base técnica para os níveis seguintes; não como objetivo final de produto.

---

## Nível 2 — Integração operacional

O sistema já possui **fluxos reais** conectados.

**Exemplo:** Consulta → Pedido de exame → Laboratório recebe automaticamente → Resultado volta para o prontuário.

Aqui entram:

- **Sincronização** entre sistemas
- **Eventos** (ex.: exame solicitado, resultado recebido)
- **Automação** de passos que antes eram manuais

**Valor para o usuário:**

- ✔ Fluxos automáticos (exames, receitas, cobertura)
- ✔ Menos trabalho manual (menos PDF, e-mail, upload)

Este nível é o **mínimo desejável** para que a feature seja percebida como útil por médicos, pacientes e administradores.

---

## Nível 3 — Plataforma conectada (hub de integrações)

O sistema funciona como **hub**: existe um **catálogo de integrações** e o administrador escolhe com quem conectar.

**Exemplo de tela:**

| Integrações disponíveis | Status |
|-------------------------|--------|
| ✔ Laboratório Hermes | Conectado |
| ✔ Farmácia Vida | Conectado |
| ✔ Plano de saúde X | Disponível — [Conectar] |

O admin **só clica em conectar**; não precisa contratar desenvolvedor para cada parceiro.

**Referências de modelo:** Slack (marketplace de apps), Stripe (dashboard + API). No nosso caso, o “marketplace” são parceiros de saúde (laboratórios, farmácias, convênios).

Este nível **muda o produto**: de “sistema com algumas integrações” para “plataforma de integrações”.

---

## Nível 4 — Ecossistema

Terceiros podem **criar aplicações** que rodam sobre a plataforma.

**Exemplos:**

- IA para análise de exames
- Integração com dispositivos médicos (monitores, glicosímetros)
- Telemonitoramento ou triagem automática

O sistema deixa de ser apenas um **software de telemedicina** e passa a ser uma **plataforma de saúde** com ecossistema de desenvolvedores e soluções especializadas.

**Requisitos típicos:** API estável, documentação, sandbox, eventos/webhooks, eventualmente SDK ou app store.

---

## Referências do mercado

| Modelo | O que faz | Lição para nós |
|--------|-----------|-----------------|
| **Stripe** | API poderosa **e** dashboard (pagamentos, clientes, webhooks, integrações) | API + interface; usuário não fica só na documentação. |
| **Slack** | Marketplace de apps (Google Drive, Jira, Notion, etc.) | Catálogo de integrações gera valor direto e expansão. |
| **Epic Systems / HL7-FHIR** | Padrões de saúde para troca de dados clínicos e interoperabilidade hospitalar | Avaliar FHIR/HL7 quando houver integração com hospitais ou sistemas que já usam esses padrões. |

---

## Alinhamento com o projeto

- O documento [Análise de propósito, UX e personas](TASK_INTEROPERABILIDADE_ANALISE_UX_PERSONAS.md) descreve uma evolução em 3 níveis (integração básica → marketplace → plataforma). Os **níveis 1 a 4** deste documento refinam essa visão, destacando que o Nível 1 sozinho não gera valor perceptível e que o Nível 4 é ecossistema de terceiros.
- O [Produto e MVP](Produto-MVP-Roadmap.md) e o [README](README.md) indicam por onde começar (fluxo exame ou receita) e como subir de maturidade.

---

*Última atualização: março/2025.*
