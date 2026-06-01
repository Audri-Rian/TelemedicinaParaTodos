import { ref } from 'vue';
import type { MediaConnectionState, VideoMediaProvider } from './VideoMediaProvider';

export function createStubVideoMediaProvider(): VideoMediaProvider {
    const localStream = ref<MediaStream | null>(null);
    const remoteStreams = ref<Map<string, MediaStream>>(new Map());
    const isMicEnabled = ref(true);
    const isCameraEnabled = ref(true);
    const connectionState = ref<MediaConnectionState>('idle');

    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    async function connect(_sfuWsUrl: string | null, _token: string): Promise<void> {
        connectionState.value = 'connecting';
        try {
            const stream = await navigator.mediaDevices.getUserMedia({ video: true, audio: true });
            localStream.value = stream;
        } catch {
            // Stub: preview sem mídia é aceitável (ex.: sem câmera no dev)
            localStream.value = null;
        }
        connectionState.value = 'connected';
    }

    function disconnect(): void {
        localStream.value?.getTracks().forEach((t) => t.stop());
        localStream.value = null;
        remoteStreams.value.clear();
        connectionState.value = 'closed';
    }

    async function publishLocalStream(): Promise<void> {
        // Stub: stream já capturado em connect()
    }

    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    async function subscribeToRemoteStream(_producerId: string): Promise<void> {
        // Stub: sem SFU real, nenhum stream remoto disponível
    }

    function getConnectionState(): MediaConnectionState {
        return connectionState.value;
    }

    function toggleMic(): void {
        if (!localStream.value) return;
        const enabled = !isMicEnabled.value;
        localStream.value.getAudioTracks().forEach((t) => {
            t.enabled = enabled;
        });
        isMicEnabled.value = enabled;
    }

    function toggleCamera(): void {
        if (!localStream.value) return;
        const enabled = !isCameraEnabled.value;
        localStream.value.getVideoTracks().forEach((t) => {
            t.enabled = enabled;
        });
        isCameraEnabled.value = enabled;
    }

    return {
        connect,
        disconnect,
        publishLocalStream,
        subscribeToRemoteStream,
        getConnectionState,
        connectionState,
        localStream,
        remoteStreams,
        isMicEnabled,
        isCameraEnabled,
        toggleMic,
        toggleCamera,
    };
}
