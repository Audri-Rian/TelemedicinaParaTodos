## 🧠 Camada de Arquitetura & Governança

Responsável por **definir como o sistema é estruturado, evolui e é governado** ao longo do tempo:

- Padrões arquiteturais.
- Regras de negócio e compliance.
- Decisões técnicas (ADRs implícitas nos documentos).
- Diagramas, guias de desenvolvimento e roadmap técnico.

### 🎯 Responsabilidades

- Descrever a **arquitetura macro** (backend, frontend, real-time, banco, infraestrutura).
- Padronizar:
  - Convenções de código (backend e frontend).
  - Organização de pastas, módulos e camadas.
  - Processos de desenvolvimento (fluxo de desenvolvimento e testes).
- Manter rastreabilidade:
  - Requisitos ↔ implementação ↔ testes.
  - Regras de sistema ↔ módulos ↔ fluxos de negócio.

### 🧩 Tecnologias / Conceitos Envolvidos

- Arquitetura em camadas, DDD Light.
- SOLID, PSR-12, boas práticas Vue/TypeScript.
- Event-Driven e real-time (Reverb, Echo, Events/Jobs).

### 📂 Documentos Relacionados

- Arquitetura geral (nesta camada):
  - `Architecture/Arquitetura.md` – arquitetura de backend/frontend, camadas e convenções.
  - `Architecture/DevGuide.md`, `VueGuide.md`, `CreatePages.md`, `README.md`.
- Documentos em docs (raiz):
  - `../../UX_ARCHITECTURE.md` – arquitetura de UX.
  - `../../CONSULTATION_FLOW.md` – fluxo de consultas ponta a ponta.
- Diagramas (nesta camada):
  - `diagrams/01_ArquiteturaGeral.md`
  - `diagrams/07_ArquiteturaCamadas.md`
  - `diagrams/02_FluxoConsulta.md`, `03_FluxoAutenticacao.md`, `04_FluxoVideoconferencia.md`
  - `diagrams/05_ComponentesFrontend.md`, `06_FluxoAgendamento.md`
  - `diagrams/INDICE.md`, `diagrams/README.md`
- Requisitos e regras (nesta camada):
  - `requirements/README.md`
  - `requirements/SystemRules.md`
  - `requirements/FuncionalitsGuide.md`
- Índice central (docs):
  - `../../../index/VisaoGeral.md`, `MatrizRequisitos.md`, `Glossario.md`, `README.md`
- Governança / débito técnico:
  - `../../TrueIssues.md`
  - `../../Pending Issues/CONFORMIDADE_CFM_LGPD.md`
  - `../../Pending Issues/ROADMAP_MONETIZACAO_VISUAL.md`
  - `../../Pending Issues/Ideias.md`

### 🤝 Relação com Outras Camadas

- Define **contratos e padrões** que as outras camadas seguem.
- Especifica:
  - Como Services, Models, Controllers e Events devem ser organizados.
  - Como o frontend é estruturado (layouts, pages, components, composables).
  - Como fluxos de mensagens e videochamadas se encaixam na arquitetura.

### 📈 Boas Práticas de Governança

- Manter a documentação:
  - **Versionada** (indicando versão e data, como já existe em vários arquivos).
  - **Enxuta e atualizada**, evitando documentos mortos.
- Sempre que:
  - Introduzir um módulo importante (ex.: billing, prescrições, notificações).
  - Fazer mudança arquitetural relevante (ex.: migração para SFU, microserviços).
  - → Registrar em um documento de decisão (pode ser uma seção “Decisão” nos próprios arquivos ou futuros ADRs).

### 🔮 Evoluções Futuras

- Introduzir um diretório formal de **ADRs** (Architecture Decision Records), ex:
  - `docs/adr/0001-escolha-laravel-reverb.md`
  - `docs/adr/0002-webRTC-p2p-vs-sfu.md`
- Formalizar um **guia de segurança** consolidando:
  - Compliance CFM/LGPD.
  - Políticas de retenção de dados médicos.
  - Requisitos de criptografia e logs de auditoria.
- Criar um **roadmap técnico** organizado por tema:
  - Escalabilidade.
  - Observabilidade.
  - Experiência do médico/paciente.
  - Integrações externas (pagamentos, prescrições eletrônicas).

