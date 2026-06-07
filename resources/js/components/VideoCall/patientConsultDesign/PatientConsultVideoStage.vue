<script setup lang="ts">
import VideoGrid from '@/components/VideoGrid.vue';
import { MicOff, PanelLeft } from 'lucide-vue-next';
import { computed, ref, watchEffect } from 'vue';

const props = defineProps<{
    doctorName: string;
    doctorShort: string;
    patientName: string;
    stageView: 'doctor-main' | 'patient-main';
    captionsOn: boolean;
    sideOpen: boolean;
    localStream: MediaStream | null;
    remoteStreams: Map<string, MediaStream>;
    isMicEnabled: boolean;
    isCameraEnabled: boolean;
}>();

const emit = defineEmits<{ openSide: [] }>();

const localVideoEl = ref<HTMLVideoElement | null>(null);

watchEffect(() => {
    if (localVideoEl.value) localVideoEl.value.srcObject = props.localStream;
});

const doctorInitials = computed(() => {
    const p = props.doctorName.trim().split(/\s+/).filter(Boolean);
    if (p.length === 0) return 'DR';
    if (p.length === 1) return p[0]!.slice(0, 2).toUpperCase();
    return (p[0]![0]! + p[p.length - 1]![0]!).toUpperCase();
});

const patientInitials = computed(() => {
    const p = props.patientName.trim().split(/\s+/).filter(Boolean);
    if (p.length === 0) return 'VC';
    if (p.length === 1) return p[0]!.slice(0, 2).toUpperCase();
    return (p[0]![0]! + p[p.length - 1]![0]!).toUpperCase();
});

const remoteLabel = computed(() => (props.stageView === 'patient-main' ? props.patientName : props.doctorName));
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

            <div v-if="remoteStreams.size === 0" class="tile-avatar pointer-events-none z-[2]">
                {{ stageView === 'patient-main' ? patientInitials : doctorInitials }}
            </div>

            <div class="tile-status pointer-events-none z-[3]">
                <span class="stat-chip">
                    <span class="signal-bars" aria-hidden="true"><i /><i /><i /><i /></span>
                    HD
                </span>
            </div>

            <div class="tile-label pointer-events-none z-[4]">
                <span class="live-mic" aria-hidden="true"
                    ><span class="audio-wave"><i /><i /><i /><i /></span
                ></span>
                {{ remoteLabel }}
            </div>

            <div v-if="captionsOn" class="caption-bar pointer-events-none z-[4]">
                <span class="speaker">{{ doctorShort }}</span>
                Pelo que você está me descrevendo, vou prescrever uma medicação profilática e pedir uma ressonância de crânio.
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
                <div v-if="!isCameraEnabled" class="self-pip-avatar z-[2]">
                    {{ stageView === 'patient-main' ? doctorInitials : patientInitials }}
                </div>
                <span class="self-pip-label z-[2]">
                    {{ stageView === 'patient-main' ? `${doctorShort}` : 'Você' }}
                </span>
                <span v-if="!isMicEnabled" class="self-pip-mute z-[3]">
                    <MicOff class="h-3.5 w-3.5" />
                </span>
            </div>

            <button v-if="!sideOpen" type="button" class="side-peek" title="Abrir painel" @click="emit('openSide')">
                <PanelLeft class="h-4.5 w-4.5" />
            </button>
        </div>
    </div>
</template>

<style scoped>
.dcv-stage-video-grid :deep(.absolute.right-3) {
    display: none;
}
</style>
