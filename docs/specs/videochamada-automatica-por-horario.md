# Feature Spec — Videochamada: fluxo agendado automático + ad-hoc voluntário

> Status: `draft`
> Autor: Tech Lead Agent · Data: 2026-05-25
> Relacionadas: `video-call-mediagateway-persistent-ui.md`, `mediasoup-integration.md`
> Fonte: `docs/Pending Issues/FIX_RAPIDO_VIDEOCHAMADA_AUTOMATICA.md`

---

## Objetivo

Suportar dois tipos de videochamada no sistema:

1. **Agendada (`scheduled`)** — controlada exclusivamente pelo sistema com base em `appointments.scheduled_at`; sem início ou aceite manual.
2. **Ad-hoc (`ad_hoc`)** — iniciada voluntariamente pelo paciente fora do horário agendado, com aceite explícito do médico; sem vínculo obrigatório com agendamento.

Ambos os tipos compartilham a mesma infraestrutura SFU (MediaSoup), o mesmo endpoint `GET /calls/active`, e o mesmo componente de UI persistente — diferenciados por `call_type` e pelas regras de ciclo de vida.

## Motivação

O fluxo atual (`POST /calls`, `POST /calls/{call}/accept`, estados `requested`/`ringing`, UI de convite) contradiz a regra de telemedicina para consultas agendadas. Ao mesmo tempo, impedir toda chamada manual elimina casos legítimos de comunicação fora do horário (dúvidas pós-consulta, urgências). O modelo híbrido resolve os dois problemas: automatiza o que tem horário definido e permite o voluntário com autorização adequada.

---

## Regras de negócio

### Chamadas agendadas (`scheduled`)

1. **Sem início manual:** paciente e médico não podem criar chamada agendada via API/UI.
2. **Sem aceite/recusa:** nenhum participante confirma; a sala fica disponível quando a janela abre.
3. **Janela de disponibilidade** (por consulta com `scheduled_at`):
    - Início: `scheduled_at - W_lead` (default **10 min**, unificado com janela de agenda)
    - Fim: `scheduled_at + W_trail` (default **10 min**; futuro: `scheduled_at + duration + W_trail`)
4. **Provisionamento automático:** dentro da janela, o sistema cria ou reaproveita **uma** `Call` + **uma** `Room` (MediaSoup) por `appointment_id`, de forma idempotente.
5. **Entrada do usuário:** ao abrir a tela, o participante apenas **conecta** na sala já provisionada (token JWT + WebSocket SFU).
6. **Encerramento automático:** fora da janela, o sistema encerra a chamada, destrói a sala no SFU e atualiza status/`ended_at`.
7. **Consultas elegíveis:** `scheduled`, `rescheduled` e, se aplicável, `in_progress`.

### Chamadas ad-hoc (`ad_hoc`)

1. **Início manual:** paciente inicia via `POST /calls` com `call_type=ad_hoc`; médico recebe notificação.
2. **Aceite obrigatório:** médico aceita ou recusa via `POST /calls/{call}/accept` ou `/reject`; fluxo `requested → ringing → accepted/rejected`.
3. **Autorização de relacionamento:** paciente só pode iniciar ad-hoc com médico com quem tenha **consulta realizada nos últimos 7 dias** (`ended_at >= now() - 7 days`, status não cancelado). Impede abuso de superfície e relacionamentos obsoletos.
4. **Um ad-hoc ativo por par:** constraint evita múltiplas chamadas simultâneas entre mesmo paciente/médico.
5. **Room provisionada no aceite:** `createRoom` ocorre no `acceptCall`, não no `createCall`.
6. **Encerramento:** qualquer participante encerra via `POST /calls/{call}/end`; `EndZombieVideoCalls` como fallback.
7. **Duração máxima:** configurável (`ad_hoc_max_duration_minutes`, default **60**); `EndZombieVideoCalls` já cobre.

---

## Contexto encontrado (codebase)

| Artefato                                                            | Situação                                                             | Ação na implementação                                                                                              |
| ------------------------------------------------------------------- | -------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------ |
| `AutoStartVideoCall`                                                | Existe; roda `everyMinute`; chama `createCall` como paciente         | Refatorar para `provisionAppointmentCall`; filtrar apenas `call_type=scheduled`                                    |
| `EndZombieVideoCalls`                                               | Encerra por inatividade (60 min) / duração máxima (120 min)          | Manter para ad-hoc; **não** usá-lo como critério de janela para scheduled                                          |
| `CallManagerService`                                                | `createCall` / `acceptCall` / `createRoom` acoplados ao fluxo manual | Separar `provisionAppointmentCall` (scheduled) vs `createCall`/`acceptCall` (ad-hoc); `call_type` controla caminho |
| `Call` model                                                        | Sem campo `call_type`                                                | Adicionar `call_type: enum(scheduled, ad_hoc)` + migration                                                         |
| Índice `calls_one_active_per_appointment_idx`                       | Partial unique em `requested`/`ringing`/`accepted`                   | Manter para scheduled; **adicionar** índice separado para ad-hoc por par doctor/patient                            |
| `GET /calls/active`                                                 | Retorna call em `requested`/`ringing`/`accepted`                     | Incluir call provisionada com `Room` e token; retornar `call_type` no payload                                      |
| `config/telemedicine.appointment.lead_minutes` / `trailing_minutes` | Default **10**                                                       | Alinhar janela de video para **5** (config dedicada); ad-hoc não usa essa config                                   |
| `MediaGatewayStub`                                                  | Binding default sem SFU                                              | Dev pode manter stub; produção com video exige `MediaGatewayHttp`                                                  |
| Frontend `useVideoCall.ts`                                          | `POST /calls`, `accept`, `reject`                                    | Manter para ad-hoc; remover do fluxo de scheduled; bifurcar por `call_type`                                        |
| `video-call-mediagateway-persistent-ui.md`                          | Modal/widget, `/calls/active`, Echo                                  | Consumir ambos os tipos; texto diferenciado por `call_type`                                                        |

---

## Decisões de arquitetura

| #   | Decisão                                                           | Definição                                                                                                                                                                                                                                                                                                                                                                          |
| --- | ----------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| D1  | `CallManagerService::provisionAppointmentCall(Appointment): Call` | Idempotente; transação DB + lock Redis `video_call_lock:{appointment_id}`; exclusivo para scheduled                                                                                                                                                                                                                                                                                |
| D2  | Status pós-provisionamento (scheduled)                            | `accepted` definido pelo sistema (`accepted_at = now()`); `requested`/`ringing` não usados no fluxo scheduled                                                                                                                                                                                                                                                                      |
| D3  | Timestamps                                                        | `requested_at` = início da janela (`scheduled_at - W_lead`) para scheduled; `accepted_at` = momento do provisionamento com sala                                                                                                                                                                                                                                                    |
| D4  | Config da janela                                                  | Novas chaves em `telemedicine.video_call`: `window_lead_minutes` e `window_trailing_minutes` (default **10**, unificado com `appointment.lead_minutes`). Agenda e sala de vídeo abrem na mesma janela — sem divergência                                                                                                                                                            |
| D5  | Rotas manuais                                                     | `POST /calls` → **permitido** apenas `call_type=ad_hoc` com relacionamento válido (consulta nos últimos 7 dias); `call_type=scheduled` via API → 403. `POST /calls/{call}/accept` e `/reject` → permitidos **apenas** para `ad_hoc`; scheduled → 403                                                                                                                               |
| D6  | Início de appointment                                             | **Não** chamar `AppointmentService::start` no provisionamento automático                                                                                                                                                                                                                                                                                                           |
| D7  | Eventos broadcast                                                 | Scheduled: emitir `VideoCallAvailable` (sala pronta, sem ring). Ad-hoc: manter `VideoCallRequested` para notificar médico + `VideoCallAccepted`/`VideoCallRejected`; **não** misturar semânticas                                                                                                                                                                                   |
| D8  | Scheduler                                                         | Manter `AutoStartVideoCall` everyMinute (scheduled); criar `EndScheduledVideoCalls` para encerrar por `scheduled_at + W_trail`; `EndZombieVideoCalls` cobre ad-hoc                                                                                                                                                                                                                 |
| D9  | MediaSoup                                                         | Scheduled: `createRoom` no provisionamento. Ad-hoc: `createRoom` no `acceptCall`. Mesmo provider, mesmo contrato                                                                                                                                                                                                                                                                   |
| D10 | Autorização ad-hoc                                                | Paciente só inicia ad-hoc com médico com quem tenha consulta **realizada nos últimos 7 dias** (`ended_at >= now() - 7 days`, status não cancelado). Gate `video-call-request-adhoc` verifica via `appointments`; index `appointments(patient_id, doctor_id, ended_at)` obrigatório para performance                                                                                |
| D11 | Unicidade ad-hoc                                                  | Índice parcial `calls_one_adhoc_per_pair_idx`: `UNIQUE (doctor_id, patient_id)` WHERE `call_type = 'ad_hoc' AND ended_at IS NULL`. **Versão MySQL desconhecida** — migration usa `DB::statement` com raw SQL; se MySQL < 8, fallback para constraint no `CallManagerService` (verificação no `createCall` dentro de transação com lock `adhoc_call_lock:{doctor_id}:{patient_id}`) |
| D13 | Prioridade `GET /calls/active`                                    | Scheduled na janela tem prioridade sobre ad-hoc ativa simultânea; ad-hoc permanece pausada no background até scheduled encerrar                                                                                                                                                                                                                                                    |
| D12 | `call_type` no modelo                                             | Campo `call_type ENUM('scheduled','ad_hoc') NOT NULL DEFAULT 'scheduled'`; migration obrigatória; `appointment_id` nullable apenas para ad-hoc                                                                                                                                                                                                                                     |

---

## Arquitetura proposta

```
[Scheduler everyMinute]
    ├─ AutoStartVideoCall
    │     → consultas com now ∈ [scheduled_at - W_lead, scheduled_at + W_trail]
    │     → CallManagerService::provisionAppointmentCall (idempotente)
    │           → Call(type=scheduled, status=accepted) + Room(SFU) + logs
    │
    └─ EndScheduledVideoCalls
          → scheduled calls cujo appointment.scheduled_at + W_trail < now()
          → destroyRoom + status ended + ended_at

[Paciente autenticado — ad-hoc]
    → POST /calls {call_type: ad_hoc, doctor_id}
    → Call(type=ad_hoc, status=requested) criada
    → Echo → VideoCallRequested → médico
    → POST /calls/{call}/accept (médico)
    → createRoom + status accepted
    → ambos conectam via /calls/active

[Usuário autenticado — qualquer tipo]
    → GET /calls/active (retorna call na janela ou ad-hoc aceita + room + token)
    → página /doctor|patient/video-call → connect SFU
```

Padrões reutilizados:

- `CallManagerService` — `provisionAppointmentCall` (scheduled) + `createCall`/`acceptCall` adaptados (ad-hoc)
- `AutoStartVideoCall` — mesma fila/schedule; query filtra `call_type=scheduled`
- `VideoCallPolicy` — gates `viewActive`, `view`; `request` apenas ad-hoc; `accept`/`reject` apenas ad-hoc
- `MediaGatewayInterface` — conforme `mediasoup-integration.md`
- UI global — conforme `video-call-mediagateway-persistent-ui.md`; texto diferenciado por `call_type`

---

## Backend

### Model — `Call`

| Campo            | Tipo                         | Descrição                                    |
| ---------------- | ---------------------------- | -------------------------------------------- |
| `call_type`      | `enum('scheduled','ad_hoc')` | Tipo; controla ciclo de vida e policies      |
| `appointment_id` | `FK nullable`                | Obrigatório para scheduled; null para ad-hoc |
| `doctor_id`      | `FK`                         | Participante médico                          |
| `patient_id`     | `FK`                         | Participante paciente                        |

### Service — `CallManagerService`

| Método                                            | Tipo      | Responsabilidade                                                                                                                 |
| ------------------------------------------------- | --------- | -------------------------------------------------------------------------------------------------------------------------------- |
| `provisionAppointmentCall(Appointment): Call`     | scheduled | Lock; busca call ativa; se existe com room, retorna; senão cria Call + createRoom + status `accepted`; idempotente               |
| `createCall(User $requester, User $target): Call` | ad-hoc    | Verifica relacionamento (D10); verifica unicidade (D11); cria Call `type=ad_hoc, status=requested`; dispara `VideoCallRequested` |
| `acceptCall(Call): Call`                          | ad-hoc    | Apenas `ad_hoc`; `createRoom`; status `accepted`; dispara `VideoCallAccepted`                                                    |
| `rejectCall(Call): Call`                          | ad-hoc    | Apenas `ad_hoc`; status `rejected`; dispara `VideoCallRejected`                                                                  |
| `getActiveCallForUser(User): ?Call`               | ambos     | Retorna call na janela (scheduled) **ou** ad-hoc aceita com room; inclui token JWT                                               |
| `endCallForAppointmentWindow(Call): void`         | scheduled | Encerra fora da janela; destroyRoom; atualiza status                                                                             |
| `endCall(Call, User): void`                       | ambos     | Encerramento voluntário; destroyRoom; atualiza status                                                                            |

**Idempotência (scheduled):**

1. `SELECT` call ativa (`accepted` + sem `ended_at` + `call_type=scheduled`) para `appointment_id`
2. Se `room` existe → return
3. Se call existe sem room → `createRoom` + update
4. Senão `Call::create` + `createRoom` em transação
5. Índice `calls_one_active_per_appointment_idx` evita duplicata concorrente

**Verificação de relacionamento (ad-hoc):**

```php
// Index necessário: appointments(patient_id, doctor_id, ended_at)
Appointment::where('doctor_id', $doctor->id)
    ->where('patient_id', $patient->id)
    ->whereNotIn('status', ['cancelled'])
    ->where('ended_at', '>=', now()->subDays(7))
    ->exists()
```

### Jobs / Filas

| Job                      | Fila      | Schedule      | Escopo            | Mudança                                                                                                               |
| ------------------------ | --------- | ------------- | ----------------- | --------------------------------------------------------------------------------------------------------------------- |
| `AutoStartVideoCall`     | `default` | `everyMinute` | scheduled         | Trocar `createCall` → `provisionAppointmentCall`; query pela janela `[scheduled_at - W_lead, scheduled_at + W_trail]` |
| `EndScheduledVideoCalls` | `default` | `everyMinute` | scheduled         | **Novo**; encerrar calls `type=scheduled` cujo `appointment.scheduled_at + W_trail < now()`                           |
| `EndZombieVideoCalls`    | `default` | `*/5`         | ad-hoc + fallback | Manter; encerrar por inatividade/duração máxima; não tocar janela de scheduled                                        |

### Endpoints

| Método | Rota                   | Ação     | Call type | Comportamento                                                                                                   |
| ------ | ---------------------- | -------- | --------- | --------------------------------------------------------------------------------------------------------------- |
| POST   | `/calls`               | `store`  | ad-hoc    | Permitido com `call_type=ad_hoc` + relacionamento válido                                                        |
| POST   | `/calls`               | `store`  | scheduled | **403** — início manual de consulta agendada proibido                                                           |
| POST   | `/calls/{call}/accept` | `accept` | ad-hoc    | Permitido para médico                                                                                           |
| POST   | `/calls/{call}/accept` | `accept` | scheduled | **403**                                                                                                         |
| POST   | `/calls/{call}/reject` | `reject` | ad-hoc    | Permitido para médico                                                                                           |
| POST   | `/calls/{call}/reject` | `reject` | scheduled | **403**                                                                                                         |
| POST   | `/calls/{call}/end`    | `end`    | ambos     | Encerramento voluntário; não substitui scheduler para scheduled                                                 |
| GET    | `/calls/active`        | `active` | ambos     | Retorna call ativa (scheduled na janela **ou** ad-hoc aceita) com `token`, `sfu_ws_url`, `call_type`, metadados |
| GET    | `/calls/{call}`        | `show`   | ambos     | Leitura para participante                                                                                       |

**Resposta `GET /calls/active`:**

```json
{
    "call_type": "scheduled|ad_hoc",
    "status": "accepted",
    "appointment_id": 123,
    "room": { "id": "...", "sfu_ws_url": "..." },
    "token": "...",
    "window": {
        "opens_at": "2026-05-25T14:55:00Z",
        "closes_at": "2026-05-25T15:05:00Z"
    }
}
```

Para ad-hoc, `appointment_id` é `null` e `window` é `null`.

### Autorização

| Gate / Policy                             | Scheduled                                                             | Ad-hoc                                       |
| ----------------------------------------- | --------------------------------------------------------------------- | -------------------------------------------- |
| `video-call-request`                      | Negar sempre                                                          | Negar (usar `video-call-request-adhoc`)      |
| `video-call-request-adhoc`                | N/A                                                                   | Permitir se relacionamento válido (D10)      |
| `video-call-accept` / `video-call-reject` | Negar sempre                                                          | Permitir para médico da chamada              |
| `video-call-view-active`                  | Permitir se participante + `now` na janela                            | Permitir se participante + status `accepted` |
| `video-call-view`                         | Participante da call                                                  | Participante da call                         |
| `video-call-end`                          | Permitir encerramento voluntário; não cancela encerramento automático | Permitir                                     |

### Config (`config/telemedicine.php`)

```php
'video_call' => [
    // Janela unificada com appointment.lead/trailing_minutes — agenda e sala abrem juntos
    'window_lead_minutes'     => (int) env('VIDEO_CALL_WINDOW_LEAD_MINUTES', 10),
    'window_trailing_minutes' => (int) env('VIDEO_CALL_WINDOW_TRAILING_MINUTES', 10),
    // Duração máxima para chamadas ad-hoc (fallback EndZombieVideoCalls)
    'ad_hoc_max_duration_minutes' => (int) env('VIDEO_CALL_ADHOC_MAX_MINUTES', 60),
    // Janela de elegibilidade para ad-hoc (consulta realizada nos últimos N dias)
    'ad_hoc_relationship_days' => (int) env('VIDEO_CALL_ADHOC_RELATIONSHIP_DAYS', 7),
    // TTL do token JWT
    'token_ttl_minutes' => (int) env('VIDEO_CALL_TOKEN_TTL_MINUTES', 15),
],
```

### Observabilidade

| Evento                          | Nível     | Contexto                                            |
| ------------------------------- | --------- | --------------------------------------------------- |
| `VIDEO_CALL_PROVISIONED`        | `info`    | `appointment_id`, `call_id`, `room_id`, `call_type` |
| `VIDEO_CALL_PROVISION_SKIPPED`  | `debug`   | já provisionada                                     |
| `VIDEO_CALL_PROVISION_FAILED`   | `error`   | `appointment_id`, exception                         |
| `VIDEO_CALL_WINDOW_ENDED`       | `info`    | `call_id`, `scheduled_at`, `window_end`             |
| `VIDEO_CALL_ADHOC_REQUESTED`    | `info`    | `call_id`, `patient_id`, `doctor_id`                |
| `VIDEO_CALL_ADHOC_ACCEPTED`     | `info`    | `call_id`, `room_id`                                |
| `VIDEO_CALL_ADHOC_REJECTED`     | `info`    | `call_id`                                           |
| `VIDEO_CALL_ADHOC_UNAUTHORIZED` | `warning` | `patient_id`, `doctor_id` — sem relacionamento      |

---

## Frontend

### Comportamento alvo

| Área                       | Scheduled                                                                            | Ad-hoc                                                                                     |
| -------------------------- | ------------------------------------------------------------------------------------ | ------------------------------------------------------------------------------------------ |
| `useVideoCall.ts`          | Remover `requestCall` do fluxo scheduled; expor `joinActiveCall` via `/calls/active` | Manter `requestCall` / `acceptCall` / `rejectCall` para ad-hoc; bifurcar por `call_type`   |
| `useVideoCallSession.ts`   | Priorizar bootstrap `/calls/active` + `VideoCallAvailable`                           | Escutar `VideoCallRequested` (médico); `VideoCallAccepted`/`Rejected` (paciente)           |
| `Patient/VideoCall.vue`    | CTA **Entrar na consulta** (não "Ligar")                                             | Botão **Ligar para médico** disponível fora da janela agendada                             |
| `Doctor/VideoCall.vue`     | Remover botão aceitar para scheduled                                                 | Modal de aceite/recusa para ad-hoc entrante                                                |
| `videoCall` store          | `idle \| available \| connecting \| in-call \| ended \| error`                       | Adicionar `ringing` para ad-hoc (médico recebendo); `calling` (paciente aguardando aceite) |
| UI persistente (spec irmã) | Texto "Consulta disponível"                                                          | Texto "Chamada recebida de [paciente]"                                                     |

### Estados de UI por tipo

**Scheduled:**

| Estado               | Comportamento                                             |
| -------------------- | --------------------------------------------------------- |
| Antes da janela      | Countdown até `scheduled_at - W_lead`; botão desabilitado |
| Na janela, sem token | Loading + retry `/calls/active`                           |
| Na janela, com token | Conectar SFU; toast em erro                               |
| Após janela          | "Consulta encerrada"; sem ações                           |

**Ad-hoc (paciente):**

| Estado               | Comportamento                                                |
| -------------------- | ------------------------------------------------------------ |
| `idle`               | Botão "Ligar para médico" (visível se relacionamento válido) |
| `calling`            | "Aguardando aceite do médico..."; botão cancelar             |
| `in-call`            | SFU conectado                                                |
| `ended` / `rejected` | "Chamada encerrada" / "Médico não atendeu"                   |

**Ad-hoc (médico):**

| Estado    | Comportamento                                             |
| --------- | --------------------------------------------------------- |
| `ringing` | Modal/widget: "[Paciente] quer falar" + Aceitar / Recusar |
| `in-call` | SFU conectado                                             |
| `ended`   | "Chamada encerrada"                                       |

### Rotas Inertia (inalteradas)

| Papel    | Rota                 | Componente              |
| -------- | -------------------- | ----------------------- |
| Médico   | `doctor.video-call`  | `Doctor/VideoCall.vue`  |
| Paciente | `patient.video-call` | `Patient/VideoCall.vue` |

---

## Banco de dados

### Migrations

**Obrigatória:**

```php
// Adicionar call_type à tabela calls
$table->enum('call_type', ['scheduled', 'ad_hoc'])->default('scheduled')->after('id');
// Tornar appointment_id nullable (já pode ser, verificar)
$table->foreignId('appointment_id')->nullable()->change();
```

**Índice ad-hoc (unicidade por par):**

```php
// Partial unique — MySQL 8+ suporte nativo; versão desconhecida: usar raw
// Se falhar em runtime, fallback = lock Redis no service (D11)
DB::statement("
    CREATE UNIQUE INDEX calls_one_adhoc_per_pair_idx
    ON calls (doctor_id, patient_id)
    WHERE call_type = 'ad_hoc' AND ended_at IS NULL
");
```

**Índice appointments (performance D10):**

```php
// Necessário para query de relacionamento 7 dias no gate ad-hoc
$table->index(['patient_id', 'doctor_id', 'ended_at'], 'appointments_adhoc_relationship_idx');
```

**Opcional (fase 2):**

- Coluna `provisioned_at` para distinguir aceite humano vs sistema
- Status `available` no partial unique index

### Índices

| Índice                                 | Escopo    | Uso                                         |
| -------------------------------------- | --------- | ------------------------------------------- |
| `calls_one_active_per_appointment_idx` | scheduled | Idempotência de provisionamento             |
| `calls_one_adhoc_per_pair_idx`         | ad-hoc    | Um ad-hoc ativo por par doctor/patient      |
| `appointments(scheduled_at, status)`   | scheduled | Query do job (considerar se volume crescer) |

---

## Infraestrutura

- **Fila:** RabbitMQ `default` — jobs existentes
- **Lock:** `video_call_lock:{appointment_id}` para scheduled (já referenciado em `config/telemedicine.php`)
- **SFU:** MediaSoup via `MediaGatewayHttp`; stub apenas dev sem SFU

---

## Segurança

- Token JWT só via `GET /calls/active` para participante autenticado (scheduled na janela; ad-hoc aceita)
- `POST /calls` com `call_type=scheduled` → 403 (não depende de validação de body; controller verifica antes)
- Verificação de relacionamento (D10) impede paciente ligar para médico sem consulta — limita surface de spam
- Rate limit existente `throttle:10,1` nas rotas de call; considerar limite menor para `POST /calls` ad-hoc (ex: `throttle:3,1`)
- `accept`/`reject` verificam que o médico autenticado é o `doctor_id` da call — não aceitar chamada de terceiro
- Não expor `appointment_id` de terceiros na API

---

## Edge cases

| Cenário                                                   | Comportamento                                                                                                                                                                                                     |
| --------------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Job roda 2x no mesmo minuto (scheduled)                   | Reaproveita call/room existente                                                                                                                                                                                   |
| Consulta reagendada após provisionar                      | `AutoStartVideoCall` detecta `scheduled_at` fora da janela atual → `EndScheduledVideoCalls` encerra; nova janela provisiona no minuto correto. Listener em `AppointmentRescheduled` pode antecipar o encerramento |
| SFU indisponível no provisionamento                       | Log error; retry no próximo minuto; `/calls/active` 503 ou resposta sem token com mensagem clara                                                                                                                  |
| Usuário entra 1 min antes do fim da janela (scheduled)    | Permite conexão; encerramento automático em `scheduled_at + W_trail`                                                                                                                                              |
| Apenas um participante na sala                            | Sala permanece até fim da janela (scheduled) ou encerramento voluntário (ad-hoc)                                                                                                                                  |
| `VIDEO_CALL_ENABLED=false`                                | Jobs no-op; `/calls/active` 204; `POST /calls` 503                                                                                                                                                                |
| Refresh durante janela                                    | Bootstrap `/calls/active` restaura sessão (spec irmã)                                                                                                                                                             |
| Paciente tenta ad-hoc com médico sem consulta             | 403 com mensagem "Você não tem consulta com este médico"                                                                                                                                                          |
| Médico com ad-hoc ativa recebe outra solicitação          | Segunda solicitação → 409 (índice D11); cliente exibe "Médico em atendimento"                                                                                                                                     |
| Paciente tem ad-hoc ativa + consulta agendada abre janela | `/calls/active` retorna scheduled (prioridade — D13); ad-hoc permanece ativa e retoma prioridade quando scheduled encerrar                                                                                        |
| Ad-hoc sem resposta do médico                             | `EndZombieVideoCalls` encerra após `ad_hoc_max_duration_minutes`                                                                                                                                                  |
| Token TTL expirado durante janela                         | Renovar via novo `GET /calls/active`; frontend faz poll periódico                                                                                                                                                 |

---

## Riscos técnicos

| Risco                                                   | Prob.     | Impacto | Mitigação                                                                                                                                                                                                  |
| ------------------------------------------------------- | --------- | ------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Divergência janela vídeo vs agenda                      | Resolvido | —       | Unificado para 10 min (D4)                                                                                                                                                                                 |
| `EndZombieVideoCalls` encerra scheduled antes da janela | Média     | Alto    | `EndScheduledVideoCalls` separado; `EndZombieVideoCalls` filtra `call_type=ad_hoc` ou usa critério independente                                                                                            |
| Frontend escuta `VideoCallRequested` para scheduled     | Média     | Médio   | Bifurcar por `call_type` no handler; testes manuais                                                                                                                                                        |
| Stub em produção sem mídia real                         | Média     | Alto    | Gate `MEDIA_GATEWAY_PROVIDER=sfu` + healthcheck                                                                                                                                                            |
| Reagendamento sem invalidar call scheduled              | Média     | Alto    | Listener `AppointmentRescheduled` + comparar `scheduled_at`                                                                                                                                                |
| Migration `call_type` em produção com calls ativas      | Média     | Alto    | Default `scheduled` cobre retrocompatibilidade; executar com janela sem agendamentos                                                                                                                       |
| Índice partial ad-hoc com MySQL < 8                     | Média     | Alto    | Versão desconhecida; migration tenta `DB::statement` raw; fallback: constraint no service com lock Redis `adhoc_call_lock:{doctor_id}:{patient_id}` dentro de transação — verificar versão antes do deploy |
| Paciente spam de ad-hoc requests                        | Baixa     | Médio   | Rate limit reduzido + gate de relacionamento                                                                                                                                                               |

---

## Plano de implementação

1. **Migration** — `call_type` + `appointment_id nullable` + índice ad-hoc
2. **Config** — `window_lead_minutes` / `window_trailing_minutes` / `ad_hoc_max_duration_minutes` em `telemedicine.video_call`
3. **Backend** — `provisionAppointmentCall` + testes de idempotência
4. **Backend** — `createCall` / `acceptCall` adaptados para ad-hoc com gate D10
5. **Backend** — Refatorar `AutoStartVideoCall` + criar `EndScheduledVideoCalls`
6. **Backend** — `getActiveCallForUser` / `CallController@active` (prioridade scheduled na janela; fallback ad-hoc aceita)
7. **Backend** — Policies atualizadas (gates por `call_type`)
8. **Backend** — `POST /calls` com validação de `call_type`; scheduled → 403; ad-hoc → fluxo
9. **Backend** — Eventos broadcast diferenciados (`VideoCallAvailable` scheduled; manter `VideoCallRequested` ad-hoc)
10. **Frontend** — `useVideoCall` bifurcado por `call_type`; `useVideoCallSession` atualizado
11. **Frontend** — `Patient/VideoCall.vue` + `Doctor/VideoCall.vue` (CTAs e modal ad-hoc)
12. **Frontend** — UI persistente: texto por `call_type`; store com estado `ringing`/`calling`
13. **Integração** — Validar `MediaGatewayHttp` nos dois caminhos (provision + accept)
14. **Testes** — Feature: provision + active + end por janela; `POST /calls scheduled` → 403; ad-hoc flow completo

---

## Critérios de aceite

| Critério                                    | Verificação                                                           |
| ------------------------------------------- | --------------------------------------------------------------------- |
| Sem início manual de scheduled              | `POST /calls` com `call_type=scheduled` → 403                         |
| Sem aceite de scheduled                     | `POST /calls/{id}/accept` em scheduled → 403                          |
| Scheduled disponível 5 min antes            | Com `scheduled_at = T`, às `T-5min` `/calls/active` retorna call+room |
| Sala MediaSoup antes da entrada (scheduled) | `rooms` existe antes de qualquer join do browser                      |
| Encerramento automático scheduled           | Às `T+5min` call `ended`, room destruída no SFU                       |
| Idempotente (scheduled)                     | Duas execuções do job → uma call ativa por appointment                |
| Ad-hoc iniciável por paciente               | `POST /calls {call_type: ad_hoc, doctor_id}` com relacionamento → 201 |
| Ad-hoc bloqueado sem relacionamento         | Sem consulta com médico → 403                                         |
| Aceite cria room (ad-hoc)                   | Após `accept`, `/calls/active` retorna room + token                   |
| Um ad-hoc ativo por par                     | Segunda solicitação → 409                                             |
| Encerramento voluntário (ad-hoc)            | `POST /calls/{id}/end` → call `ended`, room destruída                 |

---

## Checklist

### Backend

- [ ] Migration `call_type` + índice ad-hoc (com fallback MySQL < 8)
- [ ] Index `appointments_adhoc_relationship_idx` em dev migration existente ou nova
- [ ] `provisionAppointmentCall` com lock e transação
- [ ] `createCall` / `acceptCall` para ad-hoc com gate de relacionamento
- [ ] `AutoStartVideoCall` usa janela `window_*` e filtra `scheduled`
- [ ] `EndScheduledVideoCalls` por `scheduled_at` (somente scheduled)
- [ ] `EndZombieVideoCalls` não interfere com janela de scheduled
- [ ] `GET /calls/active` retorna scheduled (prioridade) ou ad-hoc aceita
- [ ] Rotas `accept`/`reject`/`store` com 403 para scheduled
- [ ] Policies atualizadas por `call_type`
- [ ] Rate limit reduzido em `POST /calls` ad-hoc
- [ ] Logs estruturados com `call_type`
- [ ] Testes unit + feature

### Frontend

- [ ] `useVideoCall` bifurcado por `call_type`
- [ ] CTA "Entrar na consulta" para scheduled
- [ ] Botão "Ligar para médico" para ad-hoc (paciente)
- [ ] Modal de aceite/recusa para ad-hoc (médico)
- [ ] Bootstrap `/calls/active` com ambos os tipos
- [ ] Store com estados `ringing` / `calling`
- [ ] UI persistente com texto diferenciado por `call_type`

### Qualidade

- [ ] `php artisan test` nos módulos de call
- [ ] Teste manual doctor + patient (scheduled na janela)
- [ ] Teste manual ad-hoc: paciente liga → médico aceita → ambos conectam
- [ ] Teste ad-hoc bloqueado: paciente sem consulta → 403
- [ ] Confirmar provider SFU em staging

---

## Fora de escopo (esta spec)

- Duração real da consulta no cálculo do fim da janela (`duration`)
- Gravação de chamada / TCLE específico de vídeo
- Alterações profundas em `MediaGatewayStub` além do necessário para dev
- Reimplementação completa da UI persistente (coberta pela spec irmã)
- Histórico de chamadas ad-hoc (listagem, paginação)
- Notificação push (push notification) para ad-hoc fora do app
