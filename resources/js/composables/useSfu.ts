import * as mediasoup from 'mediasoup-client';
import type { Consumer, Producer, RtpCapabilities, Transport } from 'mediasoup-client/lib/types';
import { ref } from 'vue';

export type SfuConnectionState = 'idle' | 'connecting' | 'connected' | 'failed' | 'closed';

interface SfuResponse {
    action: string;
    id?: string;
    routerRtpCapabilities?: RtpCapabilities;
    params?: Record<string, unknown>;
    producerId?: string;
    consumerId?: string;
    kind?: string;
    rtpParameters?: Record<string, unknown>;
    error?: string;
    [key: string]: unknown;
}

type PendingResolver = (value: SfuResponse) => void;

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
    const pendingRequests = new Map<string, PendingResolver>();
    let requestIdCounter = 0;

    const sendRequest = (action: string, data: Record<string, unknown> = {}): Promise<SfuResponse> => {
        return new Promise((resolve, reject) => {
            if (!ws || ws.readyState !== WebSocket.OPEN) {
                reject(new Error('WebSocket não conectado'));
                return;
            }

            const id = String(++requestIdCounter);
            pendingRequests.set(id, resolve);

            const timeout = setTimeout(() => {
                pendingRequests.delete(id);
                reject(new Error(`Timeout na requisição: ${action}`));
            }, 15000);

            pendingRequests.set(id, (response) => {
                clearTimeout(timeout);
                resolve(response);
            });

            ws.send(JSON.stringify({ action, id, ...data }));
        });
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
            const resolve = pendingRequests.get(msg.id as string)!;
            pendingRequests.delete(msg.id as string);
            resolve(msg);
            return;
        }

        switch (msg.action) {
            case 'newProducer':
                await handleNewProducer(msg);
                break;
            case 'peerLeft':
                handlePeerLeft(msg);
                break;
        }
    };

    const handleNewProducer = async (msg: SfuResponse) => {
        if (!recvTransport || !device || !msg.producerId) return;

        try {
            const response = await sendRequest('consume', {
                producerId: msg.producerId,
                rtpCapabilities: device.rtpCapabilities,
            });

            if (response.error || !response.consumerId || !response.kind || !response.rtpParameters) return;

            const consumer = await recvTransport.consume({
                id: response.consumerId as string,
                producerId: msg.producerId as string,
                kind: response.kind as 'audio' | 'video',
                rtpParameters: response.rtpParameters as mediasoup.types.RtpParameters,
            });

            consumers.set(consumer.id, consumer);

            const stream = new MediaStream([consumer.track]);
            const updated = new Map(remoteStreams.value);
            updated.set(msg.producerId as string, stream);
            remoteStreams.value = updated;

            sendNotification('resumeConsumer', { consumerId: consumer.id });
        } catch {
            // falha silenciosa — peer pode ter saído antes de consumir
        }
    };

    const handlePeerLeft = (msg: SfuResponse) => {
        if (!msg.producerId) return;
        const updated = new Map(remoteStreams.value);
        updated.delete(msg.producerId as string);
        remoteStreams.value = updated;
    };

    const createWebRtcTransport = async (direction: 'send' | 'recv'): Promise<Transport> => {
        const response = await sendRequest('createWebRtcTransport', { direction });

        if (response.error || !response.params) {
            throw new Error(`Falha ao criar transporte ${direction}: ${response.error ?? 'sem params'}`);
        }

        const params = response.params as {
            id: string;
            iceParameters: mediasoup.types.IceParameters;
            iceCandidates: mediasoup.types.IceCandidate[];
            dtlsParameters: mediasoup.types.DtlsParameters;
        };

        const transport = direction === 'send' ? device!.createSendTransport(params) : device!.createRecvTransport(params);

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
                try {
                    await sendRequest('join', { token });

                    const capsResponse = await sendRequest('getRouterRtpCapabilities');
                    if (!capsResponse.routerRtpCapabilities) throw new Error('Sem RTP capabilities');

                    device = new mediasoup.Device();
                    await device.load({ routerRtpCapabilities: capsResponse.routerRtpCapabilities });

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

                    connectionState.value = 'connected';
                    resolve();
                } catch (err) {
                    connectionState.value = 'failed';
                    ws?.close();
                    reject(err);
                }
            };

            ws.onmessage = (event) => {
                handleMessage(event.data as string);
            };

            ws.onerror = () => {
                connectionState.value = 'failed';
                reject(new Error('WebSocket error'));
            };

            ws.onclose = () => {
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
