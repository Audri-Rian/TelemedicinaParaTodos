/**
 * SFU Test App v3 — Teste completo de rede, mídia, SFU e edge cases.
 *
 * Funcionalidades:
 *  - Stats detalhadas: bitrate, RTT, packet loss, jitter, FPS, resolução (com cores de qualidade)
 *  - Auto-reconexão com contador
 *  - Forçar queda de conexão (simula corte de rede)
 *  - Entrada sem câmera (edge case)
 *  - Vídeo preto via canvas (simula falha de câmera)
 *  - Pause/resume de producer no servidor (SFU)
 *  - Pause/resume de consumers no servidor
 *  - Monitor de performance: heap, tracks ativas, uptime
 *  - Detecção de vazamento de tracks
 */

import * as mediasoupClient from 'mediasoup-client';

const CONFIG = typeof window !== 'undefined' && window.__SFU_TEST_CONFIG__;

if (!CONFIG?.token || !CONFIG?.sfuWsUrl) {
    console.error('SFU_TEST: config ausente em window.__SFU_TEST_CONFIG__');
} else {
    runSfuTest(CONFIG);
}

function runSfuTest(config) {
    const { token, sfuWsUrl } = config;

    // ── DOM refs ──────────────────────────────────────────────────────────────
    const logEl = document.getElementById('logContainer');
    const localVideo = document.getElementById('localVideo');
    const remoteVideosContainer = document.getElementById('remoteVideos');

    // Status bar
    const elStatusConn = document.getElementById('statusConn');
    const elStatusPeers = document.getElementById('statusPeers');
    const elStatusCam = document.getElementById('statusCam');
    const elStatusMic = document.getElementById('statusMic');
    const elStatusVideo = document.getElementById('statusVideo');
    const elStatusAutoReconn = document.getElementById('statusAutoReconn');

    // Buttons — conexão
    const btnJoin = document.getElementById('btnJoin');
    const btnLeave = document.getElementById('btnLeave');
    const btnStartCamera = document.getElementById('btnStartCamera');
    const btnStartNoCamera = document.getElementById('btnStartNoCamera');
    // Buttons — mídia
    const btnToggleCam = document.getElementById('btnToggleCam');
    const btnToggleMic = document.getElementById('btnToggleMic');
    const btnPauseVideo = document.getElementById('btnPauseVideo');
    const btnBlackVideo = document.getElementById('btnBlackVideo');
    const btnActivateAudio = document.getElementById('btnActivateAudio');
    // Buttons — dispositivos
    const btnSwitchCam = document.getElementById('btnSwitchCam');
    const btnSwitchAudioIn = document.getElementById('btnSwitchAudioIn');
    const btnSwitchAudioOut = document.getElementById('btnSwitchAudioOut');
    // Buttons — reconexão
    const btnReconnect = document.getElementById('btnReconnect');
    const btnForceDisconnect = document.getElementById('btnForceDisconnect');
    const btnToggleAutoReconn = document.getElementById('btnToggleAutoReconn');
    const btnCleanup = document.getElementById('btnCleanup');
    // Buttons — SFU
    const btnPauseProducerSFU = document.getElementById('btnPauseProducerSFU');
    const btnResumeProducerSFU = document.getElementById('btnResumeProducerSFU');
    const btnPauseAllConsumers = document.getElementById('btnPauseAllConsumers');
    const btnResumeAllConsumers = document.getElementById('btnResumeAllConsumers');
    // Buttons — remoto
    const btnToggleRemoteMute = document.getElementById('btnToggleRemoteMute');
    const btnTestCleanup = document.getElementById('btnTestCleanup');
    const btnToggleStats = document.getElementById('btnToggleStats');
    // Selects
    const selCamera = document.getElementById('selCamera');
    const selAudioIn = document.getElementById('selAudioIn');
    const selAudioOut = document.getElementById('selAudioOut');
    // Stats
    const statsCards = document.getElementById('statsCards');
    const perfHeap = document.getElementById('perfHeap');
    const perfTracks = document.getElementById('perfTracks');
    const perfReconns = document.getElementById('perfReconns');
    const perfUptime = document.getElementById('perfUptime');
    const perfAutoReconn = document.getElementById('perfAutoReconn');

    // ── State ─────────────────────────────────────────────────────────────────
    let connected = false;
    let cameraOn = false;
    let micOn = false;
    let videoSendPaused = false;
    let remoteMuted = false;
    let autoReconnect = false;
    let intentionalClose = false; // Distingue close voluntário de queda
    let producerSFUPaused = false;
    let consumersSFUPaused = false;
    let participantCount = 0;
    let reconnectCount = 0;
    let connectedAt = null;
    let statsLoopId = null;
    let statsLogTick = 0; // contador para logs periódicos a cada 5 s
    let reqId = 0;

    // Stats history para cálculo de bitrate
    let lastVideoStatsSend = null;
    let lastAudioStatsSend = null;
    /** @type {Map<string, { time: number, bytes: number }>} */
    const lastConsumerStats = new Map();

    // Track counter para detecção de vazamento
    const createdTracks = new Set();

    // ── WebRTC objects ────────────────────────────────────────────────────────
    let ws = null;
    let device = null;
    let sendTransport = null;
    let recvTransport = null;
    let audioProducer = null;
    let videoProducer = null;

    /** @type {Map<string, import('mediasoup-client').types.Consumer>} */
    const consumers = new Map();
    /** @type {Map<string, { div: HTMLDivElement, videoEl: HTMLVideoElement, audioEl: HTMLAudioElement }>} */
    const remotePeerContainers = new Map();
    const pendingNewProducers = [];
    let localStream = null;

    // ── Logging ───────────────────────────────────────────────────────────────
    function log(msg, data = null, level = 'info') {
        const text = data !== null && data !== undefined ? `${msg} ${typeof data === 'object' ? JSON.stringify(data) : String(data)}` : msg;
        const line = document.createElement('div');
        line.textContent = `[${new Date().toISOString().slice(11, 23)}] ${text}`;
        if (level === 'warn') line.style.color = '#fbbf24';
        if (level === 'error') line.style.color = '#f87171';
        if (level === 'stats') line.style.color = '#38bdf8';
        if (logEl) {
            logEl.appendChild(line);
            logEl.scrollTop = logEl.scrollHeight;
        }
        console.log('[SFU]', msg, data ?? '');
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

    // ── UI update ─────────────────────────────────────────────────────────────
    function updateUI() {
        if (elStatusConn) {
            elStatusConn.textContent = connected ? '🟢 Conectado' : '🔴 Desconectado';
            elStatusConn.style.color = connected ? '#4ade80' : '#f87171';
        }
        if (elStatusPeers) elStatusPeers.textContent = `${participantCount} participante${participantCount !== 1 ? 's' : ''}`;
        if (elStatusCam) elStatusCam.textContent = cameraOn ? '📷 ON' : '📷 OFF';
        if (elStatusMic) elStatusMic.textContent = micOn ? '🎤 ON' : '🎤 MUDO';
        if (elStatusVideo) {
            if (!videoProducer) elStatusVideo.textContent = '📹 —';
            else if (producerSFUPaused) elStatusVideo.textContent = '📹 SFU PAUSADO';
            else if (videoSendPaused) elStatusVideo.textContent = '📹 Pausado';
            else elStatusVideo.textContent = '📹 Enviando';
        }
        const arLabel = autoReconnect ? 'Auto-reconexão: ON' : 'Auto-reconexão: OFF';
        if (elStatusAutoReconn) {
            elStatusAutoReconn.textContent = arLabel;
            elStatusAutoReconn.style.color = autoReconnect ? '#4ade80' : '#555';
        }

        // Buttons
        if (btnJoin) btnJoin.disabled = connected;
        if (btnLeave) btnLeave.disabled = !connected;
        if (btnStartCamera) btnStartCamera.disabled = !connected || cameraOn;
        if (btnStartNoCamera) btnStartNoCamera.disabled = !connected || cameraOn;
        if (btnToggleCam) {
            btnToggleCam.disabled = !connected;
            btnToggleCam.textContent = cameraOn ? '📷 Desligar câmera' : '📷 Ligar câmera';
        }
        if (btnToggleMic) {
            btnToggleMic.disabled = !audioProducer;
            btnToggleMic.textContent = micOn ? '🎤 Mutar mic' : '🎤 Desmutar mic';
        }
        if (btnPauseVideo) {
            btnPauseVideo.disabled = !videoProducer;
            btnPauseVideo.textContent = videoSendPaused ? '▶ Retomar vídeo' : '⏸ Pausar vídeo';
        }
        if (btnBlackVideo) btnBlackVideo.disabled = !cameraOn && !videoProducer;
        if (btnSwitchCam) btnSwitchCam.disabled = !cameraOn;
        if (btnSwitchAudioIn) btnSwitchAudioIn.disabled = !audioProducer;
        if (btnToggleRemoteMute) {
            btnToggleRemoteMute.textContent = remoteMuted ? '🔊 Desmutar remoto' : '🔇 Mutar remoto';
        }
        if (btnToggleAutoReconn) {
            btnToggleAutoReconn.textContent = arLabel;
            btnToggleAutoReconn.classList.toggle('btn-active', autoReconnect);
        }
        if (btnPauseProducerSFU) {
            btnPauseProducerSFU.disabled = !videoProducer;
            btnPauseProducerSFU.textContent = producerSFUPaused ? '⏸ Producer (pausado)' : '⏸ Pausar Producer (SFU)';
        }
        if (btnResumeProducerSFU) btnResumeProducerSFU.disabled = !videoProducer || !producerSFUPaused;
        if (btnPauseAllConsumers) btnPauseAllConsumers.disabled = consumers.size === 0;
        if (btnResumeAllConsumers) btnResumeAllConsumers.disabled = consumers.size === 0;
        if (btnToggleStats) btnToggleStats.textContent = statsLoopId ? '⏸ Pausar' : '▶ Retomar';
    }

    // ── Device enumeration ────────────────────────────────────────────────────
    async function enumerateDevices() {
        try {
            const devices = await navigator.mediaDevices.enumerateDevices();
            const cameras = devices.filter((d) => d.kind === 'videoinput');
            const audioIns = devices.filter((d) => d.kind === 'audioinput');
            const audioOuts = devices.filter((d) => d.kind === 'audiooutput');
            const populate = (sel, list, label) => {
                if (!sel) return;
                const prev = sel.value;
                sel.innerHTML = list.map((d, i) => `<option value="${d.deviceId}">${d.label || `${label} ${i + 1}`}</option>`).join('');
                if (prev) sel.value = prev;
            };
            populate(selCamera, cameras, 'Câmera');
            populate(selAudioIn, audioIns, 'Microfone');
            populate(selAudioOut, audioOuts, 'Alto-falante');
            log(`Dispositivos: ${cameras.length} câmeras, ${audioIns.length} mics, ${audioOuts.length} speakers`);
        } catch (e) {
            log('Erro ao enumerar dispositivos', e?.message, 'warn');
        }
    }

    // ── Core join ─────────────────────────────────────────────────────────────
    async function join() {
        log('Join: enviando com token…');
        const data = await request('join', { token });
        log('JOIN ok', { peerId: data.peerId, roomId: data.roomId });

        participantCount = data.participantsCount ?? data.participants?.length ?? 0;
        connected = true;
        connectedAt = Date.now();
        device = new mediasoupClient.Device();
        await device.load({ routerRtpCapabilities: data.rtpCapabilities });
        log('Device carregado');

        const makeTransport = async (direction) => {
            const d = await request('createWebRtcTransport', { direction });
            const t =
                direction === 'send'
                    ? device.createSendTransport({
                          id: d.id,
                          iceParameters: d.iceParameters,
                          iceCandidates: d.iceCandidates,
                          dtlsParameters: d.dtlsParameters,
                      })
                    : device.createRecvTransport({
                          id: d.id,
                          iceParameters: d.iceParameters,
                          iceCandidates: d.iceCandidates,
                          dtlsParameters: d.dtlsParameters,
                      });
            t.on('connect', async ({ dtlsParameters }, cb, eb) => {
                try {
                    await request('connectWebRtcTransport', { transportId: t.id, dtlsParameters });
                    cb();
                } catch (e) {
                    eb(e);
                }
            });
            if (direction === 'send') {
                t.on('produce', async ({ kind, rtpParameters, appData }, cb, eb) => {
                    try {
                        const { id } = await request('produce', { kind, rtpParameters, appData: appData || {} });
                        cb({ id });
                    } catch (e) {
                        eb(e);
                    }
                });
            }
            return t;
        };

        sendTransport = await makeTransport('send');
        recvTransport = await makeTransport('recv');
        log('Transports criados', { send: sendTransport.id, recv: recvTransport.id });
        updateUI();

        while (pendingNewProducers.length) {
            const { producerId, peerId, kind } = pendingNewProducers.shift();
            log('newProducer', { producerId, peerId: peerId?.slice(0, 28), kind });
            await consume(producerId, peerId, kind);
        }
    }

    // ── Leave ─────────────────────────────────────────────────────────────────
    function leave(reason = 'user') {
        log(`Saindo (${reason})`);
        intentionalClose = true;
        stopStatsLoop();

        if (audioProducer) {
            try {
                audioProducer.close();
            } catch {}
            audioProducer = null;
        }
        if (videoProducer) {
            try {
                videoProducer.close();
            } catch {}
            videoProducer = null;
        }
        consumers.forEach((c) => {
            try {
                c.close();
            } catch {}
        });
        consumers.clear();
        lastConsumerStats.clear();

        if (localStream) {
            localStream.getTracks().forEach((t) => t.stop());
            localStream = null;
        }
        if (localVideo) localVideo.srcObject = null;
        remotePeerContainers.clear();
        if (remoteVideosContainer) remoteVideosContainer.innerHTML = '';
        const emptyState = document.getElementById('videoEmptyState');
        if (emptyState) emptyState.style.display = 'flex';

        if (sendTransport) {
            try {
                sendTransport.close();
            } catch {}
            sendTransport = null;
        }
        if (recvTransport) {
            try {
                recvTransport.close();
            } catch {}
            recvTransport = null;
        }

        if (ws?.readyState === WebSocket.OPEN) {
            send({ action: 'leave' });
            ws.close();
        }
        ws = null;

        connected = false;
        cameraOn = false;
        micOn = false;
        videoSendPaused = false;
        producerSFUPaused = false;
        consumersSFUPaused = false;
        participantCount = 0;
        connectedAt = null;
        lastVideoStatsSend = null;
        lastAudioStatsSend = null;
        pendingNewProducers.length = 0;
        updateUI();
    }

    // ── Connect ───────────────────────────────────────────────────────────────
    function connect() {
        if (ws) return;
        intentionalClose = false;
        const url = sfuWsUrl.replace(/^http/, 'ws');
        log('Conectando a ' + url);
        ws = new WebSocket(url);

        ws.onopen = () => {
            log('WebSocket aberto');
            join().catch((e) => {
                log('Join falhou', e.message, 'error');
                connected = false;
                updateUI();
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
                    const { peerId, participants, participantsCount } = msg.data || {};
                    participantCount = participantsCount ?? participants?.length ?? participantCount + 1;
                    log('Peer entrou', { peerId: peerId?.slice(0, 28) });
                    updateUI();
                    return;
                }

                if (msg.action === 'peerLeft') {
                    const { peerId, participantsCount, participants } = msg.data || {};
                    participantCount = participantsCount ?? participants?.length ?? Math.max(0, participantCount - 1);
                    log('Peer saiu', { peerId: peerId?.slice(0, 28) });
                    const entry = remotePeerContainers.get(peerId);
                    if (entry) {
                        entry.div.remove();
                        remotePeerContainers.delete(peerId);
                    }
                    if (remotePeerContainers.size === 0) {
                        const emptyState = document.getElementById('videoEmptyState');
                        if (emptyState) emptyState.style.display = 'flex';
                    }
                    updateUI();
                    return;
                }

                if (msg.action === 'newProducer') {
                    const { producerId, peerId, kind } = msg.data || {};
                    if (!producerId) return;
                    if (recvTransport && device?.rtpCapabilities) {
                        log('newProducer', { producerId, peerId: peerId?.slice(0, 28), kind });
                        consume(producerId, peerId, kind);
                    } else {
                        pendingNewProducers.push({ producerId, peerId, kind });
                    }
                    return;
                }

                if (!msg.action && msg.data?.producerClosed && msg.data?.consumerId) {
                    const consumer = consumers.get(msg.data.consumerId);
                    if (consumer) {
                        consumer.close();
                        consumers.delete(msg.data.consumerId);
                        lastConsumerStats.delete(msg.data.consumerId);
                    }
                    log('Consumer fechado (producer remoto encerrado)', { consumerId: msg.data.consumerId });
                    return;
                }
            } catch {}
        };

        ws.onclose = () => {
            log('WebSocket fechado' + (intentionalClose ? ' (voluntário)' : ' (inesperado)'), null, intentionalClose ? 'info' : 'warn');
            connected = false;
            updateUI();
            if (!intentionalClose && autoReconnect) {
                reconnectCount++;
                log(`Auto-reconexão ${reconnectCount} em 2s…`, null, 'warn');
                if (perfReconns) perfReconns.textContent = String(reconnectCount);
                setTimeout(() => {
                    ws = null;
                    connect();
                }, 2000);
            }
        };

        ws.onerror = () => log('WebSocket erro', null, 'error');
    }

    // ── Start camera (full — audio + video) ───────────────────────────────────
    async function startCamera(videoOnly = false) {
        if (!sendTransport) {
            log('startCamera: sem sendTransport');
            return;
        }
        try {
            const videoConstraints = selCamera?.value ? { deviceId: { exact: selCamera.value } } : true;
            const audioConstraints = selAudioIn?.value ? { deviceId: { exact: selAudioIn.value } } : true;
            log(`getUserMedia: ${videoOnly ? 'sem câmera' : 'câmera + microfone'}…`);
            localStream = await navigator.mediaDevices.getUserMedia({
                audio: audioConstraints,
                video: videoOnly ? false : videoConstraints,
            });
            localStream.getTracks().forEach((t) => createdTracks.add(t));

            if (localVideo) {
                localVideo.muted = true;
                localVideo.srcObject = localStream;
                localVideo.play().catch(() => {});
            }
            await enumerateDevices();

            const audioTrack = localStream.getAudioTracks()[0];
            if (audioTrack) {
                audioProducer = await sendTransport.produce({ track: audioTrack, appData: { source: 'mic' } });
                micOn = true;
                log('AudioProducer criado', { id: audioProducer.id });
            }

            if (!videoOnly) {
                const videoTrack = localStream.getVideoTracks()[0];
                if (videoTrack) {
                    videoProducer = await sendTransport.produce({
                        track: videoTrack,
                        codecOptions: { videoGoogleStartBitrate: 1000 },
                        appData: { source: 'camera' },
                    });
                    cameraOn = true;
                    log('VideoProducer criado', { id: videoProducer.id });
                }
            }

            startStatsLoop();
            updateUI();
        } catch (e) {
            if (e.name === 'NotAllowedError') log('Permissão negada pelo usuário!', null, 'error');
            else if (e.name === 'NotFoundError') log('Dispositivo não encontrado', null, 'error');
            else if (e.name === 'NotReadableError') log('Dispositivo ocupado por outro app', null, 'error');
            else log('getUserMedia erro', e?.message ?? String(e), 'error');
        }
    }

    // ── Toggle camera ─────────────────────────────────────────────────────────
    async function toggleCamera() {
        if (cameraOn) {
            if (videoProducer) {
                const producerId = videoProducer.id;
                try {
                    videoProducer.close();
                } catch {}
                videoProducer = null;
                request('closeProducer', { producerId }).catch(() => {});
            }
            if (localStream)
                localStream.getVideoTracks().forEach((t) => {
                    t.stop();
                    createdTracks.delete(t);
                    localStream.removeTrack(t);
                });
            cameraOn = false;
            videoSendPaused = false;
            producerSFUPaused = false;
            log('Câmera desligada');
        } else {
            if (!sendTransport) return;
            try {
                const constraints = selCamera?.value ? { deviceId: { exact: selCamera.value } } : true;
                const stream = await navigator.mediaDevices.getUserMedia({ video: constraints });
                const track = stream.getVideoTracks()[0];
                createdTracks.add(track);
                if (!localStream) localStream = new MediaStream();
                localStream.addTrack(track);
                if (localVideo) {
                    localVideo.srcObject = localStream;
                    localVideo.play().catch(() => {});
                }
                videoProducer = await sendTransport.produce({
                    track,
                    codecOptions: { videoGoogleStartBitrate: 1000 },
                    appData: { source: 'camera' },
                });
                cameraOn = true;
                videoSendPaused = false;
                producerSFUPaused = false;
                log('Câmera ligada', { id: videoProducer.id });
            } catch (e) {
                log('Erro ao ligar câmera', e?.message, 'error');
            }
        }
        updateUI();
    }

    // ── Toggle mic ────────────────────────────────────────────────────────────
    function toggleMic() {
        if (!audioProducer?.track) return;
        micOn = !micOn;
        audioProducer.track.enabled = micOn;
        log(micOn ? 'Microfone ativado' : 'Microfone mutado');
        updateUI();
    }

    // ── Pause/resume video send (track.enabled) ───────────────────────────────
    function toggleVideoSend() {
        if (!videoProducer?.track) return;
        videoSendPaused = !videoSendPaused;
        videoProducer.track.enabled = !videoSendPaused;
        log(videoSendPaused ? 'Envio de vídeo pausado (track)' : 'Envio de vídeo retomado (track)');
        updateUI();
    }

    // ── Black video — substitui câmera por canvas preto ──────────────────────
    async function toggleBlackVideo() {
        if (!videoProducer) {
            log('blackVideo: sem videoProducer ativo');
            return;
        }
        try {
            const canvas = document.createElement('canvas');
            canvas.width = 640;
            canvas.height = 480;
            canvas.getContext('2d')?.fillRect(0, 0, 640, 480);
            const stream = canvas.captureStream(5);
            const newTrack = stream.getVideoTracks()[0];
            createdTracks.add(newTrack);
            await videoProducer.replaceTrack({ track: newTrack });
            if (localStream) {
                localStream.getVideoTracks().forEach((t) => {
                    if (t !== newTrack) {
                        t.stop();
                        createdTracks.delete(t);
                        localStream.removeTrack(t);
                    }
                });
                localStream.addTrack(newTrack);
            }
            if (localVideo) localVideo.srcObject = localStream;
            log('Vídeo substituído por canvas preto (simula falha de câmera)');
        } catch (e) {
            log('Erro vídeo preto', e?.message, 'error');
        }
    }

    // ── Switch camera ─────────────────────────────────────────────────────────
    async function switchCamera() {
        const deviceId = selCamera?.value;
        if (!deviceId || !videoProducer) return;
        try {
            const stream = await navigator.mediaDevices.getUserMedia({ video: { deviceId: { exact: deviceId } } });
            const newTrack = stream.getVideoTracks()[0];
            createdTracks.add(newTrack);
            await videoProducer.replaceTrack({ track: newTrack });
            if (localStream) {
                localStream.getVideoTracks().forEach((t) => {
                    if (t !== newTrack) {
                        t.stop();
                        createdTracks.delete(t);
                        localStream.removeTrack(t);
                    }
                });
                localStream.addTrack(newTrack);
            }
            if (localVideo) localVideo.srcObject = localStream;
            log('Câmera trocada (replaceTrack)', { deviceId });
        } catch (e) {
            log('Erro ao trocar câmera', e?.message, 'error');
        }
    }

    // ── Switch audio input ────────────────────────────────────────────────────
    async function switchAudioInput() {
        const deviceId = selAudioIn?.value;
        if (!deviceId || !audioProducer) return;
        try {
            const stream = await navigator.mediaDevices.getUserMedia({ audio: { deviceId: { exact: deviceId } } });
            const newTrack = stream.getAudioTracks()[0];
            createdTracks.add(newTrack);
            await audioProducer.replaceTrack({ track: newTrack });
            if (localStream) {
                localStream.getAudioTracks().forEach((t) => {
                    if (t !== newTrack) {
                        t.stop();
                        createdTracks.delete(t);
                        localStream.removeTrack(t);
                    }
                });
                localStream.addTrack(newTrack);
            }
            log('Microfone trocado (replaceTrack)', { deviceId });
        } catch (e) {
            log('Erro ao trocar microfone', e?.message, 'error');
        }
    }

    // ── Switch audio output ───────────────────────────────────────────────────
    async function switchAudioOutput() {
        const deviceId = selAudioOut?.value;
        if (!deviceId) return;
        let count = 0;
        for (const entry of remotePeerContainers.values()) {
            if (entry.audioEl?.setSinkId) {
                try {
                    await entry.audioEl.setSinkId(deviceId);
                    count++;
                } catch (e) {
                    log('setSinkId erro', e?.message, 'warn');
                }
            }
        }
        if (count === 0) log('setSinkId não suportado ou sem áudio remoto', null, 'warn');
        else log('Alto-falante trocado', { deviceId, elementos: count });
    }

    // ── Graceful reconnect ────────────────────────────────────────────────────
    async function simulateReconnect() {
        log('Reconexão graceful…');
        leave('reconnect');
        await new Promise((r) => setTimeout(r, 600));
        connect();
    }

    // ── Force disconnect (simula queda de rede) ───────────────────────────────
    function forceDisconnect() {
        log('Forçando queda de conexão (sem leave)…', null, 'warn');
        intentionalClose = false; // Não é voluntário → auto-reconnect pode agir
        stopStatsLoop();
        if (ws) {
            try {
                ws.close();
            } catch {}
            ws = null;
        }
        connected = false;
        updateUI();
        log('WS fechado abruptamente. Se auto-reconexão estiver ON, tentará reconectar em 2s.');
    }

    // ── Auto-reconnect toggle ─────────────────────────────────────────────────
    function toggleAutoReconnect() {
        autoReconnect = !autoReconnect;
        log(autoReconnect ? 'Auto-reconexão ATIVADA' : 'Auto-reconexão desativada');
        if (perfAutoReconn) perfAutoReconn.textContent = autoReconnect ? 'ON' : 'OFF';
        updateUI();
    }

    // ── SFU: pause/resume producer server-side ────────────────────────────────
    async function pauseProducerSFU() {
        if (!videoProducer) return;
        try {
            await request('pauseProducer', { producerId: videoProducer.id });
            producerSFUPaused = true;
            log('Producer pausado no SFU (sem dados enviados ao router)');
            updateUI();
        } catch (e) {
            log('pauseProducer erro', e?.message, 'error');
        }
    }

    async function resumeProducerSFU() {
        if (!videoProducer) return;
        try {
            await request('resumeProducer', { producerId: videoProducer.id });
            producerSFUPaused = false;
            log('Producer retomado no SFU');
            updateUI();
        } catch (e) {
            log('resumeProducer erro', e?.message, 'error');
        }
    }

    // ── SFU: pause/resume all consumers server-side ───────────────────────────
    async function pauseAllConsumersSFU() {
        let count = 0;
        for (const [consumerId] of consumers) {
            try {
                await request('pauseConsumer', { consumerId });
                count++;
            } catch {}
        }
        consumersSFUPaused = true;
        log(`${count} consumers pausados no SFU (economiza banda)`);
        updateUI();
    }

    async function resumeAllConsumersSFU() {
        let count = 0;
        for (const [consumerId] of consumers) {
            try {
                await request('resumeConsumer', { consumerId });
                count++;
            } catch {}
        }
        consumersSFUPaused = false;
        log(`${count} consumers retomados no SFU`);
        updateUI();
    }

    // ── Remote mute (local) ───────────────────────────────────────────────────
    function toggleRemoteMute() {
        remoteMuted = !remoteMuted;
        for (const entry of remotePeerContainers.values()) {
            if (entry.audioEl) entry.audioEl.muted = remoteMuted;
        }
        log(remoteMuted ? 'Remoto mutado localmente' : 'Remoto desmutado');
        updateUI();
    }

    // ── Cleanup / leak detection ───────────────────────────────────────────────
    function testCleanup() {
        const activeTracks = [...createdTracks].filter((t) => t.readyState === 'live');
        const stoppedTracks = [...createdTracks].filter((t) => t.readyState === 'ended');
        log('── Relatório de Tracks ──────────────────────');
        log(`Tracks criadas pelo app: ${createdTracks.size}`);
        log(
            `  ● Ativas (live): ${activeTracks.length}`,
            activeTracks.map((t) => `${t.kind}/${t.label?.slice(0, 20)}`),
        );
        log(`  ● Encerradas (ended): ${stoppedTracks.length}`);
        log(`  ● Consumers ativos: ${consumers.size}`);
        if (audioProducer) log(`  ● AudioProducer: ${audioProducer.id}`);
        if (videoProducer) log(`  ● VideoProducer: ${videoProducer.id} | track enabled=${videoProducer.track?.enabled}`);
        if (typeof performance !== 'undefined' && performance.memory) {
            const mb = (b) => (b / 1024 / 1024).toFixed(1) + 'MB';
            log(
                `  ● Heap: usado=${mb(performance.memory.usedJSHeapSize)} / total=${mb(performance.memory.totalJSHeapSize)} / limite=${mb(performance.memory.jsHeapSizeLimit)}`,
            );
        }
        log('────────────────────────────────────────────');
        if (activeTracks.length > 3) log('⚠ Possível vazamento: muitas tracks ativas!', null, 'warn');
        else log('✓ Tracks dentro do esperado');
        if (perfTracks) perfTracks.textContent = String(activeTracks.length);
    }

    // ── Consume ───────────────────────────────────────────────────────────────
    async function consume(producerId, peerId, kind) {
        if (!recvTransport || !device?.rtpCapabilities) return;
        try {
            log('Consume: pedindo', { producerId, peerId: peerId?.slice(0, 28), kind });
            const data = await request('consume', { producerId, rtpCapabilities: device.rtpCapabilities });
            const consumer = await recvTransport.consume({
                id: data.id,
                producerId: data.producerId,
                kind: data.kind,
                rtpParameters: data.rtpParameters,
                type: data.type,
            });
            consumers.set(consumer.id, consumer);
            consumer.track.enabled = true;
            await request('resumeConsumer', { consumerId: consumer.id });

            const entry = getOrCreateRemotePeerContainer(peerId);
            const stream = new MediaStream([consumer.track]);

            if (kind === 'video') {
                entry.videoEl.srcObject = stream;
                entry.videoEl.muted = true;
                entry.videoEl.play().catch(() => {});
                if (remoteMuted) entry.videoEl.pause();
                const kf = () => request('requestKeyFrame', { consumerId: consumer.id }).catch(() => {});
                kf();
                [500, 1500, 3000].forEach((ms) => setTimeout(kf, ms));
            } else {
                entry.audioEl.srcObject = stream;
                entry.audioEl.muted = remoteMuted;
                entry.audioEl.volume = 1;
                entry.audioEl.play().catch(() => {});
                if (btnActivateAudio) btnActivateAudio.style.display = 'inline-block';
            }

            log('Consumer criado', { consumerId: consumer.id, kind, peerId: peerId?.slice(0, 28) });
            updateUI();
        } catch (e) {
            log('Consume erro', e?.message ?? String(e), 'error');
        }
    }

    function getOrCreateRemotePeerContainer(peerId) {
        let entry = remotePeerContainers.get(peerId);
        if (entry) return entry;

        // Card container
        const div = document.createElement('div');
        div.setAttribute('data-peer-id', peerId);
        div.style.cssText = 'position:relative; border-radius:8px; overflow:hidden; background:#0f0f1a; aspect-ratio:4/3;';

        // Video fills the card
        const videoEl = document.createElement('video');
        videoEl.autoplay = true;
        videoEl.playsInline = true;
        videoEl.muted = true;
        videoEl.setAttribute('playsinline', '');
        videoEl.style.cssText = 'width:100%; height:100%; display:block; object-fit:cover; background:#111;';
        div.appendChild(videoEl);

        // Label overlay
        const label = document.createElement('span');
        label.style.cssText =
            'position:absolute; bottom:5px; left:7px; font-size:9px; color:rgba(255,255,255,0.55); background:rgba(0,0,0,0.5); padding:1px 5px; border-radius:3px; text-transform:uppercase; letter-spacing:0.4px;';
        label.textContent = peerId.slice(0, 16) + '…';
        div.appendChild(label);

        // Hidden audio
        const audioEl = document.createElement('audio');
        audioEl.autoplay = true;
        audioEl.muted = false;
        audioEl.volume = 1;
        audioEl.style.cssText = 'position:absolute; width:1px; height:1px; left:-9999px;';
        div.appendChild(audioEl);

        entry = { div, videoEl, audioEl };
        remotePeerContainers.set(peerId, entry);
        if (remoteVideosContainer) remoteVideosContainer.appendChild(div);

        // Hide empty state once we have a remote peer
        const emptyState = document.getElementById('videoEmptyState');
        if (emptyState) emptyState.style.display = 'none';

        return entry;
    }

    // ── Stats ─────────────────────────────────────────────────────────────────
    function startStatsLoop() {
        if (statsLoopId) return;
        statsLoopId = setInterval(refreshStats, 1000);
    }

    function stopStatsLoop() {
        if (statsLoopId) {
            clearInterval(statsLoopId);
            statsLoopId = null;
        }
    }

    /** Converte valor numérico em classe CSS de qualidade */
    function qClass(value, good, warn) {
        if (typeof value !== 'number' || isNaN(value)) return 'q-na';
        if (value <= good) return 'q-green';
        if (value <= warn) return 'q-yellow';
        return 'q-red';
    }

    /** Para bitrate: maior é melhor */
    function qBitrate(kbps) {
        if (typeof kbps !== 'number' || isNaN(kbps)) return 'q-na';
        if (kbps >= 400) return 'q-green';
        if (kbps >= 100) return 'q-yellow';
        return 'q-red';
    }

    async function refreshStats() {
        if (!statsCards) return;
        const rows = [];
        const now = Date.now();

        // ── Video producer ──────────────────────────────────────────────────────
        if (videoProducer && typeof videoProducer.getStats === 'function') {
            try {
                const report = await videoProducer.getStats();
                let bytesSent = 0,
                    rtt = null,
                    loss = null;
                let width = 0,
                    height = 0,
                    fps = null;
                report.forEach((s) => {
                    if (s.type === 'outbound-rtp') {
                        bytesSent += s.bytesSent || 0;
                        width = s.frameWidth || width;
                        height = s.frameHeight || height;
                        fps = s.framesPerSecond ?? fps;
                    }
                    if (s.type === 'remote-inbound-rtp') {
                        if (s.roundTripTime != null) rtt = Math.round(s.roundTripTime * 1000);
                        loss = s.fractionLost != null ? (s.fractionLost * 100).toFixed(1) : loss;
                    }
                });
                let kbps = null;
                if (lastVideoStatsSend && now > lastVideoStatsSend.time) {
                    kbps = Math.round(((bytesSent - lastVideoStatsSend.bytes) * 8) / ((now - lastVideoStatsSend.time) / 1000) / 1000);
                }
                lastVideoStatsSend = { time: now, bytes: bytesSent };
                const state = producerSFUPaused ? 'SFU-PAUSADO' : videoSendPaused ? 'PAUSADO' : cameraOn ? '✓' : 'OFF';
                rows.push({
                    label: '📹 Vídeo (send)',
                    kbps,
                    rtt,
                    loss: loss ?? null,
                    fps: fps != null ? fps.toFixed(0) : null,
                    res: width && height ? `${width}×${height}` : null,
                    jitter: null,
                    state,
                });
            } catch {}
        }

        // ── Audio producer ──────────────────────────────────────────────────────
        if (audioProducer && typeof audioProducer.getStats === 'function') {
            try {
                const report = await audioProducer.getStats();
                let bytesSent = 0,
                    rtt = null,
                    loss = null;
                report.forEach((s) => {
                    if (s.type === 'outbound-rtp') bytesSent += s.bytesSent || 0;
                    if (s.type === 'remote-inbound-rtp') {
                        if (s.roundTripTime != null) rtt = Math.round(s.roundTripTime * 1000);
                        if (s.fractionLost != null) loss = (s.fractionLost * 100).toFixed(1);
                    }
                });
                let kbps = null;
                if (lastAudioStatsSend && now > lastAudioStatsSend.time) {
                    kbps = Math.round(((bytesSent - lastAudioStatsSend.bytes) * 8) / ((now - lastAudioStatsSend.time) / 1000) / 1000);
                }
                lastAudioStatsSend = { time: now, bytes: bytesSent };
                rows.push({
                    label: '🎤 Áudio (send)',
                    kbps,
                    rtt,
                    loss: loss ?? null,
                    fps: null,
                    res: null,
                    jitter: null,
                    state: micOn ? '✓' : 'MUDO',
                });
            } catch {}
        }

        // ── Consumers ───────────────────────────────────────────────────────────
        for (const [consumerId, consumer] of consumers) {
            try {
                const report = await consumer.getStats();
                let bytes = 0,
                    lost = 0,
                    rcvd = 0,
                    jitterSec = 0,
                    fps = null,
                    width = 0,
                    height = 0,
                    rtt = null;
                report.forEach((s) => {
                    if (s.type === 'inbound-rtp') {
                        bytes += s.bytesReceived || 0;
                        lost += s.packetsLost || 0;
                        rcvd += s.packetsReceived || 0;
                        jitterSec = s.jitter || jitterSec;
                        fps = s.framesPerSecond ?? fps;
                        width = s.frameWidth || width;
                        height = s.frameHeight || height;
                    }
                    if (s.type === 'candidate-pair' && s.currentRoundTripTime && s.nominated) {
                        rtt = Math.round(s.currentRoundTripTime * 1000);
                    }
                });
                const total = rcvd + lost;
                const loss = total > 0 ? parseFloat(((lost / total) * 100).toFixed(1)) : 0;
                const jitter = parseFloat((jitterSec * 1000).toFixed(1));

                const last = lastConsumerStats.get(consumerId);
                let kbps = null;
                if (last && now > last.time) {
                    kbps = Math.round(((bytes - last.bytes) * 8) / ((now - last.time) / 1000) / 1000);
                }
                lastConsumerStats.set(consumerId, { time: now, bytes });

                const emoji = consumer.kind === 'video' ? '📥 Vídeo' : '🔈 Áudio';
                rows.push({
                    label: `${emoji} recv (${consumerId.slice(0, 8)}…)`,
                    kbps,
                    rtt,
                    loss,
                    fps: fps != null ? fps.toFixed(0) : null,
                    res: width && height ? `${width}×${height}` : null,
                    jitter,
                    state: consumersSFUPaused ? 'PAUSADO' : '✓',
                });
            } catch {}
        }

        // Log de transmissão a cada 5 s (5 ticks de 1 s)
        statsLogTick++;
        if (statsLogTick % 5 === 0 && rows.length > 0) {
            for (const r of rows) {
                const parts = [];
                if (r.kbps != null) parts.push(`${r.kbps} kbps`);
                if (r.rtt != null) parts.push(`RTT ${r.rtt}ms`);
                if (r.loss != null) parts.push(`perda ${r.loss}%`);
                if (r.fps != null) parts.push(`${r.fps}fps`);
                if (r.res != null) parts.push(r.res);
                if (r.jitter != null) parts.push(`jitter ${r.jitter}ms`);
                log(`${r.label}: ${parts.join(' | ')} [${r.state}]`, null, 'stats');
            }
        }

        renderStatsCards(rows, now);
    }

    function renderStatsCards(rows, now) {
        if (statsCards) {
            if (rows.length === 0) {
                statsCards.innerHTML = '<p class="stats-empty">Nenhuma mídia ativa…</p>';
            } else {
                statsCards.innerHTML = rows
                    .map((r) => {
                        const kbpsStr = r.kbps != null ? `${r.kbps} kbps` : '—';
                        const rttStr = r.rtt != null ? `${r.rtt}ms` : '—';
                        const lossStr = r.loss != null ? `${r.loss}%` : '—';
                        const jitStr = r.jitter != null ? `${r.jitter}ms` : '—';
                        const fpsStr = r.fps != null ? r.fps : '—';
                        const resStr = r.res != null ? r.res : null;
                        const kCls = r.kbps != null ? qBitrate(r.kbps) : 'q-na';
                        const rCls = r.rtt != null ? qClass(r.rtt, 100, 300) : 'q-na';
                        const lCls = r.loss != null ? qClass(r.loss, 1, 5) : 'q-na';
                        const jCls = r.jitter != null ? qClass(r.jitter, 30, 100) : 'q-na';
                        const worst = [kCls, rCls, lCls, jCls];
                        const borderColor = worst.includes('q-red') ? '#f87171' : worst.includes('q-yellow') ? '#fbbf24' : '#4ade80';
                        const fpsRow =
                            r.fps != null
                                ? `<div class="stat-metric"><span class="stat-metric-label">FPS</span><span class="stat-metric-value">${fpsStr}</span></div>
               <div class="stat-metric"><span class="stat-metric-label">Resolução</span><span class="stat-metric-value">${resStr ?? '—'}</span></div>`
                                : '';
                        return `<div class="stat-card" style="border-left-color:${borderColor}">
            <div class="stat-card-title">${r.label}<span class="stat-state">${r.state}</span></div>
            <div class="stat-metrics">
              <div class="stat-metric"><span class="stat-metric-label">Bitrate</span><span class="stat-metric-value ${kCls}">${kbpsStr}</span></div>
              <div class="stat-metric"><span class="stat-metric-label">RTT</span><span class="stat-metric-value ${rCls}">${rttStr}</span></div>
              <div class="stat-metric"><span class="stat-metric-label">Perda</span><span class="stat-metric-value ${lCls}">${lossStr}</span></div>
              <div class="stat-metric"><span class="stat-metric-label">Jitter</span><span class="stat-metric-value ${jCls}">${jitStr}</span></div>
              ${fpsRow}
            </div>
          </div>`;
                    })
                    .join('');
            }
        }

        // Performance grid
        if (perfTracks) {
            const active = [...createdTracks].filter((t) => t.readyState === 'live').length;
            perfTracks.textContent = String(active);
        }
        if (perfReconns) perfReconns.textContent = String(reconnectCount);
        if (perfUptime && connectedAt) {
            const secs = Math.floor((now - connectedAt) / 1000);
            const mm = String(Math.floor(secs / 60)).padStart(2, '0');
            const ss = String(secs % 60).padStart(2, '0');
            perfUptime.textContent = `${mm}:${ss}`;
        }
        if (perfHeap && typeof performance !== 'undefined' && performance.memory) {
            perfHeap.textContent = (performance.memory.usedJSHeapSize / 1024 / 1024).toFixed(1) + 'MB';
        }
    }

    // ── Cleanup all ───────────────────────────────────────────────────────────
    function cleanupAll() {
        leave('cleanup');
        log('Limpeza completa.');
    }

    // ── Event listeners ───────────────────────────────────────────────────────
    btnJoin?.addEventListener('click', connect);
    btnLeave?.addEventListener('click', () => leave('user'));
    btnStartCamera?.addEventListener('click', () => startCamera(false));
    btnStartNoCamera?.addEventListener('click', () => startCamera(true));
    btnToggleCam?.addEventListener('click', toggleCamera);
    btnToggleMic?.addEventListener('click', toggleMic);
    btnPauseVideo?.addEventListener('click', toggleVideoSend);
    btnBlackVideo?.addEventListener('click', toggleBlackVideo);
    btnSwitchCam?.addEventListener('click', switchCamera);
    btnSwitchAudioIn?.addEventListener('click', switchAudioInput);
    btnSwitchAudioOut?.addEventListener('click', switchAudioOutput);
    btnReconnect?.addEventListener('click', simulateReconnect);
    btnForceDisconnect?.addEventListener('click', forceDisconnect);
    btnToggleAutoReconn?.addEventListener('click', toggleAutoReconnect);
    btnCleanup?.addEventListener('click', cleanupAll);
    btnPauseProducerSFU?.addEventListener('click', pauseProducerSFU);
    btnResumeProducerSFU?.addEventListener('click', resumeProducerSFU);
    btnPauseAllConsumers?.addEventListener('click', pauseAllConsumersSFU);
    btnResumeAllConsumers?.addEventListener('click', resumeAllConsumersSFU);
    btnToggleRemoteMute?.addEventListener('click', toggleRemoteMute);
    btnTestCleanup?.addEventListener('click', testCleanup);

    btnToggleStats?.addEventListener('click', () => {
        if (statsLoopId) {
            stopStatsLoop();
        } else {
            refreshStats();
            startStatsLoop();
        }
        updateUI();
    });

    btnActivateAudio?.addEventListener('click', () => {
        remotePeerContainers.forEach((entry) => {
            if (!entry.audioEl) return;
            entry.audioEl.muted = false;
            entry.audioEl.volume = 1;
            entry.audioEl.play().catch(() => {});
        });
        if (btnActivateAudio) btnActivateAudio.style.display = 'none';
        log('Áudio remoto ativado manualmente');
    });

    document.getElementById('btnCopyLogs')?.addEventListener('click', () => {
        if (!logEl) return;
        const text = [...logEl.children].map((el) => el.textContent).join('\n');
        navigator.clipboard
            .writeText(text)
            .then(() => {
                log('Logs copiados para a área de transferência ✓');
            })
            .catch(() => log('Falha ao copiar logs', null, 'warn'));
    });

    document.getElementById('btnClearLogs')?.addEventListener('click', () => {
        if (logEl) logEl.innerHTML = '';
    });

    window.addEventListener('beforeunload', () => {
        if (connected) send({ action: 'leave' });
    });

    // ── Init ──────────────────────────────────────────────────────────────────
    updateUI();
    log('SFU Test v3 pronto. Sala: ' + (config.roomId || '—'));
    log('💡 Dica: Use DevTools → Network → Throttle para simular internet ruim.');
}
