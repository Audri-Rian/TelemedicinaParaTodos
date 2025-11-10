<script setup lang="ts">
import { computed } from 'vue';

type AppointmentStatus =
    | 'scheduled'
    | 'in_progress'
    | 'completed'
    | 'cancelled'
    | 'rescheduled'
    | 'no_show'
    | string;

const props = withDefaults(defineProps<{
    status: AppointmentStatus;
    class?: string;
}>(), {
    class: '',
});

const statusConfig = computed(() => {
    const map: Record<string, { label: string; class: string }> = {
        scheduled: { label: 'Agendada', class: 'bg-yellow-100 text-yellow-700' },
        in_progress: { label: 'Em Andamento', class: 'bg-blue-100 text-blue-700' },
        completed: { label: 'Concluída', class: 'bg-green-100 text-green-700' },
        cancelled: { label: 'Cancelada', class: 'bg-red-100 text-red-700' },
        rescheduled: { label: 'Reagendada', class: 'bg-purple-100 text-purple-700' },
        no_show: { label: 'Não Compareceu', class: 'bg-gray-100 text-gray-700' },
    };

    return map[props.status] ?? { label: props.status, class: 'bg-gray-100 text-gray-700' };
});
</script>

<template>
    <span
        :class="[
            'inline-flex items-center gap-1 rounded-full px-3 py-1 text-xs font-medium capitalize',
            statusConfig.class,
            props.class,
        ]"
    >
        {{ statusConfig.label }}
    </span>
</template>
