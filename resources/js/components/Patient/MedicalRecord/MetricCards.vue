<script setup lang="ts">
import { useFormatters } from '@/composables/useFormatters';
import type { MedicalMetrics } from '@/types/medical-records';
import { CalendarClock, Pill, Stethoscope, TestTube } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
    metrics: MedicalMetrics;
    upcomingCount: number;
}>();

const { formatDate } = useFormatters();

const cards = computed(() => [
    {
        label: 'Consultas',
        value: props.metrics.total_consultations,
        helper: `Última: ${formatDate(props.metrics.last_consultation_at)}`,
        icon: Stethoscope,
        color: 'text-[#0f6e78]',
        bg: 'bg-[#e5f1f2]',
    },
    {
        label: 'Prescrições',
        value: props.metrics.total_prescriptions,
        helper: 'Receitas emitidas',
        icon: Pill,
        color: 'text-rose-700',
        bg: 'bg-rose-50',
    },
    {
        label: 'Exames',
        value: props.metrics.total_examinations,
        helper: 'Solicitações e laudos',
        icon: TestTube,
        color: 'text-emerald-700',
        bg: 'bg-emerald-50',
    },
    {
        label: 'Futuras',
        value: props.upcomingCount,
        helper: 'Consultas agendadas',
        icon: CalendarClock,
        color: 'text-amber-700',
        bg: 'bg-amber-50',
    },
]);
</script>

<template>
    <div class="grid gap-3 border-t border-[#e6ebee] bg-[#fbfcfc] p-4 sm:grid-cols-2 xl:grid-cols-4">
        <div v-for="card in cards" :key="card.label" class="rounded-lg border border-[#dde5ea] bg-white p-4">
            <div class="flex items-center justify-between">
                <p class="text-xs font-black text-gray-500 uppercase">{{ card.label }}</p>
                <span class="grid h-9 w-9 place-items-center rounded-lg" :class="[card.bg, card.color]">
                    <component :is="card.icon" class="h-4 w-4" />
                </span>
            </div>
            <p class="mt-3 text-3xl font-black text-gray-950">{{ card.value }}</p>
            <p class="mt-1 text-xs font-semibold text-gray-500">{{ card.helper }}</p>
        </div>
    </div>
</template>
