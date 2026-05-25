import { usePage } from '@inertiajs/vue3';
import { defineStore } from 'pinia';
import { computed, ref } from 'vue';

export type VideoCallStatus = 'idle' | 'requested' | 'ringing' | 'accepted' | 'ended' | 'rejected' | 'error';
export type VideoCallRole = 'doctor' | 'patient';

export interface ActiveCallData {
    callId: string;
    appointmentId: string;
    status: VideoCallStatus;
    role: VideoCallRole;
    token: string | null;
    sfuWsUrl: string | null;
    videoCallRoute: string;
    appointmentLabel: string | null;
}

export const useVideoCallStore = defineStore('videoCall', () => {
    const callId = ref<string | null>(null);
    const appointmentId = ref<string | null>(null);
    const status = ref<VideoCallStatus>('idle');
    const role = ref<VideoCallRole | null>(null);
    const token = ref<string | null>(null);
    const sfuWsUrl = ref<string | null>(null);
    const videoCallRoute = ref<string | null>(null);
    const appointmentLabel = ref<string | null>(null);
    const modalDismissed = ref(false);

    const isActive = computed(() => ['requested', 'ringing', 'accepted'].includes(status.value));

    const isOnVideoCallPage = computed(() => {
        try {
            const url = usePage().url;
            return url.includes('/video-call');
        } catch {
            return false;
        }
    });

    function setCall(data: ActiveCallData) {
        callId.value = data.callId;
        appointmentId.value = data.appointmentId;
        status.value = data.status;
        role.value = data.role;
        token.value = data.token;
        sfuWsUrl.value = data.sfuWsUrl;
        videoCallRoute.value = data.videoCallRoute;
        appointmentLabel.value = data.appointmentLabel;
        modalDismissed.value = false;
    }

    function setStatus(newStatus: VideoCallStatus) {
        status.value = newStatus;
    }

    function setToken(newToken: string | null, newSfuWsUrl: string | null) {
        token.value = newToken;
        sfuWsUrl.value = newSfuWsUrl;
    }

    function dismissModal() {
        modalDismissed.value = true;
    }

    function clearCall() {
        callId.value = null;
        appointmentId.value = null;
        status.value = 'idle';
        role.value = null;
        token.value = null;
        sfuWsUrl.value = null;
        videoCallRoute.value = null;
        appointmentLabel.value = null;
        modalDismissed.value = false;
    }

    return {
        callId,
        appointmentId,
        status,
        role,
        token,
        sfuWsUrl,
        videoCallRoute,
        appointmentLabel,
        modalDismissed,
        isActive,
        isOnVideoCallPage,
        setCall,
        setStatus,
        setToken,
        dismissModal,
        clearCall,
    };
});
