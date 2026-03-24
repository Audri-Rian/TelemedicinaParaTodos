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
  const statsDefault  = document.getElementById('statsDefault');
  const remotePeerCountEl = document.getElementById('remotePeerCount');
  const footerHealth  = document.getElementById('footerHealth');
  const footerUptime  = document.getElementById('footerUptime');
  const localRecTimer = document.getElementById('localRecTimer');
  const localLiveDot  = document.getElementById('localLiveDot');
  const localLiveLabel = document.getElementById('localLiveLabel');

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
  let connectedAt      = null;
  let uptimeLoopId     = null;

  /** @type {Map<string, import('mediasoup-client').types.Consumer>} */
  const consumers           = new Map();
  const remotePeerContainers = new Map();
  const pendingProducers    = [];

  // ── Logging ───────────────────────────────────────────────────────────────
  function log(msg, data = null, level = 'info') {
    const text = data !== null && data !== undefined
      ? `${msg} ${typeof data === 'object' ? JSON.stringify(data) : String(data)}`
      : msg;
    const time = new Date().toISOString().slice(11, 23);
    const el = document.createElement('div');
    el.style.display = 'flex';
    el.style.gap = '16px';
    const lvlColors = { info: '#4edea3', warn: '#ffb3ad', error: '#ffb4ab', net: '#adc7ff' };
    const lvlLabels = { info: 'INF', warn: 'WRN', error: 'ERR', net: 'NET' };
    el.innerHTML = `<span style="color:#45474b">[${time}]</span> <span style="color:${lvlColors[level] || '#4edea3'}">${lvlLabels[level] || 'INF'}</span> <span style="color:rgba(198,198,203,0.7)">${text.replace(/</g, '&lt;')}</span>`;
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
    const dot  = document.getElementById('statusDot');
    const span = document.getElementById('statusText');
    const badge = statusBadge;
    if (!badge) return;
    const colors = {
      yellow: { bg: 'rgba(255,179,173,0.1)', border: 'rgba(255,179,173,0.2)', text: '#ffb3ad', dot: '#ffb3ad' },
      green:  { bg: 'rgba(78,222,163,0.1)',  border: 'rgba(78,222,163,0.2)',  text: '#4edea3', dot: '#4edea3' },
      red:    { bg: 'rgba(255,180,171,0.1)', border: 'rgba(255,180,171,0.2)', text: '#ffb4ab', dot: '#ffb4ab' },
    };
    const c = colors[color] || colors.yellow;
    badge.style.background = c.bg;
    badge.style.borderColor = c.border;
    if (span) { span.textContent = text; span.style.color = c.text; }
    if (dot)  { dot.style.background = c.dot; dot.className = color === 'green' ? 'w-2 h-2 rounded-full bg-secondary animate-pulse' : 'w-2 h-2 rounded-full'; dot.style.background = c.dot; }
    if (footerHealth) {
      footerHealth.textContent = color === 'green' ? 'OPTIMAL' : color === 'red' ? 'CRITICAL' : 'STARTING';
      footerHealth.style.color = c.text;
    }
  }

  function updateInfoPanel() {
    if (infoParticipants) infoParticipants.textContent = String(participantCount);
    if (remotePeerCountEl) remotePeerCountEl.textContent = 'PEERS: ' + remotePeerContainers.size;
  }

  function startUptimeLoop() {
    if (uptimeLoopId) return;
    connectedAt = Date.now();
    uptimeLoopId = setInterval(function() {
      if (!connectedAt) return;
      var secs = Math.floor((Date.now() - connectedAt) / 1000);
      var hh = String(Math.floor(secs / 3600)).padStart(2, '0');
      var mm = String(Math.floor((secs % 3600) / 60)).padStart(2, '0');
      var ss = String(secs % 60).padStart(2, '0');
      var timeStr = hh + ':' + mm + ':' + ss;
      if (footerUptime) footerUptime.textContent = 'UPTIME: ' + timeStr;
      if (localRecTimer) localRecTimer.textContent = 'REC: ' + timeStr;
    }, 1000);
  }

  // ── Stats loop ────────────────────────────────────────────────────────────
  function startStatsLoop() {
    if (statsLoopId) return;
    statsLoopId = setInterval(refreshStats, 3000);
  }

  async function refreshStats() {
    if (!statsContent) return;
    const now   = Date.now();
    let totalBitrateKbps = 0;
    let totalLoss = 0;
    let lossCount = 0;
    let videoKbps = '—';
    let audioKbps = '—';

    if (videoProducer && typeof videoProducer.getStats === 'function') {
      try {
        const report = await videoProducer.getStats();
        let bytes = 0;
        report.forEach(s => { if (s.type === 'outbound-rtp') { bytes += s.bytesSent || 0; } });
        if (lastVideoStats && now > lastVideoStats.time) {
          videoKbps = Math.round(((bytes - lastVideoStats.bytes) * 8) / ((now - lastVideoStats.time) / 1000) / 1000);
          totalBitrateKbps += videoKbps;
        }
        lastVideoStats = { time: now, bytes };
      } catch {}
    }

    if (audioProducer && typeof audioProducer.getStats === 'function') {
      try {
        const report = await audioProducer.getStats();
        let bytes = 0;
        report.forEach(s => { if (s.type === 'outbound-rtp') bytes += s.bytesSent || 0; });
        if (lastAudioStats && now > lastAudioStats.time) {
          audioKbps = Math.round(((bytes - lastAudioStats.bytes) * 8) / ((now - lastAudioStats.time) / 1000) / 1000);
          totalBitrateKbps += audioKbps;
        }
        lastAudioStats = { time: now, bytes };
      } catch {}
    }

    for (const [, consumer] of consumers) {
      if (consumer.kind !== 'video') continue;
      try {
        const report = await consumer.getStats();
        let lost = 0, rcvd = 0;
        report.forEach(s => {
          if (s.type === 'inbound-rtp') { lost += s.packetsLost || 0; rcvd += s.packetsReceived || 0; }
        });
        const loss = (rcvd + lost) > 0 ? ((lost / (rcvd + lost)) * 100) : 0;
        totalLoss += loss;
        lossCount++;
      } catch {}
    }

    const avgLoss = lossCount > 0 ? (totalLoss / lossCount).toFixed(3) : '0.000';
    const heapMB = (typeof performance !== 'undefined' && performance.memory)
      ? (performance.memory.usedJSHeapSize / 1024 / 1024).toFixed(1)
      : '—';

    // Hide default placeholder
    if (statsDefault) statsDefault.style.display = 'none';

    // Render stat cards
    statsContent.innerHTML = `
      <div style="background:#131b2e; padding:16px; display:flex; flex-direction:column; gap:8px; position:relative; overflow:hidden;">
        <span style="font-size:10px; color:#c6c6cb; text-transform:uppercase; font-family:'Space Grotesk'; letter-spacing:0.1em;">Aggregate Bitrate</span>
        <div style="display:flex; align-items:baseline; gap:8px;">
          <span style="font-size:1.5rem; font-weight:700; font-family:'JetBrains Mono',monospace; color:#dae2fd;">${totalBitrateKbps || '—'}</span>
          <span style="font-size:12px; color:#c6c6cb; font-family:'JetBrains Mono',monospace;">Kbps</span>
        </div>
        <div style="font-size:10px; font-family:'JetBrains Mono',monospace; color:rgba(198,198,203,0.5);">V: ${videoKbps} | A: ${audioKbps}</div>
      </div>
      <div style="background:#131b2e; padding:16px; display:flex; flex-direction:column; gap:8px;">
        <span style="font-size:10px; color:#c6c6cb; text-transform:uppercase; font-family:'Space Grotesk'; letter-spacing:0.1em;">Packet Loss</span>
        <div style="display:flex; align-items:baseline; gap:8px;">
          <span style="font-size:1.5rem; font-weight:700; font-family:'JetBrains Mono',monospace; color:${parseFloat(avgLoss) < 1 ? '#4edea3' : '#ffb4ab'};">${avgLoss}</span>
          <span style="font-size:12px; color:#c6c6cb; font-family:'JetBrains Mono',monospace;">%</span>
        </div>
      </div>
      <div style="background:#131b2e; padding:16px; display:flex; flex-direction:column; gap:8px;">
        <span style="font-size:10px; color:#c6c6cb; text-transform:uppercase; font-family:'Space Grotesk'; letter-spacing:0.1em;">Consumers</span>
        <div style="display:flex; align-items:baseline; gap:8px;">
          <span style="font-size:1.5rem; font-weight:700; font-family:'JetBrains Mono',monospace; color:#dae2fd;">${consumers.size}</span>
          <span style="font-size:12px; color:#c6c6cb; font-family:'JetBrains Mono',monospace;">active</span>
        </div>
      </div>
      <div style="background:#131b2e; padding:16px; display:flex; flex-direction:column; gap:8px;">
        <span style="font-size:10px; color:#c6c6cb; text-transform:uppercase; font-family:'Space Grotesk'; letter-spacing:0.1em;">Memory</span>
        <div style="display:flex; align-items:baseline; gap:8px;">
          <span style="font-size:1.5rem; font-weight:700; font-family:'JetBrains Mono',monospace; color:#dae2fd;">${heapMB}</span>
          <span style="font-size:12px; color:#c6c6cb; font-family:'JetBrains Mono',monospace;">MB</span>
        </div>
      </div>`;
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
        div.style.cssText = 'position:relative; overflow:hidden; background:#131b2e; aspect-ratio:16/9;';
        const vid = document.createElement('video');
        vid.autoplay = vid.playsInline = true;
        vid.muted    = true;
        vid.style.cssText = 'width:100%; height:100%; display:block; object-fit:cover; filter:grayscale(1); opacity:0.4; transition:all 0.5s;';
        vid.onmouseenter = function() { vid.style.filter = 'grayscale(0)'; vid.style.opacity = '1'; };
        vid.onmouseleave = function() { vid.style.filter = 'grayscale(1)'; vid.style.opacity = '0.4'; };
        div.appendChild(vid);
        const lbl = document.createElement('div');
        lbl.style.cssText = 'position:absolute; bottom:4px; left:4px; background:rgba(0,0,0,0.6); padding:1px 4px;';
        lbl.innerHTML = '<span style="font-family:\'JetBrains Mono\',monospace; font-size:8px; color:#dae2fd; text-transform:uppercase; letter-spacing:0.05em;">' + peerId.slice(0, 10) + '</span>';
        div.appendChild(lbl);
        const aud = document.createElement('audio');
        aud.autoplay = true; aud.muted = false; aud.volume = 1;
        aud.style.cssText = 'position:absolute; width:1px; height:1px; left:-9999px;';
        div.appendChild(aud);
        entry = { div, videoEl: vid, audioEl: aud };
        remotePeerContainers.set(peerId, entry);
        if (remoteVideos) remoteVideos.appendChild(div);
        updateInfoPanel();
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
    if (infoConn) { infoConn.textContent = 'OK'; infoConn.style.color = '#4edea3'; }
    const connIcon = infoConn?.previousElementSibling;
    if (connIcon) { connIcon.textContent = 'bolt'; connIcon.style.color = '#4edea3'; }
    startStatsLoop();
    startUptimeLoop();
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
        if (infoMic) { infoMic.textContent = 'ON'; infoMic.style.color = '#4edea3'; }
        const micIcon = infoMic?.previousElementSibling;
        if (micIcon) { micIcon.textContent = 'mic'; micIcon.style.color = '#4edea3'; }
        log('AudioProducer criado', { id: audioProducer.id }, 'net');
      }

      const videoTrack = stream.getVideoTracks()[0];
      if (videoTrack) {
        videoProducer = await sendTransport.produce({
          track: videoTrack,
          codecOptions: { videoGoogleStartBitrate: 1000 },
          appData: { source: 'camera' },
        });
        if (infoCam) { infoCam.textContent = 'ON'; infoCam.style.color = '#4edea3'; }
        const camIcon = infoCam?.previousElementSibling;
        if (camIcon) { camIcon.textContent = 'videocam'; camIcon.style.color = '#4edea3'; }
        if (localLiveDot) { localLiveDot.className = 'w-1.5 h-1.5 rounded-full bg-error animate-pulse'; localLiveDot.style.background = ''; }
        if (localLiveLabel) { localLiveLabel.textContent = 'Live Broadcast'; localLiveLabel.style.color = '#fff'; }
        log('VideoProducer criado', { id: videoProducer.id }, 'net');
      }
    } catch (e) {
      log('Erro ao iniciar mídia: ' + (e?.message ?? String(e)), null, 'error');
      setStatus('Erro: mídia', 'red');
      if (infoCam) { infoCam.textContent = 'ERRO'; infoCam.style.color = '#ffb4ab'; }
      if (infoMic) { infoMic.textContent = 'ERRO'; infoMic.style.color = '#ffb4ab'; }
    }
  }

  // ── Auto connect on page load ─────────────────────────────────────────────
  const url = sfuWsUrl.replace(/^http/, 'ws');
  log('Auto-conectando a ' + url, null, 'net');
  ws = new WebSocket(url);

  ws.onopen = () => {
    log('WebSocket aberto', null, 'net');
    join().catch(e => {
      log('Join falhou: ' + e.message, null, 'error');
      setStatus('Erro: join', 'red');
      if (infoConn) { infoConn.textContent = 'Falhou'; infoConn.style.color = '#ffb4ab'; }
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
    log('WebSocket fechado', null, 'warn');
    setStatus('Desconectado', 'red');
    if (infoConn) { infoConn.textContent = 'OFF'; infoConn.style.color = '#ffb4ab'; }
    if (statsLoopId) { clearInterval(statsLoopId); statsLoopId = null; }
    if (uptimeLoopId) { clearInterval(uptimeLoopId); uptimeLoopId = null; }
    if (localLiveDot) { localLiveDot.className = 'w-1.5 h-1.5 rounded-full'; localLiveDot.style.background = 'rgba(198,198,203,0.3)'; }
    if (localLiveLabel) { localLiveLabel.textContent = 'Desconectado'; localLiveLabel.style.color = '#ffb4ab'; }
  };

  ws.onerror = () => {
    log('WebSocket erro', null, 'error');
    setStatus('Erro WS', 'red');
  };

  window.addEventListener('beforeunload', () => { send({ action: 'leave' }); });
}
