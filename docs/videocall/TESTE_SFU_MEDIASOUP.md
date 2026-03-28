# Guia de Teste do Servidor SFU MediaSoup

Documento técnico para **testar o servidor SFU baseado em MediaSoup** do projeto. O guia descreve o **modo integrado ao Laravel** (recomendado) e, em seguida, o modo standalone para testes totalmente desacoplados.

---

## 0. Modo integrado ao Laravel (recomendado)

A aplicação oferece uma **página de teste do SFU integrada ao Laravel**, sem autenticação e sem regras de negócio de consulta. O usuário acessa um link (por exemplo via **QR Code**), entra na chamada e vê outros participantes; os **logs de debug** são gerados no **Laravel** e no **mediasoup-server**.

### Fluxo do usuário

1. **Acessar o link** — por exemplo `https://seu-dominio.com/sfu-test` (ou o mesmo em rede local).
2. **Entrar na sala** — clicar em “Entrar na sala” na página (token e sala são obtidos automaticamente pelo Laravel).
3. **Iniciar câmera** — clicar em “Iniciar câmera” para enviar áudio/vídeo.
4. **Ver outros participantes** — cada participante que inicia a câmera aparece na área “Participantes remotos” dos demais.
5. **Logs** — na própria página (área “Logs de debug”), no console do navegador, nos logs do Laravel e nos logs do processo MediaSoup.

### O que o Laravel faz (e o que não faz)

- **Faz:** servir a rota `GET /sfu-test` (sem middleware de autenticação), garantir que a sala de teste existe no SFU (via API HTTP do MediaSoup), gerar um token JWT por acesso e injetar `token` e `sfuWsUrl` na view. Registra logs como `SFU_TEST_PAGE_VIEWED`, `SFU_TEST_ROOM_READY`, `SFU_TEST_TOKEN_ISSUED`.
- **Não exige:** login, consulta agendada, aceite de chamada, papéis médico/paciente nem nenhuma regra de negócio. Qualquer pessoa com o link pode abrir a página e entrar na mesma sala de teste.

### Como executar (modo integrado)

1. **Configurar o SFU** no Laravel (`.env`):
   - `SFU_HTTP_URL` (ex.: `http://127.0.0.1:3100`)
   - `SFU_WS_URL` (ex.: `ws://127.0.0.1:4444` ou `http://127.0.0.1:4444`)
   - `SFU_API_SECRET` e `SFU_JWT_SECRET` (mesmos valores usados no `mediasoup-server`)

2. **Usar o Media Gateway HTTP** no Laravel (quando `SFU_HTTP_URL` e `SFU_API_SECRET` estão definidos, o `AppServiceProvider` já usa `MediaGatewayHttp`).

3. **Subir o servidor MediaSoup** (em `mediasoup-server/`): `npm run dev` com as mesmas variáveis.

4. **Subir o Laravel** e acessar `http://localhost:8000/sfu-test` (ou a URL pública do app). Executar `npm run dev` (Vite) para os assets da página de teste, ou `npm run build` em produção.

5. **Múltiplos usuários:** abrir o mesmo link em várias abas ou dispositivos; todos entram na mesma sala (`sfu_test_room`). Cada acesso gera um novo `userId` de teste e um novo token.

6. **QR Code:** gerar um QR Code para a URL da página (ex.: `https://seu-dominio.com/sfu-test`) para que vários usuários escaneiem e entrem na mesma sala.

### Estrutura no projeto (modo integrado)

- **Rota:** `GET /sfu-test` em `routes/web.php` (sem auth).
- **Controller:** `App\Http\Controllers\SfuTestController` — chama `SfuTestRoomService`, passa `token`, `sfuWsUrl` e `roomId` para a view.
- **Serviço:** `App\Services\SfuTestRoomService` — `ensureTestRoom()` (cria/garante a sala no SFU via HTTP), `issueTestToken()` (emite JWT para usuário de teste).
- **View:** `resources/views/sfu-test/index.blade.php` — página com vídeos local/remotos, botões e área de log; configura `window.__SFU_TEST_CONFIG__` e carrega o bundle Vite do cliente.
- **Cliente:** `resources/js/sfu-test-app.js` — conecta ao WebSocket, faz join com o token, cria transports, produce/consume e exibe logs.

### Logs gerados

- **Laravel:** `SFU_TEST_PAGE_VIEWED`, `SFU_TEST_ROOM_READY`, `SFU_TEST_TOKEN_ISSUED` (e possíveis exceções).
- **mediasoup-server:** `PEER_JOINED`, `PRODUCER_CREATED`, `ROOM_CREATED` (se a sala for criada na primeira vez), etc.
- **Navegador:** console e a área “Logs de debug” na própria página.

---

## 1. Visão Geral

### O que é o SFU

O **SFU (Selective Forwarding Unit)** é o servidor de mídia que:

- Recebe streams de áudio e vídeo dos participantes (**producers**).
- Encaminha seletivamente esses streams para os demais participantes (**consumers**).
- Utiliza **WebRTC** para transporte de mídia e **WebSocket** para sinalização (negociação de conexão, criação de transports, producers e consumers).

No projeto, o SFU é implementado pelo serviço em `mediasoup-server/`, que expõe:

- **WebSocket** (porta padrão `4443`) para sinalização de mídia com os clientes.
- **API HTTP** (porta padrão `3000`) para criar e destruir salas (usada pelo backend ou por scripts de teste).

### Objetivo do ambiente de testes

- **Testar o funcionamento do MediaSoup SFU** (com ou sem o Laravel).
- **Validar fluxo de mídia** producer/consumer e conexão WebRTC.
- **Testar a comunicação com o servidor de sinalização** (WebSocket).
- **Permitir debugging** do sistema de videochamada (logs, estados ICE, bitrate).
- **Validar em ambiente de deploy** (URL pública, múltiplos dispositivos).
- **Facilitar entrada na sala via QR Code** para testes com vários usuários.

### O que a página de teste NÃO exige

Na página de teste (integrada ou standalone) **não são usadas**:

- Autenticação do sistema principal (login/sessão Laravel) na rota da página.
- Permissões ou políticas de negócio (papéis, consultas, agendamentos) para entrar na sala de teste.
- Fluxo de Call (request/accept/reject/end) nem banco de dados (`calls`, `rooms`, `appointments`) para a sala de teste.

No **modo integrado**, o Laravel apenas garante a sala no SFU, emite o token e serve a página; no **modo standalone**, um script gera o token e a página é servida estaticamente.

---

## 2. Objetivo do Documento

Este documento serve como **guia oficial de testes do SFU** e permite que:

1. Desenvolvedores **testem o MediaSoup de forma isolada**.
2. Se **valide** o fluxo producer/consumer e a conexão WebRTC.
3. Se **teste** a comunicação com o servidor de sinalização.
4. Se **faça debugging** da videochamada (logs, métricas, problemas comuns).
5. **Qualquer IA** consiga, a partir das instruções aqui descritas, gerar a página de teste, conectar ao MediaSoup e executar testes com múltiplos usuários e logs úteis.

Requisitos da página de teste:

- Ser **totalmente desacoplada** do sistema principal.
- **Não depender** de autenticação do Laravel nem do frontend principal.
- Ser uma **página standalone** que apenas conecta ao servidor MediaSoup SFU.
- Permitir **múltiplos usuários simultâneos** (várias abas ou dispositivos).
- Gerar **logs de conexão** visíveis no navegador e, quando possível, no servidor.

---

## 3. Estratégia de Teste

Será criada uma **página web simples e isolada** que:

1. Conecta diretamente ao **WebSocket do servidor de sinalização** do MediaSoup.
2. Usa um **token JWT** obtido por um script de teste (mesmo segredo do SFU), sem Laravel.
3. Implementa apenas o necessário para:
   - Estabelecer conexão WebRTC com o SFU.
   - Enviar áudio/vídeo (producer).
   - Receber streams de outros participantes (consumer).
   - Exibir logs no próprio navegador.

Essa página funciona como **laboratório de testes da infraestrutura de vídeo**, sem regras de negócio.

---

## 4. Arquitetura do Ambiente de Teste

### Diagrama simplificado

```text
Usuário (Browser)
       │
       ▼
Página Web de Teste (HTML + JS)
       │
       │  WebSocket (sinalização)
       ▼
Servidor Node.js MediaSoup (WS + HTTP)
       │
       │  WebRTC (mídia)
       ◄────────────────────────────►
       │
       ▼
Logs / Monitoramento (console, webrtc-internals)
```

### Fluxo de conexão

1. **Criar sala (opcional para teste):** script ou `curl` chama `POST /rooms` com `SFU_API_SECRET` e obtém `room_id`.
2. **Gerar token:** script gera JWT (HS256) com `callId`, `roomId`, `userId`, `role`, `exp` usando `SFU_JWT_SECRET`.
3. **Cliente:** abre a página de teste, informa URL do WebSocket e o token (ou usa valores padrão).
4. **Conexão:** página abre WebSocket, envia `{ action: 'join', token: '<JWT>' }`.
5. **SFU:** valida JWT, associa o peer à sala, retorna `rtpCapabilities`.
6. **Transports:** cliente pede `createWebRtcTransport` (send e recv), depois `connectWebRtcTransport` com `dtlsParameters`.
7. **Producer:** cliente captura mídia (getUserMedia), produz com `produce`; SFU notifica outros peers com `newProducer`.
8. **Consumer:** ao receber `newProducer`, cada peer envia `consume` e depois `resumeConsumer`; o stream remoto é exibido no `<video>`.

Os logs podem ser visualizados no **console do navegador**, nos **logs do servidor MediaSoup** e em **chrome://webrtc-internals**.

---

## 5. Estrutura do Ambiente de Teste

Recomenda-se organizar os arquivos em um diretório dedicado, **independente** do frontend principal do projeto.

### Estrutura recomendada

```text
tools/
  mediasoup-test-client/
    index.html      # Página com vídeos, botões e área de log
    app.js          # Lógica WebSocket + MediaSoup client
    styles.css      # Estilos mínimos
    README.md       # Instruções rápidas (opcional)

    scripts/
      generate-token.js   # Script Node para criar sala e gerar JWT (ver seção 6)
```

Alternativa:

```text
tests/
  mediasoup/
    index.html
    client.js
    style.css
```

Este ambiente **não** faz parte do build do frontend (Vue/Vite) e é servido estaticamente (por exemplo com `npx serve`, `python -m http.server` ou `php -S`).

---

## 6. Token JWT para Testes (sem Laravel)

O SFU exige um **token JWT** válido na ação `join`. Em produção o token é emitido pelo Laravel após aceite da chamada. Para testes isolados, use um **script Node** que:

1. Cria uma sala via `POST /rooms` (com `SFU_API_SECRET`).
2. Gera um JWT com o mesmo `SFU_JWT_SECRET`, contendo `callId`, `roomId`, `userId`, `role`, `exp`.

O payload do JWT deve conter (nomes aceitos pelo servidor: `callId` ou `call_id`, `roomId` ou `room_id`, `userId` ou `user_id`, `role`):

```json
{
  "callId": "call_test_001",
  "roomId": "<room_id retornado pela API>",
  "userId": "user_1",
  "role": "doctor",
  "iat": 1234567890,
  "exp": 1234567890
}
```

### Exemplo de script: `scripts/generate-token.js`

```javascript
// Uso: node scripts/generate-token.js
// Múltiplos usuários: ROOM_ID=<id> TEST_USER_ID=user_2 node scripts/generate-token.js
// Requer: npm install jsonwebtoken node-fetch (ou use fetch nativo no Node 18+)
const jwt = require('jsonwebtoken');
const fetch = require('node-fetch');

const HTTP_URL = process.env.SFU_HTTP_URL || 'http://127.0.0.1:3100';
const WS_URL = process.env.SFU_WS_URL || 'ws://127.0.0.1:4444';
const API_SECRET = process.env.SFU_API_SECRET || 'dev-api-secret';
const JWT_SECRET = process.env.SFU_JWT_SECRET || 'dev-secret';
const USER_ID = process.env.TEST_USER_ID || `user_${Date.now()}`;
const ROOM_ID = process.env.ROOM_ID || null;

async function main() {
  const body = ROOM_ID
    ? { callId: 'call_test', roomId: ROOM_ID }
    : { callId: 'call_test' };

  const res = await fetch(`${HTTP_URL}/rooms`, {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${API_SECRET}`,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify(body)
  });
  const data = await res.json();
  if (!data.room_id) throw new Error('Falha ao criar/obter sala: ' + JSON.stringify(data));

  const roomId = data.room_id;
  const payload = {
    callId: 'call_test',
    roomId,
    userId: USER_ID,
    role: 'doctor',
    iat: Math.floor(Date.now() / 1000),
    exp: Math.floor(Date.now() / 1000) + 3600
  };
  const token = jwt.sign(payload, JWT_SECRET, { algorithm: 'HS256' });

  console.log('WS_URL=', WS_URL);
  console.log('TOKEN=', token);
  console.log('roomId=', roomId);
}

main().catch((e) => { console.error(e); process.exit(1); });
```

O desenvolvedor (ou uma IA) executa este script com as mesmas variáveis de ambiente do servidor MediaSoup e cola **TOKEN** e **WS_URL** na página de teste.

**Múltiplos usuários na mesma sala:** execute o script uma vez para o primeiro usuário (sem `ROOM_ID`); anote o `roomId` impresso. Para o segundo usuário, execute: `ROOM_ID=<room_id_anotado> TEST_USER_ID=user_2 node scripts/generate-token.js`. Use o novo token na segunda aba ou dispositivo; ambos estarão na mesma sala.

---

## 7. Criação da Página Web de Teste

### Requisitos da página

- **HTML simples** com elementos para vídeo local, vídeo remoto, botões e área de log.
- **JavaScript** com conexão direta ao WebSocket do MediaSoup e uso da biblioteca **mediasoup-client** (via CDN ou build).
- **Sem dependência** do frontend principal, Laravel ou autenticação.

### Elementos básicos (HTML)

```html
<!-- Vídeo local (saída da câmera/microfone) -->
<video id="localVideo" autoplay muted playsinline></video>

<!-- Vídeo remoto (stream recebido do SFU) -->
<video id="remoteVideo" autoplay playsinline></video>

<!-- Controles -->
<button id="joinRoom">Entrar na sala</button>
<button id="startCamera">Iniciar câmera</button>
<button id="shareScreen">Compartilhar tela</button>
<button id="leaveRoom">Sair</button>

<!-- Configuração (URL do WS e token) -->
<input id="wsUrl" type="text" placeholder="ws://localhost:4444" />
<input id="tokenInput" type="text" placeholder="Token JWT" />

<!-- Console de logs na própria página -->
<div id="logContainer"></div>
```

### Propósito de cada elemento

| Elemento | Propósito |
|----------|-----------|
| `#localVideo` | Exibir o stream da câmera/microfone local (produzido pelo próprio cliente). |
| `#remoteVideo` | Exibir o stream recebido de outro participante (consumer). Em múltiplos participantes, pode ser uma lista de `<video>`. |
| `#joinRoom` | Abrir WebSocket e enviar `join` com o token; receber `rtpCapabilities`. |
| `#startCamera` | Obter getUserMedia, criar send transport, produce de áudio e vídeo. |
| `#shareScreen` | Opcional: getDisplayMedia e produzir outro track de vídeo (compartilhamento de tela). |
| `#leaveRoom` | Enviar `leave`, fechar transports e conexão WebSocket. |
| `#wsUrl` / `#tokenInput` | Permitir informar URL do WebSocket e token JWT (ex.: gerado pelo script acima). |
| `#logContainer` | Mostrar logs de conexão, erros e eventos (peer joined, producer created, consumer created, etc.). |

A página deve permitir **entrada automática em uma sala** (ao clicar em “Entrar na sala”) e **reconexão** em caso de falha (opcional: botão “Reconectar” ou retry automático).

---

## 8. Cliente JavaScript de Teste (app.js / client.js)

O cliente é responsável por:

1. Conectar ao WebSocket do servidor.
2. Inicializar **mediasoup-client** (Device, transports, produce/consume).
3. Tratar mensagens de sinalização (request/response com `id`).
4. Exibir logs de debug na página e no console.

### Passo a passo lógico

1. **Conexão com o servidor de sinalização**  
   - `new WebSocket(wsUrl)`; ao abrir, enviar `join` com o token.  
   - Tratar resposta: guardar `peerId`, `roomId`, `rtpCapabilities`.

2. **Inicializar Device**  
   - `const device = new mediasoupClient.Device();`  
   - `await device.load({ routerRtpCapabilities: rtpCapabilities });`

3. **Obter router RTP capabilities**  
   - Já recebidas no `join`; usar em `device.load()`.

4. **Criar send transport**  
   - Enviar `{ action: 'createWebRtcTransport', direction: 'send', id: reqId }`.  
   - Com a resposta, criar no cliente:  
     `const sendTransport = device.createSendTransport({ id, iceParameters, iceCandidates, dtlsParameters });`  
   - Conectar: enviar `connectWebRtcTransport` com `transportId` e `dtlsParameters` (obtidos no evento `connect` do transport).

5. **Criar receive transport**  
   - Enviar `{ action: 'createWebRtcTransport', direction: 'recv', id: reqId }`.  
   - Criar no cliente: `device.createRecvTransport(...)`.  
   - Conectar com `connectWebRtcTransport` (evento `connect` do recv transport).

6. **Criar producer**  
   - getUserMedia → `getTracks()`.  
   - Para cada track: `sendTransport.produce({ track, ... })` → obtém `rtpParameters`.  
   - Enviar `{ action: 'produce', kind, rtpParameters, appData, id: reqId }`.  
   - Guardar `producer.id` retornado pelo servidor.

7. **Consumir mídia**  
   - Ao receber mensagem do servidor `action: 'newProducer'` com `producerId`, `peerId`, `kind`:  
     - Enviar `{ action: 'consume', producerId, rtpCapabilities: device.rtpCapabilities, id: reqId }`.  
   - Na resposta, criar consumer no cliente:  
     `recvTransport.consume({ id, producerId, kind, rtpParameters, type })`.  
   - Anexar `consumer.track` ao `<video id="remoteVideo">` (ou elemento correspondente).  
   - Enviar `resumeConsumer` com o `consumerId` para o servidor.

8. **Heartbeat**  
   - Ao receber `action: 'ping'`, responder com `{ action: 'pong' }`.

9. **Logs**  
   - Para cada evento relevante (conectado, transport criado, producer criado, consumer criado, erro), adicionar linha em `#logContainer` e `console.log`.

### Exemplo de esqueleto de mensagens (cliente → servidor)

- `{ action: 'join', token: '<JWT>' }`
- `{ action: 'getRouterRtpCapabilities', id: 1 }` (opcional; join já retorna capabilities)
- `{ action: 'createWebRtcTransport', direction: 'send', id: 2 }`
- `{ action: 'createWebRtcTransport', direction: 'recv', id: 3 }`
- `{ action: 'connectWebRtcTransport', transportId: '...', dtlsParameters: {...}, id: 4 }`
- `{ action: 'produce', kind: 'video', rtpParameters: {...}, appData: {}, id: 5 }`
- `{ action: 'consume', producerId: '...', rtpCapabilities: {...}, id: 6 }`
- `{ action: 'resumeConsumer', consumerId: '...', id: 7 }`
- `{ action: 'leave', id: 8 }`
- `{ action: 'pong' }` (resposta ao ping do servidor)

Todas as respostas do servidor incluem `id` (quando houve request com `id`), `ok: true|false` e `data` ou `error`.

---

## 9. Fluxo Completo de Teste (diagrama)

```text
Usuário abre index.html
        │
        ▼
Preenche WS URL e Token (ou usa padrões)
        │
        ▼
Clica "Entrar na sala"
        │
        ▼
Conecta WebSocket ──────────────────────────► Servidor MediaSoup
        │                                              │
        │  { action: 'join', token }                   │
        │ ───────────────────────────────────────────►│
        │                                              │ valida JWT
        │  { ok: true, data: { rtpCapabilities, ... } }│
        │ ◄───────────────────────────────────────────│
        │                                              │
        ▼                                              │
device.load(rtpCapabilities)                            │
        │                                              │
        ▼                                              │
createWebRtcTransport (send) ──────────────────────────►│
createWebRtcTransport (recv) ──────────────────────────►│
connectWebRtcTransport (send + recv) ─────────────────►│
        │                                              │
        ▼                                              │
Clica "Iniciar câmera"                                 │
getUserMedia → produce (audio + video) ────────────────►│
        │                                              │
        │                          newProducer ────────► outros peers
        │                                              │
        ▼                                              │
Outro cliente recebe newProducer                        │
        │                                              │
        │  consume → resumeConsumer                     │
        │ ────────────────────────────────────────────►│
        ▼                                              │
Vídeo remoto aparece em #remoteVideo
```

---

## 10. Como Executar os Testes

### Passo 1 — Iniciar o servidor MediaSoup

No diretório `mediasoup-server/`:

```bash
cd mediasoup-server
npm i
export SFU_JWT_SECRET='dev-secret'
export SFU_API_SECRET='dev-api-secret'
export SFU_HTTP_PORT=3100
export SFU_WS_PORT=4444
npm run dev
```

(As portas podem ser alteradas; use as mesmas no script de token e na página.)

### Passo 2 — Gerar token (e criar sala)

Em outro terminal, com as mesmas variáveis:

```bash
export SFU_HTTP_URL=http://127.0.0.1:3100
export SFU_WS_URL=ws://127.0.0.1:4444
export SFU_API_SECRET=dev-api-secret
export SFU_JWT_SECRET=dev-secret
node tools/mediasoup-test-client/scripts/generate-token.js
```

Copie o valor de `TOKEN=` e `WS_URL=` para a página (ou use os padrões se a página já estiver configurada para `ws://127.0.0.1:4444`).

### Passo 3 — Servir a página de teste

Na pasta onde está `index.html` (ex.: `tools/mediasoup-test-client/`):

```bash
npx serve .
# ou
python -m http.server 8000
# ou
php -S localhost:8000
```

### Passo 4 — Abrir no navegador

- Acesse `http://localhost:8000` (ou a porta usada).
- Cole o token e a URL do WebSocket se necessário.
- Clique em **Entrar na sala**.
- Clique em **Iniciar câmera**.
- Abra **outra aba** (ou outro navegador/dispositivo) na mesma URL, use o **mesmo token** (ou gere outro token para a **mesma room_id** e outro `userId`) e repita. Os dois devem se ver.

Para simular múltiplos usuários: abra várias abas em `http://localhost:8000` e entre na mesma sala (mesmo `roomId` no token).

---

## 11. Cenários de Teste Recomendados

| Cenário | Passos | O que validar |
|---------|--------|----------------|
| **1 — Conexão básica** | Dois usuários entram na sala e iniciam câmera. | Ambos veem o vídeo um do outro; áudio ouvido. |
| **2 — Múltiplos participantes** | 3 ou mais usuários na mesma sala. | Cada um vê os vídeos dos outros; sem travamentos. |
| **3 — Desligar câmera** | Um usuário desliga a câmera (stop track ou leave). | Producer encerrado; o outro deixa de receber o stream (ou recebe evento de fechamento). |
| **4 — Reconexão** | Um usuário sai e entra novamente na mesma sala. | Novo peer; producers/consumers recriados; vídeo volta a funcionar. |
| **5 — Troca de câmera** | Trocar dispositivo de vídeo (outro track). | Novo producer ou replace track; fluxo atualizado. |
| **6 — Compartilhamento de tela** | Clicar em “Compartilhar tela”. | Segundo producer de vídeo; outros consomem a tela. |

---

## 12. Logs e Debug

### Onde observar

- **Console do navegador (F12):** logs do `app.js` (conexão, transport, producer, consumer, erros).
- **Área de log na página (#logContainer):** mesmos eventos em texto visível.
- **Terminal do servidor MediaSoup:** logs como `PEER_JOINED`, `PRODUCER_CREATED`, `ROOM_CREATED`, erros de token ou transporte.
- **WebSocket:** no DevTools → Aba *Network* → WS → mensagens enviadas/recebidas.

### chrome://webrtc-internals

1. Abra no Chrome: `chrome://webrtc-internals`
2. Deixe a aba aberta enquanto usa a página de teste.
3. Verifique estatísticas WebRTC: ICE candidates, connection state, bitrate, codecs.

Útil para diagnosticar falhas de ICE, NAT ou banda.

---

## 13. Problemas Comuns (Troubleshooting)

| Sintoma | Possível causa | O que fazer |
|---------|----------------|-------------|
| **Câmera não abre** | Permissões do navegador; HTTPS em produção. | Conferir permissões do site; usar HTTPS para getUserMedia em produção; testar em localhost. |
| **Conexão WebSocket falha** | URL errada; SFU parado; CORS não é problema para WS. | Verificar `ws://` ou `wss://`, porta e se o processo do MediaSoup está rodando. |
| **Token inválido / 401** | JWT expirado, secret diferente, payload incorreto. | Usar o mesmo `SFU_JWT_SECRET` no script e no servidor; conferir `callId`, `roomId`, `userId` e `exp`. |
| **Vídeo não aparece (remoto)** | Consumer não criado ou não anexado ao `<video>`; track não reproduzido. | Verificar se, ao receber `newProducer`, o cliente chama `consume` e `resumeConsumer` e atribui `consumer.track` ao elemento de vídeo. |
| **ICE failed / conexão WebRTC não estabelecida** | Firewall; NAT; portas UDP bloqueadas. | Garantir que as portas RTC do SFU (ex.: 40000–49999) estão abertas; em produção, considerar TURN e `announcedIp`. |
| **Sala não existe** | Join com `roomId` que não foi criado. | Criar sala antes com `POST /rooms` (script de token já faz isso) ou usar `roomId` retornado pela API. |

---

## 14. Benefícios Dessa Abordagem

- Testar o **SFU isoladamente**, sem subir Laravel nem frontend Vue.
- **Debugar** problemas de WebRTC e de sinalização com logs claros.
- **Validar** mudanças no servidor MediaSoup (novas versões, configurações).
- **Testar antes de integrar** ao Laravel (fluxo completo de mídia).
- **Acelerar** desenvolvimento e homologação da videochamada.
- **Reutilizável** por qualquer desenvolvedor ou IA a partir deste guia.

---

## 15. Teste com Múltiplos Usuários

- **Múltiplas abas:** mesma máquina, várias abas em `http://localhost:8000`; usar tokens com o **mesmo roomId** e **userId** diferente (ex.: `user_1`, `user_2`).
- **Múltiplos navegadores:** Chrome, Firefox, Edge na mesma sala.
- **Múltiplos dispositivos:** servir a página em um IP acessível na rede (ex.: `python -m http.server 8000 --bind 0.0.0.0`) e acessar pelo celular/tablet; em produção usar HTTPS e `wss://`.

Sugestão de carga simples: testar com 2, 5 e 10 usuários na mesma sala e observar CPU do servidor, banda e estabilidade.

---

## 16. Teste em Deploy

- Colocar a pasta da página de teste em um servidor estático (ex.: mesmo domínio em `/sfu-test/` ou subdomínio).
- Configurar **HTTPS** e **WSS** para o MediaSoup.
- Acessar via **URL pública** e compartilhar com a equipe.
- Em ambientes com NAT/firewall, configurar `SFU_ANNOUNCED_IP` e, se necessário, servidor TURN.

---

## 17. Geração de QR Code para Testes

Para facilitar testes com vários usuários (ex.: celulares):

1. **Deploy** da página de teste em uma URL pública (ex.: `https://meudominio.com/sfu-test/`).
2. **Gerar QR Code** dessa URL (sites ou libs como `qrcode` no Node).
3. Usuários **escaneiam** o QR Code e abrem a página.
4. Todos entram na **mesma sala** (token pode ser fixo em modo teste ou gerado por um backend mínimo que retorna token para a mesma room).
5. **Logs** são gerados no navegador e, no servidor, no processo do MediaSoup.

Isso permite validar rapidamente o SFU em rede real (Wi‑Fi, 4G) e em vários dispositivos.

---

## 18. Boas Práticas para Testes de SFU

- Testar em **mais de um navegador** (Chrome, Firefox, Safari quando possível).
- Testar em **rede móvel** (4G/5G) para verificar comportamento com perda e latência.
- Testar atrás de **NATs diferentes** (escritório, casa, celular).
- **Monitorar** uso de CPU e memória do processo do MediaSoup durante testes com vários peers.
- **Monitorar** uso de banda (upload/download) por participante.
- **Validar** estabilidade do WebRTC (manter chamada por vários minutos; entrar/sair múltiplas vezes).

---

## 19. Estrutura de Arquivos Recomendada (resumo)

```text
tools/
  mediasoup-test-client/
    index.html
    app.js
    styles.css
    README.md
    scripts/
      generate-token.js
```

Ou, em formato mínimo:

```text
sfu-test/
  index.html
  app.js
  styles.css
  README.md
```

O `README.md` pode conter apenas os passos: 1) Subir MediaSoup, 2) Gerar token, 3) Servir a pasta, 4) Abrir no navegador e colar token/WS URL.

---

## 20. Melhorias Futuras para o Ambiente de Teste

- **Painel de métricas:** exibir bitrate, pacotes perdidos, RTT por transport/consumer na própria página.
- **Simulador de múltiplos usuários:** botão “Adicionar N peers” que abre N iframes ou janelas na mesma sala.
- **Testes automatizados:** Playwright/Cypress para fluxo de join → start camera → verificar vídeo remoto.
- **Gravação de stream:** opção de gravar um consumer em arquivo (MediaRecorder) para análise.
- **Testes de carga:** script que abre muitas conexões simultâneas e mede estabilidade e recursos do SFU.

---

## 21. Objetivo Final (para execução por IA)

Este documento permite que uma IA:

1. **Gere a página de teste** (HTML + JS + CSS) conforme a estrutura e o protocolo descritos.
2. **Conecte ao MediaSoup** usando WebSocket e o protocolo de mensagens (join, createWebRtcTransport, connectWebRtcTransport, produce, consume, resumeConsumer, leave, ping/pong).
3. **Execute testes** com múltiplos usuários (múltiplas abas ou tokens com mesmo roomId).
4. **Gere logs** úteis no navegador e, quando aplicável, no servidor, para análise do SFU.

A página deve permanecer **independente** do frontend principal e **sem** regras de negócio, autenticação Laravel ou banco de dados; apenas infraestrutura de mídia e sinalização com o SFU.
