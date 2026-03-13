# Arquitetura da interoperabilidade

Este documento descreve a **arquitetura conceitual** da feature de interoperabilidade e uma **estrutura Laravel** sugerida para implementação, com foco em baixo acoplamento e extensibilidade.

---

## 1. Arquitetura conceitual

### 1.1 Visão em camadas

```
Sistema principal (aplicação Laravel)
         │
         │
   Interoperability Layer
   (orquestração, eventos, DTOs)
         │
         │
   Adapters / Connectors
         │
    ┌─────────────┬─────────────┬─────────────┐
    │ Laboratório │  Farmácia   │  Hospital   │
    │    API      │    API      │    API      │
    └─────────────┴─────────────┴─────────────┘
```

- **Sistema principal:** regras de negócio, prontuário, consultas, prescrições.
- **Interoperability Layer:** expõe e consome dados de forma padronizada; emite e consome eventos; não conhece detalhes de cada parceiro.
- **Adapters/Connectors:** um adapter por tipo de parceiro (ou por parceiro, se os protocolos forem diferentes). Traduz entre o modelo interno e a API do parceiro.

Cada parceiro tem um **adapter**. Assim evitamos acoplamento: trocar ou adicionar parceiro não exige mudar o núcleo do sistema.

### 1.2 Uso de eventos (event-driven)

Eventos ajudam a desacoplar “algo aconteceu no sistema” de “precisamos avisar o parceiro”.

**Exemplos de eventos:**

| Evento | Quando | Uso na interoperabilidade |
|--------|--------|----------------------------|
| `ExamRequested` | Médico solicita exame | Adapter do laboratório envia pedido |
| `ExamResultReceived` | Laboratório envia resultado | Atualiza prontuário, notifica se necessário |
| `PrescriptionCreated` | Receita digital emitida | Adapter da farmácia pode ser notificado (ou farmácia consulta API) |

Esses eventos podem acionar envio de dados, sincronização e webhooks para terceiros, conforme o desenho de cada integração.

---

## 2. Padrão Adapter

Cada parceiro (laboratório, farmácia, convênio, hospital) pode ter protocolo e contrato diferentes. O **Adapter**:

- Implementa uma **interface comum** (ex.: `IntegrationInterface` ou contratos por tipo: `LabIntegrationInterface`).
- Recebe DTOs ou entidades do nosso domínio e **traduz** para a API do parceiro.
- Recebe respostas do parceiro e **traduz** para o nosso modelo (ex.: resultado de exame → registro no prontuário).

Exemplo de nomes:

- `LabAdapter` (ou `LaboratorioHermesAdapter` se for específico)
- `PharmacyAdapter`
- `InsuranceAdapter` (convênio)

Novos parceiros = novos adapters, sem alterar a camada de orquestração.

---

## 3. Estrutura Laravel sugerida

Estrutura possível dentro do projeto Laravel, seguindo SOLID e mantendo integrações isoladas:

```
app/
├── Integrations/
│   ├── Contracts/
│   │   ├── IntegrationInterface.php
│   │   ├── LabIntegrationInterface.php      # opcional, se houver contrato por tipo
│   │   └── PharmacyIntegrationInterface.php
│   │
│   ├── Services/
│   │   └── IntegrationService.php           # orquestra adapters, eventos
│   │
│   ├── Adapters/
│   │   ├── LabAdapter.php
│   │   ├── PharmacyAdapter.php
│   │   └── InsuranceAdapter.php
│   │
│   ├── DTOs/
│   │   ├── ExamOrderDto.php
│   │   ├── ExamResultDto.php
│   │   └── PrescriptionValidationDto.php
│   │
│   └── Events/
│       ├── ExamRequested.php
│       ├── ExamResultReceived.php
│       └── PrescriptionCreated.php
```

**Observações:**

- **Contracts:** definem o que cada tipo de integração deve fazer (ex.: “enviar pedido de exame”, “receber resultado”), sem expor detalhes do parceiro.
- **Services:** aplicação usa o `IntegrationService` (ou serviços por domínio); o service chama o adapter correto e dispara eventos.
- **Adapters:** implementam os contracts e falam com a API externa (HTTP client, credenciais, etc.).
- **DTOs:** objetos de transferência entre nossa aplicação e os adapters; evitam expor entidades de domínio diretamente.
- **Events:** Laravel events (e listeners) para `ExamRequested`, `ExamResultReceived`, etc., podem acionar adapters ou notificações.

Esta estrutura é **proposta**; a implementação real pode variar conforme convenções do projeto (ex.: uso de módulos, namespaces diferentes). O importante é manter a separação entre **orquestração**, **adapters** e **parceiros**.

---

## 4. API pública vs. uso interno

- **Uso interno (frontend Inertia):** as rotas atuais em `/api/*` atendem o frontend; não precisam ser as mesmas da API pública.
- **API pública (parceiros):** deve ser **pensada para integradores externos**: documentação OpenAPI/Swagger, autenticação (tokens, OAuth2), versionamento e contratos estáveis. Pode ficar em prefixo dedicado (ex.: `/api/v1/public/`) ou subdomínio, conforme decisão de arquitetura.

A **Interoperability Layer** pode tanto **chamar** adapters (quando nosso sistema envia algo ao parceiro) quanto **expor** endpoints que parceiros consomem (quando o hospital busca dados nossos). Em ambos os casos, a camada de adapters/contracts mantém o núcleo protegido de mudanças nos protocolos externos.

---

## 5. Documentos relacionados

- [Task — Intenção da feature](TASK_INTEROPERABILIDADE_FEATURE.md) — escopo e objetivos
- [Produto e MVP](Produto-MVP-Roadmap.md) — o que construir primeiro (fluxo exame, receita ou exportação)
- [Níveis de maturidade](NiveisMaturidade.md) — evolução da integração técnica até ecossistema

---

*Última atualização: março/2025.*
