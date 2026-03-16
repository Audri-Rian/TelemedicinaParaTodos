## Arquitetura da Videochamada com MediaSoup SFU

Documento de arquitetura do módulo de videochamada utilizando **MediaSoup** como **SFU (Selective Forwarding Unit)** integrado ao backend **Laravel** deste projeto.

Baseado em:
- `docs/videocall/IMPLEMENTACAO_SFU_MEDIASOUP.md`
- `docs/modules/videocall/BackendSFU.md`
- Implementação atual no backend (models, services, events, migrations, configs).

O objetivo é servir como **documentação oficial de arquitetura** da solução de videochamada: visão geral, fluxos, responsabilidades de cada camada, estrutura de arquivos e diretrizes de escalabilidade.

---

## 1. Visão Geral da Arquitetura

### Componentes principais

- **Frontend (Vue / SPA)**
  - Interface de agendamento e consulta.
  - Fluxo de aceitação/rejeição de chamadas.
  - Conexão WebSocket com o servidor MediaSoup para sinalização de mídia.
  - Integração com o backend Laravel via API REST e Reverb (Echo).

- **Backend Laravel**
  - **Ciclo de vida da chamada**: solicitar, aceitar, rejeitar, encerrar.
  - **Regras de negócio**: agendamento, papéis (médico/paciente), políticas de acesso.
  - **Modelos de domínio**: `Call` (negócio) e `Room` (mídia).
  - **Orquestração**: `CallManagerService` centraliza o fluxo e integra com o **Media Gateway** / SFU via `MediaGatewayInterface`.
  - **Eventos WebSocket (Reverb)**: broadcasting de eventos de ciclo de vida (`VideoCallRequested`, `VideoCallAccepted`, `VideoCallRejected`, `VideoCallEnded`).
  - **Emissão de token JWT de sala** com `callId`, `roomId`, `userId`, `role`, `exp`.

- **Media Gateway (conceito / camada de integração)**
  - Pode ser um serviço dedicado ou a própria API HTTP do servidor MediaSoup.
  - Responsável por **criar/encerrar salas** no SFU e, em algumas variantes, **gerar ou validar tokens**.
  - No código atual existe um **stub** (`MediaGatewayStub`) que simula essa integração.

- **Servidor MediaSoup SFU**
  - Aplicação Node.js com a biblioteca `mediasoup`.
  - Protocolo **WebSocket** para sinalização com os clientes (frontend).
  - Protocolo **WebRTC** para mídia (áudio/vídeo).
  - Mantém **routers, transports, producers e consumers** de mídia.
  - Valida o **token JWT** recebido do cliente para autorizar entrada na sala.

### Papel de cada parte do sistema

- **Backend Laravel**
  - Não faz processamento de mídia.
  - Garante **segurança, autorização e integridade** do fluxo:
    - Quem pode iniciar/aceitar uma chamada.
    - Qual consulta (appointment) está associada.
    - Persistência de estado (`calls`, `rooms`).
  - Emite o **token de acesso** à sala (JWT) com informações verificadas e de curta duração.
  - Expõe **endpoints HTTP** para o frontend (request/accept/reject/end).
  - Emite **eventos Reverb** para notificar o frontend sobre atualizações de estado.

- **Servidor MediaSoup (SFU)**
  - Responsável **apenas** pela camada de mídia:
    - Criação e gerenciamento de rooms/routers.
    - Criação de WebRTC transports.
    - Recebimento de streams dos participantes (producers).
    - Distribuição seletiva dos streams para os outros participantes (consumers).
  - Não conhece regras de negócio de consulta/appointment.
  - Confia no **token JWT** emitido pelo Laravel/Media Gateway para identificar `callId`, `roomId`, `userId` e `role`.

- **WebSockets / Sinalização**
  - **Reverb (Laravel)**: sinalização de **ciclo de vida** da chamada.
    - Canal privado `video-call.{userId}`.
    - Eventos: Requested, Accepted, Rejected, Ended (e Missed futuramente).
  - **WebSocket do MediaSoup**: sinalização de **mídia**:
    - Mensagens para negociar WebRTC: `getRouterRtpCapabilities`, `createWebRtcTransport`, `connect`, `produce`, `consume`, etc.
    - Conexão autenticada via **token JWT** enviado pelo frontend.

### Diagrama de alto nível

```text
Frontend (Vue)
    │ HTTP (REST)
    ▼
Laravel API  ─── Reverb (WebSocket) ──► Frontend (eventos de ciclo de vida)
    │
    │ HTTP (Media Gateway / SFU API)
    ▼
Media Gateway / MediaSoup API
    │
    │ WebSocket (Sinalização de mídia)
    ▼
MediaSoup SFU  ⇄  Navegadores (WebRTC - áudio/vídeo)
```

---

## 2. Fluxo da Comunicação (Passo a Passo)

### 2.1. Usuário entra na sala (do ponto de vista de negócio)

1. Usuário (médico ou paciente) acessa a tela de consulta.
2. Frontend identifica a consulta/appointment corrente.
3. Ao iniciar uma chamada, o frontend faz:
   - `POST /api/video-call/request` (Laravel), informando o `appointment_id` ou o alvo.

### 2.2. Conexão com o servidor de sinalização de ciclo de vida (Reverb)

- Antes da chamada, o frontend já está conectado ao Reverb/Echo:
  - Canal privado: `video-call.{userId}`.
  - Escuta eventos:
    - `VideoCallRequested`
    - `VideoCallAccepted`
    - `VideoCallRejected`
    - `VideoCallEnded`

### 2.3. Criação da Call e disparo de `VideoCallRequested`

No backend:
- Controller chama `CallManagerService::createCall($appointment, $caller)`.
- O serviço:
  - Valida se o usuário é médico ou paciente da consulta.
  - Cria um registro em `calls` com status `requested`.
  - Determina o **destinatário** (`calleeUserId`) via `getCalleeUserId`.
  - Emite evento `VideoCallRequested` no canal `video-call.{calleeUserId}`.
  - Registra log estruturado `CALL_CREATED`.

Para o usuário destinatário:
- Frontend recebe `VideoCallRequested` no canal `video-call.{id}`.
- Exibe UI de **“X está chamando”** com opções de aceitar/rejeitar.

### 2.4. Aceitar a chamada e criação da Room no MediaSoup

Ao aceitar:
1. Frontend chama `POST /api/video-call/accept` com o identificador da call.
2. O Controller aciona `CallManagerService::acceptCall($call, $acceptedBy)`.
3. O serviço:
   - Valida se:
     - A call está em estado `requested`/`ringing`.
     - Quem está aceitando é de fato o destinatário.
   - Cria a sala de mídia via `createRoom($call)`:
     - Usa `MediaGatewayInterface::createRoom($callId)`.
     - A implementação atual (`MediaGatewayStub`) retorna um `room_id` fictício e um `sfu_node`.
     - Persiste `Room` (`rooms`):
       - `call_id`
       - `room_id`
       - `sfu_node`
   - Atualiza a `Call` para status `accepted`.
   - Se a consulta ainda não estiver `in_progress`, inicia via `AppointmentService::start`.
   - Gera o **token JWT** via `generateRoomToken($call, $room, $user)`:
     - Header:
       - `alg`: HS256
       - `typ`: JWT
     - Payload:
       - `callId`, `roomId`, `userId`, `role`, `iat`, `exp`.
     - Assinatura: `HS256` com segredo `SFU_JWT_SECRET` (ou `services.media_gateway.jwt_secret`).
   - Obtém `sfu_ws_url` de `config('services.media_gateway.sfu_ws_url', env('SFU_WS_URL'))`.
   - Emite evento `VideoCallAccepted` para médico e paciente com:
     - `call_id`
     - `token` (JWT)
     - `sfu_ws_url`
   - Registra logs `CALL_ACCEPTED` e `ROOM_CREATED`.

### 2.5. Conexão do Frontend ao Servidor MediaSoup (sinalização de mídia)

Após receber `VideoCallAccepted`:

- O frontend:
  - Passa para o composable de mídia (conceito) algo como `useMediaSoupRoom(token, sfuWsUrl)`.
  - Abre uma conexão WebSocket com o servidor MediaSoup usando `sfuWsUrl`.
  - Na conexão inicial, **envia apenas o token**.

- O servidor MediaSoup:
  - Valida o JWT com o segredo configurado.
  - Extrai do payload:
    - `callId`
    - `roomId`
    - `userId`
    - `role`
  - Verifica expiração e consistência (futuro: pode consultar o backend se a call ainda é ativa).
  - Localiza/cria a **room interna** correspondente (`roomId`) no SFU.

### 2.6. Criação dos Transports

Uma vez autenticado:

- Cliente e SFU negociam:
  - `getRouterRtpCapabilities` → cliente configura seu `RTCRtpSender`/`mediasoup-client`.
  - `createWebRtcTransport` → SFU cria um objeto `WebRtcTransport` para o peer.
  - `connectWebRtcTransport` → frontend envia `dtlsParameters` para conectar o transport.

Esse fluxo é puramente na camada de **sinalização de mídia**, entre frontend e MediaSoup via WebSocket, sem envolver Laravel diretamente.

### 2.7. Produção de mídia (Producer)

- Após o transport de envio estar conectado:
  - Frontend captura áudio/vídeo (getUserMedia).
  - Cria um `producer` via mensagem de sinalização para o SFU.
  - O SFU passa a receber o fluxo RTP desse participante.

### 2.8. Consumo de mídia (Consumer)

- Quando um novo producer é criado:
  - O SFU notifica os outros participantes presentes na mesma room.
  - Esses participantes negociam **consumers**:
    - Criam `WebRtcTransport` de recepção (se ainda não existir).
    - Enviam `rtpCapabilities` para o SFU.
    - SFU cria `consumer` e retorna parâmetros.
  - Frontend anexa o `consumer` a elementos `<video>` ou `<audio>`.

Resultado:
- Cada participante envia **um** stream (ou poucos) e recebe streams dos demais.
- O **roteamento seletivo** (SFU) evita que todos precisem enviar para todos diretamente (como no P2P).

### 2.9. Encerramento da chamada

- Qualquer participante pode encerrar a chamada:
  - Frontend chama `POST /api/video-call/end`.
  - Controller aciona `CallManagerService::endCall($call, $endedBy)`.
  - O serviço:
    - Verifica se a Call está ativa (`requested`, `ringing`, `accepted`).
    - Atualiza status para `ended` e `ended_at`.
    - Se a consulta estava `in_progress`, finaliza via `AppointmentService::end`.
    - Recupera a `Room` vinculada.
    - Chama `destroyRoom($room)`:
      - Delegado a `MediaGatewayInterface::destroyRoom($roomId)` (no stub, apenas log).
    - Emite `VideoCallEnded` para médico e paciente.
    - Loga `CALL_ENDED` e `ROOM_LEFT`.

- Do lado do SFU:
  - O servidor pode receber a notificação de destruição de sala (via API do Media Gateway).
  - Fecha todos os transports, producers e consumers associados.

### 2.10. Diagrama de fluxo: Frontend → Laravel → MediaSoup

```text
(1) Solicitação de chamada

Frontend ── POST /api/video-call/request ──► Laravel
Frontend ◄── VideoCallRequested (Reverb) ── Laravel

(2) Aceitar chamada

Frontend ── POST /api/video-call/accept ──► Laravel
Laravel ── createRoom(callId) ──► Media Gateway / SFU API
Laravel ◄─ room_id, sfu_node ── Media Gateway / SFU API
Laravel ── gera token JWT (callId, roomId, userId, role, exp)
Laravel ── VideoCallAccepted (token, sfu_ws_url) ──► Frontend

(3) Mídia (WebRTC via MediaSoup)

Frontend ── WS connect (envia token) ──► MediaSoup
MediaSoup ── valida JWT, associa a roomId/callId
MediaSoup ⇄ Frontend (sinalização WebRTC, producers/consumers)

(4) Fim da chamada

Frontend ── POST /api/video-call/end ──► Laravel
Laravel ── destroyRoom(roomId) ──► Media Gateway / SFU API
Laravel ── VideoCallEnded ──► Frontend
MediaSoup ── encerra room/transports ── (via API de destruição)
```

---

## 3. Relação entre Backend Laravel e Servidor MediaSoup

### 3.1. Comunicação Laravel → MediaSoup

No desenho arquitetural previsto:

- Laravel **não** fala diretamente WebRTC.
- Laravel se comunica com o servidor de mídia por meio de:
  - **Media Gateway HTTP** (serviço intermediário) **ou**
  - **API HTTP direta** exposta pelo servidor MediaSoup.

No código atual:

- Existe a interface `MediaGatewayInterface` e a implementação `MediaGatewayStub`.
- O stub gera um `room_id` fictício localmente; em produção, uma implementação real deve:
  - Chamar (via HTTP ou gRPC) o serviço de mídia.
  - Enviar o `callId` e outros metadados.
  - Receber o `room_id` e, opcionalmente, metadados como `sfu_node`.

### 3.2. Papel do Laravel na arquitetura

- **Autenticação e autorização**
  - Garante que somente médico e paciente da consulta possam iniciar/aceitar/encerrar chamadas.
  - Centraliza as políticas em `VideoCallPolicy` e na lógica de `CallManagerService`.

- **Gerenciamento de salas (indireto)**
  - Não cria salas diretamente no SFU.
  - Chama o Media Gateway / SFU API via `MediaGatewayInterface`.
  - Persiste `Room` na tabela `rooms` com:
    - `call_id`
    - `room_id` (ID da sala no SFU)
    - `sfu_node` (para cenários com múltiplos SFUs).

- **Permissões**
  - Verifica se o usuário é participante da `Call` / `Appointment`.
  - Impede acessos não autorizados (por ex.: paciente tentar entrar em consulta de outro paciente).

- **Orquestração da chamada**
  - **CallManagerService** faz:
    - `createCall` → cria Call + emite `VideoCallRequested`.
    - `acceptCall` → cria Room (via Gateway), gera token, emite `VideoCallAccepted`.
    - `rejectCall` → atualiza Call + emite `VideoCallRejected`.
    - `endCall` → atualiza Call/Appointment + destrói Room + emite `VideoCallEnded`.

### 3.3. Comunicação Laravel API ↔ Servidor de Sinalização ↔ MediaSoup

- **Laravel API**
  - Endpoints (conceito/planejamento):
    - `POST /api/video-call/request`
    - `POST /api/video-call/accept`
    - `POST /api/video-call/reject`
    - `POST /api/video-call/end`
  - Validações:
    - Autenticação do usuário.
    - Participação no appointment.
    - Estado atual da Call.

- **Servidor de sinalização de ciclo de vida (Reverb)**
  - Repassa **eventos de negócio** para o frontend.
  - Não participa da negociação de WebRTC.

- **Servidor de sinalização de mídia (MediaSoup WS)**
  - Recebe o **token JWT** no handshake ou na primeira mensagem.
  - Valida e estabelece a sessão WebRTC.
  - Toda a troca de mensagens de mídia (RTP, codecs, candidates) é feita diretamente entre:
    - Frontend ⇄ SFU.

### 3.4. Estrutura típica de mensagens (conceito)

- **Evento Reverb `VideoCallRequested`**
  - Canal: `video-call.{calleeUserId}`
  - Payload:
    - `call_id`
    - `appointment_id`
    - `caller: { id, name }`

- **Evento Reverb `VideoCallAccepted`**
  - Canal: `video-call.{doctorUserId}`, `video-call.{patientUserId}`
  - Payload:
    - `call_id`
    - `token` (JWT: callId, roomId, userId, role, exp)
    - `sfu_ws_url`

- **Evento Reverb `VideoCallRejected`**
  - Canal: `video-call.{callerUserId}`
  - Payload:
    - `call_id`

- **Evento Reverb `VideoCallEnded`**
  - Canal: médico e paciente.
  - Payload:
    - `call_id`

- **Mensagens WS (MediaSoup)**
  - Exemplos conceituais:
    - `{ "action": "join", "token": "..." }`
    - `{ "action": "getRouterRtpCapabilities" }`
    - `{ "action": "createWebRtcTransport", "direction": "send" }`
    - `{ "action": "connectWebRtcTransport", "transportId": "...", "dtlsParameters": {...} }`
    - `{ "action": "produce", "transportId": "...", "kind": "video", "rtpParameters": {...} }`
    - `{ "action": "consume", "producerId": "...", "rtpCapabilities": {...} }`

---

## 4. Estrutura de Arquivos do Backend Laravel

### 4.1. Visão geral

```text
app/
 ├── Contracts/
 │   └── MediaGatewayInterface.php
 ├── Models/
 │   ├── Call.php
 │   └── Room.php
 ├── Services/
 │   ├── CallManagerService.php
 │   └── MediaGatewayStub.php
 ├── Events/
 │   ├── VideoCallRequested.php
 │   ├── VideoCallAccepted.php
 │   ├── VideoCallRejected.php
 │   └── VideoCallEnded.php
 ├── Policies/
 │   └── VideoCallPolicy.php
 └── Providers/
     └── AppServiceProvider.php

config/
 ├── telemedicine.php
 └── services.php

database/
 └── migrations/
     ├── 2026_03_14_000001_create_calls_table.php
     └── 2026_03_14_000002_create_rooms_table.php
```

### 4.2. `CallManagerService.php`

**Responsável por:**
- Centralizar o **estado e orquestração** da chamada.
- Integrar com o **Media Gateway / SFU** via `MediaGatewayInterface`.
- Persistir `Call` e `Room`.
- Emitir os eventos de ciclo de vida (Requested, Accepted, Rejected, Ended).
- Gerar o **token JWT** de acesso à sala.

**Fluxo principal:**

- **Controller → CallManagerService → MediaGateway → Eventos Reverb / Persistência**

**Principais métodos:**
- `createCall(Appointments $appointment, User $caller): Call`
- `acceptCall(Call $call, User $acceptedBy): array`
- `rejectCall(Call $call, User $rejectedBy): void`
- `endCall(Call $call, User $endedBy): void`
- `createRoom(Call $call): Room`
- `destroyRoom(Room $room): void`

**Conexão com o MediaSoup:**
- Não fala diretamente com o SFU.
- Usa `MediaGatewayInterface::createRoom` / `destroyRoom` para criar/destruir salas no SFU ou em um gateway intermediário.
- Gera o token JWT que será validado pelo SFU na conexão WebSocket.

### 4.3. `MediaGatewayInterface.php` e `MediaGatewayStub.php`

**`MediaGatewayInterface`**
- Contrato de integração da camada de negócio com o sistema de mídia.
- Métodos:
  - `createRoom(string $callId): array`
  - `destroyRoom(string $roomId): void`

**`MediaGatewayStub`**
- Implementação **stub** usada atualmente.
- `createRoom`:
  - Gera `room_id` fictício (`room_<random>`).
  - Define `sfu_node` (por padrão `local` ou configurado em `services.media_gateway.sfu_node`).
- `destroyRoom`:
  - Não realiza chamada externa (apenas comentário e possibilidade de log).

**Fluxo:**
- `CallManagerService` chama o stub como se fosse o gateway real.
- Em produção, basta substituir o binding para uma implementação real (HTTP para o SFU/Gateway) em `AppServiceProvider`.

### 4.4. Modelos `Call.php` e `Room.php`

**`Call`**
- Representa a **videochamada** associada a uma consulta.
- Campos principais:
  - `appointment_id`
  - `doctor_id`
  - `patient_id`
  - `status` (`requested`, `ringing`, `accepted`, `rejected`, `ended`, `missed`)
  - `requested_at`, `accepted_at`, `ended_at`
- Relações:
  - `appointment()`: `BelongsTo Appointments`.
  - `doctor()`: `BelongsTo Doctor`.
  - `patient()`: `BelongsTo Patient`.
  - `room()`: `HasOne Room`.
- Método utilitário:
  - `isActive(): bool` → verifica se está em estados ativos.

**`Room`**
- Representa a **sala de mídia** no SFU.
- Campos:
  - `call_id` (FK)
  - `room_id` (ID no SFU)
  - `sfu_node` (identifica qual nó SFU atende a chamada, em arquiteturas distribuídas).
- Relação:
  - `call(): BelongsTo Call`.

### 4.5. Eventos de videochamada

**`VideoCallRequested`**
- Disparado quando uma call é criada (`createCall`).
- Canal: `video-call.{calleeUserId}`.
- Payload:
  - `call_id`
  - `appointment_id`
  - `caller: { id, name }`

**`VideoCallAccepted`**
- Disparado quando a call é aceita (`acceptCall`).
- Canais:
  - `video-call.{doctorUserId}`
  - `video-call.{patientUserId}`
- Payload:
  - `call_id`
  - `token` (JWT)
  - `sfu_ws_url`
- Observação:
  - O `roomId` **não** é enviado separadamente; fica dentro do token.

**`VideoCallRejected`**
- Disparado quando a call é rejeitada (`rejectCall`).
- Canal: `video-call.{callerUserId}`.
- Payload:
  - `call_id`

**`VideoCallEnded`**
- Disparado quando a call é encerrada (`endCall`).
- Canais:
  - Médico e paciente.
- Payload:
  - `call_id`

### 4.6. Políticas, providers e configs

- **`VideoCallPolicy.php`**
  - Define regras de autorização para ações de videochamada.
  - Pode validar, por exemplo:
    - Se o usuário é médico/paciente do appointment da call.
    - Se a consulta ainda está dentro da janela de horário.

- **`AppServiceProvider.php`**
  - Faz o binding:
    - `MediaGatewayInterface::class` → `MediaGatewayStub::class` (por enquanto).
  - Em ambiente real, esse binding pode apontar para uma implementação HTTP de gateway.

- **`config/telemedicine.php`**
  - Configurações de videochamada, por exemplo:
    - Tempo de inatividade da room.
    - Duração máxima da call.
    - TTL do token de sala (`token_ttl_minutes`).

- **`config/services.php`**
  - Seção `media_gateway`:
    - `sfu_ws_url`: URL base do WebSocket do SFU.
    - `sfu_node`: identificação do nó SFU.
    - `jwt_secret`: segredo para assinatura dos tokens JWT.

### 4.7. Migrations

- `create_calls_table`:
  - Cria a tabela `calls`.
  - Índices por `appointment_id`, `doctor_id`, `patient_id`, `status`.

- `create_rooms_table`:
  - Cria a tabela `rooms`.
  - FKs e índices para `call_id` e `room_id`.

---

## 5. Estrutura do Servidor MediaSoup

> Observação: O servidor MediaSoup ainda é descrito como arquitetura alvo; a implementação Node.js não faz parte deste repositório Laravel, mas a documentação já prepara a estrutura recomendada.

### 5.1. Organização sugerida do projeto MediaSoup

```text
mediasoup-server/
 ├── server.js                # bootstrap principal
 ├── config/                  # configs (portas, workers, JWT secret, etc.)
 ├── lib/
 │   ├── mediasoup.js         # criação do Worker, Router, etc.
 │   ├── rooms.js             # gerenciamento de rooms
 │   ├── peers.js             # peers conectados (por userId / callId / roomId)
 │   ├── transports.js        # create/connect WebRtcTransports
 │   ├── producers.js         # criar producers
 │   └── consumers.js         # criar consumers
 ├── signaling/
 │   ├── wsServer.js          # servidor WebSocket (ws/Socket.IO)
 │   └── handlers.js          # handlers de mensagens (join, produce, consume, etc.)
 ├── api/
 │   └── httpRoutes.js        # rotas HTTP para Laravel / Media Gateway (create-room, destroy-room)
 └── metrics/
     └── prometheus.js        # coleta e exposição de métricas (opcional)
```

### 5.2. Responsabilidade de cada módulo

- **`server.js`**
  - Inicializa o processo Node.
  - Carrega configurações.
  - Cria Workers do mediasoup.
  - Sobe servidores HTTP e WebSocket.

- **`lib/mediasoup.js`**
  - Cria e gerencia **Workers** e **Routers**.
  - Configura codecs suportados.

- **`lib/rooms.js`**
  - Mapeia `roomId` → instância de sala interna no SFU.
  - Cria sala quando recebe uma requisição de `create-room` (via API HTTP) ou quando o primeiro peer se conecta com um `roomId` válido:
    - Associa `roomId` a um `Router` específico.
    - Permite adicionar/remover peers.

- **`lib/peers.js`**
  - Representa usuários conectados à room (por `userId`, `callId`, `role`).
  - Gerencia o conjunto de **transports**, **producers** e **consumers** associados ao peer.

- **`lib/transports.js`**
  - Funções auxiliares para:
    - `createWebRtcTransport`.
    - `connectWebRtcTransport`.
  - Configura ICE, DTLS, faixa de portas, etc.

- **`lib/producers.js`**
  - Lida com o lado emissor de mídia:
    - Criação de producers de áudio e vídeo.
    - Mapeamento para os peers e rooms.

- **`lib/consumers.js`**
  - Lida com o lado receptor de mídia:
    - Criação de consumers para cada combinação peer/producer.
    - Gerenciamento de pausar/retomar consumo (caso haja otimizações).

- **`signaling/wsServer.js`**
  - Sobe o servidor WebSocket na porta definida (ex.: `SFU_WS_PORT`).
  - Na conexão, recebe o **token JWT**.
  - Valida o token usando o segredo compartilhado com Laravel.
  - Conecta o peer à `roomId` correspondente (extraída do token).

- **`signaling/handlers.js`**
  - Lida com mensagens de alto nível:
    - `join`, `leave`, `getRouterRtpCapabilities`, `createWebRtcTransport`, `connectWebRtcTransport`, `produce`, `consume`, `ping`, `pong`, etc.

- **`api/httpRoutes.js`**
  - Expõe endpoints para o backend:
    - `POST /rooms` (create-room) → retorna `room_id` e metadados.
    - `DELETE /rooms/:roomId` (destroy-room).
  - Autenticação via token interno/secret (não o mesmo JWT de sala).

- **`metrics/prometheus.js`**
  - Opcionalmente, expõe métricas para Prometheus/Grafana:
    - Número de rooms, peers, producers, consumers.
    - Bitrate, packet loss, RTT, jitter, etc.

---

## 6. Infraestrutura do Projeto

### 6.1. Integração lógica entre componentes

```text
Frontend
   │
   │  (HTTP / Reverb)
   ▼
Laravel API
   │
   │  (HTTP / gRPC)
   ▼
Media Gateway / MediaSoup API
   │
   │  (WebSocket)
   ▼
MediaSoup SFU
```

### 6.2. Containers / Serviços (cenário Docker)

Em uma arquitetura containerizada típica:

- **frontend**:
  - Servidor web para o build da SPA (Nginx/Vite preview).

- **laravel-app**:
  - PHP-FPM com o código Laravel.
  - Exposto via Nginx (reverse proxy).

- **redis / queue / broadcast**:
  - Suporte para filas e broadcasting com Reverb.

- **mediasoup-sfu**:
  - Container Node.js com o projeto MediaSoup.
  - Expõe:
    - Porta HTTP interna para API (por ex. `3000`).
    - Porta WebSocket para clientes (por ex. `4443`).

- **(opcional) media-gateway**:
  - Serviço Node/Python/Go que abstrai múltiplos SFUs.
  - Laravel fala com o gateway; o gateway fala com um ou mais SFUs.

### 6.3. Portas e variáveis de ambiente

- **Laravel / API**
  - `APP_URL`, `REVERB_SERVER`, etc.

- **MediaSoup / Gateway**
  - `SFU_URL`: URL base HTTP para API (`http://mediasoup-sfu:3000`).
  - `SFU_WS_URL`: URL WebSocket do SFU (`wss://sfu.example.com/ws` ou `ws://mediasoup-sfu:4443`).
  - `SFU_JWT_SECRET` ou `services.media_gateway.jwt_secret`: segredo para assinatura/validação do token de sala.

### 6.4. Comunicação entre containers

- **Laravel → Media Gateway / SFU API**
  - Via rede interna Docker (ex.: host `mediasoup-sfu`, porta `3000`).
  - Chamadas:
    - `POST /rooms` (create-room).
    - `DELETE /rooms/:roomId` (destroy-room).

- **Frontend → MediaSoup WS**
  - Via URL pública ou interna exposta em `sfu_ws_url`.
  - Deve estar acessível a partir do navegador do usuário final.

---

## 7. Boas Práticas e Decisões de Arquitetura

### 7.1. Separação do servidor MediaSoup do backend Laravel

- **Motivos:**
  - Escalabilidade independente (CPU intensivo para mídia).
  - Tecnologias distintas:
    - Laravel (PHP) foca em regras de negócio.
    - MediaSoup (Node.js) foca em WebRTC e processamento de mídia.
  - Facilita rodar o SFU em nós otimizados (por ex.: instâncias com mais CPU e rede).

### 7.2. Uso de WebSocket dedicado para mídia

- **Reverb** continua responsável pelo **ciclo de vida** da chamada.
- Um **WebSocket dedicado** no MediaSoup:
  - Reduz acoplamento.
  - Permite protocolos customizados para WebRTC.
  - Mantém o tráfego de mídia e de eventos de negócio separados.

### 7.3. Token JWT de curta duração

- `roomId` **nunca** confiável vindo do frontend.
- O SFU **sempre** confia no `roomId` contido no JWT assinado pelo backend.
- Expiração curta (1–5 minutos) evita uso indevido de tokens vazados.

### 7.4. Logs estruturados

- Eventos como:
  - `CALL_CREATED`, `CALL_ACCEPTED`, `ROOM_CREATED`, `ROOM_LEFT`, `CALL_ENDED`.
- Campos:
  - `call_id`, `room_id`, `user_id`, `appointment_id`.
- Facilitam auditoria e investigação de incidentes.

### 7.5. Evolução futura

- A presença de `MediaGatewayInterface` e do modelo separado `Room` prepara o sistema para:
  - Trocar o backend de mídia (ex.: LiveKit, Janus) sem mudar a camada de negócio.
  - Introduzir gravação de chamadas.
  - Rodar múltiplos SFUs em paralelo.

---

## 8. Preparação para Escalabilidade

### 8.1. Múltiplos workers MediaSoup

- O próprio MediaSoup permite:
  - **Workers** múltiplos por processo.
  - Cada worker com um ou mais **Routers**.
  - Distribuição de rooms entre workers.

**Estratégias:**
- Criar N workers ao subir o servidor (ex.: 1 por core de CPU).
- Para cada nova room (ou `callId`), escolher um worker/Router:
  - Round-robin.
  - Menor número de rooms ativas.
  - Afinidade por usuário/região.

### 8.2. Múltiplos servidores SFU

- A coluna `sfu_node` em `rooms` já permite:
  - Registrar **em qual nó SFU** a room foi criada.
  - Suportar múltiplos servidores (SFU A, SFU B, ...).

**Media Gateway (fortemente recomendado):**
- Centraliza a lógica:
  - Escolha de nó SFU.
  - Migração e failover.
  - Registro de métricas globais.
- Laravel passa apenas `callId` e alguns metadados; o Gateway decide onde criar a sala.

### 8.3. Balanceamento de carga

- **Camada HTTP/WS**:
  - Utilizar load balancer (Nginx, HAProxy, ELB, etc.) na frente de múltiplos SFUs.
  - Roteamento por:
    - Sticky sessions (por token).
    - sfu_node (o token pode carregar o nó).

- **Camada de mídia**:
  - Cada nó SFU opera de forma independente.
  - O Media Gateway e o backend sabem qual `sfu_node` atende qual `roomId`.

### 8.4. Integração com Kubernetes ou cloud

- **Pods por componente:**
  - `laravel-app` (scale horizontal).
  - `mediasoup-sfu` (scale horizontal com pods especializados).
  - `media-gateway` (opcional).

- **ConfigMaps / Secrets:**
  - Segredos de JWT (SFU_JWT_SECRET).
  - URLs internas entre serviços.

- **Horizontal Pod Autoscaler:**
  - Escalar a quantidade de pods do SFU baseado em:
    - CPU / network usage.
    - Número de rooms/peers ativos.

- **Observabilidade:**
  - Prometheus/Grafana coletando métricas das instâncias MediaSoup.
  - Logs centralizados (ELK/EFK).

---

## 9. Uso do Documento

Este documento deve ser usado como **referência principal** para:

- **Novos desenvolvedores**
  - Entender rapidamente:
    - Como o fluxo de videochamada funciona.
    - Quais arquivos/backend estão envolvidos.
    - Qual é o papel do MediaSoup e do Laravel.

- **Manutenção futura**
  - Evoluir a arquitetura (novos SFUs, gravação, métricas).
  - Fazer troubleshooting com base nos fluxos e logs descritos.

- **Discussão de arquitetura**
  - Servir como base para decisões futuras sobre:
    - Escalabilidade.
    - Distribuição de serviços.
    - Troca ou evolução da tecnologia de SFU.

Qualquer alteração significativa na implementação (endpoints, fluxo de eventos, estrutura de tokens, estrutura do servidor MediaSoup) deve ser refletida neste documento e nos documentos de referência em `docs/videocall`.

