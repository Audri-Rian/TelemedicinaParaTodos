<script setup lang="ts">
import EditClinicalNoteModal from '@/components/Patient/MedicalRecord/EditClinicalNoteModal.vue';
import EmptyBlock from '@/components/Patient/MedicalRecord/EmptyBlock.vue';
import VersionHistoryModal from '@/components/Patient/MedicalRecord/VersionHistoryModal.vue';
import { useFormatters } from '@/composables/useFormatters';
import type { ClinicalNote } from '@/types/medical-records';
import { History, Pencil } from 'lucide-vue-next';
import { ref } from 'vue';

defineProps<{
    clinicalNotes: ClinicalNote[];
    emptyText: string;
    patientId?: string;
}>();

const { formatDate } = useFormatters();

const historyTarget = ref<ClinicalNote | null>(null);
const editTarget = ref<ClinicalNote | null>(null);
</script>

<template>
    <div class="grid gap-4 md:grid-cols-2">
        <article v-for="note in clinicalNotes" :key="note.id" class="rounded-lg border border-[#dde5ea] bg-white p-4">
            <div class="flex items-start justify-between gap-2">
                <h2 class="font-black text-gray-950">{{ note.title }}</h2>
                <div v-if="patientId" class="flex shrink-0 gap-1">
                    <button
                        type="button"
                        class="rounded p-1 text-gray-400 hover:bg-[#e5f1f2] hover:text-[#0f6e78]"
                        title="Editar anotação"
                        @click="editTarget = note"
                    >
                        <Pencil class="h-4 w-4" />
                    </button>
                    <button
                        type="button"
                        class="rounded p-1 text-gray-400 hover:bg-[#e5f1f2] hover:text-[#0f6e78]"
                        title="Ver histórico de alterações"
                        @click="historyTarget = note"
                    >
                        <History class="h-4 w-4" />
                    </button>
                </div>
            </div>
            <p class="mt-1 text-sm font-semibold text-gray-500">{{ note.doctor.name }} · {{ formatDate(note.created_at) }}</p>
            <p class="mt-3 text-sm font-medium whitespace-pre-wrap text-gray-600">{{ note.content }}</p>
            <div v-if="note.tags?.length" class="mt-3 flex flex-wrap gap-2">
                <span v-for="tag in note.tags" :key="tag" class="rounded-full bg-[#e5f1f2] px-2 py-1 text-xs font-black text-[#0f6e78]">{{
                    tag
                }}</span>
            </div>
        </article>
        <EmptyBlock v-if="clinicalNotes.length === 0" :text="emptyText" />
    </div>

    <template v-if="patientId">
        <EditClinicalNoteModal v-if="editTarget" :is-open="!!editTarget" :note="editTarget" :patient-id="patientId" @close="editTarget = null" />
        <VersionHistoryModal
            v-if="historyTarget"
            :is-open="!!historyTarget"
            :patient-id="patientId"
            record-type="notes"
            :record-id="historyTarget.id"
            :record-title="historyTarget.title"
            audience="doctor"
            @close="historyTarget = null"
        />
    </template>
</template>
