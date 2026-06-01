import * as mediasoup from 'mediasoup-client';
import type { Consumer, Producer, RtpCapabilities, Transport } from 'mediasoup-client/lib/types';
import { ref } from 'vue';

export type SfuConnectionState = 'idle' | 'connecting' | 'connected' | 'failed' | 'closed';

interface SfuResponse {
    action?: string;
    id?: string;
    ok?: boolean;
    routerRtpCapabilities?: RtpCapabilities;
    rtpCapabilities?: RtpCapabilities;
    params?: Record<string, unknown>;
    producerId?: string;
    peerId?: string;
    consumerId?: string;
    kind?: string;
    rtpParameters?: Record<string, unknown>;
    error?: string | { message?: string };
    data?: Record<string, unknown>;
    [key: string]: unknown;
}

interface PendingRequest {
    resolve: (value: SfuResponse) => void;
    reject: (reason: Error) => void;
    timeout: ReturnType<typeof setTimeout>;
}

type PendingProducer = { producerId: string; peerId?: string; kind?: string };

export function useSfu() {
    const connectionState = ref<SfuConnectionState>('idle');
    const localStream = ref<MediaStream | null>(null);
    const remoteStreams = ref<Map<string, MediaStream>>(new Map());
    const isMicEnabled = ref(true);
    const isCameraEnabled = ref(true);

    let ws: WebSocket | null = null;
    let device: mediasoup.Device | null = null;
    let sendTransport: Transport | null = null;
    let recvTransport: Transport | null = null;
    const producers = new Map<string, Producer>();
    const consumers = new Map<string, Consumer>();
    const pendingRequests = new Map<string, PendingRequest>();
    const pendingNewProducers: PendingProducer[] = [];
    let requestIdCounter = 0;

    const sendRequest = (action: string, data: Record<string, unknown> = {}): Promise<SfuResponse> => {
        return new Promise((resolve, reject) => {
            if (!ws || ws.readyState !== WebSocket.OPEN) {
                reject(new Error('WebSocket não conectado'));
                return;
            }

            const id = String(++requestIdCounter);
            const timeout = setTimeout(() => {
                pendingRequests.delete(id);
                reject(new Error(`Timeout na requisição: ${action}`));
            }, 15000);

            pendingRequests.set(id, { resolve, reject, timeout });

            ws.send(JSON.stringify({ action, id, ...data }));
        });
    };

    const normalizeResponse = (msg: SfuResponse): SfuResponse => {
        if (msg.ok === false) {
            const message = typeof msg.error === 'string' ? msg.error : (msg.error?.message ?? 'Erro no servidor');
            throw new Error(message);
        }

        return (msg.data ?? msg) as SfuResponse;
    };

    const sendNotification = (action: string, data: Record<string, unknown> = {}) => {
        if (!ws || ws.readyState !== WebSocket.OPEN) return;
        ws.send(JSON.stringify({ action, ...data }));
    };

    const handleMessage = async (raw: string) => {
        let msg: SfuResponse;
        try {
            msg = JSON.parse(raw) as SfuResponse;
        } catch {
            return;
        }

        if (msg.id && pendingRequests.has(msg.id as string)) {
            const pending = pendingRequests.get(msg.id as string)!;
            pendingRequests.delete(msg.id as string);
            clearTimeout(pending.timeout);

            try {
                pending.resolve(normalizeResponse(msg));
            } catch (error) {
                pending.reject(error as Error);
            }
            return;
        }

        switch (msg.action) {
            case 'newProducer':
                await handleNewProducer(msg);
                break;
            case 'peerLeft':
                handlePeerLeft(msg);
                break;
            case 'ping':
                sendNotification('pong', msg.ts !== undefined ? { ts: msg.ts } : {});
                break;
        }
    };

    const handleNewProducer = async (msg: SfuResponse) => {
        const data = msg.data ?? {};
        const producerId = (msg.producerId ?? data.producerId) as string | undefined;
        const peerId = (msg.peerId ?? data.peerId) as string | undefined;
        const kind = (msg.kind ?? data.kind) as string | undefined;

        if (!producerId) return;

        if (!recvTransport || !device) {
            pendingNewProducers.push({ producerId, peerId, kind });
            return;
        }

        try {
            const response = await sendRequest('consume', {
                producerId,
                rtpCapabilities: device.rtpCapabilities,
            });

            const consumerId = response.consumerId ?? response.id;
            const responseProducerId = response.producerId ?? producerId;

            if (response.error || !consumerId || !response.kind || !response.rtpParameters) return;

            const consumer = await recvTransport.consume({
                id: consumerId as string,
                producerId: responseProducerId as string,
                kind: response.kind as 'audio' | 'video',
                rtpParameters: response.rtpParameters as mediasoup.types.RtpParameters,
            });

            consumers.set(consumer.id, consumer);

            const peerKey = peerId ?? 'remote';
            const updated = new Map(remoteStreams.value);
            const peerStream = updated.get(peerKey) ?? new MediaStream();
            peerStream.addTrack(consumer.track);
            updated.set(peerKey, peerStream);
            remoteStreams.value = updated;

            await sendRequest('resumeConsumer', { consumerId: consumer.id });
        } catch {
            // falha silenciosa — peer pode ter saído antes de consumir
        }
    };

    const handlePeerLeft = (msg: SfuResponse) => {
        const data = msg.data ?? {};
        const peerId = (msg.peerId ?? data.peerId) as string | undefined;
        if (!peerId) return;

        const updated = new Map(remoteStreams.value);
        updated.delete(peerId);
        remoteStreams.value = updated;
    };

    const createWebRtcTransport = async (direction: 'send' | 'recv'): Promise<Transport> => {
        const response = await sendRequest('createWebRtcTransport', { direction });

        const params = (response.params ?? response) as {
            id?: string;
            iceParameters?: mediasoup.types.IceParameters;
            iceCandidates?: mediasoup.types.IceCandidate[];
            dtlsParameters?: mediasoup.types.DtlsParameters;
        };

        if (response.error || !params.id || !params.iceParameters || !params.iceCandidates || !params.dtlsParameters) {
            throw new Error(`Falha ao criar transporte ${direction}: ${response.error ?? 'params inválidos'}`);
        }

        const transport =
            direction === 'send'
                ? device!.createSendTransport(params as mediasoup.types.TransportOptions)
                : device!.createRecvTransport(params as mediasoup.types.TransportOptions);

        transport.on('connect', ({ dtlsParameters }, callback, errback) => {
            sendRequest('connectWebRtcTransport', { transportId: transport.id, dtlsParameters })
                .then(() => callback())
                .catch(errback);
        });

        if (direction === 'send') {
            transport.on('produce', ({ kind, rtpParameters }, callback, errback) => {
                sendRequest('produce', { transportId: transport.id, kind, rtpParameters })
                    .then((res) => {
                        if (res.error || !res.id) {
                            errback(new Error((res.error as string) ?? 'Falha ao produzir'));
                        } else {
                            callback({ id: res.id as string });
                        }
                    })
                    .catch(errback);
            });
        }

        return transport;
    };

    const connect = async (sfuWsUrl: string | null, token: string): Promise<void> => {
        if (connectionState.value === 'connecting' || connectionState.value === 'connected') return;

        connectionState.value = 'connecting';
        console.debug('[VIDEO_CALL][SFU] connect() — state: connecting', { sfuWsUrl, tokenPrefix: token.slice(0, 20) + '...' });

        // Modo stub: sem SFU real, apenas captura mídia local
        if (!sfuWsUrl) {
            try {
                localStream.value = await navigator.mediaDevices.getUserMedia({
                    audio: isMicEnabled.value,
                    video: isCameraEnabled.value,
                });
            } catch {
                localStream.value = null;
            }
            connectionState.value = 'connected';
            return;
        }

        return new Promise((resolve, reject) => {
            ws = new WebSocket(sfuWsUrl);

            ws.onopen = async () => {
                console.debug('[VIDEO_CALL][SFU] WebSocket aberto — enviando join');
                try {
                    const joinResponse = await sendRequest('join', { token });

                    const routerRtpCapabilities = joinResponse.rtpCapabilities ?? joinResponse.routerRtpCapabilities;
                    if (!routerRtpCapabilities) throw new Error('Sem RTP capabilities');

                    device = new mediasoup.Device();
                    await device.load({ routerRtpCapabilities });

                    sendTransport = await createWebRtcTransport('send');
                    recvTransport = await createWebRtcTransport('recv');

                    const stream = await navigator.mediaDevices.getUserMedia({
                        audio: isMicEnabled.value,
                        video: isCameraEnabled.value,
                    });

                    localStream.value = stream;

                    for (const track of stream.getTracks()) {
                        const producer = await sendTransport.produce({ track });
                        producers.set(producer.id, producer);
                    }

                    while (pendingNewProducers.length) {
                        const pending = pendingNewProducers.shift();
                        if (pending) {
                            await handleNewProducer(pending as SfuResponse);
                        }
                    }

                    connectionState.value = 'connected';
                    console.debug('[VIDEO_CALL][SFU] Conectado com sucesso');
                    resolve();
                } catch (err) {
                    console.error('[VIDEO_CALL][SFU] Falha no join:', err);
                    connectionState.value = 'failed';
                    ws?.close();
                    reject(err);
                }
            };

            ws.onmessage = (event) => {
                handleMessage(event.data as string);
            };

            ws.onerror = (ev) => {
                console.error('[VIDEO_CALL][SFU] WebSocket error', ev);
                connectionState.value = 'failed';
                reject(new Error('WebSocket error'));
            };

            ws.onclose = (ev) => {
                console.debug('[VIDEO_CALL][SFU] WebSocket fechado', {
                    code: ev.code,
                    reason: ev.reason,
                    wasConnected: connectionState.value === 'connected',
                });
                if (connectionState.value === 'connected') {
                    connectionState.value = 'closed';
                }
            };
        });
    };

    const disconnect = () => {
        if (ws && ws.readyState === WebSocket.OPEN) {
            sendNotification('leave');
            ws.close();
        }

        ws = null;

        if (localStream.value) {
            localStream.value.getTracks().forEach((track) => track.stop());
            localStream.value = null;
        }

        producers.forEach((p) => p.close());
        producers.clear();

        consumers.forEach((c) => c.close());
        consumers.clear();

        sendTransport?.close();
        sendTransport = null;

        recvTransport?.close();
        recvTransport = null;

        device = null;
        remoteStreams.value = new Map();
        connectionState.value = 'closed';
        pendingRequests.clear();
    };

    const toggleMic = () => {
        if (!localStream.value) return;
        const audioTrack = localStream.value.getAudioTracks()[0];
        if (!audioTrack) return;
        audioTrack.enabled = !audioTrack.enabled;
        isMicEnabled.value = audioTrack.enabled;
    };

    const toggleCamera = () => {
        if (!localStream.value) return;
        const videoTrack = localStream.value.getVideoTracks()[0];
        if (!videoTrack) return;
        videoTrack.enabled = !videoTrack.enabled;
        isCameraEnabled.value = videoTrack.enabled;
    };

    const requestKeyFrame = (consumerId: string) => {
        sendNotification('requestKeyFrame', { consumerId });
    };

    return {
        connectionState,
        localStream,
        remoteStreams,
        isMicEnabled,
        isCameraEnabled,
        connect,
        disconnect,
        toggleMic,
        toggleCamera,
        requestKeyFrame,
    };
}
