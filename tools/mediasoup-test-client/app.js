/**
 * Cliente de teste do SFU MediaSoup — standalone (sem Laravel).
 * Lê URL do WebSocket e token dos inputs, conecta ao servidor, produz/consome mídia e exibe logs.
 */

(async function () {
  const { Device } = await import('https://esm.sh/mediasoup-client@3');
  const logEl = document.getElementById('logContainer');
  const localVideo = document.getElementById('localVideo');
  const remoteVideosContainer = document.getElementById('remoteVideos');
  const btnJoin = document.getElementById('joinRoom');
  const btnCamera = document.getElementById('startCamera');
  const btnShareScreen = document.getElementById('shareScreen');
  const btnLeave = document.getElementById('leaveRoom');
  const wsUrlInput = document.getElementById('wsUrl');
  const tokenInput = document.getElementById('tokenInput');

  let ws = null;
  let device = null;
  let sendTransport = null;
  let recvTransport = null;
  let producers = [];
  let consumers = new Map();
  let reqId = 0;
  let localStream = null;

  function log(msg, data = null) {
    const line = document.createElement('div');
    line.textContent = data
      ? `[${new Date().toISOString().slice(11, 23)}] ${msg} ${JSON.stringify(data)}`
      : `[${new Date().toISOString().slice(11, 23)}] ${msg}`;
    line.style.marginBottom = '4px';
    line.style.fontFamily = 'monospace';
    line.style.fontSize = '12px';
    if (logEl) logEl.appendChild(line);
    console.log(msg, data ?? '');
  }

  function send(msg) {
    if (ws && ws.readyState === WebSocket.OPEN) {
      ws.send(JSON.stringify(msg));
    }
  }

  function request(action, payload = {}) {
    const id = ++reqId;
    return new Promise((resolve, reject) => {
      const handler = (e) => {
        try {
          const msg = JSON.parse(e.data);
          if (msg.id !== id) return;
          ws.removeEventListener('message', handler);
          if (msg.ok) resolve(msg.data);
          else reject(new Error(msg.error?.message || 'Erro no servidor'));
        } catch (err) {
          ws.removeEventListener('message', handler);
          reject(err);
        }
      };
      ws.addEventListener('message', handler);
      send({ id, action, ...payload });
    });
  }

  async function join(token) {
    const data = await request('join', { token });
    log('JOIN ok', { peerId: data.peerId, roomId: data.roomId });
    const count = data.participantsCount ?? (data.participants?.length ?? 0);
    log('Participantes na sala: ' + count, data.participants?.map((p) => p.peerId) ?? []);
    device = new Device();
    await device.load({ routerRtpCapabilities: data.rtpCapabilities });
    log('Device loaded');

    const sendData = await request('createWebRtcTransport', { direction: 'send' });
    sendTransport = device.createSendTransport({
      id: sendData.id,
      iceParameters: sendData.iceParameters,
      iceCandidates: sendData.iceCandidates,
      dtlsParameters: sendData.dtlsParameters,
    });
    sendTransport.on('connect', async ({ dtlsParameters }, callback, errback) => {
      try {
        await request('connectWebRtcTransport', {
          transportId: sendTransport.id,
          dtlsParameters,
        });
        callback();
      } catch (e) {
        errback(e);
      }
    });
    sendTransport.on('produce', async ({ kind, rtpParameters, appData }, callback, errback) => {
      try {
        const { id } = await request('produce', {
          kind,
          rtpParameters,
          appData: appData || {},
        });
        callback({ id });
      } catch (e) {
        errback(e);
      }
    });

    const recvData = await request('createWebRtcTransport', { direction: 'recv' });
    recvTransport = device.createRecvTransport({
      id: recvData.id,
      iceParameters: recvData.iceParameters,
      iceCandidates: recvData.iceCandidates,
      dtlsParameters: recvData.dtlsParameters,
    });
    recvTransport.on('connect', async ({ dtlsParameters }, callback, errback) => {
      try {
        await request('connectWebRtcTransport', {
          transportId: recvTransport.id,
          dtlsParameters,
        });
        callback();
      } catch (e) {
        errback(e);
      }
    });

    log('Transports created');
    if (btnCamera) btnCamera.disabled = false;
    if (btnShareScreen) btnShareScreen.disabled = false;
    if (btnLeave) btnLeave.disabled = false;
  }

  async function startCamera() {
    if (!sendTransport) return;
    try {
      localStream = await navigator.mediaDevices.getUserMedia({ audio: true, video: true });
      if (localVideo) localVideo.srcObject = localStream;
      for (const track of localStream.getTracks()) {
        const producer = await sendTransport.produce({
          track,
          appData: { source: track.kind },
        });
        producers.push(producer);
        log('Producer created', { id: producer.id, kind: track.kind });
      }
    } catch (e) {
      log('getUserMedia error', e.message);
    }
  }

  async function consume(producerId, peerId, kind) {
    if (!recvTransport || !device.rtpCapabilities) return;
    try {
      const data = await request('consume', {
        producerId,
        rtpCapabilities: device.rtpCapabilities,
      });
      const consumer = await recvTransport.consume({
        id: data.id,
        producerId: data.producerId,
        kind: data.kind,
        rtpParameters: data.rtpParameters,
        type: data.type,
      });
      consumers.set(consumer.id, consumer);
      await request('resumeConsumer', { consumerId: consumer.id });
      const video = document.createElement('video');
      video.autoplay = true;
      video.playsInline = true;
      video.srcObject = new MediaStream([consumer.track]);
      video.style.width = '160px';
      video.style.margin = '4px';
      if (remoteVideosContainer) remoteVideosContainer.appendChild(video);
      log('Consumer created', { consumerId: consumer.id, producerId, kind });
    } catch (e) {
      log('Consume error', e.message);
    }
  }

  function leave() {
    producers.forEach((p) => p.close());
    producers = [];
    consumers.forEach((c) => c.close());
    consumers.clear();
    if (localStream) {
      localStream.getTracks().forEach((t) => t.stop());
      localStream = null;
    }
    if (localVideo) localVideo.srcObject = null;
    if (remoteVideosContainer) remoteVideosContainer.innerHTML = '';
    if (sendTransport) sendTransport.close();
    if (recvTransport) recvTransport.close();
    sendTransport = null;
    recvTransport = null;
    if (ws && ws.readyState === WebSocket.OPEN) {
      send({ action: 'leave' });
      ws.close();
    }
    ws = null;
    if (btnCamera) btnCamera.disabled = true;
    if (btnShareScreen) btnShareScreen.disabled = true;
    if (btnLeave) btnLeave.disabled = true;
    log('Left room');
  }

  function connect() {
    const sfuWsUrl = (wsUrlInput && wsUrlInput.value.trim()) || 'ws://127.0.0.1:4443';
    const token = tokenInput && tokenInput.value.trim();
    if (!token) {
      log('Informe o token JWT (execute scripts/generate-token.js e cole o valor de TOKEN=).');
      return;
    }
    if (ws) return;
    const url = sfuWsUrl.replace(/^http/, 'ws');
    log('Connecting to ' + url);
    ws = new WebSocket(url);

    ws.onopen = () => {
      log('WebSocket open');
      if (btnJoin) btnJoin.disabled = true;
      join(token).catch((e) => {
        log('Join failed', e.message);
        if (btnJoin) btnJoin.disabled = false;
      });
    };

    ws.onmessage = (e) => {
      try {
        const msg = JSON.parse(e.data);
        if (msg.action === 'ping') {
          send({ action: 'pong' });
          return;
        }
        if (msg.action === 'peerJoined') {
          const { peerId, userId, participantsCount, participants } = msg.data || {};
          log('Usuário entrou na sala', { peerId: peerId?.slice(0, 28), userId: userId?.slice(0, 24) });
          const count = participantsCount ?? (participants?.length ?? 0);
          log('Participantes na sala: ' + count, participants?.map((p) => p.peerId) ?? []);
          return;
        }
        if (msg.action === 'newProducer') {
          const { producerId, peerId, kind } = msg.data || {};
          if (producerId) consume(producerId, peerId, kind);
          return;
        }
      } catch (_) {}
    };

    ws.onclose = () => {
      log('WebSocket closed');
      if (btnJoin) btnJoin.disabled = false;
    };

    ws.onerror = (err) => {
      log('WebSocket error', err?.message ?? 'unknown');
    };
  }

  if (btnJoin) btnJoin.addEventListener('click', connect);
  if (btnCamera) {
    btnCamera.disabled = true;
    btnCamera.addEventListener('click', startCamera);
  }
  if (btnLeave) {
    btnLeave.disabled = true;
    btnLeave.addEventListener('click', leave);
  }

  if (btnShareScreen) {
    btnShareScreen.addEventListener('click', async () => {
      if (!sendTransport) return;
      try {
        const stream = await navigator.mediaDevices.getDisplayMedia({ video: true });
        const videoTrack = stream.getVideoTracks()[0];
        if (videoTrack) {
          const producer = await sendTransport.produce({
            track: videoTrack,
            appData: { source: 'screen' },
          });
          producers.push(producer);
          log('Screen producer created', { id: producer.id });
          videoTrack.onended = () => producer.close();
        }
      } catch (e) {
        log('getDisplayMedia error', e.message);
      }
    });
  }

  log('SFU Test (standalone) pronto. Preencha o token e clique em "Entrar na sala".');
})();
