<script setup lang="ts">
import { computed } from 'vue';
import AppointmentStatusBadge from '@/components/AppointmentStatusBadge.vue';

interface AppointmentDoctor {
    id: string;
    name: string;
    specializations?: (string | { id: string; name: string })[];
    avatar?: string | null;
}

interface AppointmentSummaryProps {
    id?: string;
    status: string;
    scheduled_at: string | null;
    doctor: AppointmentDoctor;
    locationLabel?: string | null;
    durationLabel?: string | null;
}

const props = defineProps<{
    appointment: AppointmentSummaryProps;
    class?: string;
}>();

const doctorSpecialization = computed(() => {
    const specializations = props.appointment.doctor.specializations ?? [];

    if (!specializations.length) {
        return 'Especialista';
    }

    const first = specializations[0];
    return typeof first === 'string' ? first : first.name;
});

const formattedDate = computed(() => {
    const { scheduled_at } = props.appointment;

    if (!scheduled_at) {
        return null;
    }

    try {
        return new Intl.DateTimeFormat('pt-BR', {
            weekday: 'short',
            day: '2-digit',
            month: 'long',
            hour: '2-digit',
            minute: '2-digit',
        }).format(new Date(scheduled_at));
    } catch (error) {
        return scheduled_at;
    }
});
</script>

<template>
    <div :class="['rounded-lg border border-gray-200 bg-white p-4 shadow-sm', props.class]">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div class="flex flex-col">
                <span class="text-sm font-semibold text-gray-700">{{ appointment.doctor.name }}</span>
                <span class="text-xs text-gray-500 capitalize">{{ doctorSpecialization }}</span>
                <span v-if="formattedDate" class="mt-1 text-xs text-gray-500">
                    {{ formattedDate }}
                </span>
            </div>

            <AppointmentStatusBadge :status="appointment.status" />
        </div>

        <div class="mt-3 space-y-1 text-xs text-gray-500">
            <div v-if="appointment.locationLabel">
                <span class="font-medium text-gray-600">Local:</span>
                <span>{{ appointment.locationLabel }}</span>
            </div>
            <div v-if="appointment.durationLabel">
                <span class="font-medium text-gray-600">Duração:</span>
                <span>{{ appointment.durationLabel }}</span>
            </div>
        </div>

        <div class="mt-4">
            <slot name="actions" :appointment="appointment" />
        </div>
    </div>
</template>
