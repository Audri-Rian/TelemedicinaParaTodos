<script setup lang="ts">
import PrescriptionForm from '@/components/Doctor/ClinicalDocuments/PrescriptionForm.vue';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { useEligibleAppointments } from '@/composables/Doctor/useEligibleAppointments';
import { onMounted, toRef } from 'vue';

const props = defineProps<{
    isOpen: boolean;
    patientId: string;
}>();

const emit = defineEmits<{ close: [] }>();

const { appointments, loading, error, load } = useEligibleAppointments(toRef(props, 'patientId'));

onMounted(load);
</script>

<template>
    <Dialog
        :open="isOpen"
        @update:open="
            (v) => {
                if (!v) emit('close');
            }
        "
    >
        <DialogContent class="flex max-h-[85vh] flex-col sm:max-w-lg">
            <DialogHeader class="shrink-0">
                <DialogTitle>Nova prescrição</DialogTitle>
            </DialogHeader>

            <div class="mt-4 min-h-0 flex-1 overflow-y-auto pr-1">
                <PrescriptionForm
                    :patient-id="patientId"
                    :appointments="appointments"
                    :appointments-loading="loading"
                    :appointments-error="error"
                    @success="emit('close')"
                    @cancel="emit('close')"
                />
            </div>
        </DialogContent>
    </Dialog>
</template>
