# Feature Spec — Videochamada: melhorias de UX, ciclo de vida e anexos

> Status: `implemented` (fases 1–5) · Data impl.: 2026-06-08
> Autor: Tech Lead Agent · Data: 2026-06-08
> Relacionadas: `videochamada-automatica-por-horario.md`, `video-call-mediagateway-persistent-ui.md`, `mediasoup-integration.md`

## Decisões confirmadas (produto)

- **`VIDEO_ROOM_INACTIVE_MINUTES=15`** confirmado.
- **Médico não tem "sair"**: o botão do médico **encerra** globalmente. Queda de
  conexão / fechar aba do médico é tratada como **queda temporária** — a call
  permanece `accepted`, o paciente aguarda e vê o aviso **"O médico está
  reconectando…"** (detectado pelo SFU: stream do médico sumiu). Se o médico não
  retornar dentro de `doctor_reconnect_grace_minutes` (default 2), o job
  `EndEmptyVideoCalls` encerra com `call_closed_reason=doctor_disconnected` e o
  paciente recebe "O médico não conseguiu retornar à chamada."
- Presença via heartbeat `POST /calls/{id}/presence` a cada 30s (Opção A).

---

## Resumo executivo

Refatorar o módulo de videochamadas para simplificar a UX (remover screen share e levantar mão), corrigir o fluxo **encerrar vs sair** (médico encerra globalmente; paciente só sai), garantir propagação consistente via backend + Reverb, melhorar anexos durante consulta e substituir erros técnicos por mensagens amigáveis contextualizadas.

**Escopo:** Frontend Vue (overlays, controles, widget/modal), composables (`useVideoCall`, `useVideoCallSession`), backend Laravel (`CallController`, `CallManagerService`, Policies, Jobs, Events), config `telemedicine.video_call`. **Sem mudança de SFU/MediaSoup** além de desconexão local no leave.

---

## Motivação

1. Controles de screen share e levantar mão são **mock de UI** (sem WebRTC/`getDisplayMedia`/broadcast) — confundem usuários.
2. Bug reportado: médico encerra dentro da chamada e paciente permanece na sala; widget/modal de convite encerra globalmente — indica **inconsistência leave/end** e **propagação incompleta** de eventos.
3. Paciente usa `endCall()` (encerramento global) apesar do modal dizer "Sair" — viola regra de negócio.
4. Jobs de auto-encerramento não emitem `VideoCallEnded` — clientes não recebem feedback uniforme.
5. Anexos funcionam via `MedicalDocument` + Echo, mas UX de upload/visualização pode ser aprimorada.

---

## Estado atual (AS-IS)

### Frontend — componentes e fluxo

| Artefato                                                         | Papel                                                                                              |
| ---------------------------------------------------------------- | -------------------------------------------------------------------------------------------------- |
| `VideoCallSessionRoot.vue`                                       | Bootstrap Echo, modal convite, widget flutuante                                                    |
| `VideoCallActiveModal.vue`                                       | Modal convite: **Fechar** + **Entrar na chamada** (sem Encerrar)                                   |
| `VideoCallFloatingWidget.vue`                                    | Widget pós-dismiss: **Entrar** + **Encerrar chamada** → `endCall()`                                |
| `DoctorVideoCallInCallOverlay.vue`                               | UI in-call médico; `@end` → modal confirmação → `handleEndCall`                                    |
| `PatientVideoCallInCallOverlay.vue`                              | UI in-call paciente; modal "Sair" mas chama mesmo `endCall()`                                      |
| `DoctorConsultControlsBar.vue` / `PatientConsultControlsBar.vue` | Mic, cam, **screen**, captions, **hand**, Encerrar/Sair                                            |
| `DoctorConsultFilesPanel.vue`                                    | Upload drag-and-drop via Inertia → prontuário                                                      |
| `PatientConsultFilesPanel.vue`                                   | Lista read-only de documentos compartilhados                                                       |
| `useVideoCall.ts`                                                | `endCall()`: `sfu.disconnect()` → `POST /calls/{id}/end` → `store.clearCall()` (erros silenciados) |
| `useVideoCallSession.ts`                                         | Echo: `VideoCallAvailable`, `Requested`, `Accepted`, `Rejected`, **`VideoCallEnded`**              |
| `useCallSharedDocuments.ts`                                      | Echo `appointments.{id}` → `.medical-document.shared`                                              |
| `stores/videoCall.ts`                                            | Estado global Pinia; `isOnVideoCallPage` oculta modal/widget na página de vídeo                    |

### Backend

| Artefato                                          | Papel                                                                         |
| ------------------------------------------------- | ----------------------------------------------------------------------------- |
| `CallController@end`                              | Autoriza `video-call-end`; delega `CallManagerService::endCall`               |
| `CallManagerService::endCall`                     | Status `ended`, destroy room SFU, emite **`VideoCallEnded`**                  |
| `CallManagerService::endCallForAppointmentWindow` | Auto-fim janela scheduled — **NÃO emite `VideoCallEnded`**                    |
| `EndScheduledVideoCalls`                          | Job minutely — chama `endCallForAppointmentWindow`                            |
| `EndZombieVideoCalls`                             | Ad-hoc inativo/duração — **NÃO emite `VideoCallEnded`**                       |
| `EndStuckInProgressAppointments`                  | Consultas in_progress presas                                                  |
| `AppointmentVideoSessionController`               | `POST /appointments/{id}/video/session` — provisiona + registra `*_joined_at` |
| `VideoCallPolicy::end`                            | **Qualquer participante** pode encerrar (precisa restringir a médico)         |
| `MedicalDocumentShared` event                     | Broadcast anexo para canal appointment                                        |

### Rotas relevantes

```
POST   /calls/{call}/end          → encerramento global (único endpoint hoje)
POST   /appointments/{id}/video/session
GET    /calls/active
POST   /doctor/patients/{patient}/medical-record/documents  (upload durante consulta)
GET    /patient/medical-records/documents/{document}/download
```

### Config atual (`config/telemedicine.php`)

| Chave                         | Default | Observação                           |
| ----------------------------- | ------- | ------------------------------------ |
| `window_trailing_minutes`     | 10      | Auto-fim após horário agendado       |
| `room_inactive_minutes`       | **60**  | Requisito pede **15 min** sala vazia |
| `ad_hoc_max_duration_minutes` | 60      | Fallback ad-hoc                      |

### Screen share / levantar mão (AS-IS)

- **Somente estado local Vue** (`screenSharing`, `handRaised` refs) + toasts.
- **Sem** integração SFU, backend ou permissões reais.
- Botões em `*ControlsBar.vue` e handlers em `*InCallOverlay.vue`.
- `PatientConsultSummaryPanel.vue`: ação "dúvida" seta `handRaised` localmente.

---

## Causa raiz provável do bug de encerramento

### Sintoma observado

- **Widget flutuante** (após dismiss do modal): `Encerrar` → `POST /calls/{id}/end` → paciente recebe `VideoCallEnded` via Echo ✅
- **Dentro da chamada (médico)**: overlay mostra toast "Consulta encerrada" mas paciente **permanece** na sala ❌

### Causas identificadas (combinadas)

| #   | Causa                               | Evidência                                                                                                                                                      |
| --- | ----------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 1   | **UI otimista sem await da API**    | `DoctorVideoCallInCallOverlay.confirmEnd()` exibe toast e emite `end` **antes** de `handleEndCall` completar; falha silenciosa parece sucesso                  |
| 2   | **Erros HTTP engolidos**            | `useVideoCall.endCall()` — `catch {}` vazio; se `POST /end` falhar (403, 422, rede), só médico desconecta localmente                                           |
| 3   | **Ordem SFU-before-API**            | `sfu.disconnect()` antes do POST; paciente vê peer sair (SFU) mas call permanece `accepted` no servidor até API OK — sem broadcast se API falhar               |
| 4   | **Paciente também chama `endCall`** | Modal paciente diz "Sair" mas executa encerramento global — comportamento inconsistente com regra desejada                                                     |
| 5   | **Auto-encerramento sem broadcast** | `endCallForAppointmentWindow` e `EndZombieVideoCalls` não disparam `VideoCallEnded` — clientes não sincronizam                                                 |
| 6   | **Confusão modal vs widget**        | `VideoCallActiveModal` não tem Encerrar; botão problemático está no **widget** (`VideoCallFloatingWidget`) após "Fechar" — usuário associa ao fluxo de convite |

**Hipótese principal:** encerramento in-call **depende do POST** para propagar; falhas silenciosas + toast otimista mascaram falha. Widget funciona quando API responde 204; in-call pode falhar intermitentemente (ex.: `currentCall` null, throttle, estado call inválido) sem feedback.

---

## Requisitos funcionais

### RF-01 Remoções

- Remover botões, props, handlers e toasts de **compartilhamento de tela** e **levantar mão** em toda UI de videochamada.
- Remover referências visuais (ícones `MonitorUp`, `Hand`) nos controles de consulta.
- Refatorar ação "dúvida" do paciente para **chat** ou mensagem no chat (sem simular raise hand).

### RF-02 Modal / widget de convite

- Modal convite: **apenas** "Entrar na chamada" (+ opcional "Fechar"/dismiss sem encerrar).
- **Remover** botão "Encerrar chamada" do `VideoCallFloatingWidget`.
- Widget: apenas status + "Entrar na chamada".

### RF-03 Encerrar vs sair (in-call)

| Persona  | Ação             | Comportamento                                                                       |
| -------- | ---------------- | ----------------------------------------------------------------------------------- |
| Médico   | Encerrar chamada | Modal confirmação → `POST /calls/{id}/end` → encerra **globalmente**                |
| Paciente | Sair da chamada  | Modal confirmação → `POST /calls/{id}/leave` → desconecta **só ele**; sala continua |

### RF-04 Encerramento automático

- Tempo agendado expira → encerramento global + mensagem contextualizada.
- Sala vazia **15+ minutos** → encerramento global + mensagem de inatividade.
- Ambos devem emitir evento broadcast para clientes conectados.

### RF-05 Feedbacks

- Mensagens amigáveis por contexto (catálogo na seção dedicada).
- Nunca exibir códigos HTTP, stack traces ou mensagens técnicas do backend.

### RF-06 Anexos

- Médico: upload intuitivo (drag-and-drop, progresso, validação inline).
- Paciente: visualização inline (PDF/imagem), download, notificação em tempo real.
- Lista sincronizada via Echo durante chamada.

---

## Requisitos não-funcionais

- **NFR-01:** Propagação de encerramento em ≤ 2s (Reverb + fila `ShouldBroadcastNow` ou queue dedicada baixa latência).
- **NFR-02:** Idempotência: `end` e `leave` repetidos retornam 204 sem efeito colateral.
- **NFR-03:** Mobile-first nos controles e painel de arquivos.
- **NFR-04:** LGPD: anexos permanecem no prontuário com visibilidade `shared`; sem exposição de URLs presigned sem auth.
- **NFR-05:** Testes Feature cobrindo end/leave por persona e broadcast.

---

## Comportamento desejado (TO-BE)

### Médico

```
[Convite modal/widget] → Entrar → SFU connect
[In-call] → Encerrar → Confirmar → POST /end → VideoCallEnded(broadcast) → todos desconectam
[Auto] janela expirada / sala vazia 15min → VideoCallEnded(reason) → toast contextual
[Sair temporário] → N/A (médico não tem "sair"; pode fechar aba — tratar como leave temporário opcional fase 2)
```

### Paciente

```
[Convite] → Entrar → SFU connect
[In-call] → Sair → Confirmar → POST /leave → SFU disconnect local; médico vê "paciente saiu"
[Médico encerra] → VideoCallEnded → overlay fecha + toast "O médico encerrou a chamada."
[Auto] tempo expirado / inatividade → toast correspondente
```

### Máquina de estados da Call (servidor)

```
requested/ringing/accepted
    ├─ POST /leave (paciente) → accepted (call continua; atualiza last_seen ou patient_left_at)
    ├─ POST /end (médico) → ended (call_closed_reason=ended_by_doctor)
    ├─ job window → ended (call_closed_reason=window_expired)
    └─ job empty 15min → ended (call_closed_reason=room_inactive)
```

**Nota:** para detectar "sala vazia 15min" é necessário rastrear presença. Opções:

- **A (recomendada):** colunas `doctor_last_seen_at`, `patient_last_seen_at` atualizadas por heartbeat `POST /calls/{id}/presence` a cada 30s enquanto SFU conectado.
- **B (simples):** considerar vazia quando `doctor_joined_at` e `patient_joined_at` são null e `accepted_at` > 15min — insuficiente se ambos entraram e saíram.
- **C:** webhook SFU room empty — depende infra MediaGateway.

**Decisão recomendada:** Opção A — heartbeat leve no composable enquanto `isInCall`.

---

## Arquitetura proposta

```
[Frontend in-call]
    ├─ leaveCall()  → POST /calls/{id}/leave  → CallManagerService::leaveCall
    └─ endCall()    → POST /calls/{id}/end    → CallManagerService::endCall (médico only)

[CallManagerService]
    ├─ leaveCall(user) → disconnect SFU local; marca left_at; event VideoCallParticipantLeft
    ├─ endCall(doctor) → status ended; destroyRoom; event VideoCallEnded(reason, ended_by_role)
    └─ endCallSystem(call, reason) → unifica jobs + broadcast

[Jobs]
    EndScheduledVideoCalls → endCallSystem(WINDOW_EXPIRED)
    EndEmptyVideoCalls (novo/refator EndZombie) → endCallSystem(ROOM_INACTIVE) quando heartbeat ausente 15min
    EndZombieVideoCalls → ad-hoc only → endCallSystem

[Reverb/Echo]
    VideoCallEnded → ambos participantes
    VideoCallParticipantLeft → participante remanescente
    medical-document.shared → appointment channel (existente)
```

### Padrões reutilizados

- `CallManagerService` — estender com `leaveCall`, `endCallSystem`, `recordPresence`
- `VideoCallEnded` — enriquecer payload: `reason`, `ended_by_role`, `message_key`
- `useVideoCall.ts` — separar `leaveCall` / `endCall`; util `mapVideoCallError(status)`
- `useCallSharedDocuments.ts` — manter; melhorar UX nos panels

---

## Mudanças por camada

### Frontend Vue

| Arquivo                               | Mudança                                                                                             |
| ------------------------------------- | --------------------------------------------------------------------------------------------------- |
| `useVideoCall.ts`                     | `leaveCall()`; `endCall()` só médico; API-first then SFU disconnect; error mapper                   |
| `useVideoCallSession.ts`              | Handlers `VideoCallEnded` com `message_key`; listener `VideoCallParticipantLeft`; presence interval |
| `VideoCallFloatingWidget.vue`         | Remover botão Encerrar                                                                              |
| `VideoCallActiveModal.vue`            | Manter só Entrar (+ Fechar dismiss)                                                                 |
| `DoctorVideoCallInCallOverlay.vue`    | Remover screen/hand; await end; toast após API                                                      |
| `PatientVideoCallInCallOverlay.vue`   | Remover screen/hand; `@end` → `leaveCall`; renomear evento `leave`                                  |
| `DoctorConsultControlsBar.vue`        | Remover props/botões screen, hand                                                                   |
| `PatientConsultControlsBar.vue`       | Idem                                                                                                |
| `PatientConsultSummaryPanel.vue`      | Ação "dúvida" → envia mensagem chat                                                                 |
| `DoctorConsultFilesPanel.vue`         | Barra progresso upload, preview thumbnail, erros inline amigáveis                                   |
| `PatientConsultFilesPanel.vue`        | Preview inline PDF/imagem, botões Ver/Baixar                                                        |
| `Doctor/VideoCall.vue`                | `handleEndCall` vs paciente `handleLeaveCall`                                                       |
| `Patient/VideoCall.vue`               | Idem                                                                                                |
| **Novo** `utils/videoCallMessages.ts` | Catálogo de mensagens + mapApiError                                                                 |

### Backend Laravel

| Arquivo                               | Mudança                                                                        |
| ------------------------------------- | ------------------------------------------------------------------------------ |
| `CallController`                      | `leave(Call $call)` action                                                     |
| `CallManagerService`                  | `leaveCall`, `endCallSystem`, `recordPresence`; policy check role em `endCall` |
| `VideoCallPolicy`                     | `end` → só médico; `leave` → participante                                      |
| `VideoCallEnded`                      | Payload: `reason`, `message_key`, `ended_by_role`                              |
| **Novo** `VideoCallParticipantLeft`   | Broadcast para peer                                                            |
| `EndScheduledVideoCalls`              | Usar `endCallSystem` + broadcast                                               |
| `EndZombieVideoCalls`                 | Idem                                                                           |
| **Novo/refator** `EndEmptyVideoCalls` | Scheduled + accepted, sem heartbeat 15min                                      |
| `routes/web/shared.php`               | `POST calls/{call}/leave`, `POST calls/{call}/presence`                        |

### Banco de dados

Editar migration original `2026_03_14_000001_create_calls_table.php` (dev local):

```php
$table->timestamp('doctor_left_at')->nullable();
$table->timestamp('patient_left_at')->nullable();
$table->timestamp('doctor_last_seen_at')->nullable();
$table->timestamp('patient_last_seen_at')->nullable();
// call_closed_reason: adicionar valores room_inactive, ended_by_doctor
```

Novos `CLOSED_REASON_*` em `Call` model:

- `CLOSED_REASON_ROOM_INACTIVE = 'room_inactive'`
- `CLOSED_REASON_ENDED_BY_DOCTOR = 'ended_by_doctor'`

### Config

```php
'room_inactive_minutes' => (int) env('VIDEO_ROOM_INACTIVE_MINUTES', 15), // era 60
'presence_interval_seconds' => (int) env('VIDEO_CALL_PRESENCE_INTERVAL', 30),
```

---

## Plano de remoção — screen share e levantar mão

### Modificar

| Arquivo                             | O que remover                                                                              |
| ----------------------------------- | ------------------------------------------------------------------------------------------ |
| `DoctorConsultControlsBar.vue`      | Props `screenSharing`, `handRaised`; botões MonitorUp/Hand; emit keys `'screen'`, `'hand'` |
| `PatientConsultControlsBar.vue`     | Idem                                                                                       |
| `DoctorVideoCallInCallOverlay.vue`  | refs `screenSharing`, `handRaised`; branches em `onCtrlToggle`                             |
| `PatientVideoCallInCallOverlay.vue` | Idem + `onSummaryAction` handRaised                                                        |
| `PatientConsultSummaryPanel.vue`    | Ícone Hand na UI de dúvida (substituir por chat)                                           |

### Verificar (sem screen share real)

| Arquivo                          | Ação                                                     |
| -------------------------------- | -------------------------------------------------------- |
| `SfuVideoMediaProvider.ts`       | Confirmar ausência de `getDisplayMedia` — nada a remover |
| `VideoCallInCallOverlayBase.vue` | Revisar se referencia screen                             |
| `Dev/SfuCallSimulator.vue`       | Manter ou alinhar controles dev                          |

### Não existe no backend

- Nenhuma policy, rota ou job de screen share/hand — remoção **100% frontend**.

---

## Correção fluxo encerramento — sequência de eventos

### Encerramento global (médico)

```
1. Médico clica Encerrar → DoctorConsultEndModal confirma
2. Frontend: POST /calls/{id}/end (sem disconnect SFU antes)
3. Backend: authorize doctor → endCall → ended + destroyRoom + VideoCallEnded
4. Frontend médico: recebe 204 → sfu.disconnect() → store.clearCall() → toast
5. Frontend paciente: Echo VideoCallEnded → sfu.disconnect() → store.clearCall() → toast message_key
6. broadcastSync() cross-tab
```

### Saída local (paciente)

```
1. Paciente clica Sair → PatientConsultEndModal confirma
2. POST /calls/{id}/leave
3. Backend: patient_left_at=now; VideoCallParticipantLeft → médico
4. Frontend paciente: 204 → sfu.disconnect() → store parcial (idle local, sem clear appointment)
5. Médico: toast "O paciente saiu da chamada." — permanece in-call
```

### Presença / sala vazia

```
1. Enquanto isInCall: POST /presence a cada 30s
2. Job EndEmptyVideoCalls (everyMinute):
   - call status=accepted, ended_at null
   - ambos last_seen_at null OR ambos > 15min atrás
   - AND nenhum joined_at recente
3. endCallSystem(ROOM_INACTIVE) + VideoCallEnded
```

---

## Sistema de anexos — melhorias propostas

### AS-IS

- Upload médico via `DoctorConsultFilesPanel` → Inertia POST documento `visibility=shared`.
- Realtime via `MedicalDocumentShared` → `useCallSharedDocuments`.
- Paciente baixa via route download; preview limitado.

### TO-BE

| Melhoria         | Detalhe                                                                        |
| ---------------- | ------------------------------------------------------------------------------ |
| Upload UX        | Barra de progresso (`form.progress`), estado uploading no card, retry em falha |
| Validação        | Mensagens inline (não toast genérico): tamanho, tipo, falha rede               |
| Preview paciente | PDF: iframe/`object` inline; imagem: thumbnail clicável; fallback download     |
| Preview médico   | Mesma lista com ícone tipo arquivo + "Enviado há X min"                        |
| Notificação      | Toast + badge na aba Arquivos ao receber `.medical-document.shared`            |
| Empty state      | Copy claro: "Nenhum documento ainda — o médico pode enviar durante a consulta" |
| Ordenação        | Mais recente primeiro (já em `upsert`)                                         |
| Segurança        | Manter auth nas rotas download; sem URL pública                                |

**Sem mudança de modelo** `MedicalDocument` — melhorias são UI + feedback. Opcional fase 2: endpoint JSON upload via axios (evita round-trip Inertia) — **fora do escopo mínimo**.

---

## Catálogo de mensagens e mapeamento de erros

### Mensagens de sucesso / info

| `message_key`             | Texto PT-BR                                        | Quando                                           |
| ------------------------- | -------------------------------------------------- | ------------------------------------------------ |
| `call.ended.generic`      | A consulta foi encerrada.                          | Encerramento genérico                            |
| `call.ended.by_doctor`    | O médico encerrou a chamada.                       | Paciente recebe VideoCallEnded por médico        |
| `call.ended.time_expired` | O tempo da consulta foi finalizado.                | Job window                                       |
| `call.ended.inactivity`   | A reunião foi encerrada por inatividade.           | Job sala vazia                                   |
| `call.left.patient`       | O paciente saiu da chamada.                        | Médico recebe ParticipantLeft                    |
| `call.left.doctor`        | O médico saiu temporariamente da chamada.          | Paciente (leave médico — fase 2 se implementado) |
| `connection.reconnecting` | Sua conexão foi interrompida. Tentando reconectar… | SFU disconnected unexpectedly                    |
| `connection.failed`       | Não foi possível restabelecer a conexão.           | Retry esgotado                                   |

### Mapeamento HTTP → amigável (`mapVideoCallError`)

| HTTP / condição   | Mensagem usuário                                          | Nunca mostrar     |
| ----------------- | --------------------------------------------------------- | ----------------- |
| 401               | Sua sessão expirou. Faça login novamente.                 | 401 Unauthorized  |
| 403               | Você não tem permissão para esta ação.                    | 403 Forbidden     |
| 404               | Esta chamada não está mais disponível.                    | 404 Not Found     |
| 422               | Não foi possível concluir a operação. Tente novamente.    | body técnico      |
| 409               | Já existe uma chamada em andamento.                       | conflict payload  |
| 500/502/503       | Serviço temporariamente indisponível. Tente em instantes. | stack trace       |
| Network error     | Verifique sua conexão com a internet.                     | AxiosError        |
| SFU token expired | Reconectando à sala…                                      | TokenExpiredError |

Implementar em `utils/videoCallMessages.ts`; usar em `useVideoCall`, overlays e pages.

---

## Riscos e dependências

| Risco                                  | Prob. | Impacto | Mitigação                                           |
| -------------------------------------- | ----- | ------- | --------------------------------------------------- |
| Heartbeat aumenta carga                | Média | Baixo   | Intervalo 30s; throttle route                       |
| Broadcast atrasado (queue)             | Média | Alto    | `ShouldBroadcastNow` em eventos call ou fila `high` |
| Paciente leave mas médico não vê       | Baixa | Médio   | Teste Feature + toast garantido                     |
| Migração presence columns              | Baixa | Baixo   | Editar create migration + migrate:fresh             |
| SFU room órfã se API falhar            | Média | Médio   | API-first; retry 1x; job cleanup                    |
| Confusão scheduled vs ad-hoc end rules | Baixa | Médio   | Policy explícita por role, não por call_type        |

**Dependências:** Laravel Reverb ativo, worker queue para broadcast, MediaGateway SFU, Redis (locks/cache).

---

## Critérios de aceite

### Remoções

- [ ] Nenhum botão/control de screen share ou levantar mão visível em produção
- [ ] Nenhum toast relacionado a screen/hand

### Convite

- [ ] Modal e widget **sem** Encerrar; apenas Entrar
- [ ] Fechar/dismiss não encerra call no servidor

### Encerrar / sair

- [ ] Médico encerra → paciente desconecta em ≤ 3s com mensagem correta
- [ ] Paciente sai → médico permanece; paciente desconecta; call continua `accepted`
- [ ] Paciente **não** consegue chamar `POST /end` (403)

### Auto-encerramento

- [ ] Após janela trailing → ambos recebem toast tempo expirado
- [ ] Sala vazia 15min → encerramento + toast inatividade

### Anexos

- [ ] Upload com feedback de progresso
- [ ] Paciente vê documento novo sem refresh
- [ ] Preview/download funcionam em mobile

### Erros

- [ ] Nenhuma UI exibe código HTTP ou mensagem técnica bruta

---

## Test plan

| Teste                                        | Tipo                         |
| -------------------------------------------- | ---------------------------- |
| Médico POST /end → VideoCallEnded dispatched | Feature                      |
| Paciente POST /leave → call still accepted   | Feature                      |
| Paciente POST /end → 403                     | Feature                      |
| EndScheduledVideoCalls → VideoCallEnded      | Feature                      |
| EndEmptyVideoCalls após 15min sem presence   | Feature                      |
| mapVideoCallError todos status               | Unit TS                      |
| Upload document during call → patient Echo   | Feature (existente estender) |

---

## Plano de implementação (fases)

### Fase 1 — Correção encerramento (crítico) · **Média-alta complexidade · ~3–4 dias**

1. Backend: `leaveCall`, policy `end` só médico, enriquecer `VideoCallEnded`
2. Backend: `endCallSystem` + broadcast nos jobs existentes
3. Frontend: separar `leaveCall`/`endCall`; API-first; remover Encerrar do widget
4. Frontend: await API antes de toast no overlay médico
5. Testes Feature end/leave/broadcast

### Fase 2 — Presença e auto-encerramento 15min · **Média complexidade · ~2 dias**

1. Migration presence/left columns
2. `POST /presence` + composable heartbeat
3. Job `EndEmptyVideoCalls`; config `room_inactive_minutes=15`
4. Mensagens contextualizadas por `call_closed_reason`

### Fase 3 — Remoções screen share / raise hand · **Baixa complexidade · ~0.5 dia**

1. Limpar controls bars e overlays
2. Refatorar ação "dúvida" → chat

### Fase 4 — Anexos UX · **Média complexidade · ~2 dias**

1. Progress upload médico
2. Preview paciente
3. Empty states e toasts padronizados

### Fase 5 — Catálogo erros e polish · **Baixa-média · ~1 dia**

1. `videoCallMessages.ts` + aplicar em todos touchpoints
2. Reconexão SFU com mensagens amigáveis
3. QA mobile

**Total estimado:** ~8–10 dias dev (1 dev full-stack).

---

## Checklist pré-implementação

- [ ] Spec revisada e aprovada
- [ ] Confirmar `VIDEO_ROOM_INACTIVE_MINUTES=15` com produto
- [ ] Confirmar se médico pode "sair temporário" (fase 2) ou só encerrar
- [ ] `/review-security` em CallController, Policy, document routes
- [ ] `/review-performance` em job presence + heartbeat
