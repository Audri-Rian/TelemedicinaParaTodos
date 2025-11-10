# Task: Núcleo de Agendamentos (Appointments)

## Por que iniciar por esta task
O módulo de agendamentos é a peça central que dá contexto operacional para todos os outros domínios: cada consulta marca o vínculo temporal e relacional entre médico e paciente, libera o `appointment_id` consumido pela videochamada, registra evidências para prontuário e mensageria, aciona notificações e, no futuro, servirá de base para faturamento e analytics. Sem uma fundação sólida aqui, qualquer camada superior precisaria de mocks ou assumiria regras inconsistentes, o que geraria retrabalho e riscos de segurança.

## Regras de Negócio
- Todo agendamento pertence simultaneamente a um médico ativo (`doctors.status = active`) e a um paciente com cadastro completo, via chaves estrangeiras obrigatórias.
- O ciclo de vida do appointment segue os status enumerados: `scheduled → in_progress → completed`, com desvios controlados para `cancelled`, `rescheduled` e `no_show`. Transições inválidas devem ser bloqueadas em service/policy.
- A janela de atendimento é parametrizada (lead/duration/grace) e precisa ser respeitada para criação, início de chamada e marcação de no-show.
- Não pode haver conflito de horário para o mesmo médico: ao criar ou reagendar, validar sobreposição em `scheduled_at` considerando a duração configurada.
- Cada registro gera um `access_code` único e armazena metadados (`metadata` JSON) como `callId`, preferências de mídia e flags de gravação.
- Logs de auditoria ficam em `appointment_logs` (eventos request, accept, start, end, cancel) garantindo rastreabilidade completa.
- Atualizações críticas (cancelar, reagendar, completar) devem notificar ambos os participantes via canais definidos (`users.{id}` ou `appointments.{id}`).
- Soft delete preserva histórico; exclusões devem ser raras e apenas administrativas, mantendo integridade de relatórios.

### Arquitetura Interna
- **Controller** → Recebe requisições HTTP/Inertia e delega para o service.
- **Service** → Contém toda a lógica de negócio (validação de conflito, status, logs, notificação).
- **Policy** → Controla acesso contextual (quem pode criar, iniciar, encerrar).
- **Observer** → Executa efeitos colaterais automáticos (gerar `access_code`, registrar logs, emitir eventos).
- **Event/Listener** → Dispara notificações e atualizações em tempo real.

### Auditoria e Logs
Cada mudança relevante no ciclo de vida gera uma entrada em `appointment_logs`:
- Campos: `appointment_id`, `event`, `user_id`, `payload`, `created_at`.
- Exemplos de evento: `"cancelled_by_patient"`, `"started_call"`, `"rescheduled"`.
- Relacionamento: `Appointment::hasMany(AppointmentLog::class)`, com eventos disparados via Observer/Service.

### Segurança e Integridade
- Toda alteração em `appointments` deve passar pela `AppointmentPolicy`.
- Não permitir mutações diretas via Eloquent — as operações devem fluir pelo `AppointmentService`.
- A criação valida `doctors.status = active` e `patients.profile_completed = true`.
- Campos críticos (`doctor_id`, `patient_id`, `scheduled_at`) não podem ser alterados após o status `in_progress`.

## Entregáveis Técnicos
1️⃣ **Criar model + migration Appointment completo (campos, índices, metadata)** — estrutura sólida no banco.  
2️⃣ **Implementar AppointmentService (create, update, cancel, reschedule, list)** — lógica de negócio centralizada.  
3️⃣ **Criar AppointmentsController com endpoints REST/Inertia** — front-end começa a consumir dados reais.  
4️⃣ **Escrever AppointmentPolicy (controle de acesso)** — segurança e coerência de fluxo.  
5️⃣ **Montar AppointmentsObserver para gerar access_code, metadata, logs** — automatização dos eventos.

### Integração com Outros Módulos
- **VideoCall:** usa `appointment_id`, `metadata.callId` e depende do status `in_progress`.
- **Messaging:** conversas podem referenciar `appointment_id` para manter contexto.
- **MedicalRecord:** cada registro médico pode se vincular a um `appointment_id`.
- **Notifications:** reagem às transições de status (`scheduled`, `cancelled`, `completed`) e acionam canais apropriados.

