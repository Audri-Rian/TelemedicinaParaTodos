<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import logoImage from '@images/brand/Logo.png';

defineOptions({
    inheritAttrs: false,
});

interface Props {
    className?: HTMLAttributes['class'];
}

defineProps<Props>();
</script>

<template>
    <img 
        :src="logoImage" 
        alt="Logo Telemedicina" 
        width="200" 
        height="200"
        :class="className" 
        v-bind="$attrs"
        loading="eager"
    />
</template>