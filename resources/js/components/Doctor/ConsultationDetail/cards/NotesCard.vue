<script setup lang="ts">
import CollapsibleCard from '@/components/Doctor/ConsultationDetail/CollapsibleCard.vue';
import { Textarea } from '@/components/ui/textarea';
import { ClipboardList } from 'lucide-vue-next';

const model = defineModel<string>({ required: true });

defineProps<{ collapsed: boolean }>();

const emit = defineEmits<{ toggle: []; change: [] }>();
</script>

<template>
    <CollapsibleCard id="notes-card" :icon="ClipboardList" :collapsed="collapsed" @toggle="emit('toggle')">
        <template #title>Anotações</template>
        <Textarea v-model="model" placeholder="Anotações adicionais sobre a consulta..." class="min-h-[100px] resize-y" @input="emit('change')" />
        <p class="mt-2 text-xs text-gray-500">{{ model.length }}/5000 caracteres</p>
    </CollapsibleCard>
</template>
