## Camada de Mídia (Media Transport Layer)

Responsável por transportar áudio e vídeo em tempo real entre participantes da consulta via **WebRTC com SFU MediaSoup**.

Esta camada não contém regra de negócio de consulta, agenda ou permissão de usuário. Ela recebe do Laravel apenas os dados necessários para entrar na sala já autorizada: `token` JWT e `sfu_ws_url`.

### Responsabilidades

- Conectar o navegador ao WebSocket do SFU.
- Executar o fluxo MediaSoup:
    - `join` com JWT.
    - criação de transports WebRTC.
    - conexão DTLS/ICE.
    - publicação de áudio/vídeo com `produce`.
    - consumo de streams remotos com `consume`.
- Capturar mídia local com `getUserMedia`.
- Controlar câmera, microfone, desconexão e limpeza de tracks.
- Expor streams local/remotos para os componentes Vue.

### Tecnologias envolvidas

- **WebRTC** - protocolo de mídia em tempo real.
- **MediaSoup / SFU** - roteamento seletivo de mídia no servidor.
- **mediasoup-client** - cliente WebRTC usado pelo frontend.
- **WebSocket do SFU** - sinalização técnica de mídia.
- **Vue/Pinia** - estado e interface de chamada.

### Documentos relacionados

- `../signaling/videocall/VideoCallImplementation.md` - implementação atual de videochamada.
- `../signaling/videocall/VideoCallTasks.md` - checklist do módulo.
- `../../videocall/TESTE_SFU_MEDIASOUP.md` - guia de teste do SFU.
- `../architecture-governance/diagrams/04_FluxoVideoconferencia.md` - fluxo arquitetural.

### Fluxo de mídia

1. Laravel autoriza a chamada e emite token com `callId`, `roomId`, `userId`, `role` e expiração.
2. Frontend chama `SfuVideoMediaProvider.connect(sfuWsUrl, token)`.
3. `useSfu.ts` abre WebSocket com o SFU.
4. Cliente envia `join` com o token.
5. SFU valida JWT e retorna RTP capabilities.
6. Cliente cria transports de envio e recebimento.
7. Cliente captura câmera/microfone e publica producers.
8. Ao receber `newProducer`, cliente cria consumers e monta `remoteStreams`.
9. Ao sair, frontend fecha WebSocket, transports, producers, consumers e tracks locais.

### Dependências com outras camadas

- **Recebe estado de negócio da camada de sinalização:** eventos Reverb indicam quando uma chamada está disponível, aceita ou encerrada.
- **Não acessa banco de dados:** `roomId` confiável fica no JWT emitido pelo backend.
- **Não decide autorização:** policies e endpoints Laravel decidem quem pode entrar.
- **É consumida pela apresentação:** telas de médico/paciente renderizam streams e controles.

### Boas práticas

- Nunca enviar `roomId` ou token vindo do usuário como fonte de verdade.
- Renovar token por `/calls/active` ou `/appointments/{appointment}/video/session` quando necessário.
- Logar erros técnicos sem PII.
- Monitorar ICE/DTLS state, bitrate, perda de pacotes e tempo até primeiro frame.
- Em produção, configurar portas RTC, `announcedIp` e TURN quando a rede exigir.

### Evoluções futuras

- Gravação centralizada no SFU ou serviço dedicado, condicionada a consentimento e regra LGPD/CFM.
- Compartilhamento de tela com `getDisplayMedia`.
- Métricas de qualidade por chamada.
- Testes E2E com múltiplos dispositivos.
