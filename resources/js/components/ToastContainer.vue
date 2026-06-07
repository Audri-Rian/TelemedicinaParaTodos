<script setup lang="ts">
import { useToast, type ToastType } from '@/composables/useToast';
import { CheckCircle2, AlertCircle, AlertTriangle, Info, X } from 'lucide-vue-next';

const { toasts, dismiss } = useToast();

const iconFor = (type: ToastType) => {
    switch (type) {
        case 'success':
            return CheckCircle2;
        case 'error':
            return AlertCircle;
        case 'warning':
            return AlertTriangle;
        default:
            return Info;
    }
};

const palette: Record<ToastType, { container: string; icon: string; title: string; text: string; dismiss: string }> = {
    success: {
        container: 'border-emerald-200 bg-emerald-50',
        icon: 'text-emerald-600',
        title: 'text-emerald-900',
        text: 'text-emerald-800',
        dismiss: 'text-emerald-600 hover:bg-emerald-100',
    },
    error: {
        container: 'border-red-200 bg-red-50',
        icon: 'text-red-600',
        title: 'text-red-900',
        text: 'text-red-800',
        dismiss: 'text-red-600 hover:bg-red-100',
    },
    warning: {
        container: 'border-amber-200 bg-amber-50',
        icon: 'text-amber-600',
        title: 'text-amber-900',
        text: 'text-amber-800',
        dismiss: 'text-amber-600 hover:bg-amber-100',
    },
    info: {
        container: 'border-sky-200 bg-sky-50',
        icon: 'text-sky-600',
        title: 'text-sky-900',
        text: 'text-sky-800',
        dismiss: 'text-sky-600 hover:bg-sky-100',
    },
};
</script>

<template>
    <teleport to="body">
        <!--
            Sem aria-live/aria-atomic no container: cada toast (role="alert" + :aria-live)
            é a live region individual. Combinar os dois causa anúncios duplicados em
            screen readers.
        -->
        <div
            class="pointer-events-none fixed inset-x-0 top-4 z-[200] flex flex-col items-center gap-3 px-4 sm:inset-x-auto sm:right-4 sm:top-4 sm:items-end"
            data-testid="toast-container"
        >
            <TransitionGroup name="toast">
                <div
                    v-for="toast in toasts"
                    :key="toast.id"
                    role="alert"
                    :aria-live="toast.type === 'error' ? 'assertive' : 'polite'"
                    :class="[
                        'pointer-events-auto w-full max-w-sm overflow-hidden rounded-xl border px-4 py-3 shadow-lg backdrop-blur-sm',
                        palette[toast.type].container,
                    ]"
                    :data-testid="`toast-${toast.type}`"
                >
                    <div class="flex items-start gap-3">
                        <component
                            :is="iconFor(toast.type)"
                            :class="['mt-0.5 size-5 shrink-0', palette[toast.type].icon]"
                            aria-hidden="true"
                        />
                        <div class="flex-1 space-y-0.5">
                            <p v-if="toast.title" :class="['text-sm font-semibold leading-5', palette[toast.type].title]">
                                {{ toast.title }}
                            </p>
                            <p :class="['text-sm leading-5', palette[toast.type].text]">
                                {{ toast.message }}
                            </p>
                        </div>
                        <button
                            v-if="toast.dismissible"
                            type="button"
                            :class="['shrink-0 rounded-md p-1 transition-colors', palette[toast.type].dismiss]"
                            :aria-label="`Fechar notificação: ${toast.message}`"
                            @click="dismiss(toast.id)"
                        >
                            <X class="size-4" aria-hidden="true" />
                        </button>
                    </div>
                </div>
            </TransitionGroup>
        </div>
    </teleport>
</template>

<style scoped>
.toast-enter-active,
.toast-leave-active {
    transition:
        opacity 250ms ease,
        transform 250ms ease;
}

.toast-enter-from {
    opacity: 0;
    transform: translateY(-8px);
}

.toast-leave-to {
    opacity: 0;
    transform: translateX(16px);
}

.toast-move {
    transition: transform 250ms ease;
}
</style>
