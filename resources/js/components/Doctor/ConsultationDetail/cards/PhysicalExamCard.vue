<script setup lang="ts">
import CollapsibleCard from '@/components/Doctor/ConsultationDetail/CollapsibleCard.vue';
import { Textarea } from '@/components/ui/textarea';
import { Stethoscope } from 'lucide-vue-next';

const model = defineModel<string>({ required: true });

defineProps<{ collapsed: boolean }>();

const emit = defineEmits<{ toggle: []; change: [] }>();
</script>

<template>
    <CollapsibleCard id="physical-exam-card" :icon="Stethoscope" :collapsed="collapsed" @toggle="emit('toggle')">
        <template #title>Exame Físico</template>
        <Textarea v-model="model" placeholder="Descreva o exame físico realizado..." class="min-h-[150px] resize-y" @input="emit('change')" />
        <p class="mt-2 text-xs text-gray-500">{{ model.length }}/5000 caracteres</p>
    </CollapsibleCard>
</template>
