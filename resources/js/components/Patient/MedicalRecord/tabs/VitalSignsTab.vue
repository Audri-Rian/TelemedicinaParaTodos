<script setup lang="ts">
import EmptyBlock from '@/components/Patient/MedicalRecord/EmptyBlock.vue';
import { useFormatters } from '@/composables/useFormatters';
import type { VitalSignEntry } from '@/types/medical-records';

defineProps<{
    vitalSigns: VitalSignEntry[];
    emptyText: string;
}>();

const { formatDate } = useFormatters();
</script>

<template>
    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        <article v-for="entry in vitalSigns" :key="entry.id" class="rounded-lg border border-[#dde5ea] bg-white p-4">
            <h2 class="font-black text-gray-950">{{ formatDate(entry.recorded_at, true) }}</h2>
            <p class="mt-1 text-sm font-semibold text-gray-500">{{ entry.doctor?.name || 'Registro clínico' }}</p>
            <div class="mt-4 grid grid-cols-2 gap-2 text-sm">
                <p><span class="font-black">PA:</span> {{ entry.blood_pressure?.systolic || '—' }}/{{ entry.blood_pressure?.diastolic || '—' }}</p>
                <p><span class="font-black">Temp:</span> {{ entry.temperature || '—' }}°C</p>
                <p><span class="font-black">FC:</span> {{ entry.heart_rate || '—' }} bpm</p>
                <p><span class="font-black">O₂:</span> {{ entry.oxygen_saturation || '—' }}%</p>
                <p><span class="font-black">Peso:</span> {{ entry.weight || '—' }} kg</p>
                <p><span class="font-black">Altura:</span> {{ entry.height || '—' }} cm</p>
            </div>
            <p v-if="entry.notes" class="mt-3 text-sm font-medium text-gray-600">{{ entry.notes }}</p>
        </article>
        <EmptyBlock v-if="vitalSigns.length === 0" :text="emptyText" />
    </div>
</template>
