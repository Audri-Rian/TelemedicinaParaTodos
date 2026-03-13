# Nova implementação do SFU (MediaSoup)

Documento da **nova implementação** de videochamada com MediaSoup como SFU: arquitetura, fluxo, backend, servidor de mídia e frontend. O que **remover** e **manter** em relação ao P2P está em documento separado (*Remover e manter — saída do P2P*).

**Decisões:**
- Sinalização de mídia: **WebSocket próprio** do servidor MediaSoup (não Reverb como proxy).
- Ciclo de vida da chamada: **Laravel + Reverb** (solicitar, aceitar, rejeitar, encerrar) e regras de negócio (appointment, políticas).

---

## Arquitetura alvo

```
                    ┌─────────────────────────────────────────────────────────┐
                    │                     Laravel (Backend)                    │
                    │  • Regras de negócio (Appointments, Policies)            │
                    │  • Request/Accept/End call (HTTP + Reverb)               │
                    │  • Criação de sala no MediaSoup + emissão de token       │
                    └───────────────────────┬─────────────────────────────────┘
                                            │
        ┌──────────────────────────────────┼──────────────────────────────────┐
        │                                  │                                  │
        ▼                                  ▼                                  ▼
┌─────────────────┐              ┌─────────────────┐              ┌─────────────────────────┐
│  Reverb (WS)    │              │  API HTTP      │              │  Servidor MediaSoup     │
│  video-call.*   │              │  /video-call/* │              │  (Node.js)              │
│  eventos de     │              │  request       │─────────────►│  • Rooms / Routers      │
│  vida da chamada│              │  accept        │ criar sala   │  • WS próprio (signaling)│
└────────┬────────┘              │  reject / end  │ token        │  • WebRTC (mídia)       │
         │                       └────────┬──────┘              └────────────┬────────────┘
         │                                │                                  │
         ▼                                ▼                                  ▼
┌─────────────────────────────────────────────────────────────────────────────────────────┐
│                              Frontend (Vue)                                              │
│  • useVideoCallSignaling: Echo + estado (idle, ringing, in_call) + roomId/token/SFU_WS_URL │
│  • useMediaSoupRoom: WS MediaSoup, join, produce/consume, localStream, remoteStreams    │
│  • Páginas: Doctor/Consultations, Patient/VideoCall — orquestram os dois composables     │
└─────────────────────────────────────────────────────────────────────────────────────────┘
```

---

## Fluxo da chamada (pós-implementação)

1. **Solicitar chamada:** Front chama `POST /api/video-call/request` (target ou appointment_id). Laravel valida appointment e políticas; emite evento Reverb para o destinatário (ex. `VideoCallRequested`), **sem peerId**.
2. **Destinatário recebe:** Echo recebe evento; exibe modal “X está chamando”. Aceita ou rejeita.
3. **Aceitar:** Front chama `POST /api/video-call/accept`. Laravel valida; chama servidor MediaSoup para criar sala; obtém `roomId` e `token`; emite `VideoCallAccepted` (ex. para o chamador e para quem aceitou) com `roomId`, `token`, `SFU_WS_URL`.
4. **Entrar na sala de mídia:** Cada lado usa `useMediaSoupRoom(roomId, token)` e conecta ao WebSocket do MediaSoup: getRouterRtpCapabilities, createWebRtcTransport, connect, produce (câmera/microfone), consume (streams dos outros). `<video>` recebem os streams.
5. **Encerrar:** Qualquer um chama `POST /api/video-call/end`. Laravel emite `VideoCallEnded` e pode avisar o MediaSoup para fechar a sala. Front chama `leave()` no `useMediaSoupRoom` e volta ao estado idle no `useVideoCallSignaling`.

---

## Backend (Laravel) — o que construir

### Nova API HTTP

- **Solicitar:** `POST /api/video-call/request` — body: target (user_id ou appointment_id). Valida appointment, janela, políticas; emite evento para o destinatário (sem peerId).
- **Aceitar:** `POST /api/video-call/accept` — body: identificador da solicitação. Valida; **chama MediaSoup** para criar sala; obtém roomId e token; persiste se necessário; emite evento com roomId, token, SFU_WS_URL.
- **Rejeitar:** `POST /api/video-call/reject` — emite evento.
- **Encerrar:** `POST /api/video-call/end` — atualiza consulta (started_at/ended_at); emite evento; opcionalmente notifica MediaSoup para fechar a sala.

### Novos eventos (Reverb)

- **VideoCallRequested** — para o destinatário: quem está chamando, appointment_id, etc. (sem peerId).
- **VideoCallAccepted** — para quem precisa entrar na sala: roomId, token, SFU_WS_URL.
- **VideoCallRejected**, **VideoCallEnded**.

### Canal e autorização

- Canal (ex. `video-call.{userId}` ou `video-call-lifecycle.{userId}`): apenas o usuário dono do canal. Manter ou renomear em `routes/channels.php`.

### Integração Laravel ↔ MediaSoup

- Serviço ou cliente HTTP (Laravel) que chama o servidor MediaSoup (Node): criar room (por appointment_id ou call_id), retornar roomId e token; opcionalmente encerrar room. O servidor MediaSoup expõe API REST ou WS interno para o Laravel.

### Persistência

- Modelos/tabelas para roomId, appointment_id, token, timestamps (sem peerId). Recriar ou migrar a partir do que existir; ver documento *Remover e manter* para o que não reaproveitar.

---

## Servidor MediaSoup (Node.js) — o que construir

- Projeto Node com **mediasoup**; **WebSocket** (ex. Socket.IO ou `ws`) para sinalização com o cliente.
- **API para Laravel:** criar room (retorna roomId, token); encerrar room.
- **Protocolo cliente ↔ servidor:** getRouterRtpCapabilities, createWebRtcTransport, connect, produce, consume (conforme documentação mediasoup).
- Variáveis de ambiente: `SFU_WS_URL`, `SFU_URL` (já reservadas no projeto).

---

## Frontend (Vue) — o que construir

### Composables

- **useVideoCallSignaling**
  - Apenas Reverb (Echo); canal privado do usuário para eventos de videochamada.
  - Estado: idle, ringing_out, ringing_in, connecting, in_call, ended, error.
  - Dados: quem está chamando, appointment; quando aceito: roomId, token, SFU_WS_URL.
  - Métodos: requestCall(user), acceptCall(), rejectCall(), endCall() (HTTP + eventos Reverb).
  - Não conhece MediaSoup nem WebRTC.

- **useMediaSoupRoom**
  - Entrada: roomId, token, SFU_WS_URL (e opcionalmente configurações de mídia).
  - WebSocket para o MediaSoup; protocolo de sinalização (getRouterRtpCapabilities, createWebRtcTransport, connect, produce, consume) com **mediasoup-client** (ou equivalente).
  - Expõe: localStream, remoteStreams (por participante), estado (connecting, connected, error), join(), leave(), opcionalmente mute/unmute.
  - Não conhece Reverb nem papéis médico/paciente.

### Páginas

- **Doctor/Consultations.vue**, **Patient/VideoCall.vue:** usam `useVideoCallSignaling` para solicitar chamada, modal de chamada recebida, aceitar/rejeitar, encerrar. Quando estado = in_call e houver roomId/token, montam componente que usa `useMediaSoupRoom(roomId, token)` e liga streams aos `<video>`.
- **Dev/VideoTest.vue:** recriar como tela de teste do fluxo MediaSoup (opcional).

### Dependências e env

- Adicionar **mediasoup-client** (ou equivalente) em `package.json`.
- Expor **SFU_WS_URL** (e SFU_URL se necessário) no build (Vite/env).

---

## Checklist de implementação

### Backend (Laravel)

- [ ] Definir novos endpoints: request, accept, reject, end (contratos conforme acima).
- [ ] Implementar novos eventos Reverb: VideoCallRequested, VideoCallAccepted, VideoCallRejected, VideoCallEnded.
- [ ] Implementar integração Laravel ↔ MediaSoup (criar sala, roomId/token; encerrar sala).
- [ ] Ajustar ou recriar modelos/tabelas de videochamada (roomId, appointment_id, token).
- [ ] Manter políticas e regras de appointment na nova API.
- [ ] Manter canal Reverb para videochamada com autorização por usuário.

### Servidor MediaSoup (Node.js)

- [ ] Projeto Node com mediasoup; WebSocket para sinalização.
- [ ] API para Laravel: criar room (roomId, token), encerrar room.
- [ ] Protocolo de mensagens cliente ↔ servidor (getRouterRtpCapabilities, createWebRtcTransport, connect, produce, consume).
- [ ] Configurar SFU_WS_URL e SFU_URL no .env.

### Frontend (Vue)

- [ ] Criar useVideoCallSignaling (Echo, estado, request/accept/reject/end, roomId/token/SFU_WS_URL).
- [ ] Criar useMediaSoupRoom (WS MediaSoup, mediasoup-client, join/leave, localStream, remoteStreams).
- [ ] Atualizar Doctor/Consultations.vue e Patient/VideoCall.vue para usar os dois composables e nova API.
- [ ] Atualizar ou recriar Dev/VideoTest.vue para testes com MediaSoup.
- [ ] Configurar SFU_WS_URL (e SFU_URL se necessário) no build.

---

## Referências no projeto

- **Remover e manter (P2P):** [REMOVER_E_MANTER_P2P.md](REMOVER_E_MANTER_P2P.md)
- **Camada de mídia:** [docs/layers/media/README.md](../layers/media/README.md)
- **Infraestrutura (SFU):** [docs/DistributedSystems/EstruturaInicial.md](../DistributedSystems/EstruturaInicial.md)
- **Variáveis reservadas:** [deploy/pc2/.env.example](../../deploy/pc2/.env.example) — SFU_URL, SFU_WS_URL
