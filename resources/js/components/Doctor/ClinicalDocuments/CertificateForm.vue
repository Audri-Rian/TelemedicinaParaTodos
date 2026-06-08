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

const form = useForm<{
    appointment_id: string;
    type: string;
    start_date: string;
    end_date: string;
    days: number | null;
    reason: string;
    restrictions: string;
}>({
    appointment_id: props.appointmentId ?? '',
    type: 'absence',
    start_date: new Date().toISOString().slice(0, 10),
    end_date: '',
    days: null,
    reason: '',
    restrictions: '',
});

const hasFixedAppointment = computed(() => Boolean(props.appointmentId));
const canSubmit = computed(() => Boolean(form.appointment_id) && form.start_date !== '' && form.reason.trim() !== '');

const types = [
    { value: 'absence', label: 'Afastamento' },
    { value: 'attendance', label: 'Comparecimento' },
    { value: 'disability', label: 'Incapacidade' },
    { value: 'other', label: 'Outro' },
];

function submit(): void {
    if (!canSubmit.value || form.processing) return;

    form.post(`/doctor/patients/${props.patientId}/medical-record/certificates`, {
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
            <Label for="new-cert-type">Tipo</Label>
            <select
                id="new-cert-type"
                v-model="form.type"
                class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm"
            >
                <option v-for="t in types" :key="t.value" :value="t.value">{{ t.label }}</option>
            </select>
        </div>

        <div class="grid grid-cols-3 gap-3">
            <div class="space-y-1.5">
                <Label for="new-cert-start"> Data início <span class="text-red-500">*</span> </Label>
                <Input id="new-cert-start" v-model="form.start_date" type="date" :class="{ 'border-red-500': form.errors.start_date }" />
            </div>
            <div class="space-y-1.5">
                <Label for="new-cert-end">Data fim</Label>
                <Input id="new-cert-end" v-model="form.end_date" type="date" />
            </div>
            <div class="space-y-1.5">
                <Label for="new-cert-days">Dias</Label>
                <Input id="new-cert-days" v-model="form.days" type="number" min="1" />
            </div>
        </div>
        <p v-if="form.errors.start_date" class="text-xs text-red-600">{{ form.errors.start_date }}</p>
        <p v-if="form.errors.end_date" class="text-xs text-red-600">{{ form.errors.end_date }}</p>
        <p v-if="form.errors.days" class="text-xs text-red-600">{{ form.errors.days }}</p>

        <div class="space-y-1.5">
            <Label for="new-cert-reason"> Motivo clínico <span class="text-red-500">*</span> </Label>
            <Textarea id="new-cert-reason" v-model="form.reason" rows="3" class="resize-none" :class="{ 'border-red-500': form.errors.reason }" />
            <p v-if="form.errors.reason" class="text-xs text-red-600">{{ form.errors.reason }}</p>
        </div>

        <div class="space-y-1.5">
            <Label for="new-cert-restrictions">Restrições</Label>
            <Textarea id="new-cert-restrictions" v-model="form.restrictions" rows="2" class="resize-none" />
            <p v-if="form.errors.restrictions" class="text-xs text-red-600">{{ form.errors.restrictions }}</p>
        </div>

        <div class="flex justify-end gap-2 border-t border-[#dde5ea] pt-4">
            <Button type="button" variant="outline" :disabled="form.processing" @click="emit('cancel')">Cancelar</Button>
            <Button type="submit" :disabled="form.processing || !canSubmit">
                {{ form.processing ? 'Emitindo...' : 'Emitir atestado' }}
            </Button>
        </div>
    </form>
</template>
