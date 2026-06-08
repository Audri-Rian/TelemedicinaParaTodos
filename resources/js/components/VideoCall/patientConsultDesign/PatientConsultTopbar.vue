<script setup lang="ts">
import type { PatientConsultDoctor } from '@/components/VideoCall/patientConsultDesign/patientConsultDesignData';
import { Clock, Info, Settings, Stethoscope } from 'lucide-vue-next';

defineProps<{
    doctor: PatientConsultDoctor;
    elapsed: string;
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
