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
    name: '',
    type: 'lab',
    justification: '',
    instructions: '',
    priority: 'normal',
});

const hasFixedAppointment = computed(() => Boolean(props.appointmentId));
const canSubmit = computed(() => Boolean(form.appointment_id) && form.name.trim() !== '' && form.justification.trim() !== '');

const types = [
    { value: 'lab', label: 'Laboratorial' },
    { value: 'image', label: 'Imagem' },
    { value: 'other', label: 'Outro' },
];

function submit(): void {
    if (!canSubmit.value || form.processing) return;

    form.post(`/doctor/patients/${props.patientId}/medical-record/examinations`, {
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

        <div class="space-y-1.5">
            <Label for="new-exam-name"> Nome do exame <span class="text-red-500">*</span> </Label>
            <Input id="new-exam-name" v-model="form.name" placeholder="Hemograma completo" :class="{ 'border-red-500': form.errors.name }" />
            <p v-if="form.errors.name" class="text-xs text-red-600">{{ form.errors.name }}</p>
        </div>

        <div class="grid grid-cols-2 gap-3">
            <div class="space-y-1.5">
                <Label for="new-exam-type">Tipo</Label>
                <select
                    id="new-exam-type"
                    v-model="form.type"
                    class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm"
                >
                    <option v-for="t in types" :key="t.value" :value="t.value">{{ t.label }}</option>
                </select>
            </div>
            <div class="space-y-1.5">
                <Label for="new-exam-priority">Urgência</Label>
                <select
                    id="new-exam-priority"
                    v-model="form.priority"
                    class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm"
                >
                    <option value="normal">Rotina</option>
                    <option value="urgent">Urgente</option>
                </select>
            </div>
        </div>

        <div class="space-y-1.5">
            <Label for="new-exam-justification"> Justificativa clínica <span class="text-red-500">*</span> </Label>
            <Textarea
                id="new-exam-justification"
                v-model="form.justification"
                rows="3"
                class="resize-none"
                :class="{ 'border-red-500': form.errors.justification }"
            />
            <p v-if="form.errors.justification" class="text-xs text-red-600">{{ form.errors.justification }}</p>
        </div>

        <div class="space-y-1.5">
            <Label for="new-exam-instructions">Preparo / instruções</Label>
            <Textarea id="new-exam-instructions" v-model="form.instructions" rows="2" class="resize-none" />
            <p v-if="form.errors.instructions" class="text-xs text-red-600">{{ form.errors.instructions }}</p>
        </div>

        <div class="flex justify-end gap-2 border-t border-[#dde5ea] pt-4">
            <Button type="button" variant="outline" :disabled="form.processing" @click="emit('cancel')">Cancelar</Button>
            <Button type="submit" :disabled="form.processing || !canSubmit">
                {{ form.processing ? 'Solicitando...' : 'Solicitar exame' }}
            </Button>
        </div>
    </form>
</template>
