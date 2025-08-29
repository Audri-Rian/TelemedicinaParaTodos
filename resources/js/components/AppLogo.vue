<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';

interface Props {
    logoSrc?: string;
    logoAlt?: string;
}

defineProps<Props>();
</script>

<template>
    <div class="size-12">
        <img 
            v-if="logoSrc" 
            :src="logoSrc" 
            :alt="logoAlt || 'Logo Telemedicina'" 
        />
        <AppLogoIcon v-else class="" />
    </div>
    <div class="text-left">
        <span class="ml-1 text-xl font-semibold">Telemedicina</span>
        <p class="ml-1.5 text-gray-400 text-xs">Para todos</p>
    </div>
</template>
