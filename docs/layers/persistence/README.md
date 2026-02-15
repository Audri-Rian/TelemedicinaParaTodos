## 🗄️ Camada de Persistência (Persistence Layer)

Responsável por **armazenar, consultar e versionar dados** do sistema de telemedicina:

- Banco relacional (consultas, prontuários, mensagens, videoconferências).
- Redis para cache, filas, sessões e contadores.
- Armazenamento de arquivos (documentos médicos, PDFs, assets).

### 🎯 Responsabilidades

- Garantir **integridade e consistência** dos dados de domínio:
  - `Appointments`, `Medical Records`, `Messages`, `VideoCallRoom`, `VideoCallEvent`, etc.
- Modelar entidades de domínio com Eloquent (relacionamentos, casts, scopes).
- Otimizar consultas com índices, paginação e acesso eficiente.
- Fornecer base para relatórios, auditoria e rastreabilidade.

### 🧩 Tecnologias Envolvidas

- **MySQL/PostgreSQL** – banco de dados principal.
- **Redis** – cache, filas, contadores, sessões (planejado/atual).
- **Laravel Eloquent** – ORM e migrations.
- **Sistema de arquivos / S3** – para documentos médicos e assets.

### 📂 Documentos Relacionados

- Banco de dados:
  - `../../database/README.md` – visão geral do banco.
  - `../../database/diagrama_banco_dados.md` – diagrama de entidades e relacionamentos.
- Requisitos e regras:
  - `../../requirements/SystemRules.md` – regras de sistema (consultas, prontuários, videoconferência).
  - `../../requirements/FuncionalitsGuide.md` – guia de funcionalidades.
- Módulos de domínio:
  - `../../modules/appointments/AppointmentsArchitecture.md`
  - `../../modules/appointments/AppointmentsLogica.md`
  - `../../modules/appointments/AppointmentsUXFlow.md`
  - `../../modules/appointments/AppointmentsFrontendIntegration.md`
  - `../../modules/MedicalRecords/MedicalRecordsDoctor.md`
  - `../../modules/MedicalRecords/MedicalRecordsPatient.md`
  - `../../modules/messages/README.md` – seção de estrutura de dados (`messages`).
  - `../../CONSULTATION_FLOW.md` – fluxo de consultas ligados ao modelo.

### 🔄 Fluxos Típicos

- **Consulta Médica**:
  1. Migrations definem tabelas (`appointments`, `appointment_logs`, `video_call_rooms`, etc.).
  2. Services (`AppointmentService`, `MedicalRecordService`) usam Models para ler/escrever.
  3. Observers e Events usam esses modelos para registrar logs e emitir eventos.

- **Mensagens**:
  - Tabela `messages` com índices em `(sender_id, receiver_id, created_at)` e `(receiver_id, read_at)` para performance.

- **Videoconferência**:
  - `VideoCallRoom` e `VideoCallEvent` registram histórico de salas e eventos, sustentando auditoria/compliance.

### 🤝 Dependências com Outras Camadas

- **Usada por**:
  - Camada de Aplicação (Services/Controllers) para executar regras de negócio.
  - Camada de Sinalização (para compor eventos baseados em estado atual).
  - Camada de Arquitetura & Governança (para garantir rastreabilidade e compliance).

### 📈 Boas Práticas e Escalabilidade

- Usar **UUIDs** para identificar entidades sensíveis (já aplicado no projeto).
- Manter **Soft Deletes** onde histórico é importante (mensagens, registros clínicos).
- Centralizar acesso complexo em **scopes de Eloquent** e **Services**, não em controllers.
- Monitorar:
  - Queries lentas (slow query log / APM).
  - Crescimento de tabelas críticas (logs, eventos de videochamada, mensagens).
- Introduzir **Redis** para:
  - Cache de listas estáticas e contadores de não lidas.
  - Armazenar sessões da aplicação.

### 🔮 Evoluções Futuras

- Migração planejada para **PostgreSQL**:
  - Ver `../../Pending Issues/TransitionPostgreeSQL.md`.
  - Aproveitar tipos avançados (JSONB, enums, range types) para modelar horários, logs, etc.
- Uso intensivo de **Redis**:
  - Ver `../../Pending Issues/TransitionRedis.md`.
  - Cache de consultas frequentes e contadores (ex.: mensagens não lidas).
- Event Sourcing / Event-Driven:
  - Introduzir eventos de domínio persistidos (ex.: `ConsultationScheduled`, `MessageSent`, `VideoCallEstablished`).
  - Alimentar projeções (read models) para dashboards e relatórios.

