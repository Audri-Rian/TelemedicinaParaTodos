<script setup lang="ts">
import EmptyBlock from '@/components/Patient/MedicalRecord/EmptyBlock.vue';
import { useFormatters } from '@/composables/useFormatters';
import type { Prescription } from '@/types/medical-records';

defineProps<{
    prescriptions: Prescription[];
    emptyText: string;
}>();

const { formatDate, formatStatus } = useFormatters();
</script>

<template>
    <div class="grid gap-4 md:grid-cols-2">
        <article v-for="prescription in prescriptions" :key="prescription.id" class="rounded-lg border border-[#dde5ea] bg-white p-4">
            <h2 class="font-black text-gray-950">Emitida em {{ formatDate(prescription.issued_at) }}</h2>
            <p class="mt-1 text-sm font-semibold text-gray-500">Médico: {{ prescription.doctor?.name || '—' }}</p>
            <div class="mt-4 space-y-2 text-sm text-gray-700">
                <p class="font-black text-gray-950">Medicamentos</p>
                <ul class="ml-4 list-disc">
                    <li v-for="(med, idx) in prescription.medications" :key="idx">
                        {{ med.name || 'Medicamento' }} · {{ med.dosage || '' }} {{ med.frequency || '' }}
                    </li>
                </ul>
                <p><span class="font-black text-gray-950">Instruções:</span> {{ prescription.instructions || '—' }}</p>
                <p><span class="font-black text-gray-950">Validade:</span> {{ formatDate(prescription.valid_until) }}</p>
                <p><span class="font-black text-gray-950">Status:</span> {{ formatStatus(prescription.status) }}</p>
            </div>
        </article>
        <EmptyBlock v-if="prescriptions.length === 0" :text="emptyText" />
    </div>
</template>
