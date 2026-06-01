<script setup lang="ts">
import type { PatientConsultDoctor } from '@/components/VideoCall/patientConsultDesign/patientConsultDesignData';
import { Clock, Info, Settings, Stethoscope } from 'lucide-vue-next';

defineProps<{
    doctor: PatientConsultDoctor;
    elapsed: string;
    recording: boolean;
}>();

const emit = defineEmits<{
    openTweaks: [];
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
            <strong>Sua consulta</strong>
            <span style="opacity: 0.6">·</span>
            <span>{{ doctor.name }}</span>
            <span style="opacity: 0.6">·</span>
            <span>{{ doctor.specialty }}</span>
        </div>

        <div class="top-right">
            <span
                v-if="recording"
                style="
                    display: inline-flex;
                    align-items: center;
                    gap: 7px;
                    height: 26px;
                    padding: 0 10px;
                    border-radius: 999px;
                    background: rgba(180, 83, 9, 0.14);
                    border: 1px solid rgba(180, 83, 9, 0.35);
                    color: #fbbf24;
                    font-size: 12px;
                    font-weight: 500;
                "
            >
                <span class="rec-dot" style="background: #f59e0b" />
                Consulta sendo gravada
            </span>
            <span class="timer-pill">
                <Clock class="h-3.5 w-3.5" />
                {{ elapsed }}
            </span>
            <button type="button" class="top-icon-btn" title="Ajuda">
                <Info class="h-4 w-4" />
            </button>
            <button type="button" class="top-icon-btn" title="Configurações" @click="emit('openTweaks')">
                <Settings class="h-4 w-4" />
            </button>
        </div>
    </header>
</template>
