<script setup lang="ts">
import CertificateForm from '@/components/Doctor/ClinicalDocuments/CertificateForm.vue';
import ExaminationForm from '@/components/Doctor/ClinicalDocuments/ExaminationForm.vue';
import PrescriptionForm from '@/components/Doctor/ClinicalDocuments/PrescriptionForm.vue';
import { X } from 'lucide-vue-next';
import { computed } from 'vue';

export type InCallDocumentKind = 'rx' | 'exam' | 'certificate';

const props = defineProps<{
    open: boolean;
    kind: InCallDocumentKind;
    patientId: string;
    appointmentId: string;
}>();

const emit = defineEmits<{
    close: [];
    issued: [kind: InCallDocumentKind];
}>();

const title = computed(() => {
    if (props.kind === 'rx') return 'Prescrever medicamento';
    if (props.kind === 'exam') return 'Solicitar exame';
    return 'Emitir atestado';
});

const onSuccess = () => emit('issued', props.kind);
</script>

<template>
    <!-- Dialog do reka-ui fica em z-50, abaixo do overlay da chamada (z-60); usa o modal próprio do design (z-80) -->
    <div v-if="open" class="modal-backdrop" @click.self="emit('close')">
        <div
            class="modal"
            role="dialog"
            aria-modal="true"
            aria-labelledby="dcv-doc-title"
            style="width: 560px; max-height: 85vh; display: flex; flex-direction: column"
            @click.stop
        >
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px">
                <h3 id="dcv-doc-title" style="margin: 0">{{ title }}</h3>
                <button type="button" class="btn btn-outline" style="height: 30px; padding: 0 10px" @click="emit('close')">
                    <X class="h-3.5 w-3.5" />
                </button>
            </div>
            <p>Vinculado à consulta em andamento. O documento fica disponível no prontuário do paciente.</p>

            <div style="margin-top: 14px; overflow-y: auto; flex: 1; min-height: 0; padding-right: 4px">
                <PrescriptionForm
                    v-if="kind === 'rx'"
                    :patient-id="patientId"
                    :appointment-id="appointmentId"
                    @success="onSuccess"
                    @cancel="emit('close')"
                />
                <ExaminationForm
                    v-else-if="kind === 'exam'"
                    :patient-id="patientId"
                    :appointment-id="appointmentId"
                    @success="onSuccess"
                    @cancel="emit('close')"
                />
                <CertificateForm v-else :patient-id="patientId" :appointment-id="appointmentId" @success="onSuccess" @cancel="emit('close')" />
            </div>
        </div>
    </div>
</template>
