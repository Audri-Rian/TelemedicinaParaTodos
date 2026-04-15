<script setup lang="ts">
import { Clock } from 'lucide-vue-next';

interface Props {
    label?: string;
}

withDefaults(defineProps<Props>(), {
    label: 'Em breve',
});
</script>

<template>
    <div class="relative">
        <!-- Conteúdo original (opaco) -->
        <div class="pointer-events-none select-none opacity-30 blur-[1px]" aria-hidden="true">
            <slot />
        </div>

        <!-- Overlay centralizado -->
        <div class="absolute inset-0 flex items-center justify-center">
            <div class="flex items-center gap-2.5 rounded-full border border-border/60 bg-background/90 px-5 py-2.5 shadow-sm backdrop-blur-sm">
                <Clock class="size-4 text-muted-foreground" stroke-width="2" />
                <span class="text-sm font-semibold tracking-wide text-muted-foreground">{{ label }}</span>
            </div>
        </div>
    </div>
</template>
