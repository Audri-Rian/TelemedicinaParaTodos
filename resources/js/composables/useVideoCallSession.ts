import { echo } from '@laravel/echo-vue';
import axios from 'axios';

import { createSfuVideoMediaProvider } from '@/services/video-call-media/SfuVideoMediaProvider';
import type { VideoMediaProvider } from '@/services/video-call-media/VideoMediaProvider';
import type { VideoCallStatus, VideoCallType } from '@/stores/videoCall';
import { useVideoCallStore } from '@/stores/videoCall';
import { useToast } from './useToast';
import { isSafeInternalPath } from './useVideoCallNavigation';

interface ActiveCallResponse {
    data: {
        call_id: string;
        call_type: VideoCallType;
        appointment_id: string | null;
        status: 'requested' | 'ringing' | 'calling' | 'accepted';
        role: 'doctor' | 'patient';
        token: string | null;
        video_call_route: string;
        appointment_label: string | null;
        window: { opens_at: string; closes_at: string } | null;
    } | null;
}

let echoChannel: ReturnType<NonNullable<ReturnType<typeof echo>>['private']> | null = null;
let broadcastChannel: BroadcastChannel | null = null;
let initialized = false;

// Shared provider instance — reused across pages
let mediaProvider: VideoMediaProvider | null = null;
const allowedSyncStatuses: VideoCallStatus[] = ['idle', 'requested', 'ringing', 'calling', 'accepted', 'ended', 'rejected', 'error'];

function isValidSyncStatus(value: unknown): value is VideoCallStatus {
    return typeof value === 'string' && allowedSyncStatuses.includes(value as VideoCallStatus);
}

function getMediaProvider(): VideoMediaProvider {
    if (!mediaProvider) {
        mediaProvider = createSfuVideoMediaProvider();
    }
    return mediaProvider;
}

export function useVideoCallSession() {
    const store = useVideoCallStore();
    const { warning: toastWarning, info: toastInfo } = useToast();

    async function bootstrap(): Promise<void> {
        try {
            const response = await axios.get<ActiveCallResponse>('/calls/active');
            const data = response.data?.data;
            if (!data) return;

            store.setCall({
                callId: data.call_id,
                callType: data.call_type,
                appointmentId: data.appointment_id,
                status: data.status,
                role: data.role,
                token: data.token,
                sfuWsUrl: null,
                videoCallRoute: data.video_call_route,
                appointmentLabel: data.appointment_label,
                window: data.window,
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

        // Scheduled: sala provisionada pelo sistema
        echoChannel.listen('.VideoCallAvailable', () => {
            if (store.isActive && store.callType === 'scheduled') return;
            // Re-bootstrap para obter token e room
            bootstrap();
            broadcastSync();
        });

        // Ad-hoc: médico recebe solicitação
        echoChannel.listen(
            '.VideoCallRequested',
            (data: { call_id: string; appointment_id: string | null; caller: { id: number; name: string }; video_call_route?: string }) => {
                if (store.isActive) return;
                const safeRoute = data.video_call_route && isSafeInternalPath(data.video_call_route) ? data.video_call_route : '/video-call';
                store.setCall({
                    callId: data.call_id,
                    callType: 'ad_hoc',
                    appointmentId: data.appointment_id,
                    status: 'ringing',
                    role: store.role ?? 'doctor',
                    token: null,
                    sfuWsUrl: null,
                    videoCallRoute: safeRoute,
                    appointmentLabel: null,
                    window: null,
                });
                broadcastSync();
            },
        );

        echoChannel.listen('.VideoCallAccepted', (data: { call_id: string; token: string; sfu_ws_url: string | null }) => {
            if (store.callId !== data.call_id) return;
            store.setStatus('accepted');
            store.setToken(data.token, data.sfu_ws_url ?? null);
            broadcastSync();
        });

        echoChannel.listen('.VideoCallRejected', (data: { call_id: string }) => {
            if (store.callId !== data.call_id) return;
            toastWarning('Chamada recusada');
            store.setStatus('rejected');
            broadcastSync();
        });

        echoChannel.listen('.VideoCallEnded', (data: { call_id: string }) => {
            if (store.callId !== data.call_id) return;
            const wasConnected = getMediaProvider().getConnectionState() === 'connected';
            getMediaProvider().disconnect();
            store.clearCall();
            broadcastSync();
            if (wasConnected) {
                toastInfo('Consulta encerrada.');
            }
        });

        // BroadcastChannel: sync cross-tab (sem token — segurança)
        if (typeof BroadcastChannel !== 'undefined') {
            broadcastChannel = new BroadcastChannel('video-call-session');
            broadcastChannel.onmessage = (ev) => {
                const { type, callId, status } = ev.data ?? {};
                if (type === 'sync' && callId && isValidSyncStatus(status)) {
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
            echoChannel.stopListening('.VideoCallAvailable');
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
