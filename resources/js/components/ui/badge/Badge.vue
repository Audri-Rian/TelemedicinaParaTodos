<script setup lang="ts">
import { computed } from 'vue';
import { cn } from '@/lib/utils';

interface Props {
    variant?: 'default' | 'secondary' | 'destructive' | 'outline';
    class?: string;
}

const props = withDefaults(defineProps<Props>(), {
    variant: 'default',
});

const variantClasses = {
    default: 'border-transparent bg-primary text-gray-900 hover:bg-primary/80',
    secondary: 'border-transparent bg-gray-100 text-gray-900 hover:bg-gray-200',
    destructive: 'border-transparent bg-red-500 text-white hover:bg-red-600',
    outline: 'border-gray-300 text-gray-900 bg-transparent',
};

const badgeClasses = computed(() => 
    cn(
        'inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-2',
        variantClasses[props.variant],
        props.class
    )
);
</script>

<template>
    <div :class="badgeClasses">
        <slot />
    </div>
</template>

