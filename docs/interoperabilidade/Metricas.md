# Métricas e KPIs da interoperabilidade

Se a feature de interoperabilidade for lançada, é importante **medir** adoção, uso e impacto. Este documento lista **métricas sugeridas** para acompanhar o sucesso da feature e tomar decisões de produto e engenharia.

---

## 1. Métricas de adoção e uso

| Métrica | Descrição | Uso |
|---------|-----------|-----|
| **Integrações ativas** | Quantos parceiros (laboratórios, farmácias, convênios) estão conectados e ativos por clínica ou globalmente | Saber se a capacidade está sendo usada; priorizar suporte e novos parceiros |
| **Fluxos automatizados** | Quantos pedidos de exame enviados, resultados recebidos, receitas validadas ou coberturas consultadas por período | Medir “volume” real da interoperabilidade |
| **Redução de uploads manuais** | Comparar número de anexos/PDFs de exames antes e depois da integração com laboratório (por clínica ou por período) | Validar que o fluxo automático substitui o manual |

---

## 2. Métricas de experiência e eficiência

| Métrica | Descrição | Uso |
|---------|-----------|-----|
| **Tempo economizado em consulta** | Tempo médio que o médico leva para acessar/consultar resultados de exame (ou receitas) antes vs. depois da integração | Valor percebido pelo médico; justificar investimento |
| **Taxa de conclusão de fluxo** | % de “pedido de exame enviado” que resulta em “resultado recebido” no sistema (no prazo esperado) | Qualidade da integração e da parceria |
| **Erros de integração** | Quantidade de falhas (timeout, rejeição, dado inválido) por adapter ou por parceiro | Estabilidade; priorizar correções e suporte |

---

## 3. Métricas de produto e estratégia

| Métrica | Descrição | Uso |
|---------|-----------|-----|
| **Clínicas com pelo menos 1 integração ativa** | % ou número de clínicas que conectaram ao menos um laboratório, farmácia ou convênio | Penetração da feature; base para efeito de rede |
| **Parceiros com integração ativa** | Quantos laboratórios, farmácias ou planos distintos estão conectados à plataforma | Crescimento do ecossistema (ver [Produto e MVP](Produto-MVP-Roadmap.md)) |

---

## 4. Como usar

- **MVP 1 (laboratório):** priorizar integrações ativas, fluxos automatizados (pedidos + resultados), redução de uploads manuais e erros de integração.
- **MVP 2 (farmácia):** adicionar métricas de receitas validadas e dispensadas.
- **MVP 3 (exportação hospitalar):** adicionar consumo da API (chamadas por hospital/parceiro) e escopo de dados acessados.

As métricas devem ser **operacionalizáveis** (definir onde são coletadas: logs, analytics, banco). Detalhes de implementação (dashboards, ferramentas) ficam para o planejamento técnico.

---

## 5. Documentos relacionados

- [Produto e MVP](Produto-MVP-Roadmap.md) — impacto estratégico e priorização
- [Níveis de maturidade](NiveisMaturidade.md) — evolução da feature
- [Análise de propósito, UX e personas](TASK_INTEROPERABILIDADE_ANALISE_UX_PERSONAS.md) — valor para cada persona

---

*Última atualização: março/2025.*
