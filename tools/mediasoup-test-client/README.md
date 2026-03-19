# Cliente de teste SFU MediaSoup (standalone)

Página e scripts para testar o servidor SFU MediaSoup **sem Laravel**. Use quando quiser validar apenas o MediaSoup (WebSocket, WebRTC, produce/consume).

Ver também: [TESTE_SFU_MEDIASOUP.md](../../docs/videocall/TESTE_SFU_MEDIASOUP.md) no repositório principal.

## Passos para executar

### 1. Subir o servidor MediaSoup

No diretório do projeto principal:

```bash
cd mediasoup-server
npm i
export SFU_JWT_SECRET='dev-secret'
export SFU_API_SECRET='dev-api-secret'
# Opcional: SFU_HTTP_PORT=3100 SFU_WS_PORT=4444
npm run dev
```

(Portas padrão: HTTP 3000, WebSocket 4443.)

### 2. Gerar o token (e criar sala)

Neste diretório (`tools/mediasoup-test-client/`):

```bash
npm install
npm run token
# ou: node scripts/generate-token.js
```

Use as mesmas variáveis do servidor. Exemplo para mesma máquina:

```bash
export SFU_HTTP_URL=http://127.0.0.1:3000
export SFU_WS_URL=ws://127.0.0.1:4443
export SFU_API_SECRET=dev-api-secret
export SFU_JWT_SECRET=dev-secret
node scripts/generate-token.js
```

Copie o valor de `TOKEN=` e `WS_URL=` (se precisar colar na página).

**Múltiplos usuários na mesma sala:** rode o script uma vez e anote o `roomId`. Para o segundo usuário: `ROOM_ID=<room_id_anotado> TEST_USER_ID=user_2 node scripts/generate-token.js`. Use o novo token na segunda aba.

### 3. Servir esta pasta

Ainda em `tools/mediasoup-test-client/`:

```bash
npx serve .
# ou: python -m http.server 8000
# ou: php -S localhost:8000
```

### 4. Abrir no navegador

- Acesse `http://localhost:8000` (ou a porta usada).
- Cole o **token** no campo "Token JWT".
- Ajuste a URL do WebSocket se necessário (padrão `ws://127.0.0.1:4443`).
- Clique em **Entrar na sala**.
- Clique em **Iniciar câmera**.
- Abra outra aba (ou outro dispositivo), use um token da **mesma sala** (mesmo `roomId`) e repita. Os dois devem se ver em "Participantes remotos".

## Estrutura

- `index.html` — Página com vídeos, botões e área de log.
- `app.js` — Lógica WebSocket + mediasoup-client (join, transports, produce/consume).
- `styles.css` — Estilos mínimos.
- `scripts/generate-token.js` — Cria sala e gera JWT para testes.
