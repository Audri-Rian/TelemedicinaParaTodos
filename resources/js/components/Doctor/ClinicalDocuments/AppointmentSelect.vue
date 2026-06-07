<script setup lang="ts">
import { Label } from '@/components/ui/label';
import { eligibleAppointmentLabel, type EligibleAppointment } from '@/types/clinical-documents';

defineProps<{
    modelValue: string;
    appointments: EligibleAppointment[];
    loading?: boolean;
    error?: string | null;
    fieldError?: string;
}>();

const emit = defineEmits<{ 'update:modelValue': [value: string] }>();
</script>

<template>
    <div class="space-y-1.5">
        <Label for="doc-appointment"> Consulta vinculada <span class="text-red-500">*</span> </Label>
        <select
            id="doc-appointment"
            :value="modelValue"
            class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm"
            :disabled="loading || appointments.length === 0"
            @change="emit('update:modelValue', ($event.target as HTMLSelectElement).value)"
        >
            <option value="" disabled>{{ loading ? 'Carregando consultas…' : 'Selecione a consulta' }}</option>
            <option v-for="appointment in appointments" :key="appointment.id" :value="appointment.id">
                {{ eligibleAppointmentLabel(appointment) }}
            </option>
        </select>
        <p v-if="!loading && appointments.length === 0 && !error" class="text-xs text-amber-700">Nenhuma consulta disponível para vincular.</p>
        <p v-if="error" class="text-xs text-red-600">{{ error }}</p>
        <p v-if="fieldError" class="text-xs text-red-600">{{ fieldError }}</p>
    </div>
</template>
