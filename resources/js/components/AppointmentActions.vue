<script setup lang="ts">
import { computed } from 'vue';
import { Button } from '@/components/ui/button';

type AppointmentStatus =
    | 'scheduled'
    | 'in_progress'
    | 'completed'
    | 'cancelled'
    | 'rescheduled'
    | 'no_show'
    | string;

type AppointmentPermissions = {
    start?: boolean;
    cancel?: boolean;
    reschedule?: boolean;
};

const props = withDefaults(defineProps<{
    appointment: {
        id?: string;
        status: AppointmentStatus;
        can?: AppointmentPermissions;
    };
    loadingStart?: boolean;
    loadingCancel?: boolean;
    loadingReschedule?: boolean;
    showReschedule?: boolean;
    class?: string;
}>(), {
    loadingStart: false,
    loadingCancel: false,
    loadingReschedule: false,
    showReschedule: true,
    class: '',
});

const emit = defineEmits<{
    (event: 'start'): void;
    (event: 'cancel'): void;
    (event: 'reschedule'): void;
}>();

const canStart = computed(() => {
    if (props.appointment.can && typeof props.appointment.can.start === 'boolean') {
        return props.appointment.can.start;
    }

    return ['scheduled', 'rescheduled'].includes(props.appointment.status);
});

const canCancel = computed(() => {
    if (props.appointment.can && typeof props.appointment.can.cancel === 'boolean') {
        return props.appointment.can.cancel;
    }

    return ['scheduled', 'rescheduled'].includes(props.appointment.status);
});

const canReschedule = computed(() => {
    if (!props.showReschedule) {
        return false;
    }

    if (props.appointment.can && typeof props.appointment.can.reschedule === 'boolean') {
        return props.appointment.can.reschedule;
    }

    return ['scheduled', 'rescheduled'].includes(props.appointment.status);
});
</script>

<template>
    <div :class="['flex flex-wrap gap-2', props.class]">
        <Button
            v-if="canStart"
            class="bg-primary hover:bg-primary/90 text-gray-900"
            :disabled="loadingStart"
            @click="emit('start')"
        >
            <span v-if="loadingStart">Iniciando...</span>
            <span v-else>Iniciar Consulta</span>
        </Button>

        <Button
            v-if="canCancel"
            variant="outline"
            class="border-red-300 text-red-700 hover:bg-red-50"
            :disabled="loadingCancel"
            @click="emit('cancel')"
        >
            <span v-if="loadingCancel">Cancelando...</span>
            <span v-else>Cancelar Consulta</span>
        </Button>

        <Button
            v-if="canReschedule"
            variant="outline"
            class="border-primary text-primary hover:bg-primary/10"
            :disabled="loadingReschedule"
            @click="emit('reschedule')"
        >
            <span v-if="loadingReschedule">Reagendando...</span>
            <span v-else>Reagendar</span>
        </Button>
    </div>
</template>
