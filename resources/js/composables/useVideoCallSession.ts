import { echo } from '@laravel/echo-vue';
import axios from 'axios';

import { createSfuVideoMediaProvider } from '@/services/video-call-media/SfuVideoMediaProvider';
import type { VideoMediaProvider } from '@/services/video-call-media/VideoMediaProvider';
import { useVideoCallStore } from '@/stores/videoCall';
import { useToast } from './useToast';

interface ActiveCallResponse {
    data: {
        call_id: string;
        appointment_id: string;
        status: 'requested' | 'ringing' | 'accepted';
        role: 'doctor' | 'patient';
        token: string | null;
        sfu_ws_url: string | null;
        video_call_route: string;
        appointment_label: string | null;
    } | null;
}

let echoChannel: ReturnType<NonNullable<ReturnType<typeof echo>>['private']> | null = null;
let broadcastChannel: BroadcastChannel | null = null;
let initialized = false;

// Shared provider instance — reused across pages
let mediaProvider: VideoMediaProvider | null = null;

function getMediaProvider(): VideoMediaProvider {
    if (!mediaProvider) {
        mediaProvider = createSfuVideoMediaProvider();
    }
    return mediaProvider;
}

export function useVideoCallSession() {
    const store = useVideoCallStore();
    const { warning: toastWarning, error: toastError } = useToast();

    async function bootstrap(): Promise<void> {
        try {
            const response = await axios.get<ActiveCallResponse>('/calls/active');
            const data = response.data?.data;
            if (!data) return;

            store.setCall({
                callId: data.call_id,
                appointmentId: data.appointment_id,
                status: data.status,
                role: data.role,
                token: data.token,
                sfuWsUrl: data.sfu_ws_url,
                videoCallRoute: data.video_call_route,
                appointmentLabel: data.appointment_label,
            });
        } catch {
            // bootstrap miss: sem call ativa ou endpoint indisponível
        }
    }

    function setupEchoListeners(userId: string): void {
        if (initialized) return;
        initialized = true;

        const echoInstance = echo();
        if (!echoInstance) return;

        echoChannel = echoInstance.private(`video-call.${userId}`);

        echoChannel.listen(
            '.VideoCallRequested',
            (data: { call_id: string; appointment_id: string; caller: { id: number; name: string }; video_call_route?: string }) => {
                if (store.isActive) return;
                store.setCall({
                    callId: data.call_id,
                    appointmentId: data.appointment_id,
                    status: 'ringing',
                    role: store.role ?? 'patient',
                    token: null,
                    sfuWsUrl: null,
                    videoCallRoute: data.video_call_route ?? '/video-call',
                    appointmentLabel: null,
                });
                broadcastSync();
            },
        );

        echoChannel.listen('.VideoCallAccepted', (data: { call_id: string; token: string; sfu_ws_url: string | null }) => {
            if (store.callId !== data.call_id) return;
            store.setStatus('accepted');
            store.setToken(data.token, data.sfu_ws_url ?? null);
            broadcastSync();

            if (data.token) {
                getMediaProvider()
                    .connect(data.sfu_ws_url ?? null, data.token)
                    .catch(() => {
                        toastError('Erro ao conectar à sala de vídeo');
                        store.setStatus('error');
                    });
            }
        });

        echoChannel.listen('.VideoCallRejected', (data: { call_id: string }) => {
            if (store.callId !== data.call_id) return;
            toastWarning('Chamada recusada');
            store.setStatus('rejected');
            broadcastSync();
        });

        echoChannel.listen('.VideoCallEnded', (data: { call_id: string }) => {
            if (store.callId !== data.call_id) return;
            getMediaProvider().disconnect();
            store.clearCall();
            broadcastSync();
        });

        // BroadcastChannel: sync cross-tab (sem token — segurança)
        if (typeof BroadcastChannel !== 'undefined') {
            broadcastChannel = new BroadcastChannel('video-call-session');
            broadcastChannel.onmessage = (ev) => {
                const { type, callId, status } = ev.data ?? {};
                if (type === 'sync' && callId && status) {
                    if (status === 'ended' || status === 'rejected') {
                        store.clearCall();
                    } else if (callId !== store.callId) {
                        bootstrap();
                    } else {
                        store.setStatus(status);
                    }
                }
            };
        }
    }

    function broadcastSync(): void {
        broadcastChannel?.postMessage({
            type: 'sync',
            callId: store.callId,
            status: store.status,
        });
    }

    function teardown(): void {
        if (echoChannel) {
            echoChannel.stopListening('.VideoCallRequested');
            echoChannel.stopListening('.VideoCallAccepted');
            echoChannel.stopListening('.VideoCallRejected');
            echoChannel.stopListening('.VideoCallEnded');
            echoChannel = null;
        }
        broadcastChannel?.close();
        broadcastChannel = null;
        initialized = false;
    }

    return {
        store,
        mediaProvider: getMediaProvider(),
        bootstrap,
        setupEchoListeners,
        teardown,
    };
}
