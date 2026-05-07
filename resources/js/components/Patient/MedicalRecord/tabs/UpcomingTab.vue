<script setup lang="ts">
import EmptyBlock from '@/components/Patient/MedicalRecord/EmptyBlock.vue';
import { useFormatters } from '@/composables/useFormatters';
import type { Appointment } from '@/types/medical-records';

defineProps<{
    upcomingAppointments: Appointment[];
    emptyText: string;
}>();

const { formatDate, formatStatus } = useFormatters();
</script>

<template>
    <div class="grid gap-4 md:grid-cols-2">
        <article v-for="appointment in upcomingAppointments" :key="appointment.id" class="rounded-lg border border-[#dde5ea] bg-white p-4">
            <h2 class="font-black text-gray-950">{{ formatDate(appointment.scheduled_at, true) }}</h2>
            <p class="mt-1 text-sm font-semibold text-gray-500">{{ appointment.doctor.user.name }}</p>
            <p class="mt-3 text-sm font-medium text-gray-600">Status: {{ formatStatus(appointment.status) }}</p>
        </article>
        <EmptyBlock v-if="upcomingAppointments.length === 0" :text="emptyText" />
    </div>
</template>
