<script setup lang="ts">
import { Clock, Stethoscope } from 'lucide-vue-next';

defineProps<{
    patientName: string;
    elapsed: string;
    recording: boolean;
}>();

const emit = defineEmits<{
    toggleRecording: [];
}>();
</script>

<template>
    <header class="topbar">
        <div class="brand">
            <span class="brand-mark">
                <Stethoscope class="h-3.5 w-3.5" :stroke-width="2" />
            </span>
            Tele
        </div>
        <span class="brand-sep" />
        <div class="top-meta">
            <strong>Teleconsulta</strong>
            <span style="opacity: 0.6">·</span>
            <span>{{ patientName }}</span>
            <span style="opacity: 0.6">·</span>
            <span>Clínica geral</span>
        </div>

        <div class="top-right">
            <span class="timer-pill">
                <Clock class="h-3.5 w-3.5" />
                {{ elapsed }}
            </span>
            <button type="button" class="top-btn outline" @click="emit('toggleRecording')">
                <template v-if="recording">
                    <span class="rec-dot" />
                    Parar gravação
                </template>
                <template v-else>
                    <span style="width: 7px; height: 7px; border-radius: 999px; background: #94a3b8; display: inline-block" />
                    Iniciar gravação
                </template>
            </button>
        </div>
    </header>
</template>
