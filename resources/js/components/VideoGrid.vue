<script setup lang="ts">
import { computed, ref, watchEffect } from 'vue';

interface Props {
    localStream: MediaStream | null;
    remoteStreams: Map<string, MediaStream>;
    isMicEnabled: boolean;
    isCameraEnabled: boolean;
}

const props = defineProps<Props>();

const localVideoEl = ref<HTMLVideoElement | null>(null);

watchEffect(() => {
    if (localVideoEl.value) {
        localVideoEl.value.srcObject = props.localStream;
    }
});

const remoteVideoEls = ref<Map<string, HTMLVideoElement>>(new Map());

const setRemoteVideoEl = (peerId: string, el: HTMLVideoElement | null) => {
    if (!el) return;
    remoteVideoEls.value.set(peerId, el);
    const stream = props.remoteStreams.get(peerId);
    if (stream) el.srcObject = stream;
};

watchEffect(() => {
    props.remoteStreams.forEach((stream, peerId) => {
        const el = remoteVideoEls.value.get(peerId);
        if (el && el.srcObject !== stream) el.srcObject = stream;
    });
});

const remoteEntries = computed(() => [...props.remoteStreams.entries()]);

const gridClass = computed(() => {
    const count = remoteEntries.value.length;
    if (count <= 1) return 'grid-cols-1';
    if (count <= 4) return 'grid-cols-2';
    return 'grid-cols-3';
});
</script>

<template>
    <div class="relative flex h-full w-full flex-col bg-[#0b2030]">
        <div class="min-h-0 flex-1 p-2" :class="['grid gap-2', gridClass]">
            <div v-for="[peerId] in remoteEntries" :key="peerId" class="relative overflow-hidden rounded-lg bg-[#0f2a3a]">
                <video
                    :ref="(el) => setRemoteVideoEl(peerId, el as HTMLVideoElement | null)"
                    autoplay
                    playsinline
                    class="h-full w-full object-cover"
                />
            </div>

            <div v-if="remoteEntries.length === 0" class="flex items-center justify-center rounded-lg bg-[#0f2a3a]">
                <p class="text-sm font-semibold text-white/50">Aguardando participante...</p>
            </div>
        </div>

        <div class="absolute right-3 bottom-20 h-36 w-48 overflow-hidden rounded-lg border-2 border-white/20 bg-[#0f2a3a] shadow-lg">
            <video ref="localVideoEl" autoplay playsinline muted class="h-full w-full object-cover" :class="{ 'opacity-0': !isCameraEnabled }" />
            <div v-if="!isCameraEnabled" class="absolute inset-0 flex items-center justify-center bg-[#0f2a3a]">
                <span class="text-xs font-semibold text-white/50">Câmera desligada</span>
            </div>
            <div v-if="!isMicEnabled" class="absolute top-1 right-1 rounded-full bg-red-500 p-1">
                <svg class="h-3 w-3 text-white" viewBox="0 0 24 24" fill="currentColor">
                    <path
                        d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4M9 11V5a3 3 0 016 0v2M3 3l18 18"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        fill="none"
                    />
                </svg>
            </div>
        </div>
    </div>
</template>
