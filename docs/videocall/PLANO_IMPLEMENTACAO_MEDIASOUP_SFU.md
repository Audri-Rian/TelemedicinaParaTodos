# Plano de Implementação do Servidor MediaSoup SFU (revisado)

Documento de referência: [IMPLEMENTACAO_SFU_MEDIASOUP.md](IMPLEMENTACAO_SFU_MEDIASOUP.md) e [Arquitetura_SFU_MediaSoup.md](../modules/videocall/Arquitetura_SFU_MediaSoup.md).

Este plano incorpora **ajustes importantes** de arquitetura MediaSoup (1 router por room, 2 transports por peer, ICE, bitrate, cleanup, rate limiting) e **melhorias para produção** (balanceamento de workers, backpressure, simulcast).

---

## Visão geral

- **Objetivo**: servidor MediaSoup SFU em Node.js, com WebSocket de sinalização e API HTTP para Laravel/Media Gateway.
- **Integrações**: Laravel chama create-room/destroy-room via HTTP; frontend conecta ao SFU via WebSocket com token JWT.

---

## 1. Estrutura inicial do projeto MediaSoup

- Projeto Node.js (fora do monólito Laravel):
  - `server.js`, `config/`, `lib/mediasoup.js`, `lib/rooms.js`, `lib/peers.js`, `lib/transports.js`, `lib/producers.js`, `lib/consumers.js`, `signaling/wsServer.js`, `signaling/handlers.js`, `api/httpRoutes.js`.
- Dependências: `mediasoup`, `ws` (ou `socket.io`), `express`, `jsonwebtoken`, logger.
- Config: `httpPort`, `wsPort`, `numWorkers`, `jwtSecret`, `rtcMinPort`/`rtcMaxPort`, `listenIps`, codecs, **ICE servers (STUN/TURN)** e **bitrate máximo**.

---

## 2. Workers e Routers — 1 router por room

**Ajuste fundamental (recomendação oficial MediaSoup):**

- **Não** criar 1 router por worker global.
- **Arquitetura correta**: 1 **router por room**. O router representa a sala de mídia; codecs e RTP capabilities ficam no router.

```text
worker
   ├── router (room1)
   ├── router (room2)
   ├── router (room3)
```

**Implementação:**

- **mediasoup.js**:
  - Criar N **workers** (um por core ou configurável).
  - **Não** criar routers na inicialização.
  - Exportar: `createRouter()` — escolhe um worker (ver seção 9.1) e chama `worker.createRouter()` **ao criar a room**.
- **rooms.js**:
  - `createRoom(roomId)`:
    1. Chamar `createRouter()` (obtém um router novo por room).
    2. Registrar a room com esse router e estrutura de peers.
  - `getRoom(roomId)`, `closeRoom(roomId)` (fechar router e liberar recursos).

---

## 3. Transports — 2 transports por peer

**Ajuste:** usar **2 transports por peer** (padrão da comunidade, melhor controle e debug).

- **sendTransport**: envio de mídia (producers).
- **recvTransport**: recepção de mídia (consumers).

**Implementação em transports.js:**

- `createWebRtcTransport(router, direction)` com `direction === 'send'` ou `direction === 'recv'`.
- Cada peer terá:
  - `sendTransport` (um)
  - `recvTransport` (um).
- No handler de sinalização: mensagens distintas para criar/connectar send e recv (ex.: `createWebRtcTransport { direction: 'send' }` e `createWebRtcTransport { direction: 'recv' }`).

---

## 4. ICE servers (STUN/TURN) — obrigatório

Sem ICE muitos clientes atrás de NAT falham.

**STUN**

- Descobrir IP público do cliente.
- Exemplo: `stun:stun.l.google.com:19302`.

**TURN**

- Necessário em NAT restrito/simétrico.
- Servidor recomendado: **Coturn** (self-hosted ou serviço).

**Implementação:**

- Configurar em `config/` (ou `.env`):
  - `iceServers`: array com `{ urls: 'stun:...' }` e, se houver TURN, `{ urls: 'turn:...', username, credential }`.
- Ao criar `WebRtcTransport`, o MediaSoup usa `webRtcServer` ou opções de ICE; garantir que o servidor SFU anuncie o mesmo conjunto de ICE servers (ou que o cliente receba essa lista na sinalização para uso no `RTCPeerConnection` do lado cliente, conforme o fluxo mediasoup-client).
- Documentar no plano: variáveis `STUN_URL`, `TURN_URL`, `TURN_USERNAME`, `TURN_CREDENTIAL` e uso no transport.

---

## 5. Bitrate máximo (telemedicina)

**Controle importante para qualidade previsível.**

- Recomendação telemedicina:
  - Resolução: **720p**
  - Frame rate: **30 fps**
  - Bitrate máximo: **~1.5 Mbps** (vídeo).

MediaSoup suporta:

- `maxIncomingBitrate` no router ou no producer/consumer.
- Configurar no router ao criar a room (ou por producer) para limitar o bitrate de entrada e evitar sobrecarga.

**Implementação:**

- Em `createRouter()` ou na configuração da room: definir `maxIncomingBitrate` (ex.: 1_500_000 bps para vídeo).
- Documentar valor em config (ex.: `videoMaxBitrate`) para ajuste por ambiente.

---

## 6. Cleanup agressivo ao sair do peer

Quando um peer sai (leave ou disconnect), é obrigatório fechar recursos na **ordem correta** para evitar memory leak.

**Ordem recomendada:**

1. `consumer.close()` (todos os consumers do peer)
2. `producer.close()` (todos os producers do peer)
3. `transport.close()` (sendTransport e recvTransport)
4. Remover o peer da room (`peer.remove()` / estrutura de dados)

**Implementação em peers.js / handlers.js:**

- Função `removePeer(peer)` (ou equivalente) que:
  1. Itera e fecha todos os consumers do peer.
  2. Itera e fecha todos os producers do peer.
  3. Fecha `sendTransport` e `recvTransport`.
  4. Remove o peer da room e notifica outros peers (participant_left).
- Chamar `removePeer` em: evento `leave`, desconexão WebSocket e antes de fechar a room.

---

## 7. Rate limiting na API HTTP

Proteger endpoints contra abuso (ex.: DoS).

- **POST /rooms** (create-room):
  - Aplicar rate limit por IP (ex.: **10 requests/min por IP**).
- **DELETE /rooms/:roomId**:
  - Opcionalmente limitar por IP ou por token de API.

**Implementação:**

- Middleware de rate limiting (ex.: `express-rate-limit`) nas rotas da API HTTP.
- Configurável (ex.: `API_RATE_LIMIT_WINDOW_MS`, `API_RATE_LIMIT_MAX_REQUESTS`).

---

## 8. WebSocket de sinalização e API HTTP (resumo)

- **wsServer.js**: WebSocket na porta configurada; primeira mensagem/handshake com **token JWT**; validar e extrair `callId`, `roomId`, `userId`, `role`.
- **handlers.js**: protocolo com `join`, `getRouterRtpCapabilities`, `createWebRtcTransport` (send e recv separados), `connectWebRtcTransport`, `produce`, `consume`, `leave`, `ping`/`pong`.
- **httpRoutes.js**:
  - `POST /rooms` → create room (retorna `room_id`, `sfu_node`); com rate limit.
  - `DELETE /rooms/:roomId` → destroy room (chama `closeRoom` e cleanup).
  - Autenticação da API (secret/token interno) para apenas Laravel/Media Gateway.

---

## 9. Melhorias avançadas (produção escalável)

### 9.1. Balanceamento de workers

- **Não** usar round-robin simples para atribuir routers a workers.
- **Estratégias**:
  - Escolher o **worker com menor número de routers** ativos.
  - Ou (se disponível) worker com **menor uso de CPU**.
- Objetivo: distribuir carga entre workers de forma mais equilibrada.

### 9.2. Backpressure detection

- MediaSoup permite detectar quando o cliente está sobrecarregado (ex.: eventos de congestionamento).
- Usar para **reduzir bitrate automaticamente** (ex.: diminuir `maxIncomingBitrate` ou sinalizar ao cliente para reduzir resolução/bitrate).

### 9.3. Adaptive simulcast

- **Simulcast**: enviar múltiplas qualidades do mesmo stream (low, medium, high).
- Benefícios:
  - Internet lenta → consumir camada baixa.
  - Internet boa → consumir camada alta.
- Implementação (conforme documentação MediaSoup):
  - Configurar encodings no producer (simulcast) e, no consumer, escolher a camada conforme condições de rede (ou deixar o SFU escolher).
- Muito importante para experiência em telemedicina com redes variáveis.

---

## 10. Checklist de implementação (revisado)

- [ ] Estrutura Node.js e config (portas, workers, JWT, ICE servers, bitrate).
- [ ] Workers sem routers globais; **createRouter() ao criar room** (1 router por room).
- [ ] Dois transports por peer: **sendTransport** e **recvTransport**.
- [ ] ICE: STUN (ex.: stun.l.google.com) e TURN (Coturn) configurados e usados.
- [ ] Bitrate máximo para telemedicina (ex.: 720p, 30fps, 1.5 Mbps; maxIncomingBitrate).
- [ ] Cleanup na ordem: consumer.close() → producer.close() → transport.close() → peer.remove().
- [ ] Rate limiting em POST /rooms (ex.: 10 req/min por IP).
- [ ] WebSocket com JWT; handlers para join, getRouterRtpCapabilities, createWebRtcTransport (send/recv), produce, consume, leave, ping/pong.
- [ ] API HTTP create-room / destroy-room com autenticação.
- [ ] (Avançado) Balanceamento de workers por menor número de routers (ou CPU).
- [ ] (Avançado) Backpressure e redução automática de bitrate.
- [ ] (Avançado) Simulcast adaptativo (múltiplas camadas por producer).
- [ ] Integração Laravel: implementação real de MediaGatewayInterface e config (sfu_ws_url, jwt_secret).
- [ ] Logs estruturados e métricas (opcional Prometheus).
- [ ] Testes ponta a ponta (request → accept → VideoCallAccepted → WS + token → mídia entre dois clientes → end).

---

*Plano revisado com ajustes de arquitetura MediaSoup e melhorias para produção. Última atualização: março 2026.*
