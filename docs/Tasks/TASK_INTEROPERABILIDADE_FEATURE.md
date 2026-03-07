# TASK — Interoperabilidade: API para parceiros e integrações externas

## Intenção da feature

Este documento descreve **apenas o propósito** da nova feature de interoperabilidade. A implementação detalhada (arquitetura, UX, fluxos e priorização) será definida e estudada em momento posterior.

---

## Objetivo

Implementar **interoperabilidade** no projeto Telemedicina para Todos, no sentido definido pela validação corporativa:

> *Capacidade de consumir e expor serviços para sistemas legados ou parceiros externos via protocolos padronizados.*

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

- **Estado**: Feature definida em nível de intenção; implementação **não iniciada**.
- **Próximos passos**: Estudo e definição de implementação completa, UX e roadmap.

---

*Documento criado para registrar a intenção da feature. Última atualização: março/2025.*
