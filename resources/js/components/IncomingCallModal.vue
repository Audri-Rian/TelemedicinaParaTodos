<script setup lang="ts">
import { Phone, PhoneOff, Video } from 'lucide-vue-next';

interface Props {
    callerName: string;
    isAccepting?: boolean;
}

withDefaults(defineProps<Props>(), {
    isAccepting: false,
});

const emit = defineEmits<{
    accept: [];
    reject: [];
}>();
</script>

<template>
    <div
        role="dialog"
        aria-modal="true"
        aria-labelledby="incoming-call-title"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm"
    >
        <div class="w-full max-w-sm rounded-2xl bg-white p-6 shadow-2xl">
            <div class="mb-5 flex flex-col items-center text-center">
                <div class="mb-3 grid h-16 w-16 place-items-center rounded-full bg-teal-100">
                    <Video class="h-8 w-8 text-teal-600" />
                </div>
                <p class="text-sm font-semibold text-gray-500">Chamada de vídeo recebida</p>
                <h2 id="incoming-call-title" class="mt-1 text-xl font-black text-gray-950">
                    {{ callerName }}
                </h2>
            </div>

            <div class="flex gap-3">
                <button
                    type="button"
                    class="flex flex-1 items-center justify-center gap-2 rounded-xl bg-red-100 py-3 text-sm font-black text-red-700 transition hover:bg-red-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-400 disabled:opacity-60"
                    :disabled="isAccepting"
                    @click="emit('reject')"
                >
                    <PhoneOff class="h-4 w-4" />
                    Recusar
                </button>
                <button
                    type="button"
                    class="flex flex-1 items-center justify-center gap-2 rounded-xl bg-teal-500 py-3 text-sm font-black text-white transition hover:bg-teal-400 focus:outline-none focus-visible:ring-2 focus-visible:ring-teal-400 disabled:opacity-60"
                    :disabled="isAccepting"
                    @click="emit('accept')"
                >
                    <Phone class="h-4 w-4" />
                    {{ isAccepting ? 'Entrando...' : 'Aceitar' }}
                </button>
            </div>
        </div>
    </div>
</template>
