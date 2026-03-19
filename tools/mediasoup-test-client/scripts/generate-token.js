// Uso: node scripts/generate-token.js
// Múltiplos usuários: ROOM_ID=<id> TEST_USER_ID=user_2 node scripts/generate-token.js
// Requer: npm install jsonwebtoken (fetch nativo no Node 18+)
const jwt = require('jsonwebtoken');

const HTTP_URL = process.env.SFU_HTTP_URL || 'http://127.0.0.1:3000';
const WS_URL = process.env.SFU_WS_URL || 'ws://127.0.0.1:4443';
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
      Authorization: `Bearer ${API_SECRET}`,
      'Content-Type': 'application/json',
    },
    body: JSON.stringify(body),
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
    exp: Math.floor(Date.now() / 1000) + 3600,
  };
  const token = jwt.sign(payload, JWT_SECRET, { algorithm: 'HS256' });

  console.log('WS_URL=', WS_URL);
  console.log('TOKEN=', token);
  console.log('roomId=', roomId);
}

main().catch((e) => {
  console.error(e);
  process.exit(1);
});
