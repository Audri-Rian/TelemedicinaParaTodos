<script setup lang="ts">
import CollapsibleCard from '@/components/Doctor/ConsultationDetail/CollapsibleCard.vue';
import { Textarea } from '@/components/ui/textarea';
import { Stethoscope } from 'lucide-vue-next';

const model = defineModel<string>({ required: true });

defineProps<{ collapsed: boolean }>();

const emit = defineEmits<{ toggle: []; change: [] }>();
</script>

<template>
    <CollapsibleCard id="chief-complaint-card" :icon="Stethoscope" :collapsed="collapsed" @toggle="emit('toggle')">
        <template #title>Queixa Principal</template>
        <Textarea v-model="model" placeholder="Descreva a queixa principal do paciente..." class="min-h-[100px] resize-y" @input="emit('change')" />
        <p class="mt-2 text-xs text-gray-500">{{ model.length }}/1000 caracteres</p>
    </CollapsibleCard>
</template>
