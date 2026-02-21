<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';

interface Props {
    logoSrc?: string;
    logoAlt?: string;
}

defineProps<Props>();
</script>

<template>
    <!-- Quando a sidebar está recolhida (group-data-[collapsible=icon]), só o logo aparece e encolhe para caber -->
    <div class="size-14 shrink-0 flex items-center justify-center overflow-hidden group-data-[collapsible=icon]:size-10">
        <img
            v-if="logoSrc"
            :src="logoSrc"
            :alt="logoAlt || 'Logo Telemedicina'"
            class="h-full w-full object-contain"
        />
        <AppLogoIcon v-else class="h-full w-full max-w-full max-h-full object-contain" />
    </div>
    <!-- Texto oculto quando a sidebar está recolhida para não mostrar "Tele" truncado -->
    <div class="text-left group-data-[collapsible=icon]:hidden">
        <span class="ml-1 text-xl font-semibold">Telemedicina</span>
        <p class="ml-1.5 text-gray-400 text-sm">Para todos</p>
    </div>
</template>
