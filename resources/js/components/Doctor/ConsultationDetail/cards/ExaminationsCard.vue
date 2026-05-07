<script setup lang="ts">
import CollapsibleCard from '@/components/Doctor/ConsultationDetail/CollapsibleCard.vue';
import { Badge } from '@/components/ui/badge';
import { TestTube } from 'lucide-vue-next';

defineProps<{
    collapsed: boolean;
    examinations: Array<Record<string, unknown>>;
}>();

const emit = defineEmits<{ toggle: [] }>();

function getPartnerName(partner: unknown): string {
    return (partner as Record<string, string>)?.name ?? '';
}
</script>

<template>
    <CollapsibleCard id="examinations-card" :icon="TestTube" :collapsed="collapsed" @toggle="emit('toggle')">
        <template #title>Exames Solicitados</template>
        <template #header-extra>
            <span class="text-xs text-gray-500">Ctrl+X</span>
        </template>

        <div v-if="examinations.length > 0" class="mb-4">
            <h4 class="mb-2 text-sm font-semibold">Exames Registrados</h4>
            <div class="space-y-2">
                <div v-for="exam in examinations" :key="String(exam.id)" class="rounded border bg-gray-50 p-3">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="font-medium">{{ exam.name }}</p>
                            <div class="mt-0.5 flex items-center gap-2">
                                <p class="text-sm text-gray-600">{{ exam.type }}</p>
                                <template v-if="exam.source === 'integration'">
                                    <span
                                        v-if="getPartnerName(exam.partner)"
                                        class="inline-flex items-center gap-1 rounded-full border border-blue-200 bg-blue-50 px-2 py-0.5 text-[10px] font-semibold text-blue-700"
                                    >
                                        Recebido do {{ getPartnerName(exam.partner) }}
                                    </span>
                                    <span
                                        v-if="exam.status !== 'completed'"
                                        class="inline-flex items-center gap-1 rounded-full border border-amber-200 bg-amber-50 px-2 py-0.5 text-[10px] font-semibold text-amber-700"
                                    >
                                        Aguardando resultado
                                    </span>
                                </template>
                            </div>
                        </div>
                        <Badge>{{ exam.status }}</Badge>
                    </div>
                </div>
            </div>
        </div>
        <div class="text-sm text-gray-500">Use o botão "Solicitar Exame" no prontuário completo para adicionar exames.</div>
    </CollapsibleCard>
</template>
