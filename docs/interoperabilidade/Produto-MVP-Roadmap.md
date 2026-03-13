# Produto, MVP e pergunta de produto

Este documento define a **pergunta central** que orienta a feature, os **MVPs realistas** e o **impacto estratégico** da interoperabilidade no produto. Alinha decisões de “o que construir primeiro” com valor para o usuário.

---

## 1. A pergunta que define a feature

Antes de implementar, a pergunta certa **não** é:

- *“Como criar uma API?”*

E sim:

- **“Qual fluxo médico queremos automatizar primeiro?”**

A feature deve **nascer a partir do fluxo** (ex.: consulta → exame → resultado), não da API por si só. A API é o meio; o fluxo é o fim.

**Exemplos de resposta:**

- Fluxo **consulta → exame → resultado** (laboratório)
- Fluxo **consulta → receita → farmácia**
- Fluxo **agendamento → validação de convênio**

Escolher **um** fluxo inicial permite entregar valor rápido e aprender com uso real antes de generalizar.

---

## 2. MVP realista

Não é necessário começar com todos os parceiros. Abaixo, MVPs enxutos e com valor claro.

### MVP 1 — Integração com laboratório

**Fluxo:**

```
Consulta
   ↓
Médico solicita exame
   ↓
Sistema envia pedido ao laboratório parceiro
   ↓
Paciente realiza exame
   ↓
Resultado retorna automaticamente para o prontuário
```

**Valor:** elimina upload manual de PDF, e-mail e “trazer resultado na próxima consulta”. Só esse fluxo já gera ganho perceptível para médico e paciente.

**Requisitos mínimos:** API (ou integração ponto a ponto) para envio de pedido e recebimento de resultado; exibição no prontuário com origem e data.

---

### MVP 2 — Integração com farmácia

**Fluxo:**

```
Receita digital emitida na consulta
   ↓
Farmácia integrada recebe / valida
   ↓
Paciente compra medicamento
   ↓
(Opcional) Dispensação registrada no prontuário
```

**Valor:** receita digital aceita na farmácia, menos fraude e menos papel; possível rastreio de dispensação.

**Requisitos mínimos:** mecanismo de validação da receita (API ou código); opcional: evento de dispensação de volta ao sistema.

---

### MVP 3 — Exportação de dados para hospital

**Fluxo:** hospitais (ou sistemas hospitalares) **consomem** dados do nosso sistema, com consentimento e escopo definido.

**Exemplos de endpoints (conceitual):**

- `GET /api/patients/{id}` (resumo permitido por contrato/LGPD)
- `GET /api/exams` (exames do paciente)
- `GET /api/prescriptions` (prescrições recentes)

**Valor:** continuidade do cuidado quando o paciente vai à emergência ou internação; hospital vê histórico relevante sem depender de papel ou do paciente.

**Requisitos mínimos:** API pública documentada, autenticação e autorização, consentimento do paciente quando exigido por lei.

---

## 3. Ordem sugerida e dependências

| Fase | Foco | Depende de |
|------|------|-------------|
| MVP 1 | Laboratório (pedido + resultado) | API interna/externa, prontuário exibindo resultados, 1 parceiro piloto |
| MVP 2 | Farmácia (receita digital) | Receita digital já existente no sistema, contrato com 1 farmácia |
| MVP 3 | Exportação para hospital | API pública, autenticação, documentação (OpenAPI), LGPD/consentimento |

A **pergunta de produto** (“qual fluxo primeiro?”) pode ser respondida com **MVP 1** (exame) ou **MVP 2** (receita), conforme prioridade de negócio e disponibilidade de parceiro piloto.

---

## 4. Impacto estratégico

Interoperabilidade bem executada vira **vantagem competitiva** e **retenção**.

| Efeito | Descrição |
|--------|-----------|
| **Efeito de rede** | Quanto mais integrações (laboratório, farmácia, convênio), mais valor o sistema tem para a clínica e para o paciente. Sistema isolado tem pouco valor; sistema conectado vira padrão de uso. |
| **Lock-in saudável** | Quando a clínica conecta laboratório, farmácia e convênio, trocar de sistema passa a ser custoso. Isso aumenta retenção, desde que o valor entregue seja real (fluxos automáticos, menos trabalho). |
| **Crescimento do ecossistema** | Terceiros (outros laboratórios, farmácias, planos) passam a querer integrar. O produto evolui de “software” para “plataforma” (ver [Níveis de maturidade](NiveisMaturidade.md)). |

Por isso, faz sentido medir a feature (ver [Métricas](Metricas.md)) e priorizar um fluxo completo (MVP 1 ou 2) em vez de apenas expor endpoints soltos.

---

## 5. Documentos relacionados

- [Níveis de maturidade](NiveisMaturidade.md) — em que nível estamos e para onde evoluir
- [Análise de propósito, UX e personas](TASK_INTEROPERABILIDADE_ANALISE_UX_PERSONAS.md) — casos de uso e personas
- [Métricas e KPIs](Metricas.md) — KPIs para acompanhar sucesso da feature

---

*Última atualização: março/2025.*
