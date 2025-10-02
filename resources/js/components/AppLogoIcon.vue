<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

interface Props {
    className?: HTMLAttributes['class'];
}

defineProps<Props>();

const logoUrl = `/storage/LogoTelemedicina.svg?v=${import.meta.env.VITE_APP_VERSION}`;
</script>

<template>
    <img 
        :src="logoUrl" 
        alt="Logo Telemedicina" 
        width="200" 
        height="100"
        :class="className" 
        v-bind="$attrs"
        loading="eager"
    />
</template>