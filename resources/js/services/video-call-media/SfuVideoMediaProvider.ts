import { useSfu } from '@/composables/useSfu';
import type { VideoMediaProvider } from './VideoMediaProvider';

export function createSfuVideoMediaProvider(): VideoMediaProvider {
    const sfu = useSfu();

    async function connect(sfuWsUrl: string | null, token: string): Promise<void> {
        if (!sfuWsUrl) {
            throw new Error('URL WebSocket do SFU ausente.');
        }

        await sfu.connect(sfuWsUrl, token);

        if (sfu.connectionState.value !== 'connected') {
            throw new Error('Conexão com o SFU não foi estabelecida.');
        }

        if (import.meta.env.DEV) {
            console.info('[VideoCall][SFU] Conexão com o SFU estabelecida com sucesso.', {
                connectionState: sfu.connectionState.value,
            });
        }
    }

    return {
        connect,
        disconnect: sfu.disconnect,
        publishLocalStream: async () => {},
        subscribeToRemoteStream: async () => {},
        getConnectionState: () => sfu.connectionState.value,
        connectionState: sfu.connectionState,
        localStream: sfu.localStream,
        remoteStreams: sfu.remoteStreams,
        isMicEnabled: sfu.isMicEnabled,
        isCameraEnabled: sfu.isCameraEnabled,
        toggleMic: sfu.toggleMic,
        toggleCamera: sfu.toggleCamera,
    };
}
