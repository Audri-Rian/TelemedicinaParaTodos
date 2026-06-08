<script setup lang="ts">
import { Clock, Stethoscope } from 'lucide-vue-next';

defineProps<{
    patientName: string;
    elapsed: string;
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
        </div>
    </header>
</template>
