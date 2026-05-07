<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ChevronDown, ChevronUp } from 'lucide-vue-next';
import type { Component } from 'vue';

defineProps<{
    id?: string;
    icon: Component;
    collapsed: boolean;
}>();

const emit = defineEmits<{ toggle: [] }>();
</script>

<template>
    <Card :id="id">
        <CardHeader class="flex cursor-pointer flex-row items-center justify-between" @click="emit('toggle')">
            <CardTitle class="flex items-center gap-2">
                <component :is="icon" class="h-5 w-5" />
                <slot name="title" />
            </CardTitle>
            <div class="flex items-center gap-2">
                <slot name="header-extra" />
                <component :is="collapsed ? ChevronDown : ChevronUp" class="h-4 w-4" />
            </div>
        </CardHeader>
        <CardContent v-if="!collapsed">
            <slot />
        </CardContent>
    </Card>
</template>
