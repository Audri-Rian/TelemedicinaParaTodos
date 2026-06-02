# Implementação de Videochamada com SFU MediaSoup

## Visão geral

O sistema atual de videochamada usa **WebRTC com SFU MediaSoup**, não P2P/PeerJS. A aplicação Laravel controla a regra de negócio da chamada; o servidor SFU transporta mídia e executa a sinalização técnica de WebRTC por WebSocket próprio.

- **Backend:** Laravel 12, `CallManagerService`, `CallController`, `AppointmentVideoSessionController`.
- **Mídia:** `mediasoup-client` no frontend e MediaSoup/SFU via `MediaGatewayInterface`.
- **Sinalização de negócio:** Laravel Reverb/Echo nos canais privados `video-call.{userId}`.
- **Sinalização de mídia:** WebSocket do SFU (`SFU_WS_URL`/`media_ws_url`), com ações `join`, `createWebRtcTransport`, `connectWebRtcTransport`, `produce`, `consume` e `resumeConsumer`.
- **Persistência:** `calls` representa a chamada de negócio; `rooms` representa a sala criada no SFU.

## Separação de responsabilidades

| Camada        | Responsabilidade                                                                                                             |
| ------------- | ---------------------------------------------------------------------------------------------------------------------------- |
| Laravel       | Autorizar usuários, criar/aceitar/encerrar chamadas, provisionar sala, emitir token JWT do SFU, disparar eventos de negócio. |
| Reverb/Echo   | Notificar disponibilidade, solicitação, aceite, rejeição e encerramento de chamadas. Não troca SDP, ICE nem `peerId`.        |
| SFU MediaSoup | Receber WebSocket do cliente, validar JWT, criar transports WebRTC e encaminhar áudio/vídeo entre participantes.             |
| Frontend Vue  | Manter estado global da chamada, conectar ao SFU, capturar mídia local, publicar producers e consumir streams remotos.       |

## Modelo de domínio

### `Call`

Representa a chamada no domínio médico.

- `call_type`: `scheduled` ou `ad_hoc`.
- `status`: `requested`, `ringing`, `accepted`, `rejected`, `ended`, `missed`.
- `appointment_id`: preenchido em chamadas agendadas.
- `doctor_id` e `patient_id`: participantes autorizados.
- `doctor_joined_at` e `patient_joined_at`: presença na chamada agendada.
- `call_closed_reason`: motivo de encerramento (`no_show`, `doctor_no_show`, `patient_no_show`, `ended_by_user`, `window_expired`).

### `Room`

Representa a sala de mídia no SFU.

- `call_id`: vínculo com a chamada.
- `room_id`: identificador retornado pelo SFU.
- `sfu_node`: nó do gateway/SFU que criou a sala.
- `media_ws_url`: URL WebSocket usada pelo cliente para entrar na sala.

## Fluxo de chamada agendada

1. O job `AutoStartVideoCall` roda a cada minuto.
2. Dentro da janela configurada (`VIDEO_CALL_WINDOW_LEAD_MINUTES` e `VIDEO_CALL_WINDOW_TRAILING_MINUTES`), o Laravel chama `CallManagerService::provisionAppointmentCall`.
3. O serviço cria ou reutiliza uma `Call` do tipo `scheduled`, cria uma `Room` via `MediaGatewayInterface` e dispara `VideoCallAvailable` para médico e paciente.
4. O frontend recebe o evento no canal `video-call.{userId}` e chama `/calls/active` ou o usuário entra pela página de videochamada.
5. Ao entrar em uma consulta específica, o frontend chama `POST /appointments/{appointment}/video/session`.
6. O backend autoriza a entrada, garante a sala de forma idempotente, emite JWT com `callId`, `roomId`, `userId`, `role`, `iat` e `exp`, e retorna `token`, `sfu_ws_url`, `room_id`, `role` e janela da chamada.
7. O frontend conecta no WebSocket do SFU e faz o fluxo MediaSoup (`join`, transports, `produce`, `consume`).
8. O job `EndScheduledVideoCalls` encerra chamadas agendadas fora da janela quando médico ou paciente não entrou.

## Fluxo de chamada ad-hoc

1. O paciente chama `POST /calls` com `call_type=ad_hoc` e `doctor_id`.
2. `StoreCallRequest`, policies e `CallManagerService::createCall` validam autenticação, papel do usuário e vínculo recente com o médico.
3. O backend cria `Call` com `status=requested` e dispara `VideoCallRequested` para o médico.
4. O médico aceita com `POST /calls/{call}/accept`.
5. O backend cria a `Room`, muda a chamada para `accepted`, gera token JWT do SFU e dispara `VideoCallAccepted` para médico e paciente.
6. Ambos conectam ao SFU usando `token` e `sfu_ws_url`.
7. O médico pode recusar com `POST /calls/{call}/reject`; qualquer participante autorizado pode encerrar com `POST /calls/{call}/end`.
8. O job `EndZombieVideoCalls` marca chamadas pendentes como `missed` ou encerra chamadas ad-hoc aceitas que excederam o tempo configurado.

## Rotas

```php
POST /calls
GET  /calls/active
GET  /calls/{call}
POST /calls/{call}/accept
POST /calls/{call}/reject
POST /calls/{call}/end
POST /appointments/{appointment}/video/session
```

Todas rodam com `auth` e `verified`; ações sensíveis passam por policy.

## Eventos Reverb

| Evento               | Destino           | Uso                                                                 |
| -------------------- | ----------------- | ------------------------------------------------------------------- |
| `VideoCallAvailable` | médico e paciente | Chamada agendada já provisionada. Front reconsulta `/calls/active`. |
| `VideoCallRequested` | médico            | Solicitação ad-hoc recebida.                                        |
| `VideoCallAccepted`  | médico e paciente | Chamada ad-hoc aceita; carrega token e `sfu_ws_url`.                |
| `VideoCallRejected`  | chamador          | Chamada ad-hoc recusada.                                            |
| `VideoCallEnded`     | médico e paciente | Chamada encerrada; frontend desconecta mídia e limpa estado.        |

Esses eventos não carregam `peerId`; a sala confiável fica dentro do token ou é retornada por endpoint autenticado.

## Frontend

Arquivos principais:

- `resources/js/composables/useVideoCall.ts`: API de alto nível para solicitar, aceitar, entrar e encerrar chamadas.
- `resources/js/composables/useVideoCallSession.ts`: bootstrap por `/calls/active`, listeners Reverb e sincronização entre abas.
- `resources/js/composables/useSfu.ts`: fluxo MediaSoup/WebSocket, transports, producers, consumers e streams.
- `resources/js/services/video-call-media/SfuVideoMediaProvider.ts`: provider de mídia usado pelas telas.
- `resources/js/stores/videoCall.ts`: estado Pinia da chamada ativa.
- `resources/js/pages/Doctor/VideoCall.vue` e `resources/js/pages/Patient/VideoCall.vue`: telas de chamada.

## Configuração

Variáveis relevantes:

```env
SFU_HTTP_URL=http://127.0.0.1:3100
SFU_WS_URL=ws://127.0.0.1:4444
SFU_API_SECRET=...
SFU_JWT_SECRET=...
VIDEO_CALL_ENABLED=true
VIDEO_CALL_WINDOW_LEAD_MINUTES=10
VIDEO_CALL_WINDOW_TRAILING_MINUTES=10
VIDEO_CALL_TOKEN_TTL_MINUTES=10
VIDEO_CALL_ADHOC_RELATIONSHIP_DAYS=7
VIDEO_CALL_ADHOC_MAX_MINUTES=60
VIDEO_ROOM_INACTIVE_MINUTES=60
```

O Laravel usa `MediaGatewayHttp` quando `SFU_HTTP_URL` e `SFU_API_SECRET` estão configurados; caso contrário, pode usar `MediaGatewayStub` conforme binding do ambiente.

## Teste e debug

- Guia de teste integrado/standalone: `docs/videocall/TESTE_SFU_MEDIASOUP.md`.
- Página de teste: `GET /sfu-test`.
- Simulador dev: `resources/js/pages/Dev/SfuCallSimulator.vue`.
- Logs úteis no Laravel: `VIDEO_CALL_PROVISIONED`, `VIDEO_CALL_ADHOC_REQUESTED`, `VIDEO_CALL_ADHOC_ACCEPTED`, `CALL_ENDED`, `VIDEO_CALL_WINDOW_ENDED`.
- Logs úteis no SFU: criação de sala, entrada de peer, criação de producer/consumer e falhas ICE/DTLS.

## Problemas comuns

| Problema                   | Causa provável                                               | Ação                                                                                 |
| -------------------------- | ------------------------------------------------------------ | ------------------------------------------------------------------------------------ |
| Token inválido ou expirado | `SFU_JWT_SECRET` divergente ou TTL vencido                   | Conferir segredo no Laravel e no SFU; reconsultar `/calls/active` para token fresco. |
| WebSocket do SFU falha     | `SFU_WS_URL` incorreto, porta fechada ou serviço parado      | Validar URL, proxy e processo SFU.                                                   |
| Sala não provisiona        | `SFU_HTTP_URL`/`SFU_API_SECRET` ausentes ou SFU indisponível | Conferir `services.media_gateway` e logs `VIDEO_CALL_PROVISION_FAILED`.              |
| Sem mídia remota           | Falha de producer/consumer, ICE/DTLS ou permissões de mídia  | Verificar logs do navegador, permissões de câmera/microfone e portas RTC do SFU.     |

## Status

O módulo atual é **SFU/MediaSoup**. Documentos antigos sobre PeerJS/P2P devem ser tratados apenas como histórico de migração, não como implementação vigente.
