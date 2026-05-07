<script setup lang="ts">
import EmptyBlock from '@/components/Patient/MedicalRecord/EmptyBlock.vue';
import { useFormatters } from '@/composables/useFormatters';
import type { ClinicalNote } from '@/types/medical-records';

defineProps<{
    clinicalNotes: ClinicalNote[];
    emptyText: string;
}>();

const { formatDate } = useFormatters();
</script>

<template>
    <div class="grid gap-4 md:grid-cols-2">
        <article v-for="note in clinicalNotes" :key="note.id" class="rounded-lg border border-[#dde5ea] bg-white p-4">
            <h2 class="font-black text-gray-950">{{ note.title }}</h2>
            <p class="mt-1 text-sm font-semibold text-gray-500">{{ note.doctor.name }} · {{ formatDate(note.created_at) }}</p>
            <p class="mt-3 text-sm font-medium whitespace-pre-wrap text-gray-600">{{ note.content }}</p>
            <div v-if="note.tags?.length" class="mt-3 flex flex-wrap gap-2">
                <span v-for="tag in note.tags" :key="tag" class="rounded-full bg-[#e5f1f2] px-2 py-1 text-xs font-black text-[#0f6e78]">{{
                    tag
                }}</span>
            </div>
        </article>
        <EmptyBlock v-if="clinicalNotes.length === 0" :text="emptyText" />
    </div>
</template>
