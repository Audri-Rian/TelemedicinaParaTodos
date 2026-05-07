<script setup lang="ts">
import CollapsibleCard from '@/components/Doctor/ConsultationDetail/CollapsibleCard.vue';
import { Pill } from 'lucide-vue-next';

defineProps<{
    collapsed: boolean;
    prescriptions: Array<Record<string, unknown>>;
}>();

const emit = defineEmits<{ toggle: [] }>();

function getMedicationNames(medications: unknown): string {
    const meds = medications as Array<Record<string, string>> | undefined;
    return meds?.map((m) => m.name).join(', ') ?? '';
}
</script>

<template>
    <CollapsibleCard id="prescription-card" :icon="Pill" :collapsed="collapsed" @toggle="emit('toggle')">
        <template #title>Prescrição</template>
        <template #header-extra>
            <span class="text-xs text-gray-500">Ctrl+P</span>
        </template>

        <div v-if="prescriptions.length > 0" class="mb-4">
            <h4 class="mb-2 text-sm font-semibold">Prescrições Registradas</h4>
            <div class="space-y-2">
                <div v-for="prescription in prescriptions" :key="String(prescription.id)" class="rounded border bg-gray-50 p-3">
                    <div class="text-sm">
                        <p class="font-medium">
                            {{ getMedicationNames(prescription.medications) }}
                        </p>
                        <p v-if="prescription.instructions" class="mt-1 text-gray-600">{{ prescription.instructions }}</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="text-sm text-gray-500">Use o botão "Registrar Prescrição" no prontuário completo para adicionar prescrições.</div>
    </CollapsibleCard>
</template>
