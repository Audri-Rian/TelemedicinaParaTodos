<script setup lang="ts">
import EmptyBlock from '@/components/Patient/MedicalRecord/EmptyBlock.vue';
import { useFormatters } from '@/composables/useFormatters';
import type { Appointment } from '@/types/medical-records';

defineProps<{
    consultations: Appointment[];
    emptyText: string;
}>();

const { formatDate, formatStatus } = useFormatters();
</script>

<template>
    <div class="overflow-hidden rounded-lg border border-[#dde5ea]">
        <table class="min-w-full divide-y divide-[#dde5ea] text-sm">
            <thead class="bg-[#f7f8f9] text-xs font-black text-gray-500 uppercase">
                <tr>
                    <th class="px-4 py-3 text-left">Data</th>
                    <th class="px-4 py-3 text-left">Médico</th>
                    <th class="px-4 py-3 text-left">Status</th>
                    <th class="px-4 py-3 text-left">Diagnóstico</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-[#eef2f4] bg-white">
                <tr v-for="appointment in consultations" :key="appointment.id">
                    <td class="px-4 py-3 font-semibold">{{ formatDate(appointment.scheduled_at, true) }}</td>
                    <td class="px-4 py-3">{{ appointment.doctor.user.name }}</td>
                    <td class="px-4 py-3">{{ formatStatus(appointment.status) }}</td>
                    <td class="px-4 py-3">{{ appointment.diagnosis || '—' }}</td>
                </tr>
                <tr v-if="consultations.length === 0">
                    <td colspan="4" class="px-4 py-8 text-center">
                        <EmptyBlock :text="emptyText" />
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
