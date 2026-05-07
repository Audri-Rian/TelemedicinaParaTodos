<script setup lang="ts">
import { CheckCircle2 } from 'lucide-vue-next';

defineProps<{ show: boolean }>();
</script>

<template>
    <Transition
        enter-active-class="transition-all duration-300 ease-out"
        enter-from-class="translate-y-[-100%] opacity-0"
        enter-to-class="translate-y-0 opacity-100"
        leave-active-class="transition-all duration-300 ease-in"
        leave-from-class="translate-y-0 opacity-100"
        leave-to-class="translate-y-[-100%] opacity-0"
    >
        <div v-if="show" class="fixed top-4 right-4 z-50 flex items-center gap-2 rounded-lg bg-green-500 px-4 py-3 text-white shadow-lg">
            <CheckCircle2 class="h-5 w-5" />
            <span class="font-medium">Rascunho salvo com sucesso!</span>
        </div>
    </Transition>
</template>
