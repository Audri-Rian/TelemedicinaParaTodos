# Remover e manter — saída do P2P

Documento **apenas** de inventário: o que **remover** e o que **manter** ao eliminar a estrutura P2P (PeerJS) de videochamadas. Não descreve a nova implementação; para isso ver o documento da nova implementação SFU.

---

## Manter (não faz parte da videochamada P2P)

### Regras de negócio e configuração

- **Appointments:** modelo, relações, status (scheduled, in_progress, completed, etc.).
- **AppointmentPolicy** e regras de “quem pode iniciar/aceitar consulta”.
- **Janela de acesso:** `config/telemedicine.php` (lead_minutes, duration, grace, etc.).
- **Regras médico/paciente:** apenas médico e paciente da consulta podem participar da chamada.

### Modelos de domínio (não específicos de P2P)

- **User**, **Doctor**, **Patient**, **Appointments** e demais modelos que não existem só para troca de `peerId`.

### Reverb e canais (outras features)

- **Canais:** `messages.{id}`, `appointment.{participantId}`, `notifications.{id}`.
- **Reverb em si:** servidor WebSocket continua; apenas os *eventos* e o *uso* do canal de videochamada mudam.

### Aplicação em geral

- **Autenticação,** middleware, rotas que não forem exclusivas de videochamada P2P.
- **Rotas e controllers** de consultas, prontuários, mensagens, etc.

---

## Remover (estrutura P2P e uso de peerId)

### Backend (Laravel)

| O quê | Onde / detalhe |
|-------|-----------------|
| Controller que recebe e repassa `peerId` | `app/Http/Controllers/VideoCall/VideoCallController.php` — métodos `requestVideoCall` e `requestVideoCallStatus`. |
| Eventos que carregam `peerId` | `app/Events/RequestVideoCall.php`, `app/Events/RequestVideoCallStatus.php`. |
| Rotas antigas | Em `routes/web.php`: `POST video-call/request/{user}`, `POST video-call/request/status/{user}`. |
| Uso atual do canal `video-call.{id}` | Forma atual (eventos com peerId); o canal em si pode ser mantido ou renomeado na nova implementação. |
| Jobs/Events/Models que só existem para o fluxo “troca de peerId” | Avaliar um a um; remover o que for apenas suporte ao P2P. |

### Frontend

| O quê | Onde / detalhe |
|-------|-----------------|
| Dependência PeerJS | `package.json` — remover `peerjs`. |
| Composable que mistura Echo + PeerJS + mídia | `resources/js/composables/useVideoCall.ts` — remover (após nova implementação estável). |
| Uso de Peer, peerId, peerCall, call.answer(), peer.call() | Em todas as páginas que usam videochamada: `Doctor/Consultations.vue`, `Patient/VideoCall.vue`, `Dev/VideoTest.vue`. |

---

## Avaliar (manter ideia/regras, recriar ou adaptar)

- **VideoCallPolicy:** manter as *regras* (quem pode solicitar/aceitar); recriar ou adaptar à nova API (sem peerId).
- **VideoCallRoom / VideoCallEvent:** se hoje guardam só dados P2P (ex. peerId), recriar modelos/tabelas para o novo modelo (roomId, appointment_id, token, etc.); não reaproveitar estrutura pensada em peerId.
- **Eventos de sala:** `VideoCallRoomCreated`, `VideoCallRoomExpired`, `VideoCallUserJoined`, `VideoCallUserLeft` — decidir se continuam no novo fluxo ou são substituídos por outros eventos.
- **Jobs:** `CleanupOldVideoCallEvents`, `ExpireVideoCallRooms`, `UpdateAppointmentFromRoom` — adaptar ao novo modelo (roomId, sala no SFU).
- **Commands:** `CheckVideoCallSetup`, `TestVideoCallPolicy` — atualizar para nova API e políticas.
- **Canal `video-call.{id}` em `routes/channels.php`:** manter o canal (ou renomear); será usado para os *novos* eventos de ciclo de vida da chamada.

---

*Este arquivo não descreve a nova implementação SFU; apenas o que deve ser removido e o que deve ser mantido ao sair do P2P.*
