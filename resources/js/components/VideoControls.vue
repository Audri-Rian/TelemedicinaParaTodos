<script setup lang="ts">
import { Maximize2, Mic, MicOff, Phone, Video, VideoOff } from 'lucide-vue-next';

interface Props {
    isMicEnabled: boolean;
    isCameraEnabled: boolean;
    isEnding?: boolean;
}

withDefaults(defineProps<Props>(), {
    isEnding: false,
});

const emit = defineEmits<{
    toggleMic: [];
    toggleCamera: [];
    end: [];
    fullscreen: [];
}>();
</script>

<template>
    <div class="flex items-center justify-center gap-3 bg-[#0b2030] py-4">
        <button
            type="button"
            :title="isMicEnabled ? 'Desligar microfone' : 'Ligar microfone'"
            class="grid h-12 w-12 place-items-center rounded-full transition focus:outline-none focus-visible:ring-2 focus-visible:ring-white/50"
            :class="isMicEnabled ? 'bg-white/10 text-white hover:bg-white/20' : 'bg-red-500 text-white hover:bg-red-400'"
            @click="emit('toggleMic')"
        >
            <MicOff v-if="!isMicEnabled" class="h-5 w-5" />
            <Mic v-else class="h-5 w-5" />
        </button>

        <button
            type="button"
            :title="isCameraEnabled ? 'Desligar câmera' : 'Ligar câmera'"
            class="grid h-12 w-12 place-items-center rounded-full transition focus:outline-none focus-visible:ring-2 focus-visible:ring-white/50"
            :class="isCameraEnabled ? 'bg-white/10 text-white hover:bg-white/20' : 'bg-red-500 text-white hover:bg-red-400'"
            @click="emit('toggleCamera')"
        >
            <VideoOff v-if="!isCameraEnabled" class="h-5 w-5" />
            <Video v-else class="h-5 w-5" />
        </button>

        <button
            type="button"
            title="Encerrar chamada"
            class="grid h-14 w-14 place-items-center rounded-full bg-red-600 text-white transition hover:bg-red-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-400 disabled:opacity-60"
            :disabled="isEnding"
            @click="emit('end')"
        >
            <Phone class="h-6 w-6 rotate-[135deg]" />
        </button>

        <button
            type="button"
            title="Tela cheia"
            class="grid h-12 w-12 place-items-center rounded-full bg-white/10 text-white transition hover:bg-white/20 focus:outline-none focus-visible:ring-2 focus-visible:ring-white/50"
            @click="emit('fullscreen')"
        >
            <Maximize2 class="h-5 w-5" />
        </button>
    </div>
</template>
