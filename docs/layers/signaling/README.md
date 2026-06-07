## Camada de Sinalização (Signaling Layer)

Responsável por orquestrar comunicação em tempo real sem trafegar mídia diretamente.

No módulo de videochamada atual, esta camada usa **Laravel Reverb/Echo** para eventos de negócio. A sinalização técnica de WebRTC/MediaSoup acontece no **WebSocket do SFU**, fora do Reverb.

### Responsabilidades

- Avisar disponibilidade de chamada agendada.
- Entregar solicitação, aceite, rejeição e encerramento de chamadas ad-hoc.
- Garantir canais privados por usuário.
- Propagar mudanças de estado para o frontend.
- Manter eventos pequenos e idempotentes.

### Tecnologias envolvidas

- **Laravel Reverb** - servidor WebSocket da aplicação.
- **Laravel Broadcasting / Events** - eventos `VideoCallAvailable`, `VideoCallRequested`, `VideoCallAccepted`, `VideoCallRejected`, `VideoCallEnded` e eventos de mensagens.
- **Laravel Echo** - cliente JS para escutar canais privados.
- **Redis/queues** quando configurados para fan-out e execução assíncrona.

### Documentos relacionados

- Videochamada:
    - `videocall/README.md`
    - `videocall/VideoCallImplementation.md`
    - `videocall/VideoCallTasks.md`
    - `../media/README.md`
- Mensagens:
    - `messages/README.md`
- Arquitetura:
    - `../architecture-governance/Architecture/Arquitetura.md`
    - `../architecture-governance/diagrams/01_ArquiteturaGeral.md`
    - `../architecture-governance/diagrams/07_ArquiteturaCamadas.md`

### Fluxos típicos

#### Videochamada agendada

1. `AutoStartVideoCall` encontra consultas dentro da janela.
2. `CallManagerService` provisiona `Call` e `Room`.
3. Laravel dispara `VideoCallAvailable` para `video-call.{doctorUserId}` e `video-call.{patientUserId}`.
4. Frontend faz bootstrap em `/calls/active` ou entra via `/appointments/{appointment}/video/session`.
5. Frontend conecta ao SFU com token JWT. A negociação WebRTC não passa pelo Reverb.

#### Videochamada ad-hoc

1. Paciente cria chamada em `POST /calls`.
2. Laravel dispara `VideoCallRequested` para o médico.
3. Médico aceita ou rejeita.
4. Ao aceitar, Laravel cria a `Room`, gera token do SFU e dispara `VideoCallAccepted`.
5. Participantes conectam ao SFU.
6. Encerramento dispara `VideoCallEnded` para limpar estado dos participantes.

#### Mensagens

1. Serviço de mensagens valida, persiste e dispara `MessageSent`.
2. Evento é broadcastado em canais privados dos participantes.
3. Frontend atualiza a conversa em tempo real.

### Dependências com outras camadas

- **Persistência:** lê/grava `calls`, `rooms`, `messages`, `appointments`.
- **Domínio:** usa services, policies e FormRequests.
- **Mídia:** apenas entrega estado e dados de entrada; a mídia usa o SFU.
- **Apresentação:** Vue/Echo consome eventos para atualizar UI.

### Boas práticas e escala

- Não trafegar SDP, ICE, `peerId` ou payloads grandes pelo Reverb.
- Não enviar PII desnecessária em eventos.
- Centralizar autorização em policies e `routes/channels.php`.
- Tratar eventos como idempotentes.
- Monitorar latência e taxa de eventos em Reverb separadamente das métricas do SFU.

### Evoluções futuras

- Presença online/em chamada.
- Push notification para chamadas recebidas.
- Healthcheck e failover de SFU antes de aceitar chamada.
- Métricas de entrega de eventos de negócio.
