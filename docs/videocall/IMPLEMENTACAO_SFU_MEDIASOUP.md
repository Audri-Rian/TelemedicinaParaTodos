# Nova implementação do SFU (MediaSoup)

Documento da **nova implementação** de videochamada com MediaSoup como SFU: arquitetura, fluxo, backend, servidor de mídia e frontend. O que **remover** e **manter** em relação ao P2P está em documento separado (*Remover e manter — saída do P2P*).

**Decisões:**
- Sinalização de mídia: **WebSocket próprio** do servidor MediaSoup (não Reverb como proxy).
- Ciclo de vida da chamada: **Laravel + Reverb** (solicitar, aceitar, rejeitar, encerrar) e regras de negócio (appointment, políticas).
- **Token de acesso à sala:** JWT assinado e de curta duração; **roomId nunca vem do frontend** — sempre dentro do token emitido pelo backend.
- **Call vs Room:** separação clara: Call (negócio/consulta) → Room (mídia/SFU). Centralização em **CallManagerService** e **Media Gateway**.

---

## Segurança: token JWT e roomId

### Regra fundamental

**O frontend não decide em qual sala entrar.** Ele apenas recebe um **token assinado** pelo backend e envia esse token ao SFU. O SFU valida o token e autoriza (ou não) a entrada.

- **roomId nunca é confiável vindo do cliente.** Sempre vem **dentro do token** assinado.
- Evita: usuários em salas indevidas, replay de tokens, acesso direto ao SFU com roomId adulterado (vazamento de dados médicos).

### Token JWT

- **Formato:** JWT assinado (HMAC ou RS256), payload **incluindo callId** além de roomId, userId, role e exp.
- **Payload ideal:**
  ```json
  {
    "callId": "call_123",
    "roomId": "room_abc",
    "userId": 45,
    "role": "doctor",
    "exp": 1710000000
  }
  ```
- **Por que incluir callId:** o SFU pode validar se a call ainda está ativa, emitir métricas por call e registrar logs melhores (call_id, room_id, user_id).
- **Expiração:** curta — **1 a 5 minutos** (típico).
- **Emissor:** Laravel (ou Media Gateway, se adotado) após validar aceite da chamada.
- **Uso:** Frontend recebe o token no evento `VideoCallAccepted`; conecta ao SFU e envia **apenas o token**; o SFU valida assinatura e expiração e permite entrada na sala indicada no payload.

### Fluxo correto

```
Frontend                    Laravel                      SFU
   │                           │                           │
   │  POST /accept              │                           │
   │ ─────────────────────────►│                           │
   │                           │  cria sala (via Gateway)   │
   │                           │  gera JWT (callId, roomId, userId, role, exp)
   │                           │                           │
   │  VideoCallAccepted        │                           │
   │  (token, SFU_WS_URL)      │                           │
   │ ◄─────────────────────────│                           │
   │                           │                           │
   │  conecta ao SFU           │                           │
   │  envia token              │                           │
   │ ───────────────────────────────────────────────────►│
   │                           │                     valida token
   │                           │                     permite entrada
```

---

## Separar Call (negócio) de Room (mídia)

Dois conceitos distintos:

| | **Call** (negócio) | **Room** (mídia) |
|---|-------------------|------------------|
| **Representa** | Consulta médica / solicitação de videochamada | Sala de vídeo no SFU |
| **Campos típicos** | `call_id`, `appointment_id`, `doctor_id`, `patient_id`, `status` | `room_id`, `sfu_node`, `created_at` |
| **Relacionamento** | Call **tem** uma Room (1:1 enquanto a chamada estiver ativa) | Room pertence a uma Call |

Benefícios: gravação futura, reconectar chamada, migrar sala entre nós SFU sem alterar o conceito de "consulta".

---

## Arquitetura alvo

Com **Media Gateway** (recomendado): Laravel não entende mídia; delega criação de sala e token ao Gateway.

```
                    ┌─────────────────────────────────────────────────────────┐
                    │                     Laravel (Backend)                    │
                    │  • Regras de negócio (Appointments, Policies)            │
                    │  • Request/Accept/Reject/End call (HTTP + Reverb)        │
                    │  • CallManagerService: estado da chamada, persistência    │
                    │  • Não gera roomId/token — delega ao Media Gateway       │
                    └───────────────────────┬─────────────────────────────────┘
                                            │
        ┌──────────────────────────────────┼──────────────────────────────────┐
        │                                  │                                  │
        ▼                                  ▼                                  ▼
┌─────────────────┐              ┌─────────────────┐              ┌─────────────────────────┐
│  Reverb (WS)    │              │  API HTTP       │              │  Media Gateway          │
│  video-call.*   │              │  /video-call/*  │  create-room │  • Escolhe SFU          │
│  eventos de     │              │  request        │──────────────►│  • Cria room            │
│  vida da chamada│              │  accept         │  roomId+JWT  │  • Retorna roomId+JWT   │
└────────┬────────┘              │  reject / end   │              │  • (Futuro: gravação)   │
         │                       └────────┬───────┘              └────────────┬────────────┘
         │                                │                                  │
         ▼                                ▼                                  ▼
┌─────────────────────────────────────────────────────────────────────────────────────────┐
│  Frontend: useVideoCallSignaling (Echo, estados) + useMediaSoupRoom (token, SFU_WS_URL)  │
└─────────────────────────────────────────────────────────────────────────────────────────┘
                                                                              │
                                                                              ▼
                                                                    ┌─────────────────────────┐
                                                                    │  MediaSoup Cluster      │
                                                                    │  • Rooms / Routers       │
                                                                    │  • WS (signaling)        │
                                                                    │  • WebRTC (mídia)        │
                                                                    └─────────────────────────┘
```

Fluxo ao aceitar: Frontend → `POST /accept` → Laravel → `POST /create-room` → Media Gateway → cria room, retorna roomId + JWT → Laravel emite evento com **token** e SFU_WS_URL → Frontend conecta ao SFU enviando **apenas o token**.

### Responsabilidades de cada parte

| Camada | Responsabilidades |
|--------|-------------------|
| **Laravel** | Usuários, consultas, agenda, autenticação, regras de negócio. Não entende mídia (RTP, WebRTC, transports). |
| **Media Gateway** | Criar salas, gerenciar SFUs, gerar/validar tokens de sala, monitorar streams, (futuro) gravação. Fala com o cluster MediaSoup. Facilita trocar SFU (ex.: LiveKit) e escalar (múltiplos nós). |
| **MediaSoup** | Apenas rotear áudio e vídeo (SFU). |

---

## Fluxo da chamada (pós-implementação)

1. **Solicitar chamada:** Front chama `POST /api/video-call/request` (target ou appointment_id). Laravel valida appointment e políticas; emite evento Reverb para o destinatário (`VideoCallRequested`), **sem peerId**. Inicia **timeout de chamada** (ex.: 30s); se expirar sem aceite → `VideoCallMissed`.
2. **Destinatário recebe:** Echo recebe evento; exibe modal “X está chamando”. Aceita ou rejeita.
3. **Aceitar:** Front chama `POST /api/video-call/accept`. Laravel valida; delega ao Media Gateway (ou ao servidor MediaSoup) a criação da sala; **backend gera JWT** (callId, roomId, userId, role, exp); emite `VideoCallAccepted` com **token** e `SFU_WS_URL` (roomId só dentro do token; frontend não envia roomId como dado confiável).
4. **Entrar na sala de mídia:** Cada lado usa `useMediaSoupRoom(token, SFU_WS_URL)` e conecta ao WebSocket do MediaSoup **enviando o token**; SFU valida e autoriza; getRouterRtpCapabilities, createWebRtcTransport, connect, produce, consume. `<video>` recebem os streams.
5. **Encerrar:** Qualquer um chama `POST /api/video-call/end`. Laravel emite `VideoCallEnded` e avisa o Media Gateway/SFU para fechar a sala. Front chama `leave()` no `useMediaSoupRoom` e volta ao estado idle no `useVideoCallSignaling`.

---

## Backend (Laravel) — o que construir

### CallManagerService

Centralizar estado da chamada, integração com SFU/Media Gateway e persistência em um **CallManagerService** (ou `CallManager`), evitando lógica espalhada em controllers.

Conceitualmente:

- **createCall()** — inicia solicitação de chamada (request); valida appointment/políticas; persiste Call; emite evento; inicia timeout.
- **acceptCall()** — valida; chama Media Gateway para criar room; obtém roomId; gera JWT (callId, roomId, userId, role, exp); persiste Room ligada à Call; emite evento com token e SFU_WS_URL.
- **rejectCall()** — atualiza estado; emite evento.
- **endCall()** — atualiza consulta (started_at/ended_at); emite evento; chama **destroyRoom()** no Gateway/SFU.
- **createRoom()** — delega ao Media Gateway: criar sala no SFU; retornar roomId (e opcionalmente o Gateway gera/retorna o JWT, ou Laravel gera JWT com o roomId retornado).
- **destroyRoom()** — notifica Media Gateway/SFU para fechar a sala.

Com crescimento (múltiplos SFUs, gravação), o CallManager pode delegar toda a parte de mídia ao Media Gateway.

### Nova API HTTP

- **Solicitar:** `POST /api/video-call/request` — body: target (user_id ou appointment_id). Valida appointment, janela, políticas; emite evento para o destinatário (sem peerId). Timeout de chamada (ex.: 30s) → `VideoCallMissed` se não aceitar.
- **Aceitar:** `POST /api/video-call/accept` — body: identificador da solicitação. Valida; via CallManager, chama Media Gateway para criar sala; **Laravel (ou Gateway) gera JWT** com callId, roomId, userId, role, exp (1–5 min); emite evento com **token** e SFU_WS_URL (não enviar roomId como dado confiável ao cliente).
- **Rejeitar:** `POST /api/video-call/reject` — emite evento.
- **Encerrar:** `POST /api/video-call/end` — atualiza consulta; emite evento; destroyRoom no Gateway/SFU.

### Novos eventos (Reverb)

- **VideoCallRequested** — para o destinatário: quem está chamando, appointment_id, etc. (sem peerId).
- **VideoCallAccepted** — para quem precisa entrar na sala: **token** (JWT), SFU_WS_URL. roomId só dentro do token.
- **VideoCallRejected**, **VideoCallEnded**, **VideoCallMissed** (timeout sem aceite).

### Canal e autorização

- Canal (ex. `video-call.{userId}` ou `video-call-lifecycle.{userId}`): apenas o usuário dono do canal. Manter ou renomear em `routes/channels.php`.

### Integração Laravel ↔ Media Gateway / MediaSoup

- **Media Gateway** (recomendado): Laravel chama `POST /create-room` (call_id ou appointment_id); Gateway escolhe SFU, cria room, retorna roomId; Laravel gera JWT (callId, roomId, userId, role, exp) e emite evento. Ou o Gateway gera o JWT e retorna token + roomId; Laravel apenas repassa no evento.
- **Sem Gateway:** cliente HTTP (Laravel) que chama diretamente o servidor MediaSoup: criar room, retornar roomId; Laravel gera JWT (callId, roomId, userId, role, exp) e emite evento. Servidor MediaSoup expõe API REST para criar/encerrar room e **valida JWT** na conexão WebSocket do cliente.

### Persistência (Call e Room)

- **Call (negócio):** `call_id`, `appointment_id`, `doctor_id`, `patient_id`, `status`, timestamps.
- **Room (mídia):** `room_id`, `call_id` (FK), `sfu_node`, `created_at`. Token não persiste longo prazo; JWT é de uso único e curto.
- Recriar ou migrar a partir do que existir; ver documento *Remover e manter* para o que não reaproveitar.

---

## Servidor MediaSoup (Node.js) — o que construir

- Projeto Node com **mediasoup**; **WebSocket** (ex. Socket.IO ou `ws`) para sinalização com o cliente.
- **API para Laravel / Media Gateway:** criar room (retorna roomId); encerrar room. Token JWT é gerado pelo Laravel (ou Gateway) e **validado pelo SFU** na conexão do cliente: cliente envia token; SFU verifica assinatura e expiração e extrai callId, roomId, userId, role; permite ou nega entrada. Com callId o SFU pode validar se a call ainda está ativa, emitir métricas por call e registrar logs melhores.
- **Protocolo cliente ↔ servidor:** cliente conecta enviando **token**; getRouterRtpCapabilities, createWebRtcTransport, connect, produce, consume (conforme documentação mediasoup).
- **Heartbeat de conexão:** implementar ping/pong (cliente → ping, servidor → pong) ou heartbeat do WebSocket. Se a conexão for perdida (cliente desconectado, rede caiu, browser fechou), emitir evento **CallDisconnected** (para o outro participante ou para o backend) e encerrar recursos daquele peer.
- **Métricas de qualidade:** MediaSoup expõe métricas (packet loss, bitrate, RTT, jitter). Enviar para Prometheus/Grafana ou ao menos logar; ajuda a debugar vídeo travando, áudio robotizado.
- Variáveis de ambiente: `SFU_WS_URL`, `SFU_URL`, segredo/chave para validar JWT (já reservadas no projeto).

---

## Heartbeat e reconexão

### Heartbeat

- WebRTC pode falhar silenciosamente (cliente desconectado, rede caiu, browser fechou).
- **Sugestão:** heartbeat no canal WebSocket (client → ping, server → pong). Se perder N pongs, considerar desconectado e emitir **CallDisconnected** (Reverb ou evento para o outro participante).

### Reconexão automática

- Muito importante em telemedicina (ex.: paciente muda de rede wifi → 4G; sem reconexão a consulta cai).
- **Guardar no frontend:** token (e SFU_WS_URL) da chamada atual; se a conexão cair, permitir **reconnect**.
- **Fluxo:** cliente reconecta → envia mesmo token (se ainda válido) ou solicita novo token via Laravel → join existing room → recriar transports/producers/consumers. Backend pode emitir evento para o outro participante indicando “reconectando” se desejado.

---

## Logs estruturados e timeout

### Logs

- Criar logs claros para eventos importantes, com dados estruturados (call_id, room_id, user_id, appointment_id). Exemplos: **CALL_CREATED**, **CALL_ACCEPTED**, **ROOM_CREATED**, **ROOM_JOINED**, **ROOM_LEFT**, **CALL_ENDED**, **CALL_DISCONNECTED**.

### Timeout de chamada

- **Cenário:** médico chama paciente; paciente nunca responde → chamada fica pendente.
- **Solução:** `call_timeout` (ex.: 30s). Se expirar sem aceite, emitir **VideoCallMissed** e limpar estado.

---

## Preparar gravação (arquitetura futura)

- Se a telemedicina evoluir, pode ser necessário **gravação da consulta**. MediaSoup permite isso via **plain transport** (e.g. RTP para um recorder).
- **Arquitetura sugerida:** Users → MediaSoup → Recorder → Storage (ex.: S3). Mesmo que não implemente agora, deixar a arquitetura preparada (Media Gateway pode orquestrar o recorder; Laravel apenas registra que a chamada foi gravada, se necessário).

---

## Frontend (Vue) — o que construir

### Composables

- **useVideoCallSignaling**
  - Apenas Reverb (Echo); canal privado do usuário para eventos de videochamada.
  - **Estado (robusto):** idle, requesting, ringing_out, ringing_in, connecting, connected, reconnecting, ended, error.
  - Dados: quem está chamando, appointment; quando aceito: **token**, SFU_WS_URL (roomId só dentro do token; frontend não usa roomId como dado confiável).
  - Métodos: requestCall(user), acceptCall(), rejectCall(), endCall() (HTTP + eventos Reverb).
  - Não conhece MediaSoup nem WebRTC.

- **useMediaSoupRoom**
  - **Entrada:** token (JWT), SFU_WS_URL (e opcionalmente configurações de mídia). **Não receber roomId do cliente** — o roomId é extraído pelo SFU a partir do token.
  - WebSocket para o MediaSoup: cliente envia **token**; SFU valida e autoriza; protocolo de sinalização (getRouterRtpCapabilities, createWebRtcTransport, connect, produce, consume) com **mediasoup-client** (ou equivalente).
  - Expõe: localStream, remoteStreams (por participante), estado (connecting, connected, reconnecting, error), join(), leave(), reconnect() (reconexão com token guardado), opcionalmente mute/unmute.
  - Não conhece Reverb nem papéis médico/paciente.

### Páginas

- **Doctor/Consultations.vue**, **Patient/VideoCall.vue:** usam `useVideoCallSignaling` para solicitar chamada, modal de chamada recebida, aceitar/rejeitar, encerrar. Quando estado = connected (ou in_call) e houver token e SFU_WS_URL, montam componente que usa `useMediaSoupRoom(token, SFU_WS_URL)` e liga streams aos `<video>`. Suportar estado reconnecting e reconnect().
- **Dev/VideoTest.vue:** recriar como tela de teste do fluxo MediaSoup (opcional).

### Dependências e env

- Adicionar **mediasoup-client** (ou equivalente) em `package.json`.
- Expor **SFU_WS_URL** (e SFU_URL se necessário) no build (Vite/env).

---

## Checklist de implementação

### Backend (Laravel)

- [x] CallManagerService: createCall(), acceptCall(), rejectCall(), endCall(), createRoom(), destroyRoom() (integração com Media Gateway ou SFU).
- [x] Definir novos endpoints: request, accept, reject, end (contratos conforme acima); timeout de chamada (ex.: 30s) → VideoCallMissed.
- [x] Implementar novos eventos Reverb: VideoCallRequested, VideoCallAccepted, VideoCallRejected, VideoCallEnded, VideoCallMissed.
- [x] Token JWT: gerar após accept (payload: callId, roomId, userId, role, exp; 1–5 min); nunca confiar em roomId vindo do frontend.
- [x] Integração Laravel ↔ Media Gateway (ou MediaSoup): criar sala, obter roomId; Laravel gera JWT; encerrar sala.
- [x] Modelos Call e Room separados (Call: appointment_id, doctor_id, patient_id, status; Room: room_id, call_id, sfu_node).
- [x] Logs estruturados: CALL_CREATED, CALL_ACCEPTED, ROOM_CREATED, ROOM_JOINED, ROOM_LEFT, CALL_ENDED (call_id, room_id, user_id, appointment_id).
- [x] Manter políticas e regras de appointment na nova API.
- [x] Manter canal Reverb para videochamada com autorização por usuário.

### Media Gateway (opcional mas recomendado)

- [ ] Serviço que recebe create-room do Laravel; escolhe SFU; cria room; retorna roomId (e opcionalmente gera/retorna JWT).
- [ ] destroy-room para encerrar sala. Preparar arquitetura para gravação (Recorder → Storage).

### Servidor MediaSoup (Node.js)

- [ ] Projeto Node com mediasoup; WebSocket para sinalização.
- [ ] Validar JWT na conexão do cliente; extrair callId, roomId, userId, role; permitir ou negar entrada (roomId nunca confiável do cliente); usar callId para validar call ativa, métricas e logs.
- [ ] API para Laravel/Gateway: criar room (retorna roomId), encerrar room.
- [ ] Protocolo cliente ↔ servidor: cliente envia token; getRouterRtpCapabilities, createWebRtcTransport, connect, produce, consume.
- [ ] Heartbeat: ping/pong; ao perder conexão emitir CallDisconnected.
- [ ] Métricas de qualidade (packet loss, bitrate, RTT, jitter) → Prometheus/Grafana ou log.
- [ ] Configurar SFU_WS_URL, SFU_URL e segredo JWT no .env.

### Frontend (Vue)

- [ ] useVideoCallSignaling: Echo; estados idle, requesting, ringing_out, ringing_in, connecting, connected, reconnecting, ended, error; recebe token + SFU_WS_URL (não roomId confiável).
- [ ] useMediaSoupRoom: entrada token + SFU_WS_URL; envia token ao SFU; join(), leave(), reconnect(); localStream, remoteStreams.
- [ ] Atualizar Doctor/Consultations.vue e Patient/VideoCall.vue para usar os dois composables e nova API; suportar reconexão.
- [ ] Atualizar ou recriar Dev/VideoTest.vue para testes com MediaSoup.
- [ ] Configurar SFU_WS_URL (e SFU_URL se necessário) no build.

---

## Referências no projeto

- **Remover e manter (P2P):** [REMOVER_E_MANTER_P2P.md](REMOVER_E_MANTER_P2P.md)
- **Camada de mídia:** [docs/layers/media/README.md](../layers/media/README.md)
- **Infraestrutura (SFU):** [docs/DistributedSystems/EstruturaInicial.md](../DistributedSystems/EstruturaInicial.md)
- **Variáveis reservadas:** [deploy/pc2/.env.example](../../deploy/pc2/.env.example) — SFU_URL, SFU_WS_URL
