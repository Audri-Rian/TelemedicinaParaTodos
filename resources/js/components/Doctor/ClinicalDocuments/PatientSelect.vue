<script setup lang="ts">
import { Label } from '@/components/ui/label';
import type { EligibleDocumentPatient } from '@/types/clinical-documents';

defineProps<{
    modelValue: string;
    patients: EligibleDocumentPatient[];
    relationshipDays?: number;
    loading?: boolean;
    error?: string | null;
    fieldError?: string;
}>();

const emit = defineEmits<{ 'update:modelValue': [value: string] }>();
</script>

<template>
    <div class="space-y-1.5">
        <Label for="doc-patient"> Paciente <span class="text-red-500">*</span> </Label>
        <div v-if="loading" class="h-9 w-full animate-pulse rounded-md bg-zinc-100" />
        <select
            v-else
            id="doc-patient"
            :value="modelValue"
            class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm"
            :disabled="patients.length === 0"
            @change="emit('update:modelValue', ($event.target as HTMLSelectElement).value)"
        >
            <option value="" disabled>Selecione o paciente</option>
            <option v-for="p in patients" :key="p.id" :value="p.id">{{ p.name }}</option>
        </select>
        <p v-if="!loading && patients.length === 0 && !error" class="text-xs text-amber-700">
            Nenhum paciente elegível para emissão nos últimos {{ relationshipDays ?? 10 }} dias.
        </p>
        <p v-if="error" class="text-xs text-red-600">{{ error }}</p>
        <p v-if="fieldError" class="text-xs text-red-600">{{ fieldError }}</p>
    </div>
</template>
