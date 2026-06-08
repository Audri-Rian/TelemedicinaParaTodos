<script setup lang="ts">
import type { VideoCallStatus } from '@/stores/videoCall';
import { PhoneOff, Video } from 'lucide-vue-next';

defineProps<{
    callStatus: VideoCallStatus;
    appointmentLabel?: string | null;
}>();

const emit = defineEmits<{
    enter: [];
    end: [];
}>();

const statusLabel: Record<string, string> = {
    requested: 'Aguardando',
    ringing: 'Chamando...',
    accepted: 'Em andamento',
};
</script>

<template>
    <Transition
        enter-active-class="transition ease-out duration-200"
        enter-from-class="opacity-0 translate-y-2"
        enter-to-class="opacity-100 translate-y-0"
        leave-active-class="transition ease-in duration-150"
        leave-from-class="opacity-100 translate-y-0"
        leave-to-class="opacity-0 translate-y-2"
    >
        <div
            v-show="true"
            class="fixed top-6 right-6 z-50 flex items-center gap-2 rounded-xl border border-teal-200 bg-white px-3 py-2 shadow-lg max-sm:top-4 max-sm:right-4 max-sm:left-4"
        >
            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-teal-100">
                <Video class="h-4 w-4 text-teal-600" />
            </div>

            <div class="flex min-w-0 flex-1 flex-col">
                <span class="text-xs leading-tight font-semibold text-teal-700">
                    {{ statusLabel[callStatus] ?? 'Videochamada' }}
                </span>
                <span v-if="appointmentLabel" class="truncate text-xs text-gray-500">
                    {{ appointmentLabel }}
                </span>
            </div>

            <button
                class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-teal-600 text-white transition-colors hover:bg-teal-700"
                title="Entrar na chamada"
                @click="emit('enter')"
            >
                <Video class="h-3.5 w-3.5" />
            </button>

            <button
                class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-red-100 text-red-600 transition-colors hover:bg-red-200"
                title="Encerrar chamada"
                @click="emit('end')"
            >
                <PhoneOff class="h-3.5 w-3.5" />
            </button>
        </div>
    </Transition>
</template>
