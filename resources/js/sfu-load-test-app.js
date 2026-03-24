/**
 * SFU Load Test — cliente auto-join para testes de carga.
 * Entra na sala automaticamente, ativa câmera e microfone sem interação do usuário.
 * Cada aba gera um ID único; suporta múltiplas abas simultâneas.
 */

import * as mediasoupClient from 'mediasoup-client';

const CONFIG = typeof window !== 'undefined' && window.__SFU_LOAD_CONFIG__;

if (!CONFIG?.token || !CONFIG?.sfuWsUrl) {
  console.error('SFU_LOAD: config ausente em window.__SFU_LOAD_CONFIG__');
} else {
  runLoadTest(CONFIG);
}

function runLoadTest(config) {
  const { token, sfuWsUrl } = config;

  // ── DOM refs ──────────────────────────────────────────────────────────────
  const logEl         = document.getElementById('logContainer');
  const localVideo    = document.getElementById('localVideo');
  const remoteVideos  = document.getElementById('remoteVideos');
  const statusBadge   = document.getElementById('statusBadge');
  const infoUserId    = document.getElementById('infoUserId');
  const infoParticipants = document.getElementById('infoParticipants');
  const infoCam       = document.getElementById('infoCam');
  const infoMic       = document.getElementById('infoMic');
  const infoConn      = document.getElementById('infoConn');
  const statsContent  = document.getElementById('statsContent');

  // ── State ─────────────────────────────────────────────────────────────────
  let ws               = null;
  let device           = null;
  let sendTransport    = null;
  let recvTransport    = null;
  let audioProducer    = null;
  let videoProducer    = null;
  let participantCount = 0;
  let reqId            = 0;
  let statsLoopId      = null;
  let lastVideoStats   = null;
  let lastAudioStats   = null;

  /** @type {Map<string, import('mediasoup-client').types.Consumer>} */
  const consumers           = new Map();
  const remotePeerContainers = new Map();
  const pendingProducers    = [];

  // ── Logging ───────────────────────────────────────────────────────────────
  function log(msg, data = null) {
    const text = data !== null && data !== undefined
      ? `${msg} ${typeof data === 'object' ? JSON.stringify(data) : String(data)}`
      : msg;
    const el = document.createElement('div');
    el.textContent = `[${new Date().toISOString().slice(11, 23)}] ${text}`;
    if (logEl) { logEl.appendChild(el); logEl.scrollTop = logEl.scrollHeight; }
    console.log('[LOAD]', msg, data ?? '');
  }

  // ── WS helpers ────────────────────────────────────────────────────────────
  function send(msg) {
    if (ws?.readyState === WebSocket.OPEN) ws.send(JSON.stringify(msg));
  }

  function request(action, payload = {}) {
    const id = ++reqId;
    return new Promise((resolve, reject) => {
      const handler = (e) => {
        try {
          const msg = JSON.parse(e.data);
          if (msg.id !== id) return;
          ws.removeEventListener('message', handler);
          msg.ok ? resolve(msg.data) : reject(new Error(msg.error?.message || 'Erro'));
        } catch (err) {
          ws.removeEventListener('message', handler);
          reject(err);
        }
      };
      ws.addEventListener('message', handler);
      send({ id, action, ...payload });
    });
  }

  // ── UI helpers ────────────────────────────────────────────────────────────
  function setStatus(text, color = 'yellow') {
    if (!statusBadge) return;
    statusBadge.textContent = text;
    statusBadge.className   = `badge badge-${color}`;
  }

  function updateInfoPanel() {
    if (infoParticipants) infoParticipants.textContent = String(participantCount);
  }

  // ── Stats loop ────────────────────────────────────────────────────────────
  function startStatsLoop() {
    if (statsLoopId) return;
    statsLoopId = setInterval(refreshStats, 3000);
  }

  async function refreshStats() {
    if (!statsContent) return;
    const lines = [];
    const now   = Date.now();

    if (videoProducer && typeof videoProducer.getStats === 'function') {
      try {
        const report = await videoProducer.getStats();
        let bytes = 0, pkts = 0;
        report.forEach(s => { if (s.type === 'outbound-rtp') { bytes += s.bytesSent || 0; pkts += s.packetsSent || 0; } });
        let kbps = '—';
        if (lastVideoStats && now > lastVideoStats.time) {
          kbps = Math.round(((bytes - lastVideoStats.bytes) * 8) / ((now - lastVideoStats.time) / 1000) / 1000);
        }
        lastVideoStats = { time: now, bytes };
        lines.push(`📹 Vídeo: ${kbps} kbps | ${pkts} pkts`);
      } catch {}
    }

    if (audioProducer && typeof audioProducer.getStats === 'function') {
      try {
        const report = await audioProducer.getStats();
        let bytes = 0;
        report.forEach(s => { if (s.type === 'outbound-rtp') bytes += s.bytesSent || 0; });
        let kbps = '—';
        if (lastAudioStats && now > lastAudioStats.time) {
          kbps = Math.round(((bytes - lastAudioStats.bytes) * 8) / ((now - lastAudioStats.time) / 1000) / 1000);
        }
        lastAudioStats = { time: now, bytes };
        lines.push(`🎤 Áudio: ${kbps} kbps`);
      } catch {}
    }

    for (const [, consumer] of consumers) {
      if (consumer.kind !== 'video') continue;
      try {
        const report = await consumer.getStats();
        let bytes = 0, lost = 0, rcvd = 0;
        report.forEach(s => {
          if (s.type === 'inbound-rtp') { bytes += s.bytesReceived || 0; lost += s.packetsLost || 0; rcvd += s.packetsReceived || 0; }
        });
        const loss = (rcvd + lost) > 0 ? ((lost / (rcvd + lost)) * 100).toFixed(1) : '0';
        lines.push(`📥 Recv: ${bytes} bytes | perda ${loss}%`);
      } catch {}
    }

    if (statsContent) statsContent.innerHTML = lines.join('<br>') || 'Coletando…';
  }

  // ── Consume ───────────────────────────────────────────────────────────────
  async function consume(producerId, peerId, kind) {
    if (!recvTransport || !device?.rtpCapabilities) return;
    try {
      const data     = await request('consume', { producerId, rtpCapabilities: device.rtpCapabilities });
      const consumer = await recvTransport.consume({
        id: data.id, producerId: data.producerId,
        kind: data.kind, rtpParameters: data.rtpParameters, type: data.type,
      });
      consumers.set(consumer.id, consumer);
      consumer.track.enabled = true;
      await request('resumeConsumer', { consumerId: consumer.id });

      // Get or create peer container
      let entry = remotePeerContainers.get(peerId);
      if (!entry) {
        const div = document.createElement('div');
        div.className = 'load-remote-wrap';
        const lbl = document.createElement('p');
        lbl.textContent = peerId.slice(0, 16) + '…';
        div.appendChild(lbl);
        const vid = document.createElement('video');
        vid.className = 'load-remote-video';
        vid.autoplay = vid.playsInline = true;
        vid.muted    = true;
        div.appendChild(vid);
        const aud = document.createElement('audio');
        aud.autoplay = true; aud.muted = false; aud.volume = 1;
        aud.style.cssText = 'position:absolute; width:1px; height:1px; left:-9999px;';
        div.appendChild(aud);
        entry = { div, videoEl: vid, audioEl: aud };
        remotePeerContainers.set(peerId, entry);
        if (remoteVideos) remoteVideos.appendChild(div);
      }

      const stream = new MediaStream([consumer.track]);
      if (kind === 'video') {
        entry.videoEl.srcObject = stream;
        entry.videoEl.play().catch(() => {});
        const kf = () => request('requestKeyFrame', { consumerId: consumer.id }).catch(() => {});
        kf(); setTimeout(kf, 1000); setTimeout(kf, 3000);
      } else {
        entry.audioEl.srcObject = stream;
        entry.audioEl.play().catch(() => {});
      }

      log(`Consumer ${kind} criado`, { consumerId: consumer.id, peerId: peerId?.slice(0, 20) });
    } catch (e) {
      log('Consume erro', e?.message ?? String(e));
    }
  }

  // ── Join ──────────────────────────────────────────────────────────────────
  async function join() {
    setStatus('Conectando…', 'yellow');
    if (infoConn) infoConn.textContent = 'Entrando…';

    const data = await request('join', { token });
    participantCount = data.participantsCount ?? data.participants?.length ?? 1;

    if (infoUserId) infoUserId.textContent = data.peerId;
    updateInfoPanel();
    log('Join ok', { peerId: data.peerId });

    device = new mediasoupClient.Device();
    await device.load({ routerRtpCapabilities: data.rtpCapabilities });

    // Send transport
    const sendData = await request('createWebRtcTransport', { direction: 'send' });
    sendTransport  = device.createSendTransport({
      id: sendData.id, iceParameters: sendData.iceParameters,
      iceCandidates: sendData.iceCandidates, dtlsParameters: sendData.dtlsParameters,
    });
    sendTransport.on('connect', async ({ dtlsParameters }, cb, eb) => {
      try { await request('connectWebRtcTransport', { transportId: sendTransport.id, dtlsParameters }); cb(); }
      catch (e) { eb(e); }
    });
    sendTransport.on('produce', async ({ kind, rtpParameters, appData }, cb, eb) => {
      try { const { id } = await request('produce', { kind, rtpParameters, appData: appData || {} }); cb({ id }); }
      catch (e) { eb(e); }
    });

    // Recv transport
    const recvData = await request('createWebRtcTransport', { direction: 'recv' });
    recvTransport  = device.createRecvTransport({
      id: recvData.id, iceParameters: recvData.iceParameters,
      iceCandidates: recvData.iceCandidates, dtlsParameters: recvData.dtlsParameters,
    });
    recvTransport.on('connect', async ({ dtlsParameters }, cb, eb) => {
      try { await request('connectWebRtcTransport', { transportId: recvTransport.id, dtlsParameters }); cb(); }
      catch (e) { eb(e); }
    });

    // Process any producers that arrived before transports were ready
    while (pendingProducers.length) {
      const { producerId, peerId, kind } = pendingProducers.shift();
      await consume(producerId, peerId, kind);
    }

    // Auto start camera + mic
    await startMedia();

    setStatus('Conectado', 'green');
    if (infoConn) infoConn.textContent = 'OK';
    startStatsLoop();
  }

  // ── Auto start media ──────────────────────────────────────────────────────
  async function startMedia() {
    try {
      log('Iniciando câmera e microfone automaticamente…');
      const stream = await navigator.mediaDevices.getUserMedia({ audio: true, video: true });

      if (localVideo) { localVideo.muted = true; localVideo.srcObject = stream; localVideo.play().catch(() => {}); }

      const audioTrack = stream.getAudioTracks()[0];
      if (audioTrack) {
        audioProducer = await sendTransport.produce({ track: audioTrack, appData: { source: 'mic' } });
        if (infoMic) { infoMic.textContent = 'ON'; infoMic.style.color = '#4ade80'; }
        log('AudioProducer criado', { id: audioProducer.id });
      }

      const videoTrack = stream.getVideoTracks()[0];
      if (videoTrack) {
        videoProducer = await sendTransport.produce({
          track: videoTrack,
          codecOptions: { videoGoogleStartBitrate: 1000 },
          appData: { source: 'camera' },
        });
        if (infoCam) { infoCam.textContent = 'ON'; infoCam.style.color = '#4ade80'; }
        log('VideoProducer criado', { id: videoProducer.id });
      }
    } catch (e) {
      log('Erro ao iniciar mídia', e?.message ?? String(e));
      setStatus('Erro: mídia', 'red');
      if (infoCam) infoCam.textContent = 'ERRO';
      if (infoMic) infoMic.textContent = 'ERRO';
    }
  }

  // ── Auto connect on page load ─────────────────────────────────────────────
  const url = sfuWsUrl.replace(/^http/, 'ws');
  log('Auto-conectando a ' + url);
  ws = new WebSocket(url);

  ws.onopen = () => {
    log('WebSocket aberto');
    join().catch(e => {
      log('Join falhou', e.message);
      setStatus('Erro: join', 'red');
      if (infoConn) infoConn.textContent = 'Falhou';
    });
  };

  ws.onmessage = (e) => {
    try {
      const msg = JSON.parse(e.data);

      if (msg.action === 'ping') { send({ action: 'pong' }); return; }

      if (msg.action === 'peerJoined') {
        const { participants, participantsCount } = msg.data || {};
        participantCount = participantsCount ?? participants?.length ?? participantCount + 1;
        updateInfoPanel();
        return;
      }

      if (msg.action === 'peerLeft') {
        const { peerId, participantsCount, participants } = msg.data || {};
        participantCount = participantsCount ?? participants?.length ?? Math.max(0, participantCount - 1);
        updateInfoPanel();
        const entry = remotePeerContainers.get(peerId);
        if (entry) { entry.div.remove(); remotePeerContainers.delete(peerId); }
        return;
      }

      if (msg.action === 'newProducer') {
        const { producerId, peerId, kind } = msg.data || {};
        if (!producerId) return;
        if (recvTransport && device?.rtpCapabilities) consume(producerId, peerId, kind);
        else pendingProducers.push({ producerId, peerId, kind });
        return;
      }

      // producerClosed
      if (!msg.action && msg.data?.producerClosed && msg.data?.consumerId) {
        const consumer = consumers.get(msg.data.consumerId);
        if (consumer) { consumer.close(); consumers.delete(msg.data.consumerId); }
        return;
      }
    } catch {}
  };

  ws.onclose = () => {
    log('WebSocket fechado');
    setStatus('Desconectado', 'red');
    if (infoConn) infoConn.textContent = 'OFF';
    if (statsLoopId) { clearInterval(statsLoopId); statsLoopId = null; }
  };

  ws.onerror = () => {
    log('WebSocket erro');
    setStatus('Erro WS', 'red');
  };

  window.addEventListener('beforeunload', () => { send({ action: 'leave' }); });
}
