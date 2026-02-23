## 🔔 Camada de Sinalização (Signaling Layer)

Responsável por **orquestrar comunicação em tempo real**, sem trafegar mídia diretamente. Aqui vivem:

- Eventos de videoconferência (pedido/aceite/estado da chamada).
- Mensagens em tempo real (chat médico–paciente).
- Presença e estado de sessões.
- Integração com **Laravel Reverb**, **Laravel Echo** e canais privados.

### 🎯 Responsabilidades

- Gerenciar **ciclos de vida de sessão**: início, aceite, encerramento, expiração.
- Garantir que somente usuários autorizados recebam eventos (channels privados + policies).
- Propagar mudanças de estado de domínio para o frontend via broadcasting.
- Suportar **retries**, reconexão e idempotência de eventos críticos.

### 🧩 Tecnologias Envolvidas

- **Laravel Reverb** – Servidor WebSocket.
- **Laravel Broadcasting / Events** – `RequestVideoCall`, `RequestVideoCallStatus`, `MessageSent`, `VideoCallRoomCreated`, `VideoCallRoomExpired`, etc.
- **Laravel Echo** – Cliente JS para escutar canais privados.
- **Redis / PubSub** (quando configurado) – Fan-out de eventos entre processos.

### 📂 Documentos Relacionados

- Videoconferência – sinalização:
  - `../videocall/README.md` (módulo de videochamadas – visão geral).
  - `../videocall/VideoCallTasks.md` (fluxo de request/accept/status via eventos).
  - `../videocall/VideoCallImplementation.md` (detalhes de fluxo P2P + eventos).
  - `../../diagrams/04_FluxoVideoconferencia.md` (sequence diagram com Reverb + PeerJS).
- Mensagens (chat):
  - `../messages/README.md` (sistema de mensagens, canais `messages.{id}`, evento `MessageSent`).
- Arquitetura de comunicação:
  - `../../Architecture/Arquitetura.md` → seção “Sistema de Eventos e Broadcasting”.
  - `../../diagrams/01_ArquiteturaGeral.md` e `../../diagrams/07_ArquiteturaCamadas.md`.

> Observação: os arquivos permanecem fisicamente em `docs/modules` e `docs/diagrams`; esta camada atua como índice arquitetural.

### 🔄 Fluxos Típicos

- **VideoCall**:
  1. `VideoCallController` chama evento `RequestVideoCall`.
  2. Evento é broadcastado para canal privado `video-call.{patientId}`.
  3. Paciente aceita → `RequestVideoCallStatus` broadcastado para `video-call.{doctorId}`.
  4. Ambos trocam `peerId` via eventos e estabelecem P2P pela camada de mídia.

- **Mensagens**:
  1. `MessageService::sendMessage` valida, persiste e dispara `MessageSent`.
  2. Evento é broadcastado em `messages.{sender_id}` e `messages.{receiver_id}`.
  3. Frontend escuta via Echo (`private('messages.{id}')`) e atualiza o chat em tempo real.

### 🤝 Dependências com Outras Camadas

- **Depende de**:
  - Camada de Persistência (para ler/gravar `VideoCallRoom`, `VideoCallEvent`, `Message`, `Appointments`).
  - Camada de Arquitetura & Governança (regras de negócio em `SystemRules.md`, políticas de acesso).
- **É consumida por**:
  - Camada de Mídia (usa sinalização para trocar `peerId` e estados de chamada).
  - Camada de Apresentação (Vue/Echo consomem eventos para atualizar UI).

### 📈 Boas Práticas e Escalabilidade

- Usar **eventos pequenos e específicos** (não enviar payloads gigantes via WebSocket).
- Projetar eventos como **idempotentes** (reprodução não deve quebrar estado).
- Centralizar regras de autorização em **Policies** + `routes/channels.php`.
- Para alta escala:
  - Usar Redis como backend de broadcasting.
  - Escalar workers de Reverb horizontalmente.
  - Medir taxa de eventos e latência com métricas (ex.: Prometheus + Grafana / CloudWatch).

### 🔮 Evoluções Futuras

- Adicionar **eventos de presença** (online/offline/em chamada).
- Integrar notificações push (Web Push / mobile) usando mesma semântica de eventos.
- Desacoplar sinalização de videoconferência para um **serviço dedicado de signaling** (microserviço ou função serverless) se a carga de videochamadas crescer muito.

