<script setup lang="ts">
import { Captions, ChevronDown, Maximize2, Mic, MicOff, MoreHorizontal, PhoneOff, Stamp, Video, VideoOff } from 'lucide-vue-next';

defineProps<{
    micOn: boolean;
    camOn: boolean;
    captionsOn: boolean;
    isEnding: boolean;
}>();

const emit = defineEmits<{
    toggle: [key: 'mic' | 'cam' | 'captions' | 'more'];
    end: [];
    fullscreen: [];
}>();
</script>

<template>
    <div class="controls">
        <div class="ctrl-left">
            <span style="display: inline-flex; align-items: center; gap: 6px; color: var(--stage-text-muted)">
                <Stamp class="h-3.5 w-3.5" />
                Consulta segura · ponta-a-ponta
            </span>
        </div>

        <div class="ctrl-center">
            <button type="button" class="ctrl-btn" :class="{ 'toggled-off': !micOn }" :aria-pressed="micOn" @click="emit('toggle', 'mic')">
                <Mic v-if="micOn" class="h-5 w-5" />
                <MicOff v-else class="h-5 w-5" />
                <span class="tip">{{ micOn ? 'Desligar microfone' : 'Ligar microfone' }}</span>
                <span class="ctrl-caret">
                    <ChevronDown class="h-2.5 w-2.5 -rotate-90" />
                </span>
            </button>
            <button type="button" class="ctrl-btn" :class="{ 'toggled-off': !camOn }" :aria-pressed="camOn" @click="emit('toggle', 'cam')">
                <Video v-if="camOn" class="h-5 w-5" />
                <VideoOff v-else class="h-5 w-5" />
                <span class="tip">{{ camOn ? 'Desligar câmera' : 'Ligar câmera' }}</span>
                <span class="ctrl-caret">
                    <ChevronDown class="h-2.5 w-2.5 -rotate-90" />
                </span>
            </button>
            <button type="button" class="ctrl-btn" :class="{ 'toggled-on': captionsOn }" @click="emit('toggle', 'captions')">
                <Captions class="h-5 w-5" />
                <span class="tip">{{ captionsOn ? 'Desativar legendas' : 'Ativar legendas em tempo real' }}</span>
            </button>

            <span class="ctrl-divider" />

            <button type="button" class="ctrl-btn" @click="emit('toggle', 'more')">
                <MoreHorizontal class="h-5 w-5" />
                <span class="tip">Mais opções</span>
            </button>

            <button type="button" class="ctrl-end" :disabled="isEnding" @click="emit('end')">
                <PhoneOff class="h-4 w-4" />
                Encerrar
            </button>
        </div>

        <div class="ctrl-right">
            <button
                type="button"
                class="ctrl-btn"
                style="min-width: 0; padding: 0 12px; height: 38px; border-radius: 10px"
                title="Tela cheia"
                @click="emit('fullscreen')"
            >
                <Maximize2 class="h-4 w-4" />
            </button>
        </div>
    </div>
</template>
