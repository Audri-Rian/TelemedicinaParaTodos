import type { Ref } from 'vue';

export type MediaConnectionState = 'idle' | 'connecting' | 'connected' | 'failed' | 'closed';

export interface VideoMediaProvider {
    connect(sfuWsUrl: string | null, token: string): Promise<void>;
    disconnect(): void;
    publishLocalStream(): Promise<void>;
    subscribeToRemoteStream(producerId: string): Promise<void>;
    getConnectionState(): MediaConnectionState;
    localStream: Ref<MediaStream | null>;
    remoteStreams: Ref<Map<string, MediaStream>>;
    isMicEnabled: Ref<boolean>;
    isCameraEnabled: Ref<boolean>;
    toggleMic(): void;
    toggleCamera(): void;
}
