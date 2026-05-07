<script setup lang="ts">
import EmptyBlock from '@/components/Patient/MedicalRecord/EmptyBlock.vue';
import { useFormatters } from '@/composables/useFormatters';
import type { Diagnosis } from '@/types/medical-records';

defineProps<{
    diagnoses: Diagnosis[];
    emptyText: string;
}>();

const { formatStatus } = useFormatters();
</script>

<template>
    <div class="grid gap-4 md:grid-cols-2">
        <article v-for="diagnosis in diagnoses" :key="diagnosis.id" class="rounded-lg border border-[#dde5ea] bg-white p-4">
            <h2 class="font-black text-gray-950">{{ diagnosis.cid10_code }} · {{ diagnosis.cid10_description || 'CID-10' }}</h2>
            <p class="mt-1 text-sm font-semibold text-gray-500">{{ formatStatus(diagnosis.type) }} · {{ diagnosis.doctor.name }}</p>
            <p class="mt-3 text-sm font-medium text-gray-600">{{ diagnosis.description || 'Sem descrição adicional.' }}</p>
        </article>
        <EmptyBlock v-if="diagnoses.length === 0" :text="emptyText" />
    </div>
</template>
