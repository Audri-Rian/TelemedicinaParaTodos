# Módulo de Videochamadas

Este diretório documenta a camada de sinalização de negócio das videochamadas. A implementação vigente usa **MediaSoup como SFU** para mídia e **Laravel Reverb** apenas para eventos de estado da chamada.

## Arquivos

- [VideoCallImplementation.md](VideoCallImplementation.md) - implementação atual com `Call`, `Room`, MediaGateway, Reverb e SFU.
- [VideoCallTasks.md](VideoCallTasks.md) - checklist operacional e técnico do módulo atual.
- [../../../videocall/TESTE_SFU_MEDIASOUP.md](../../../videocall/TESTE_SFU_MEDIASOUP.md) - guia de teste do SFU.
- [../../../videocall/MIGRACAO_P2P_PARA_MEDIASOUP.md](../../../videocall/MIGRACAO_P2P_PARA_MEDIASOUP.md) - histórico da migração P2P -> SFU.

## Funcionalidades

- Chamadas agendadas provisionadas automaticamente na janela do appointment.
- Chamadas ad-hoc de paciente para médico com validação de vínculo recente.
- Aceite, rejeição e encerramento por rotas autenticadas.
- Tokens JWT curtos para entrada no SFU.
- Estado global no frontend via Pinia e sincronização entre abas.
- Transporte de mídia via WebRTC/MediaSoup, sem PeerJS.

## Componentes técnicos

| Área             | Arquivos principais                                                                                                                            |
| ---------------- | ---------------------------------------------------------------------------------------------------------------------------------------------- |
| Backend          | `app/Services/CallManagerService.php`, `app/Http/Controllers/CallController.php`, `app/Http/Controllers/AppointmentVideoSessionController.php` |
| Gateway de mídia | `app/Contracts/MediaGatewayInterface.php`, `app/Services/MediaGatewayHttp.php`, `app/Services/MediaGatewayStub.php`                            |
| Eventos          | `app/Events/VideoCallAvailable.php`, `VideoCallRequested.php`, `VideoCallAccepted.php`, `VideoCallRejected.php`, `VideoCallEnded.php`          |
| Jobs             | `app/Jobs/AutoStartVideoCall.php`, `EndScheduledVideoCalls.php`, `EndZombieVideoCalls.php`                                                     |
| Frontend         | `resources/js/composables/useVideoCall.ts`, `useVideoCallSession.ts`, `useSfu.ts`, `resources/js/stores/videoCall.ts`                          |
| Mídia            | `resources/js/services/video-call-media/SfuVideoMediaProvider.ts`, `mediasoup-client`                                                          |

## Fluxo resumido

1. Laravel cria ou localiza uma `Call`.
2. Laravel cria uma `Room` no SFU via MediaGateway.
3. Laravel emite evento Reverb para avisar os participantes.
4. Frontend busca token em `/calls/active` ou `/appointments/{appointment}/video/session`.
5. Frontend conecta no WebSocket do SFU com JWT.
6. MediaSoup negocia transports e encaminha áudio/vídeo.
7. Encerramento limpa estado frontend, atualiza `calls` e destrói sala no gateway quando aplicável.

## Requisitos implementados

- **RF004** - Realizar consultas online por videoconferência.
- **RF012** - Videoconferência em tempo real.

## Status

Implementação atual: **SFU MediaSoup**.

PeerJS/P2P foi removido da arquitetura vigente. Referências a P2P devem ficar restritas aos documentos históricos de migração.
