<script setup lang="ts">
import CreateExaminationModal from '@/components/Doctor/ClinicalDocuments/CreateExaminationModal.vue';
import EmptyBlock from '@/components/Patient/MedicalRecord/EmptyBlock.vue';
import { useFormatters } from '@/composables/useFormatters';
import type { Examination } from '@/types/medical-records';
import { Link } from '@inertiajs/vue3';
import { FileText, Plus } from 'lucide-vue-next';
import { ref } from 'vue';

defineProps<{
    examinations: Examination[];
    emptyText: string;
    patientId?: string;
}>();

const { formatDate, formatStatus, isSafeUrl } = useFormatters();

const createOpen = ref(false);

function getResultSummary(results: unknown): string {
    return (results as Record<string, string>)?.summary || '—';
}
</script>

<template>
    <div v-if="patientId" class="mb-4 flex justify-end">
        <button
            type="button"
            class="inline-flex items-center gap-1.5 rounded-lg bg-[#0f6e78] px-3 py-2 text-sm font-black text-white hover:bg-[#0a4f57]"
            @click="createOpen = true"
        >
            <Plus class="h-4 w-4" />
            Solicitar exame
        </button>
    </div>

    <div class="grid gap-4 md:grid-cols-2">
        <article v-for="exam in examinations" :key="exam.id" class="rounded-lg border border-[#dde5ea] bg-white p-4">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h2 class="font-black text-gray-950">{{ exam.name }}</h2>
                    <p class="mt-1 text-sm font-semibold text-gray-500">{{ formatStatus(exam.type) }} · {{ formatStatus(exam.status) }}</p>
                </div>
                <span
                    v-if="exam.source === 'integration' && exam.partner"
                    class="rounded-full bg-[#e5f1f2] px-2 py-1 text-[11px] font-black text-[#0f6e78]"
                >
                    {{ exam.partner.name }}
                </span>
            </div>
            <div class="mt-4 space-y-2 text-sm text-gray-700">
                <p><span class="font-black text-gray-950">Solicitado:</span> {{ formatDate(exam.requested_at) }}</p>
                <p><span class="font-black text-gray-950">Concluído:</span> {{ formatDate(exam.completed_at) }}</p>
                <p><span class="font-black text-gray-950">Resultado:</span> {{ getResultSummary(exam.results) }}</p>
                <Link
                    v-if="isSafeUrl(exam.attachment_url)"
                    :href="exam.attachment_url!"
                    target="_blank"
                    class="inline-flex items-center font-black text-[#0f6e78] hover:underline"
                >
                    <FileText class="mr-1 h-4 w-4" />
                    Ver laudo
                </Link>
            </div>
        </article>
        <EmptyBlock v-if="examinations.length === 0" :text="emptyText" />
    </div>

    <CreateExaminationModal v-if="patientId && createOpen" :is-open="createOpen" :patient-id="patientId" @close="createOpen = false" />
</template>
