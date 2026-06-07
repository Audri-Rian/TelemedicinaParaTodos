<script setup lang="ts">
import AppointmentSelect from '@/components/Doctor/ClinicalDocuments/AppointmentSelect.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import type { EligibleAppointment } from '@/types/clinical-documents';
import { useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        patientId: string;
        appointments?: EligibleAppointment[];
        appointmentId?: string | null;
        appointmentsLoading?: boolean;
        appointmentsError?: string | null;
    }>(),
    {
        appointments: () => [],
        appointmentId: null,
        appointmentsLoading: false,
        appointmentsError: null,
    },
);

const emit = defineEmits<{ success: []; cancel: [] }>();

const form = useForm({
    appointment_id: props.appointmentId ?? '',
    medications: [{ name: '', dosage: '', frequency: '' }],
    instructions: '',
    valid_until: '',
});

const hasFixedAppointment = computed(() => Boolean(props.appointmentId));
const canSubmit = computed(() => Boolean(form.appointment_id) && form.medications.some((med) => med.name.trim() !== ''));

function addMedication(): void {
    form.medications.push({ name: '', dosage: '', frequency: '' });
}

function removeMedication(idx: number): void {
    form.medications.splice(idx, 1);
}

function submit(): void {
    if (!canSubmit.value || form.processing) return;

    // preserveState mantém overlay/streams vivos no in-call e o modal montado nas tabs
    form.post(`/doctor/patients/${props.patientId}/medical-record/prescriptions`, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            form.reset();
            emit('success');
        },
    });
}
</script>

<template>
    <form class="flex flex-col gap-4" @submit.prevent="submit">
        <AppointmentSelect
            v-if="!hasFixedAppointment"
            v-model="form.appointment_id"
            :appointments="appointments"
            :loading="appointmentsLoading"
            :error="appointmentsError"
            :field-error="form.errors.appointment_id"
        />

        <div class="space-y-2">
            <div class="flex items-center justify-between">
                <Label>Medicamentos</Label>
                <Button type="button" variant="outline" size="sm" @click="addMedication">+ Adicionar</Button>
            </div>

            <div v-for="(med, idx) in form.medications" :key="idx" class="space-y-2 rounded-lg border border-[#dde5ea] p-3">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-gray-500">Medicamento {{ idx + 1 }}</span>
                    <button
                        v-if="form.medications.length > 1"
                        type="button"
                        class="text-xs text-red-500 hover:underline"
                        @click="removeMedication(idx)"
                    >
                        Remover
                    </button>
                </div>
                <div class="grid grid-cols-3 gap-2">
                    <div class="col-span-3 space-y-1">
                        <Label :for="`new-med-name-${idx}`" class="text-xs">Nome</Label>
                        <Input :id="`new-med-name-${idx}`" v-model="med.name" placeholder="Nome do medicamento" />
                    </div>
                    <div class="space-y-1">
                        <Label :for="`new-med-dosage-${idx}`" class="text-xs">Dose</Label>
                        <Input :id="`new-med-dosage-${idx}`" v-model="med.dosage" placeholder="500mg" />
                    </div>
                    <div class="col-span-2 space-y-1">
                        <Label :for="`new-med-freq-${idx}`" class="text-xs">Frequência</Label>
                        <Input :id="`new-med-freq-${idx}`" v-model="med.frequency" placeholder="1x ao dia" />
                    </div>
                </div>
            </div>
            <p v-if="form.errors.medications" class="text-xs text-red-600">{{ form.errors.medications }}</p>
        </div>

        <div class="space-y-1.5">
            <Label for="new-presc-instructions">Instruções</Label>
            <Textarea id="new-presc-instructions" v-model="form.instructions" rows="3" class="resize-none" />
            <p v-if="form.errors.instructions" class="text-xs text-red-600">{{ form.errors.instructions }}</p>
        </div>

        <div class="space-y-1.5">
            <Label for="new-presc-valid-until">Válido até</Label>
            <Input id="new-presc-valid-until" v-model="form.valid_until" type="date" />
            <p v-if="form.errors.valid_until" class="text-xs text-red-600">{{ form.errors.valid_until }}</p>
        </div>

        <div class="flex justify-end gap-2 border-t border-[#dde5ea] pt-4">
            <Button type="button" variant="outline" :disabled="form.processing" @click="emit('cancel')">Cancelar</Button>
            <Button type="submit" :disabled="form.processing || !canSubmit">
                {{ form.processing ? 'Emitindo...' : 'Emitir prescrição' }}
            </Button>
        </div>
    </form>
</template>
