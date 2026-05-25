import { useVideoCallStore } from '@/stores/videoCall';
import axios from 'axios';
import { computed, ref } from 'vue';
import { useToast } from './useToast';
import { useVideoCallSession } from './useVideoCallSession';

export type CallState = 'idle' | 'requesting' | 'ringing' | 'accepted' | 'rejected' | 'ended' | 'error';

interface IncomingCall {
    callId: string;
    appointmentId: string;
    callerName: string;
}

export function useVideoCall() {
    const store = useVideoCallStore();
    const session = useVideoCallSession();
    const { error: toastError, warning: toastWarning } = useToast();

    const isLoading = ref(false);
    const incomingCall = ref<IncomingCall | null>(null);

    // callState mapeado da store (retrocompatível — 'requesting' não existe na store)
    const callState = computed<CallState>(() => {
        const s = store.status;
        if (s === 'idle') return 'idle';
        if (s === 'requested') return 'requesting';
        if (s === 'ringing') return 'ringing';
        if (s === 'accepted') return 'accepted';
        if (s === 'rejected') return 'rejected';
        if (s === 'ended') return 'ended';
        return 'error';
    });

    const currentCall = computed(() => (store.callId ? { callId: store.callId, token: store.token ?? '', sfuWsUrl: store.sfuWsUrl ?? '' } : null));

    const sfu = session.mediaProvider;

    const requestCall = async (appointmentId: string): Promise<void> => {
        if (isLoading.value) return;
        isLoading.value = true;
        store.setStatus('requested');

        try {
            const response = await axios.post<{ data: { call_id: string } }>('/calls', { appointment_id: appointmentId });
            const callId = response.data.data.call_id;
            store.setCall({
                callId,
                appointmentId,
                status: 'requested',
                role: store.role ?? 'patient',
                token: null,
                sfuWsUrl: null,
                videoCallRoute: store.videoCallRoute ?? '/video-call',
                appointmentLabel: store.appointmentLabel,
            });
        } catch (err: unknown) {
            const axiosErr = err as { response?: { status?: number; data?: { message?: string; data?: { call_id?: string } } } };
            if (axiosErr.response?.status === 409) {
                const existingCallId = axiosErr.response?.data?.data?.call_id;
                if (existingCallId) {
                    store.setStatus('requested');
                    return;
                }
                toastWarning(axiosErr.response?.data?.message ?? 'Chamada já em andamento');
            } else {
                toastError((axiosErr.response?.data as { message?: string })?.message ?? 'Não foi possível iniciar a chamada');
            }
            store.setStatus('error');
        } finally {
            isLoading.value = false;
        }
    };

    const acceptCall = async (callId: string): Promise<void> => {
        if (isLoading.value) return;
        isLoading.value = true;

        try {
            const response = await axios.post<{ data: { token: string; sfu_ws_url: string | null } }>(`/calls/${callId}/accept`);
            const { token, sfu_ws_url } = response.data.data;

            store.setStatus('accepted');
            store.setToken(token, sfu_ws_url ?? null);
            incomingCall.value = null;

            await sfu.connect(sfu_ws_url ?? null, token);
        } catch (err: unknown) {
            const axiosErr = err as { response?: { data?: { message?: string } } };
            toastError(axiosErr.response?.data?.message ?? 'Erro ao aceitar a chamada');
            store.setStatus('error');
        } finally {
            isLoading.value = false;
        }
    };

    const rejectCall = async (callId: string): Promise<void> => {
        incomingCall.value = null;

        try {
            await axios.post(`/calls/${callId}/reject`);
            store.setStatus('rejected');
        } catch {
            store.setStatus('idle');
        }
    };

    const endCall = async (callId: string): Promise<void> => {
        sfu.disconnect();

        try {
            await axios.post(`/calls/${callId}/end`);
        } catch {
            // encerramento local garantido mesmo se requisição falhar
        }

        store.clearCall();
    };

    const setupEchoListeners = (userId: number): void => {
        session.setupEchoListeners(userId);
    };

    const teardownEchoListeners = (): void => {
        session.teardown();
    };

    return {
        callState,
        currentCall,
        incomingCall,
        isLoading,
        sfu,
        requestCall,
        acceptCall,
        rejectCall,
        endCall,
        setupEchoListeners,
        teardownEchoListeners,
    };
}
