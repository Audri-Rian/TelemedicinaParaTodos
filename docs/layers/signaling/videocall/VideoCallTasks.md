# Checklist do Sistema de Videochamada SFU

## Estado atual

O módulo atual usa **MediaSoup/SFU**. Não há troca de `peerId` nem conexão P2P via PeerJS no fluxo vigente.

## Backend

- [x] Modelar chamada de negócio em `calls`.
- [x] Modelar sala de mídia em `rooms`.
- [x] Criar `MediaGatewayInterface` para abstrair SFU/gateway.
- [x] Criar `MediaGatewayHttp` para chamar o SFU por HTTP.
- [x] Criar `MediaGatewayStub` para ambientes sem SFU real.
- [x] Provisionar chamada agendada com `CallManagerService::provisionAppointmentCall`.
- [x] Criar chamada ad-hoc com `CallManagerService::createCall`.
- [x] Aceitar, rejeitar e encerrar chamada ad-hoc.
- [x] Gerar JWT curto de sala com `callId`, `roomId`, `userId`, `role`, `iat`, `exp`.
- [x] Expor `/calls/active` para recuperar estado e token fresco.
- [x] Expor `/appointments/{appointment}/video/session` para entrada idempotente em chamada agendada.
- [x] Usar policies para request, accept, reject, end, view e join de sessão.

## Jobs e manutenção

- [x] `AutoStartVideoCall`: provisiona chamadas agendadas dentro da janela.
- [x] `EndScheduledVideoCalls`: encerra chamadas agendadas fora da janela quando algum participante não entrou.
- [x] `EndZombieVideoCalls`: encerra ad-hoc aceitas vencidas e marca pendentes como `missed`.
- [x] Locks/cache para evitar provisionamento concorrente.
- [x] Cache curto para `/calls/active`, invalidado em mudança de estado.

## Eventos Reverb

- [x] `VideoCallAvailable`: avisa que a chamada agendada está disponível.
- [x] `VideoCallRequested`: avisa médico sobre chamada ad-hoc.
- [x] `VideoCallAccepted`: entrega token e URL do SFU para chamada ad-hoc aceita.
- [x] `VideoCallRejected`: avisa chamador sobre recusa.
- [x] `VideoCallEnded`: avisa participantes para limpar estado e desconectar mídia.

## Frontend

- [x] Estado global em `resources/js/stores/videoCall.ts`.
- [x] API de alto nível em `resources/js/composables/useVideoCall.ts`.
- [x] Listeners Reverb e bootstrap em `resources/js/composables/useVideoCallSession.ts`.
- [x] Conexão MediaSoup em `resources/js/composables/useSfu.ts`.
- [x] Provider de mídia em `resources/js/services/video-call-media/SfuVideoMediaProvider.ts`.
- [x] Telas separadas para médico e paciente.
- [x] Sincronização cross-tab via `BroadcastChannel` sem compartilhar token.
- [x] Reconexão buscando token fresco por `/calls/active`.

## SFU / MediaSoup

- [x] Cliente usa `mediasoup-client`.
- [x] WebSocket do SFU recebe `join` com JWT.
- [x] Cliente cria transports de envio e recebimento.
- [x] Cliente publica áudio/vídeo com `produce`.
- [x] Cliente consome producers remotos com `consume` e `resumeConsumer`.
- [x] Página de teste documentada em `docs/videocall/TESTE_SFU_MEDIASOUP.md`.

## Pendências recomendadas

- [ ] Healthcheck explícito do SFU antes de aceitar chamada quando `VIDEO_CALL_REQUIRE_SFU_HEALTH=true`.
- [ ] Métricas de qualidade: ICE/DTLS state, bitrate, perda de pacote e tempo de conexão.
- [ ] Registro auditável de entrada/saída de participantes em tabela própria ou logs estruturados, sem PII.
- [ ] Estratégia de TURN/portas RTC em produção.
- [ ] Testes E2E cobrindo duas abas/dispositivos no fluxo SFU.
- [ ] Gravação de consulta, se a regra de negócio e consentimento LGPD/CFM forem definidos.

## Critérios de aceite

- Usuário autenticado só acessa chamada própria.
- Chamada agendada abre na janela configurada e retorna token fresco.
- Chamada ad-hoc só é criada por paciente com vínculo recente com o médico.
- Médico consegue aceitar ou rejeitar chamada ad-hoc.
- Participantes conectam ao mesmo `roomId` no SFU via JWT.
- Encerramento limpa estado no frontend e atualiza `calls`.
- Não existe dependência de PeerJS, `peerId`, `peer.call()` ou sinalização P2P no fluxo atual.
