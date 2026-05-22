# Feature Spec — Integração Mediasoup SFU: Videochamada Médico-Paciente

> Status: `draft`
> Autor: Tech Lead Agent · Data: 2026-05-22

---

## Objetivo

Expor via HTTP + Inertia o fluxo completo de videochamada médico-paciente sobre SFU mediasoup: CallController com rotas de ação, composables WebRTC (useSfu + useVideoCall), UI de vídeo real no VideoCall.vue (paciente) e página VideoCall.vue (médico).

## Motivação

`CallManagerService` está completo e funcional mas sem rotas HTTP expostas. O frontend carece de composables WebRTC e UI de vídeo. Broadcasting via Reverb está configurado no app.ts mas as variáveis de ambiente não têm valores. A transição de P2P para SFU já está arquitetada — falta conectar as camadas.

---

## Regras de negócio

1. Apenas participantes diretos do appointment (doctor_id / patient_id) podem iniciar, aceitar, rejeitar ou encerrar uma chamada — validado pela `VideoCallPolicy`.
2. Chamada só pode ser iniciada dentro da janela de tempo: `lead_minutes` antes e `trailing_minutes` após o horário agendado (configurado em `telemedicine.appointment`), ou quando o appointment está `in_progress`.
3. O token JWT (HS256) é gerado pelo Laravel no momento do `acceptCall` — o frontend nunca recebe `roomId` diretamente, apenas via payload do token.
4. Ao aceitar uma chamada: criar sala no SFU via `MediaGatewayHttp::createRoom`, persistir `Room`, atualizar appointment para `in_progress` (via `AppointmentService::start`).
5. Ao encerrar: atualizar appointment via `AppointmentService::end`, destruir sala no SFU via `MediaGatewayHttp::destroyRoom`.
6. Token TTL: `telemedicine.video_call.token_ttl_minutes` (padrão 5 min) — gerado no accept, válido para o join inicial no mediasoup-server.
7. Idempotência: se já existe `Call` ativa para o appointment, retornar erro 409 em vez de criar duplicata.
8. Sala "zumbi" encerrada pelo `EndZombieVideoCalls` job após `room_inactive_minutes` (padrão 60 min).
9. O médico inicia a chamada (cria o `Call`); o paciente recebe notificação via Reverb e aceita/rejeita.
10. Ambos os participantes recebem `VideoCallAccepted` com token + `sfu_ws_url` para se conectar ao mediasoup-server.

---

## Arquitetura proposta

```
[Paciente/Médico clica "Iniciar"]
        ↓
POST /calls (CallController@store)
        ↓
CallManagerService::createCall()
        ↓
event(VideoCallRequested) → Reverb → video-call.{calleeUserId}
        ↓
[Destinatário recebe push no useVideoCall → exibe modal de aceite]
        ↓
POST /calls/{call}/accept (CallController@accept)
        ↓
CallManagerService::acceptCall()
  ├─ MediaGatewayHttp::createRoom() → POST /rooms no mediasoup-server
  ├─ Room::create() no MySQL
  ├─ AppointmentService::start()
  └─ generateRoomToken() → JWT HS256
        ↓
event(VideoCallAccepted) → Reverb → video-call.{doctorId} + video-call.{patientId}
        ↓
[Ambos recebem token + sfu_ws_url]
        ↓
useSfu::connect(sfuWsUrl, token)
  ├─ WS join com JWT no payload
  ├─ getRouterRtpCapabilities
  ├─ createWebRtcTransport (send + recv)
  ├─ getUserMedia → produce (audio/video)
  └─ consume (on newProducer)
        ↓
[Vídeo em tempo real — UI VideoCall.vue]
        ↓
POST /calls/{call}/end (CallController@end)
        ↓
CallManagerService::endCall()
  ├─ AppointmentService::end()
  └─ MediaGatewayHttp::destroyRoom()
        ↓
event(VideoCallEnded) → Reverb → ambos saem da UI
```

Padrões reutilizados:

- `CallManagerService` — sem alterações, consumido pelo controller
- `VideoCallPolicy` — `request`, `accept`, `reject`, `end` já implementados
- `MediaGatewayHttp` / `MediaGatewayStub` — binding via `AppServiceProvider` (SFU_HTTP_URL presente → Http; ausente → Stub)
- `useLoadState` — gerenciamento de estado de loading nos composables
- `useToast` — feedback de erros ao usuário
- Wayfinder — gera automaticamente actions TypeScript ao rodar `php artisan wayfinder:generate`

---

## Infraestrutura — Variáveis de ambiente obrigatórias

Adicionar ao `.env` (e `.env.example` já tem os comentários):

```
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=<gerar>
REVERB_APP_KEY=<gerar>
REVERB_APP_SECRET=<gerar>
REVERB_HOST=localhost
REVERB_PORT=8080
REVERB_SCHEME=http

SFU_HTTP_URL=http://127.0.0.1:3080
SFU_WS_URL=ws://127.0.0.1:4443
SFU_JWT_SECRET=<mesmo valor configurado no mediasoup-server>
SFU_API_SECRET=<mesmo valor configurado no mediasoup-server>
```

`config/services.php` (verificar/adicionar):

```php
'media_gateway' => [
    'sfu_http_url' => env('SFU_HTTP_URL'),
    'sfu_ws_url'   => env('SFU_WS_URL'),
    'jwt_secret'   => env('SFU_JWT_SECRET'),
    'api_secret'   => env('SFU_API_SECRET'),
],
```

---

## Backend

### Endpoints

| Método | Rota                   | Controller@action       | FormRequest        | Throttle |
| ------ | ---------------------- | ----------------------- | ------------------ | -------- |
| POST   | `/calls`               | `CallController@store`  | `StoreCallRequest` | `10,1`   |
| POST   | `/calls/{call}/accept` | `CallController@accept` | —                  | `10,1`   |
| POST   | `/calls/{call}/reject` | `CallController@reject` | —                  | `10,1`   |
| POST   | `/calls/{call}/end`    | `CallController@end`    | —                  | `10,1`   |
| GET    | `/calls/{call}`        | `CallController@show`   | —                  | —        |

**Arquivo de rotas:** `routes/web/shared.php` (acessível tanto por médico quanto paciente, com middleware `auth`).

Alternativa aceita: rotas duplicadas em `patient.php` e `doctor.php` para clareza de middleware por role.

### CallController

`app/Http/Controllers/CallController.php`

- `store(StoreCallRequest $request)` → valida `appointment_id`, resolve `Appointments`, chama `$this->authorize('request', $appointment)` (VideoCallPolicy), delega a `CallManagerService::createCall()`, retorna JSON `{ call_id }` com HTTP 201.
- `accept(Call $call)` → resolve Call via route model binding, chama `$this->authorize('accept', $call->appointment)`, delega a `CallManagerService::acceptCall()`, retorna JSON `{ token, sfu_ws_url }` com HTTP 200.
- `reject(Call $call)` → autoriza via `reject`, delega a `rejectCall()`, retorna HTTP 204.
- `end(Call $call)` → autoriza via `end`, delega a `endCall()`, retorna HTTP 204.
- `show(Call $call)` → retorna estado atual da chamada (call_id, status, room_id se existir) para polling de fallback.

Sem lógica de negócio no controller. Exceções de `CallManagerService` capturadas e convertidas em respostas HTTP adequadas:

- `InvalidArgumentException` → 422
- `RuntimeException` (JWT secret ausente) → 500 (logar)

### StoreCallRequest

`app/Http/Requests/StoreCallRequest.php`

```php
'appointment_id' => 'required|uuid|exists:appointments,id',
```

Middleware adicional: verificar se já existe `Call` ativa para o appointment (`Call::where('appointment_id', ...)->whereIn('status', ['requested','ringing','accepted'])->exists()`) → retornar 409 com mensagem `"Chamada já em andamento para esta consulta"`.

### Autorização

- Middleware: `auth`, `throttle:10,1`
- Policy: `VideoCallPolicy` — já implementada e registrada
- Route model binding: `Call` usa `HasUuids`, binding implícito funciona

### Jobs / Filas

| Job                   | Fila                 | Quando disparar         | Timeout |
| --------------------- | -------------------- | ----------------------- | ------- |
| `EndZombieVideoCalls` | `default` (RabbitMQ) | Schedule: a cada 30 min | 120s    |

O job já existe. Verificar se está registrado no `routes/console.php` com `Schedule::job(EndZombieVideoCalls::class)->everyThirtyMinutes()`.

---

## Frontend

### Composables

#### `useSfu.ts`

`resources/js/composables/useSfu.ts`

Responsabilidade: gerenciar conexão WebSocket raw com mediasoup-server e fluxo WebRTC via `mediasoup-client`.

Estados expostos:

- `connectionState: Ref<'idle' | 'connecting' | 'connected' | 'failed' | 'closed'>`
- `localStream: Ref<MediaStream | null>`
- `remoteStreams: Ref<Map<string, MediaStream>>` — keyed por `producerId`
- `isMicEnabled: Ref<boolean>`
- `isCameraEnabled: Ref<boolean>`

Métodos expostos:

- `connect(sfuWsUrl: string, token: string): Promise<void>` — abre WS, envia `join` com JWT no payload, executa handshake de capabilities e transports
- `disconnect(): void` — envia `leave`, fecha WS e libera tracks
- `toggleMic(): void`
- `toggleCamera(): void`
- `requestKeyFrame(consumerId: string): void`

Fluxo interno (sem código de implementação):

1. `new WebSocket(sfuWsUrl)` — ws raw (não Socket.IO)
2. `join` action com `{ token }` no payload
3. `getRouterRtpCapabilities` → instanciar `mediasoup.createDevice()`
4. `createWebRtcTransport` para send e recv separados
5. `connectWebRtcTransport` com DTLS parameters
6. `getUserMedia` → `produce` audio e vídeo (se habilitados)
7. Ao receber `newProducer` server-push → `consume` e montar `MediaStream`
8. Ao receber `peerLeft` → remover stream do mapa

Dependência: `mediasoup-client ^3.18.7` (já no package.json).

#### `useVideoCall.ts`

`resources/js/composables/useVideoCall.ts`

Responsabilidade: orquestrar estado da chamada + eventos Reverb + ações HTTP.

Estados expostos:

- `callState: Ref<'idle' | 'requesting' | 'ringing' | 'accepted' | 'rejected' | 'ended' | 'error'>`
- `currentCall: Ref<{ callId: string; token: string; sfuWsUrl: string } | null>`
- `incomingCall: Ref<{ callId: string; appointmentId: string } | null>` — para modal de aceite

Métodos expostos:

- `requestCall(appointmentId: string): Promise<void>` — POST `/calls`
- `acceptCall(callId: string): Promise<void>` — POST `/calls/{call}/accept` → recebe token + sfu_ws_url → chama `useSfu.connect()`
- `rejectCall(callId: string): Promise<void>` — POST `/calls/{call}/reject`
- `endCall(callId: string): Promise<void>` — POST `/calls/{call}/end` → chama `useSfu.disconnect()`
- `setupEchoListeners(userId: number): void` — subscreve canal `video-call.{userId}`

Eventos Reverb escutados (via `@laravel/echo-vue`):

- `.VideoCallRequested` → seta `incomingCall` (se o usuário for o destinatário)
- `.VideoCallAccepted` → seta `currentCall` com token + sfu_ws_url; chama `useSfu.connect()`
- `.VideoCallRejected` → seta `callState = 'rejected'`; mostra toast
- `.VideoCallEnded` → seta `callState = 'ended'`; chama `useSfu.disconnect()`

Usa `useLoadState` para loading states por ação e `useToast` para erros.

### Componentes

| Componente                | Novo/Reutilizado | Responsabilidade                                                  |
| ------------------------- | ---------------- | ----------------------------------------------------------------- |
| `VideoCall.vue` (Patient) | Reescrito        | UI de vídeo real + modal de aceite de chamada recebida            |
| `Doctor/VideoCall.vue`    | Novo             | Lista consultas elegíveis + botão "Iniciar chamada" + UI de vídeo |
| `VideoGrid.vue`           | Novo             | Grid responsivo `<video>` local + remotos                         |
| `VideoControls.vue`       | Novo             | Botões mic/cam/encerrar/fullscreen                                |
| `IncomingCallModal.vue`   | Novo             | Modal de notificação de chamada entrante (aceitar/rejeitar)       |

### Estrutura `VideoCall.vue` (Patient) — estados de UI

- **idle:** lista de médicos com agendamentos (comportamento atual mantido)
- **requesting:** spinner "Aguardando médico..."
- **ringing:** exibe `IncomingCallModal` se o paciente for destinatário
- **accepted:** renderiza `VideoGrid` + `VideoControls`; `useSfu.connect()` já foi chamado
- **rejected/ended:** toast + retorno ao estado idle
- **error:** mensagem de erro + botão retry

### `Doctor/VideoCall.vue` — estados de UI

- **idle:** lista de consultas do dia com appointments elegíveis (status `scheduled`/`rescheduled`/`in_progress` dentro da janela de tempo)
- **requesting:** spinner após clicar "Iniciar chamada"
- **ringing:** "Aguardando paciente aceitar..."
- **accepted:** `VideoGrid` + `VideoControls`
- **rejected:** toast "Paciente recusou a chamada"
- **ended:** retorno ao idle

### Rotas Inertia

| Método | Rota                  | Controller                                     | Componente              |
| ------ | --------------------- | ---------------------------------------------- | ----------------------- |
| GET    | `/doctor/video-call`  | `DoctorVideoCallController@index`              | `Doctor/VideoCall.vue`  |
| GET    | `/patient/video-call` | `PatientVideoCallController@index` (existente) | `Patient/VideoCall.vue` |

**Arquivo de rotas:** adicionar em `routes/web/doctor.php`:

```
Route::get('video-call', [DoctorVideoCallController::class, 'index'])->name('video-call');
```

### DoctorVideoCallController

`app/Http/Controllers/Doctor/DoctorVideoCallController.php`

- `index()` → busca appointments do médico no dia/janela de tempo elegíveis; retorna Inertia com `{ appointments: [...] }`.
- Mesma lógica de janela de tempo de `PatientVideoCallController` mas filtrada pelo doctor_id do usuário autenticado.

### Wayfinder

Após criar `CallController` e `DoctorVideoCallController`, executar:

```
php artisan wayfinder:generate
```

Isso gera automaticamente os arquivos de action TypeScript em `resources/js/actions/`.

---

## Banco de dados

Sem novas migrations necessárias. Modelos `Call` e `Room` já existem com estrutura completa.

### Índices necessários (verificar se existem)

| Tabela  | Coluna(s)                  | Motivo                                                   |
| ------- | -------------------------- | -------------------------------------------------------- |
| `calls` | `appointment_id`, `status` | filtro de chamadas ativas por appointment (idempotência) |
| `calls` | `doctor_id`, `status`      | listagem de chamadas do médico                           |
| `calls` | `patient_id`, `status`     | listagem de chamadas do paciente                         |
| `rooms` | `call_id`                  | lookup de Room por Call (já tem FK implícita)            |

---

## Observabilidade

| O que logar                | Nível     | Contexto                                 |
| -------------------------- | --------- | ---------------------------------------- |
| Chamada criada             | `info`    | `call_id`, `appointment_id`, `caller_id` |
| Chamada aceita             | `info`    | `call_id`, `room_id`, `appointment_id`   |
| Chamada encerrada          | `info`    | `call_id`, `room_id`, `duration_seconds` |
| Falha ao criar sala no SFU | `error`   | `call_id`, `exception`, `sfu_http_url`   |
| JWT secret ausente         | `error`   | `call_id`                                |
| Sala destruída (zombie)    | `warning` | `room_id`, `call_id`, `inactive_since`   |

`CallManagerService` já loga `CALL_CREATED`, `CALL_ACCEPTED`, `CALL_ENDED`, `ROOM_CREATED`, `ROOM_LEFT`. Nenhuma alteração necessária no service.

---

## Segurança

- **Auth:** middleware `auth` em todas as rotas de ação
- **Autorização:** `VideoCallPolicy` em cada ação do controller via `$this->authorize()`
- **Token JWT:** gerado server-side com `SFU_JWT_SECRET`; TTL de 5 min; não expõe `roomId` no broadcastWith — apenas dentro do token
- **Canal Reverb:** `video-call.{userId}` é canal privado — `channels.php` já valida `user->id === $id`
- **Idempotência:** verificar chamada ativa antes de criar duplicata (retornar 409)
- **Rate limiting:** `throttle:10,1` em todas as ações de chamada
- **CSRF:** protegido automaticamente por Inertia (header `X-CSRF-TOKEN`)
- **MediaGatewayStub:** disponível para ambiente de desenvolvimento sem mediasoup-server rodando; binding automático via `AppServiceProvider`

---

## Edge Cases

1. Paciente clica "iniciar" duas vezes → segundo POST retorna 409 "Chamada já em andamento"
2. Médico encerra chamada enquanto paciente ainda está conectando → `VideoCallEnded` chega via Reverb; `useSfu.disconnect()` trata WS não conectado gracefully
3. `SFU_JWT_SECRET` ausente em produção → `CallManagerService::generateRoomToken()` lança `RuntimeException`; controller captura e retorna HTTP 500 + loga; frontend exibe toast "Erro interno — tente novamente"
4. mediasoup-server offline → `MediaGatewayHttp::createRoom()` falha; sem `MediaGatewayStub` em produção → HTTP 502 do controller; `acceptCall` falha; status da chamada não transita para `accepted`
5. Token expirado (usuário demorou >5 min para entrar na sala) → mediasoup-server rejeita `join`; `useSfu` seta `connectionState = 'failed'`; `useVideoCall` exibe toast + fallback para `callState = 'error'` com opção de reentrar (novo accept necessário)
6. Reverb desconectado (paciente em rede instável) → Echo reconecta automaticamente; se chamada foi aceita antes da queda, estado é recuperado via GET `/calls/{call}` (polling de fallback no `useVideoCall`)
7. Usuário fecha aba durante chamada → `beforeunload` chama `useSfu.disconnect()` + POST `/calls/{call}/end`; se falhar, `EndZombieVideoCalls` limpa a sala após `room_inactive_minutes`
8. Race condition: dois POSTs de `accept` simultâneos → o segundo encontra `call.status = 'accepted'` e retorna 422 "Chamada não está em estado solicitado ou tocando" (já tratado no `CallManagerService::acceptCall`)

---

## Riscos técnicos

| Risco                                                                 | Probabilidade | Impacto | Mitigação                                                                                                   |
| --------------------------------------------------------------------- | ------------- | ------- | ----------------------------------------------------------------------------------------------------------- |
| mediasoup-client WebRTC handshake diferente do esperado pelo servidor | Alta          | Alto    | Testar com `VideoTest.vue` (já existe como dev page) antes de integrar na UI principal                      |
| Echo/@laravel/echo-vue API diferente de `window.Echo` puro            | Média         | Médio   | Verificar versão `laravel-echo ^2.2.0`; usar `useListen` do `@laravel/echo-vue` ou instância manual         |
| BROADCAST_CONNECTION=log em produção (silencia eventos)               | Alta          | Alto    | Checklist de deploy: validar `.env` com BROADCAST*CONNECTION=reverb e REVERB*\* preenchidos                 |
| token_ttl_minutes=5 muito curto se Reverb demorar                     | Média         | Médio   | Aumentar para 15 min em `telemedicine.video_call.token_ttl_minutes` ou reemitir token via endpoint dedicado |
| SFU_JWT_SECRET não sincronizado entre Laravel e mediasoup-server      | Média         | Alto    | Documentar que ambos devem usar o mesmo valor; considerar health check no AppServiceProvider                |
| Vazamento de stream local (tracks não fechadas)                       | Média         | Médio   | `useSfu.disconnect()` deve chamar `track.stop()` em todos os tracks do `localStream`                        |
| `EndZombieVideoCalls` não registrado no scheduler                     | Baixa         | Médio   | Verificar `routes/console.php` na implementação                                                             |

---

## Plano de implementação

Ordenado por dependência técnica:

1. `[Infra]` Preencher `.env` com `BROADCAST_CONNECTION=reverb` e `REVERB_*` — pré-requisito para tudo
2. `[Infra]` Verificar/adicionar bloco `media_gateway` em `config/services.php`
3. `[Backend]` `StoreCallRequest` com validação de `appointment_id` + verificação de chamada ativa (409)
4. `[Backend]` `CallController` com 5 métodos delegando a `CallManagerService`
5. `[Backend]` Adicionar rotas em `routes/web/shared.php` (ou split patient/doctor) com throttle
6. `[Backend]` `DoctorVideoCallController@index` com lógica de janela de tempo
7. `[Backend]` Adicionar rota GET `/doctor/video-call` em `routes/web/doctor.php`
8. `[Backend]` Verificar índices DB nas tabelas `calls` e `rooms`
9. `[Backend]` Verificar `EndZombieVideoCalls` registrado no scheduler em `routes/console.php`
10. `[Frontend]` Rodar `php artisan wayfinder:generate` após criar controllers
11. `[Frontend]` `useSfu.ts` — conexão WS raw + mediasoup-client WebRTC
12. `[Frontend]` `useVideoCall.ts` — estado + Echo listeners + ações HTTP
13. `[Frontend]` `VideoGrid.vue` + `VideoControls.vue` — componentes de vídeo
14. `[Frontend]` `IncomingCallModal.vue` — modal de chamada entrante
15. `[Frontend]` Reescrever `Patient/VideoCall.vue` com UI de vídeo real
16. `[Frontend]` Criar `Doctor/VideoCall.vue` com lista de consultas + UI de vídeo
17. `[Testes]` Unit: `CallController` → ações delegadas corretamente
18. `[Testes]` Feature: fluxo completo create → accept → end com MediaGatewayStub
19. `[Testes]` Manual: testar com mediasoup-server local + dois browsers

---

## Checklist

### Backend

- [ ] `BROADCAST_CONNECTION=reverb` e `REVERB_*` configurados
- [ ] `config/services.media_gateway` com todas as keys
- [ ] `StoreCallRequest` com regra de idempotência (409)
- [ ] `CallController` sem lógica de negócio — apenas delegação + autorização
- [ ] Rotas com middleware `auth` e `throttle:10,1`
- [ ] `DoctorVideoCallController` com janela de tempo correta
- [ ] Índices DB verificados
- [ ] `EndZombieVideoCalls` no scheduler

### Frontend

- [ ] `useSfu.ts` com tipagem TypeScript completa (states, methods)
- [ ] `useVideoCall.ts` com Echo listeners para todos os 4 eventos
- [ ] Fallback de polling GET `/calls/{call}` se Reverb desconectar
- [ ] `VideoGrid.vue` com `<video autoplay playsinline>` (sem `controls`)
- [ ] `IncomingCallModal.vue` com botões aceitar/rejeitar acessíveis
- [ ] `beforeunload` handler para cleanup de stream e chamada
- [ ] `useSfu.disconnect()` chama `track.stop()` em todas as tracks
- [ ] Loading/erro/vazio/sucesso em todas as ações
- [ ] `useToast` para feedback ao usuário
- [ ] Props tipadas com interfaces TypeScript
- [ ] `php artisan wayfinder:generate` executado após novos controllers

### Qualidade

- [ ] Testes de feature com `MediaGatewayStub` (sem mediasoup-server real)
- [ ] Sem query N+1 no `DoctorVideoCallController` (eager load appointments)
- [ ] `php artisan test` passando
