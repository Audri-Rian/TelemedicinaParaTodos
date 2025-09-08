<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { home } from '@/routes';
import { Link } from '@inertiajs/vue3';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div class="min-h-svh bg-background">
        <!-- Header com logo e nome no canto superior esquerdo -->
        <div class="absolute left-4 md:left-8 z-10">
            <Link :href="home()"
                class="group flex items-center gap-2 md:gap-4 font-medium transition-all duration-300 hover:scale-105">
            <div
                class="flex h-8 w-8 md:h-12 md:w-12 items-center justify-center rounded-lg md:rounded-xl bg-gradient-to-br from-primary/20 to-primary/10 shadow-lg backdrop-blur-sm border border-white/20 group-hover:shadow-xl group-hover:from-primary/30 group-hover:to-primary/20 transition-all duration-300">
                <AppLogoIcon
                    class="size-5 md:size-8 fill-current text-primary group-hover:text-primary/90 transition-colors duration-300" />
            </div>
            <div class="flex flex-col">
                <span
                    class="text-sm md:text-xl font-bold text-gray-800 dark:text-white group-hover:text-primary transition-colors duration-300">Telemedicina
                    para Todos</span>
                <span class="text-xs text-gray-500 dark:text-gray-400 font-medium hidden md:block">Cuidando da sua
                    saúde</span>
            </div>
            </Link>
        </div>

        <!-- Conteúdo principal centralizado -->
        <div class="flex min-h-svh flex-col items-center justify-center gap-6 p-6 md:p-10 mt-4">
            <div>
                <div class="flex flex-col gap-8">
                    <div class="space-y-2 text-center">
                        <h1 class="text-2xl md:text-3xl lg:text-4xl font-bold">{{ title }}</h1>
                        <p class="text-center text-sm md:text-base lg:text-lg text-muted-foreground">{{ description }}
                        </p>
                    </div>
                    <slot />
                </div>
            </div>
        </div>
    </div>
</template>
