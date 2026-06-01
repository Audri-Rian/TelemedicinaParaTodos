<script setup lang="ts">
import VideoGrid from '@/components/VideoGrid.vue';
import { Mic, PanelRight } from 'lucide-vue-next';
import { computed, ref, watchEffect } from 'vue';

const props = defineProps<{
    patientName: string;
    captionsOn: boolean;
    sideOpen: boolean;
    localStream: MediaStream | null;
    remoteStreams: Map<string, MediaStream>;
    isMicEnabled: boolean;
    isCameraEnabled: boolean;
}>();

const emit = defineEmits<{
    openSide: [];
}>();

const localVideoEl = ref<HTMLVideoElement | null>(null);

watchEffect(() => {
    if (localVideoEl.value) {
        localVideoEl.value.srcObject = props.localStream;
    }
});

const initials = computed(() => {
    const parts = props.patientName.trim().split(/\s+/).filter(Boolean);
    if (parts.length === 0) return '?';
    if (parts.length === 1) return parts[0]!.slice(0, 2).toUpperCase();
    return (parts[0]![0]! + parts[parts.length - 1]![0]!).toUpperCase();
});
</script>

<template>
    <div class="stage">
        <div class="stage-tile">
            <div class="dcv-stage-video-grid pointer-events-auto absolute inset-0 z-[1] min-h-0">
                <VideoGrid
                    :local-stream="localStream"
                    :remote-streams="remoteStreams"
                    :is-mic-enabled="isMicEnabled"
                    :is-camera-enabled="isCameraEnabled"
                    class="h-full min-h-0"
                />
            </div>

            <div class="pointer-events-none absolute inset-0 z-0" aria-hidden="true">
                <div class="tile-bg absolute inset-0" />
            </div>

            <div v-if="remoteStreams.size === 0" class="tile-avatar pointer-events-none z-[2]">{{ initials }}</div>

            <div class="tile-status pointer-events-none z-[3]">
                <span class="stat-chip">HD</span>
                <span class="stat-chip">
                    <span class="signal-bars" aria-hidden="true"> <i /><i /><i /><i /> </span>
                    78 ms
                </span>
            </div>

            <div v-if="captionsOn" class="caption-bar pointer-events-none z-[4]">
                <span class="speaker">{{ patientName }}</span>
                Transcrição em tempo real (protótipo): “Estou ouvindo bem, pode continuar.”
            </div>

            <div class="tile-label pointer-events-none z-[4]">
                <span class="live-mic" aria-hidden="true">
                    <span class="audio-wave"> <i /><i /><i /><i /> </span>
                </span>
                {{ patientName }}
                <Mic v-if="isMicEnabled" class="h-3.5 w-3.5 opacity-80" />
            </div>

            <div class="self-pip pointer-events-none z-[5]">
                <div class="self-pip-bg" aria-hidden="true" />
                <video
                    ref="localVideoEl"
                    class="absolute inset-0 z-[1] h-full w-full object-cover"
                    autoplay
                    playsinline
                    muted
                    :class="{ 'opacity-0': !isCameraEnabled }"
                />
                <div v-if="!isCameraEnabled" class="self-pip-avatar z-[2]">DR</div>
                <div class="self-pip-label z-[2]">
                    <Mic class="h-3 w-3" />
                    Você
                </div>
                <div v-if="!isMicEnabled" class="self-pip-mute z-[3]" title="Microfone desligado">
                    <Mic class="h-3.5 w-3.5" />
                </div>
            </div>

            <button v-if="!sideOpen" type="button" class="side-peek" title="Abrir painel" @click="emit('openSide')">
                <PanelRight class="h-4 w-4" />
            </button>
        </div>
    </div>
</template>

<style scoped>
.dcv-stage-video-grid :deep(.absolute.right-3) {
    display: none;
}
</style>
