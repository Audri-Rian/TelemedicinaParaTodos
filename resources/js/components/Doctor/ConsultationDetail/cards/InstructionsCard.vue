<script setup lang="ts">
import CollapsibleCard from '@/components/Doctor/ConsultationDetail/CollapsibleCard.vue';
import { Textarea } from '@/components/ui/textarea';
import { FileText } from 'lucide-vue-next';

const model = defineModel<string>({ required: true });

defineProps<{ collapsed: boolean }>();

const emit = defineEmits<{ toggle: []; change: [] }>();
</script>

<template>
    <CollapsibleCard id="instructions-card" :icon="FileText" :collapsed="collapsed" @toggle="emit('toggle')">
        <template #title>Orientações</template>
        <Textarea v-model="model" placeholder="Orientações para o paciente..." class="min-h-[100px] resize-y" @input="emit('change')" />
        <p class="mt-2 text-xs text-gray-500">{{ model.length }}/2000 caracteres</p>
    </CollapsibleCard>
</template>
