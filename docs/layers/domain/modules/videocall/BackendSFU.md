# Back-end do sistema de videochamada (SFU)

Documentação do **back-end Laravel** do módulo de videochamada com arquitetura **SFU (Selective Forwarding Unit)**. O ciclo de vida da chamada (solicitar, aceitar, rejeitar, encerrar) fica no Laravel; a mídia (áudio/vídeo) é tratada por um servidor SFU (ex.: MediaSoup) acessado via **token JWT** — o frontend nunca envia nem confia em `roomId` vindo do cliente.

**Documento de referência da implementação:** [docs/videocall/IMPLEMENTACAO_SFU_MEDIASOUP.md](../../videocall/IMPLEMENTACAO_SFU_MEDIASOUP.md).

---

## Visão geral do SFU no back-end

- **Call (negócio):** representa a solicitação e o estado da videochamada ligada a uma consulta (appointment). Campos: appointment, médico, paciente, status, timestamps.
- **Room (mídia):** representa a sala de mídia no SFU. Uma Call tem no máximo uma Room enquanto a chamada estiver ativa. O `room_id` é o identificador da sala no SFU; **nunca** é confiável vindo do frontend — só dentro do token emitido pelo back-end.
- **Token:** JWT (ou stub) com `callId`, `roomId`, `userId`, `role`, `exp`. O frontend recebe o token no evento de chamada aceita e envia **apenas o token** ao SFU; o SFU valida e autoriza a entrada na sala.
- **Sinalização de ciclo de vida:** Laravel + Reverb (canal privado `video-call.{userId}`). Eventos: VideoCallRequested, VideoCallAccepted, VideoCallRejected, VideoCallEnded (e futuramente VideoCallMissed para timeout).
- **Integração com SFU:** o Laravel não fala WebRTC; delega criação e destruição de sala a um **Media Gateway** (ou diretamente ao servidor SFU) via interface. A implementação atual é um stub; a real fará HTTP para o Gateway/SFU.

---

## Arquitetura em camadas

| Camada        | Responsabilidade no back-end |
|---------------|------------------------------|
| **HTTP/API**  | Endpoints request / accept / reject / end (a implementar). Validação e autorização (políticas, appointment). |
| **Serviço**   | `CallManagerService`: orquestra createCall, acceptCall, rejectCall, endCall, createRoom, destroyRoom; persiste Call e Room; emite eventos. |
| **Gateway**   | `MediaGatewayInterface`: createRoom(callId), destroyRoom(roomId). Implementação stub hoje; depois: HTTP para Media Gateway ou SFU. |
| **Domínio**   | Modelos `Call` e `Room`; regras de transição de estado da chamada. |
| **Eventos**   | VideoCallRequested, VideoCallAccepted, VideoCallRejected, VideoCallEnded — broadcast no canal `video-call.{userId}`. |
| **Persistência** | Tabelas `calls` e `rooms`; relacionamento Call 1:1 Room. |

---

## Estrutura de arquivos do back-end

### Modelos

| Arquivo | Função |
|---------|--------|
| **`app/Models/Call.php`** | Entidade de negócio da videochamada. Relaciona appointment, doctor, patient; possui status (requested, ringing, accepted, rejected, ended, missed) e timestamps (requested_at, accepted_at, ended_at). Relação `hasOne` com Room. Método `isActive()` para saber se a chamada ainda está em andamento. |
| **`app/Models/Room.php`** | Entidade de mídia: sala no SFU. Armazena `room_id` (ID no SFU), `call_id` (FK para Call) e `sfu_node` (opcional). Relação `belongsTo` Call. O `room_id` é a única referência confiável à sala no SFU; nunca deve ser determinado pelo frontend. |

### Serviços

| Arquivo | Função |
|---------|--------|
| **`app/Services/CallManagerService.php`** | Ponto central da orquestração. Recebe `MediaGatewayInterface` e `AppointmentService` por injeção. **createCall:** valida que o usuário é participante do appointment, cria Call em status requested, emite VideoCallRequested para o destinatário, registra log CALL_CREATED. **acceptCall:** valida que quem aceita é o destinatário, chama createRoom (Gateway), persiste Room, atualiza Call para accepted, inicia a consulta se ainda não estiver em progresso, gera token (stub/JWT futuro), emite VideoCallAccepted com token e sfu_ws_url para médico e paciente, log CALL_ACCEPTED/ROOM_CREATED. **rejectCall:** atualiza Call para rejected e emite VideoCallRejected para quem solicitou. **endCall:** atualiza Call para ended, encerra a consulta se em progresso, chama destroyRoom no Gateway, emite VideoCallEnded para ambos, log CALL_ENDED/ROOM_LEFT. **createRoom:** delega ao Media Gateway e persiste o modelo Room. **destroyRoom:** delega ao Gateway o encerramento da sala. Auxiliar **getCalleeUserId** identifica o destinatário da chamada; **generateRoomToken** hoje retorna um stub (JWT em tarefa futura). |
| **`app/Services/MediaGatewayStub.php`** | Implementação stub de `MediaGatewayInterface`. createRoom retorna um `room_id` fictício e `sfu_node` (config ou "local"); destroyRoom não realiza chamada externa. Usada até existir integração real com Media Gateway ou servidor SFU. |

### Contrato (interface)

| Arquivo | Função |
|---------|--------|
| **`app/Contracts/MediaGatewayInterface.php`** | Contrato da integração Laravel ↔ Media Gateway (ou SFU). **createRoom(callId):** cria sala no SFU e retorna array com `room_id` e opcionalmente `sfu_node`. **destroyRoom(roomId):** encerra a sala no SFU. Permite trocar a implementação (stub, Gateway HTTP, etc.) sem alterar o CallManagerService. |

### Eventos (Broadcasting)

| Arquivo | Função |
|---------|--------|
| **`app/Events/VideoCallRequested.php`** | Disparado quando uma chamada é solicitada. Transmite no canal privado do destinatário (`video-call.{calleeUserId}`). Payload: call_id, appointment_id, dados do caller (id, name). Nome do evento: VideoCallRequested. |
| **`app/Events/VideoCallAccepted.php`** | Disparado quando a chamada é aceita. Transmite nos canais do médico e do paciente. Payload: call_id, **token** (JWT/stub) e **sfu_ws_url**. O roomId não é enviado como dado confiável — fica apenas dentro do token. Nome: VideoCallAccepted. |
| **`app/Events/VideoCallRejected.php`** | Disparado quando o destinatário rejeita. Transmite no canal de quem solicitou a chamada. Payload: call_id. Nome: VideoCallRejected. |
| **`app/Events/VideoCallEnded.php`** | Disparado quando a chamada é encerrada. Transmite nos canais do médico e do paciente. Payload: call_id. Nome: VideoCallEnded. |

### Políticas e configuração

| Arquivo | Função |
|---------|--------|
| **`app/Policies/VideoCallPolicy.php`** | Política de autorização para ações de videochamada. Estrutura básica mantida para evoluir com as regras (quem pode solicitar/aceitar/rejeitar/encerrar conforme appointment e papéis). |
| **`config/telemedicine.php`** | Seção `video_call`: room_inactive_minutes, room_max_duration_minutes; referência ao modelo SFU e ao doc de implementação. |
| **`config/services.php`** | Seção `media_gateway`: sfu_ws_url, sfu_node (usados pelo stub e pelo evento VideoCallAccepted). |
| **`app/Providers/AppServiceProvider.php`** | Registra o binding `MediaGatewayInterface` → `MediaGatewayStub`. |

### Persistência (migrations)

| Arquivo | Função |
|---------|--------|
| **`database/migrations/2026_03_14_000001_create_calls_table.php`** | Cria a tabela `calls`: id (UUID), appointment_id, doctor_id, patient_id, status, requested_at, accepted_at, ended_at, timestamps. Índices por status, appointment, doctor e patient. |
| **`database/migrations/2026_03_14_000002_create_rooms_table.php`** | Cria a tabela `rooms`: id, call_id (FK), room_id (string, único), sfu_node (nullable). |
| **`database/migrations/2026_03_14_100000_drop_p2p_video_call_tables.php`** | Remove as tabelas legadas do modelo P2P (video_call_events, video_call_rooms). |

### Canal Reverb

| Onde | Função |
|------|--------|
| **`routes/channels.php`** | Canal privado `video-call.{id}`: apenas o usuário cujo id coincide com o do canal pode escutar. Usado por todos os eventos de ciclo de vida da videochamada. |

---

## Fluxo resumido (back-end)

1. **Solicitar:** Controller (a implementar) valida appointment e usuário, chama `CallManagerService::createCall(appointment, caller)`. Serviço cria Call, emite VideoCallRequested para o destinatário.
2. **Aceitar:** Controller recebe o aceite, chama `acceptCall(call, user)`. Serviço cria Room via Gateway, persiste Room, gera token, atualiza Call e consulta, emite VideoCallAccepted com token e sfu_ws_url.
3. **Rejeitar:** Controller chama `rejectCall(call, user)`. Serviço atualiza Call e emite VideoCallRejected.
4. **Encerrar:** Controller chama `endCall(call, user)`. Serviço atualiza Call, encerra consulta se necessário, chama `destroyRoom(room)` no Gateway e emite VideoCallEnded.

O frontend usa o token e o sfu_ws_url recebidos no VideoCallAccepted para conectar ao SFU e enviar apenas o token; o SFU valida e autoriza a entrada na sala.

---

## Logs estruturados

O CallManagerService registra eventos para auditoria e diagnóstico:

- **CALL_CREATED** — call_id, appointment_id, user_id (quem solicitou).
- **CALL_ACCEPTED** — call_id, room_id, user_id, appointment_id.
- **ROOM_CREATED** — call_id, room_id.
- **ROOM_LEFT** — room_id, call_id (ao destruir a sala).
- **CALL_ENDED** — call_id, room_id, user_id, appointment_id.

---

## Próximos passos (checklist de implementação)

Conforme [IMPLEMENTACAO_SFU_MEDIASOUP.md](../../videocall/IMPLEMENTACAO_SFU_MEDIASOUP.md):

- Endpoints HTTP: request, accept, reject, end.
- Timeout de chamada (ex.: 30s) e evento VideoCallMissed.
- Geração de JWT real para o token de sala (callId, roomId, userId, role, exp; 1–5 min).
- Implementação real do Media Gateway (HTTP para SFU/Gateway) em vez do stub.
- Completar VideoCallPolicy com regras de quem pode solicitar/aceitar.
- Controllers e rotas que utilizem o CallManagerService e a política.

---

*Documento do módulo de videochamada — back-end SFU. Última atualização: março 2026.*
