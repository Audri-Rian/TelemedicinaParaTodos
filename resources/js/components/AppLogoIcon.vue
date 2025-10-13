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
        :class="[className, 'w-14 h-14 md:w-18 md:h-16']" 
        v-bind="$attrs"
        loading="eager"
    />
</template>