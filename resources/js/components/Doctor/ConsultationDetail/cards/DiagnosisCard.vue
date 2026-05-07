<script setup lang="ts">
import CID10Autocomplete from '@/components/CID10Autocomplete.vue';
import CollapsibleCard from '@/components/Doctor/ConsultationDetail/CollapsibleCard.vue';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { FileText } from 'lucide-vue-next';

const diagnosisModel = defineModel<string>('diagnosis', { required: true });
const cid10Model = defineModel<string>('cid10', { required: true });

defineProps<{ collapsed: boolean }>();

const emit = defineEmits<{ toggle: []; change: [] }>();
</script>

<template>
    <CollapsibleCard id="diagnosis-card" :icon="FileText" :collapsed="collapsed" @toggle="emit('toggle')">
        <template #title>
            Diagnóstico
            <Badge v-if="cid10Model" variant="outline">{{ cid10Model }}</Badge>
        </template>
        <template #header-extra>
            <span class="text-xs text-gray-500">Ctrl+D</span>
        </template>

        <div class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700"> CID-10 <span class="text-xs text-gray-400">(opcional)</span> </label>
                    <CID10Autocomplete v-model="cid10Model" placeholder="Digite o código CID-10 (ex: J00)" @select="emit('change')" />
                    <p class="mt-1 text-xs text-gray-500">Código da Classificação Internacional de Doenças</p>
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700">Descrição do Diagnóstico</label>
                    <Input v-model="diagnosisModel" placeholder="Descrição do diagnóstico" maxlength="500" @input="emit('change')" />
                    <p class="mt-1 text-xs text-gray-500">{{ diagnosisModel.length }}/500 caracteres</p>
                </div>
            </div>
        </div>
    </CollapsibleCard>
</template>
