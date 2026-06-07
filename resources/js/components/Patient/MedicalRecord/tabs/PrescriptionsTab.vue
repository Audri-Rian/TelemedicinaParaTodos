<script setup lang="ts">
import CreatePrescriptionModal from '@/components/Doctor/ClinicalDocuments/CreatePrescriptionModal.vue';
import EditPrescriptionModal from '@/components/Patient/MedicalRecord/EditPrescriptionModal.vue';
import EmptyBlock from '@/components/Patient/MedicalRecord/EmptyBlock.vue';
import VersionHistoryModal from '@/components/Patient/MedicalRecord/VersionHistoryModal.vue';
import { useFormatters } from '@/composables/useFormatters';
import type { Prescription } from '@/types/medical-records';
import { History, Pencil, Plus } from 'lucide-vue-next';
import { ref } from 'vue';

defineProps<{
    prescriptions: Prescription[];
    emptyText: string;
    patientId?: string;
}>();

const { formatDate, formatStatus } = useFormatters();

const historyTarget = ref<Prescription | null>(null);
const editTarget = ref<Prescription | null>(null);
const createOpen = ref(false);

function canEdit(prescription: Prescription): boolean {
    // Block edit if signed/verified — observer handles server-side enforcement
    return !['signed', 'verified'].includes(prescription.signature_status ?? '');
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
            Nova prescrição
        </button>
    </div>

    <div class="grid gap-4 md:grid-cols-2">
        <article v-for="prescription in prescriptions" :key="prescription.id" class="rounded-lg border border-[#dde5ea] bg-white p-4">
            <div class="flex items-start justify-between gap-2">
                <h2 class="font-black text-gray-950">Emitida em {{ formatDate(prescription.issued_at) }}</h2>
                <div v-if="patientId" class="flex shrink-0 gap-1">
                    <button
                        v-if="canEdit(prescription)"
                        type="button"
                        class="rounded p-1 text-gray-400 hover:bg-[#e5f1f2] hover:text-[#0f6e78]"
                        title="Editar prescrição"
                        @click="editTarget = prescription"
                    >
                        <Pencil class="h-4 w-4" />
                    </button>
                    <button
                        type="button"
                        class="rounded p-1 text-gray-400 hover:bg-[#e5f1f2] hover:text-[#0f6e78]"
                        title="Ver histórico de alterações"
                        @click="historyTarget = prescription"
                    >
                        <History class="h-4 w-4" />
                    </button>
                </div>
            </div>
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

    <template v-if="patientId">
        <CreatePrescriptionModal v-if="createOpen" :is-open="createOpen" :patient-id="patientId" @close="createOpen = false" />
        <EditPrescriptionModal
            v-if="editTarget"
            :is-open="!!editTarget"
            :prescription="editTarget"
            :patient-id="patientId"
            @close="editTarget = null"
        />
        <VersionHistoryModal
            v-if="historyTarget"
            :is-open="!!historyTarget"
            :patient-id="patientId"
            record-type="prescriptions"
            :record-id="historyTarget.id"
            :record-title="`Prescrição emitida em ${formatDate(historyTarget.issued_at)}`"
            audience="doctor"
            @close="historyTarget = null"
        />
    </template>
</template>
