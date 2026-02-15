## 🎥 Camada de Mídia (Media Transport Layer)

Responsável por **transportar áudio, vídeo e dados em tempo real** entre participantes da consulta, normalmente de forma **P2P (peer-to-peer)**.

Nesta camada NÃO há regra de negócio de consulta ou permissão de usuário; ela apenas cuida de:

- Conexão WebRTC (negociação de mídia).
- Streams de áudio/vídeo.
- Qualidade de chamada, reconexão e encerramento.

### 🎯 Responsabilidades

- Estabelecer e manter **conexões WebRTC** entre navegador do médico e do paciente.
- Gerenciar:
  - Captura de mídia (câmera, microfone, futuramente tela).
  - Encerramento limpo de chamadas e liberação de recursos.
  - Tratamento de erros de mídia e reconexão básica.
- Permitir evolução futura para topologias:
  - **P2P** (atual).
  - **SFU** (Selective Forwarding Unit).
  - **MCU** (Multipoint Control Unit).

### 🧩 Tecnologias Envolvidas

- **WebRTC** – Protocolo de mídia em tempo real.
- **PeerJS** – Abstração para WebRTC P2P.
- **Vue.js** – Componentes de UI de vídeo.
- **Navigator MediaDevices** (`getUserMedia`) – captura de mídia no navegador.

### 📂 Documentos Relacionados

- Videochamadas (implementação de mídia):
  - `../../modules/videocall/VideoCallImplementation.md` – foco na implementação WebRTC/PeerJS.
  - `../../modules/videocall/README.md` – visão geral do módulo de videochamadas.
  - `../../modules/videocall/VideoCallTasks.md` – fluxo de caller/callee, `callUser`, `peer.on('call')`, etc.
  - `../../diagrams/04_FluxoVideoconferencia.md` – sequence diagram destacando a parte P2P.
- Frontend:
  - Páginas de videoconferência (ex.: `resources/js/pages/Patient/VideoCall.vue`, `Dev/VideoTest.vue` – ver código).

### 🔄 Fluxo de Comunicação de Mídia

1. **Sinalização** (camada de Sinalização) troca `peerId` entre médico e paciente.
2. Cada lado:
   - Conecta ao servidor PeerJS.
   - Captura fluxo local (`getUserMedia`).
3. **Chamador**:
   - Usa `peer.call(remotePeerId, localStream)` para iniciar conexão.
4. **Receptor**:
   - Escuta `peer.on('call')` e responde com `call.answer(localStream)`.
5. Ambos:
   - Recebem `remoteStream` e exibem nos elementos `<video>`.
6. **Encerramento**:
   - Parar tracks de mídia.
   - Fechar `call` e limpar refs no composable `useVideoCall`.

### 🤝 Dependências com Outras Camadas

- **Recebe sinalização de**:
  - Camada de Sinalização (`RequestVideoCall`, `RequestVideoCallStatus`).
- **Não acessa diretamente**:
  - Banco de dados, regras de negócio ou policies.
- **É usada por**:
  - Camada de Apresentação (componentes Vue de vídeo).

### 📈 Boas Práticas e Escalabilidade

- Encapsular toda a lógica em um **composable dedicado** (`useVideoCall.ts`) com:
  - Controle de estado (connecting, in_call, ended).
  - Tratamento de erros (permissão negada, falha de rede).
  - Timeouts para chamadas não atendidas.
- Isolar credenciais/configuração do PeerJS em variáveis de ambiente.
- Planejar migração futura para **SFU** quando:
  - Houver >2 participantes por chamada.
  - For necessário gravar chamadas ou fazer broadcast.

### 🔮 Evoluções Futuras

- **SFU (Selective Forwarding Unit)**:
  - Introduzir um servidor de mídia (ex.: Janus, Mediasoup, LiveKit) para mixar/rotear streams.
  - A camada de Sinalização passa a orquestrar rooms/sessions no SFU.
- **Gravação de Consultas**:
  - Centralizar gravação no lado servidor (SFU/MCU) por compliance.
- **Screen Sharing**:
  - Extender composable para `getDisplayMedia` e múltiplos streams (câmera + tela).
- **QoS e Monitoramento**:
  - Coletar métricas WebRTC (bitrate, jitter, packet loss) e exportar para observabilidade.

