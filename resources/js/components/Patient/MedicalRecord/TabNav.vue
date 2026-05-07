<script setup lang="ts">
import type { TabId } from '@/types/medical-records';

defineProps<{
    tabs: Array<{ id: TabId; label: string; count: number }>;
    activeTab: TabId;
}>();

const emit = defineEmits<{
    change: [id: TabId];
}>();
</script>

<template>
    <div class="border-b border-[#dde5ea] px-3 pt-3">
        <nav class="flex gap-1 overflow-x-auto">
            <button
                v-for="tab in tabs"
                :key="tab.id"
                type="button"
                class="inline-flex items-center gap-2 rounded-t-lg border-b-2 px-3 py-3 text-sm font-black whitespace-nowrap transition"
                :class="
                    activeTab === tab.id
                        ? 'border-[#0f6e78] bg-[#e5f1f2] text-[#0f6e78]'
                        : 'border-transparent text-gray-500 hover:bg-[#f4f6f8] hover:text-gray-800'
                "
                @click="emit('change', tab.id)"
            >
                {{ tab.label }}
                <span class="rounded-full bg-white px-2 py-0.5 text-[11px] text-gray-500">{{ tab.count }}</span>
            </button>
        </nav>
    </div>
</template>
