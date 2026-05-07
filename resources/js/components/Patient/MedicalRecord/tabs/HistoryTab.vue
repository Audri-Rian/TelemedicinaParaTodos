<script setup lang="ts">
import EmptyBlock from '@/components/Patient/MedicalRecord/EmptyBlock.vue';
import { Button } from '@/components/ui/button';
import { useFormatters } from '@/composables/useFormatters';
import type { Appointment } from '@/types/medical-records';
import { ref, watch } from 'vue';

const props = defineProps<{
    consultations: Appointment[];
    emptyText: string;
}>();

const { formatDate, formatStatus } = useFormatters();

const expandedItems = ref<Set<string>>(new Set());

watch(
    () => props.consultations,
    (items) => {
        if (items.length && expandedItems.value.size === 0) {
            expandedItems.value.add(items[0].id);
        }
    },
    { immediate: true },
);

function toggleExpand(id: string) {
    const next = new Set(expandedItems.value);
    if (next.has(id)) {
        next.delete(id);
    } else {
        next.add(id);
    }
    expandedItems.value = next;
}
</script>

<template>
    <div class="space-y-3">
        <article v-for="consultation in consultations" :key="consultation.id" class="overflow-hidden rounded-lg border border-[#dde5ea] bg-white">
            <header class="flex flex-col gap-3 bg-[#f7f8f9] p-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-lg font-black text-gray-950">{{ formatDate(consultation.scheduled_at, true) }}</h2>
                    <p class="mt-1 text-sm font-semibold text-gray-600">
                        {{ consultation.doctor.user.name }}
                        <span v-if="consultation.doctor.specializations?.length"> · {{ consultation.doctor.specializations[0].name }}</span>
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="rounded-full bg-white px-3 py-1 text-xs font-black text-gray-600">{{ formatStatus(consultation.status) }}</span>
                    <Button variant="outline" size="sm" class="border-[#dde5ea] font-extrabold" @click="toggleExpand(consultation.id)">
                        {{ expandedItems.has(consultation.id) ? 'Recolher' : 'Detalhes' }}
                    </Button>
                </div>
            </header>

            <div v-if="expandedItems.has(consultation.id)" class="grid gap-4 p-4 md:grid-cols-2">
                <div>
                    <p class="text-xs font-black text-gray-500 uppercase">Diagnóstico</p>
                    <p class="mt-1 text-sm font-semibold text-gray-700">{{ consultation.diagnosis || 'Não informado' }}</p>
                </div>
                <div>
                    <p class="text-xs font-black text-gray-500 uppercase">CID-10</p>
                    <p class="mt-1 text-sm font-semibold text-gray-700">{{ consultation.cid10 || '—' }}</p>
                </div>
                <div>
                    <p class="text-xs font-black text-gray-500 uppercase">Sintomas</p>
                    <p class="mt-1 text-sm font-semibold text-gray-700">{{ consultation.symptoms || 'Não informado' }}</p>
                </div>
                <div>
                    <p class="text-xs font-black text-gray-500 uppercase">Exames solicitados</p>
                    <p class="mt-1 text-sm font-semibold text-gray-700">{{ consultation.requested_exams || 'Não informado' }}</p>
                </div>
            </div>
        </article>

        <EmptyBlock v-if="consultations.length === 0" :text="emptyText" />
    </div>
</template>
