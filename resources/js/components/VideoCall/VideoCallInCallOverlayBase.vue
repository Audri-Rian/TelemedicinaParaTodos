<script setup lang="ts">
import VideoControls from '@/components/VideoControls.vue';
import VideoGrid from '@/components/VideoGrid.vue';
import { ref } from 'vue';

interface Props {
    isInCall: boolean;
    localStream: MediaStream | null;
    remoteStreams: Map<string, MediaStream>;
    isMicEnabled: boolean;
    isCameraEnabled: boolean;
    isEnding: boolean;
}

defineProps<Props>();

const emit = defineEmits<{
    toggleMic: [];
    toggleCamera: [];
    end: [];
}>();

const videoRoomRef = ref<HTMLElement | null>(null);

const handleFullscreen = () => {
    videoRoomRef.value?.requestFullscreen?.();
};
</script>

<template>
    <Teleport to="body">
        <div v-if="isInCall" ref="videoRoomRef" class="fixed inset-0 z-50 flex flex-col bg-[#0b2030]">
            <VideoGrid
                :local-stream="localStream"
                :remote-streams="remoteStreams"
                :is-mic-enabled="isMicEnabled"
                :is-camera-enabled="isCameraEnabled"
                class="min-h-0 flex-1"
            />
            <VideoControls
                :is-mic-enabled="isMicEnabled"
                :is-camera-enabled="isCameraEnabled"
                :is-ending="isEnding"
                @toggle-mic="emit('toggleMic')"
                @toggle-camera="emit('toggleCamera')"
                @end="emit('end')"
                @fullscreen="handleFullscreen"
            />
        </div>
    </Teleport>
</template>
