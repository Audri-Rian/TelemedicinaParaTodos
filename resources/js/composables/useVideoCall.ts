import { useVideoCallStore } from '@/stores/videoCall';
import axios from 'axios';
import { computed, ref } from 'vue';
import { useToast } from './useToast';
import { useVideoCallSession } from './useVideoCallSession';

export type CallState = 'idle' | 'calling' | 'requesting' | 'ringing' | 'accepted' | 'rejected' | 'ended' | 'error';

interface IncomingCall {
    callId: string;
    appointmentId: string | null;
    callerName: string;
}

export function useVideoCall() {
    const store = useVideoCallStore();
    const session = useVideoCallSession();
    const { error: toastError, warning: toastWarning } = useToast();

    const isLoading = ref(false);
    const incomingCall = ref<IncomingCall | null>(null);

    const callState = computed<CallState>(() => {
        const s = store.status;
        if (s === 'idle') return 'idle';
        if (s === 'requested') return 'requesting';
        if (s === 'calling') return 'calling';
        if (s === 'ringing') return 'ringing';
        if (s === 'accepted') return 'accepted';
        if (s === 'rejected') return 'rejected';
        if (s === 'ended') return 'ended';
        return 'error';
    });

    const currentCall = computed(() =>
        store.callId ? { callId: store.callId, token: store.token ?? '', sfuWsUrl: store.sfuWsUrl ?? '', callType: store.callType } : null,
    );

    const sfu = session.mediaProvider;

    /**
     * Conecta (ou reconecta) a uma chamada scheduled já provisionada via /calls/active.
     * Sempre busca token fresco do servidor para evitar expiração JWT.
     * Sem call provisionada (ex.: consulta in_progress fora da janela do job),
     * cai no provisionamento idempotente via /video/session.
     */
    const joinActiveCall = async (fallbackAppointmentId?: string | null): Promise<void> => {
        if (isLoading.value) return;

        // Consulta selecionada tem precedência: com múltiplas calls abertas, a call do
        // store pode ser de OUTRO appointment — provisiona a sala da consulta escolhida
        if (fallbackAppointmentId && store.appointmentId && store.appointmentId !== fallbackAppointmentId) {
            console.debug('[VIDEO_CALL] Call ativa é de outro appointment — entrando na consulta selecionada', {
                storeAppointmentId: store.appointmentId,
                selectedAppointmentId: fallbackAppointmentId,
            });
            await joinVideoSession(fallbackAppointmentId);
            return;
        }

        if (!store.callId) {
            const appointmentId = store.appointmentId ?? fallbackAppointmentId;
            if (appointmentId) {
                console.debug('[VIDEO_CALL] Sem call ativa — provisionando via /video/session', { appointmentId });
                await joinVideoSession(appointmentId);
                return;
            }
            console.warn('[VIDEO_CALL] joinActiveCall() sem callId e sem appointmentId — nada a fazer');
            toastError('Nenhuma chamada ativa encontrada para esta consulta.');
            return;
        }

        isLoading.value = true;

        const currentState = sfu.getConnectionState();
        console.debug('[VIDEO_CALL] joinActiveCall() — estado atual SFU:', currentState);

        if (currentState === 'closed' || currentState === 'failed') {
            console.debug('[VIDEO_CALL] Desconectando SFU antes de reconectar');
            sfu.disconnect();
        }

        try {
            // Token fresco: evita TokenExpiredError no SFU
            console.debug('[VIDEO_CALL] Buscando token fresco via /calls/active...');
            await session.bootstrap();

            if (!store.token) {
                console.warn('[VIDEO_CALL] /calls/active não retornou token — chamada encerrada');
                toastError('Chamada não encontrada ou encerrada.');
                store.clearCall();
                return;
            }

            console.debug('[VIDEO_CALL] Token obtido — conectando ao SFU', { sfuWsUrl: store.sfuWsUrl });
            await sfu.connect(store.sfuWsUrl ?? null, store.token);
            store.setStatus('accepted');
            console.debug('[VIDEO_CALL] joinActiveCall() — conectado com sucesso');
        } catch (err) {
            console.error('[VIDEO_CALL] joinActiveCall() — falha ao conectar SFU:', err);
            // Room pode ter sido destruído no SFU (peers saíram, room expirou).
            // Se temos appointmentId, re-provisiona via session endpoint (idempotente).
            if (store.appointmentId) {
                console.debug('[VIDEO_CALL] Tentando re-provisionar room via /video/session', { appointmentId: store.appointmentId });
                isLoading.value = false;
                await joinVideoSession(store.appointmentId);
                return;
            }
            toastError('Não foi possível conectar à sala de vídeo');
            store.setStatus('error');
        } finally {
            isLoading.value = false;
        }
    };

    /**
     * Entra diretamente na sala de vídeo de um appointment agendado.
     * Backend provisiona a sala (idempotente) e retorna token + SFU URL.
     */
    const joinVideoSession = async (appointmentId: string): Promise<void> => {
        if (isLoading.value) return;
        isLoading.value = true;
        store.setStatus('calling');

        try {
            const response = await axios.post<{
                data: {
                    call_id: string;
                    room_id: string;
                    role: 'doctor' | 'patient';
                    token: string;
                    sfu_ws_url: string | null;
                    sfu_node: string | null;
                    window: { opens_at: string; closes_at: string };
                };
            }>(`/appointments/${appointmentId}/video/session`);

            const { call_id, role, token, sfu_ws_url, window: callWindow } = response.data.data;

            store.setCall({
                callId: call_id,
                callType: 'scheduled',
                appointmentId,
                status: 'accepted',
                role,
                token,
                sfuWsUrl: sfu_ws_url ?? null,
                videoCallRoute: store.videoCallRoute ?? (role === 'doctor' ? '/doctor/video-call' : '/patient/video-call'),
                appointmentLabel: null,
                window: callWindow ?? null,
            });

            await sfu.connect(sfu_ws_url ?? null, token);
        } catch (err: unknown) {
            const axiosErr = err as { response?: { status?: number; data?: { message?: string } } };
            const status = axiosErr.response?.status;
            if (status === 403) {
                toastError('Acesso não autorizado para esta consulta.');
            } else if (status === 503) {
                toastWarning(axiosErr.response?.data?.message ?? 'Sala em preparação, tente novamente.');
            } else {
                toastError(axiosErr.response?.data?.message ?? 'Não foi possível entrar na consulta.');
            }
            store.setStatus('error');
        } finally {
            isLoading.value = false;
        }
    };

    /**
     * Inicia chamada ad-hoc (paciente → médico).
     */
    const requestCall = async (doctorId: string): Promise<void> => {
        if (isLoading.value) return;
        isLoading.value = true;
        store.setStatus('calling');

        try {
            const response = await axios.post<{ data: { call_id: string } }>('/calls', {
                call_type: 'ad_hoc',
                doctor_id: doctorId,
            });
            const callId = response.data.data.call_id;
            store.setCall({
                callId,
                callType: 'ad_hoc',
                appointmentId: null,
                status: 'calling',
                role: store.role ?? 'patient',
                token: null,
                sfuWsUrl: null,
                videoCallRoute: store.videoCallRoute ?? '/video-call',
                appointmentLabel: store.appointmentLabel,
                window: null,
            });
        } catch (err: unknown) {
            const axiosErr = err as { response?: { status?: number; data?: { message?: string; data?: { call_id?: string } } } };
            if (axiosErr.response?.status === 409) {
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
        session.setupEchoListeners(String(userId));
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
        joinActiveCall,
        joinVideoSession,
        requestCall,
        acceptCall,
        rejectCall,
        endCall,
        setupEchoListeners,
        teardownEchoListeners,
    };
}
