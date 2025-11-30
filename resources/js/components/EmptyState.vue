<script setup lang="ts">
import type { Component } from 'vue';
import { LucideIcon } from 'lucide-vue-next';
import { Link } from '@inertiajs/vue3';

interface Props {
    icon?: Component | LucideIcon;
    title: string;
    description: string;
    subDescription?: string;
    actionLabel?: string;
    actionHref?: string;
    actionIcon?: Component | LucideIcon;
    variant?: 'default' | 'subtle' | 'minimal';
}

const props = withDefaults(defineProps<Props>(), {
    variant: 'default',
});

const iconSize = props.variant === 'minimal' ? 'h-8 w-8' : props.variant === 'subtle' ? 'h-12 w-12' : 'h-16 w-16';
const iconColor = props.variant === 'minimal' ? 'text-gray-400' : props.variant === 'subtle' ? 'text-gray-300' : 'text-primary/30';
const iconBg = props.variant === 'minimal' ? '' : props.variant === 'subtle' ? 'bg-primary/5 rounded-full p-3' : 'bg-primary/10 rounded-full p-4';
</script>

<template>
    <div
        :class="[
            'flex flex-col items-center justify-center text-center',
            variant === 'default' && 'rounded-2xl border border-dashed border-gray-300 px-6 py-12',
            variant === 'subtle' && 'px-4 py-8',
            variant === 'minimal' && 'px-2 py-4',
        ]"
    >
        <!-- Ícone com background -->
        <div v-if="icon" :class="['mb-4', iconBg]">
            <component
                :is="icon"
                :class="[iconSize, iconColor]"
            />
        </div>
        
        <!-- Título principal -->
        <h3
            :class="[
                'font-semibold text-gray-900 mb-2',
                variant === 'minimal' ? 'text-sm' : variant === 'subtle' ? 'text-base' : 'text-lg',
            ]"
        >
            {{ title }}
        </h3>
        
        <!-- Descrição principal -->
        <p
            :class="[
                'text-gray-600 mb-2 max-w-md',
                variant === 'minimal' ? 'text-xs' : variant === 'subtle' ? 'text-sm' : 'text-sm',
            ]"
        >
            {{ description }}
        </p>
        
        <!-- Descrição secundária (tom convidativo) -->
        <p
            v-if="subDescription"
            :class="[
                'text-gray-500 mb-6 max-w-md',
                variant === 'minimal' ? 'text-xs' : 'text-sm',
            ]"
        >
            {{ subDescription }}
        </p>
        <div v-else-if="!subDescription && actionLabel" class="mb-6" />
        
        <!-- CTA - Botão de ação -->
        <Link
            v-if="actionLabel && actionHref"
            :href="actionHref"
            :class="[
                'inline-flex items-center justify-center rounded-xl font-semibold transition shadow-sm hover:shadow-md',
                variant === 'minimal'
                    ? 'bg-primary/20 hover:bg-primary/30 text-primary px-4 py-2 text-sm'
                    : 'bg-primary hover:bg-primary/90 text-gray-900 px-6 py-3 text-base',
            ]"
        >
            <component v-if="actionIcon" :is="actionIcon" class="mr-2 h-4 w-4" />
            {{ actionLabel }}
        </Link>
    </div>
</template>

