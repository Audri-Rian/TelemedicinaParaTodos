<script setup lang="ts">
import EditCertificateModal from '@/components/Patient/MedicalRecord/EditCertificateModal.vue';
import EmptyBlock from '@/components/Patient/MedicalRecord/EmptyBlock.vue';
import VersionHistoryModal from '@/components/Patient/MedicalRecord/VersionHistoryModal.vue';
import { useFormatters } from '@/composables/useFormatters';
import type { MedicalCertificate } from '@/types/medical-records';
import { Link } from '@inertiajs/vue3';
import { FileCheck2, History, Pencil } from 'lucide-vue-next';
import { ref } from 'vue';

defineProps<{
    medicalCertificates: MedicalCertificate[];
    emptyText: string;
    patientId?: string;
}>();

const { formatDate, formatStatus, isSafeUrl } = useFormatters();

const historyTarget = ref<MedicalCertificate | null>(null);
const editTarget = ref<MedicalCertificate | null>(null);

function canEdit(certificate: MedicalCertificate): boolean {
    return !['signed', 'verified'].includes(certificate.signature_status ?? '');
}
</script>

<template>
    <div class="grid gap-4 md:grid-cols-2">
        <article v-for="certificate in medicalCertificates" :key="certificate.id" class="rounded-lg border border-[#dde5ea] bg-white p-4">
            <div class="flex items-start justify-between gap-2">
                <h2 class="font-black text-gray-950">{{ formatStatus(certificate.type) }}</h2>
                <div v-if="patientId" class="flex shrink-0 gap-1">
                    <button
                        v-if="canEdit(certificate)"
                        type="button"
                        class="rounded p-1 text-gray-400 hover:bg-[#e5f1f2] hover:text-[#0f6e78]"
                        title="Editar atestado"
                        @click="editTarget = certificate"
                    >
                        <Pencil class="h-4 w-4" />
                    </button>
                    <button
                        type="button"
                        class="rounded p-1 text-gray-400 hover:bg-[#e5f1f2] hover:text-[#0f6e78]"
                        title="Ver histórico de alterações"
                        @click="historyTarget = certificate"
                    >
                        <History class="h-4 w-4" />
                    </button>
                </div>
            </div>
            <p class="mt-1 text-sm font-semibold text-gray-500">{{ certificate.doctor.name }} · {{ formatDate(certificate.created_at) }}</p>
            <p class="mt-3 text-sm font-medium text-gray-600">{{ certificate.reason }}</p>
            <p class="mt-2 text-xs font-black text-gray-500">Código: {{ certificate.verification_code }}</p>
            <Link
                v-if="isSafeUrl(certificate.pdf_url)"
                :href="certificate.pdf_url!"
                target="_blank"
                class="mt-4 inline-flex items-center font-black text-[#0f6e78] hover:underline"
            >
                <FileCheck2 class="mr-1 h-4 w-4" />
                Ver PDF
            </Link>
        </article>
        <EmptyBlock v-if="medicalCertificates.length === 0" :text="emptyText" />
    </div>

    <template v-if="patientId">
        <EditCertificateModal
            v-if="editTarget"
            :is-open="!!editTarget"
            :certificate="editTarget"
            :patient-id="patientId"
            @close="editTarget = null"
        />
        <VersionHistoryModal
            v-if="historyTarget"
            :is-open="!!historyTarget"
            :patient-id="patientId"
            record-type="certificates"
            :record-id="historyTarget.id"
            :record-title="`Atestado — ${formatStatus(historyTarget.type)}`"
            audience="doctor"
            @close="historyTarget = null"
        />
    </template>
</template>
