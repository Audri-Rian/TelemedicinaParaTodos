<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { ChevronLeft, ChevronRight, X } from 'lucide-vue-next';
import { computed, nextTick, onMounted, onUnmounted, ref } from 'vue';

interface TourStepConfig {
    id: string;
    title: string;
    description: string;
    target: string;
    position: 'top' | 'bottom' | 'left' | 'right';
}

interface Props {
    step: TourStepConfig;
    currentIndex: number;
    totalSteps: number;
    progress: number;
    isLastStep: boolean;
    canGoBack: boolean;
    isMobile: boolean;
    isLoading: boolean;
}

const props = defineProps<Props>();

defineEmits<{
    next: [];
    previous: [];
    close: [];
    complete: [];
}>();

const tooltipRef = ref<HTMLElement | null>(null);
const tooltipStyle = ref<Record<string, string>>({});

const position = computed(() => (props.isMobile ? 'bottom' : props.step.position));

// Eleva o elemento acima do overlay (z-40) para aparecer no "buraco" do spotlight
const applyHighlight = (el: Element) => {
    const htmlEl = el as HTMLElement;
    htmlEl.dataset.tourHighlighted = 'true';
    htmlEl.style.position = 'relative';
    htmlEl.style.zIndex = '41';
};

const removeHighlight = (el: Element) => {
    const htmlEl = el as HTMLElement;
    if (htmlEl.dataset.tourHighlighted !== 'true') return;
    delete htmlEl.dataset.tourHighlighted;
    htmlEl.style.position = '';
    htmlEl.style.zIndex = '';
};

const getTarget = () => document.querySelector(props.step.target);

const updateTooltipPosition = () => {
    nextTick(() => {
        const targetEl = getTarget();
        if (!targetEl || !tooltipRef.value) {
            // Fallback: centraliza na viewport
            tooltipStyle.value = {
                top: '50%',
                left: '50%',
                transform: 'translate(-50%, -50%)',
            };
            return;
        }

        const targetRect = targetEl.getBoundingClientRect();
        const tooltipRect = tooltipRef.value.getBoundingClientRect();
        const vw = window.innerWidth;
        const vh = window.innerHeight;
        const gap = 12;

        let top = 0;
        let left = 0;

        switch (position.value) {
            case 'right':
                top = targetRect.top + targetRect.height / 2 - tooltipRect.height / 2;
                left = targetRect.right + gap;
                break;
            case 'left':
                top = targetRect.top + targetRect.height / 2 - tooltipRect.height / 2;
                left = targetRect.left - tooltipRect.width - gap;
                break;
            case 'bottom':
                top = targetRect.bottom + gap;
                left = targetRect.left + targetRect.width / 2 - tooltipRect.width / 2;
                break;
            case 'top':
                top = targetRect.top - tooltipRect.height - gap;
                left = targetRect.left + targetRect.width / 2 - tooltipRect.width / 2;
                break;
        }

        // Clampa para não sair da viewport
        left = Math.max(gap, Math.min(left, vw - tooltipRect.width - gap));
        top = Math.max(gap, Math.min(top, vh - tooltipRect.height - gap));

        tooltipStyle.value = {
            top: `${top}px`,
            left: `${left}px`,
        };
    });
};

const highlightAndPosition = () => {
    const targetEl = getTarget();
    if (targetEl) {
        targetEl.scrollIntoView({ behavior: 'smooth', block: 'center', inline: 'nearest' });
        applyHighlight(targetEl);
        // Re-calcula após scroll completar (smooth ~300ms)
        setTimeout(updateTooltipPosition, 350);
    }
    updateTooltipPosition();
};

onMounted(() => {
    highlightAndPosition();
    window.addEventListener('resize', updateTooltipPosition);
    window.addEventListener('scroll', updateTooltipPosition, { passive: true, capture: true });
});

onUnmounted(() => {
    const targetEl = getTarget();
    if (targetEl) removeHighlight(targetEl);
    window.removeEventListener('resize', updateTooltipPosition);
    window.removeEventListener('scroll', updateTooltipPosition, true);
});
</script>

<template>
    <div
        ref="tooltipRef"
        :style="tooltipStyle"
        class="fixed z-[52] w-80 animate-in rounded-xl border border-gray-200 bg-white p-5 shadow-2xl duration-200 fade-in-0 zoom-in-95"
        role="region"
        :aria-label="`Passo ${currentIndex + 1} de ${totalSteps}`"
    >
        <!-- Barra de progresso -->
        <div class="mb-4">
            <div class="mb-1.5 flex items-center justify-between text-xs text-gray-500">
                <span class="font-medium">{{ currentIndex + 1 }} / {{ totalSteps }}</span>
                <span>{{ Math.round(progress) }}%</span>
            </div>
            <div class="h-1.5 w-full rounded-full bg-gray-100">
                <div class="h-1.5 rounded-full bg-primary transition-all duration-300" :style="{ width: `${progress}%` }" />
            </div>
        </div>

        <!-- Conteúdo -->
        <h3 class="mb-1.5 text-base font-semibold text-gray-900">
            {{ step.title }}
        </h3>
        <p class="mb-4 text-sm leading-relaxed text-gray-600">
            {{ step.description }}
        </p>

        <!-- Navegação -->
        <div class="flex items-center justify-between gap-2">
            <Button v-if="canGoBack" @click="$emit('previous')" variant="outline" size="sm" :disabled="isLoading">
                <ChevronLeft class="mr-1 h-3.5 w-3.5" />
                Anterior
            </Button>
            <div v-else />

            <div class="ml-auto flex items-center gap-2">
                <Button @click="$emit('close')" variant="ghost" size="sm" :disabled="isLoading" aria-label="Fechar tour">
                    <X class="h-3.5 w-3.5" />
                </Button>
                <Button v-if="!isLastStep" @click="$emit('next')" variant="default" size="sm" :disabled="isLoading">
                    Próximo
                    <ChevronRight class="ml-1 h-3.5 w-3.5" />
                </Button>
                <Button v-else @click="$emit('complete')" variant="default" size="sm" :disabled="isLoading">
                    {{ isLoading ? 'Salvando...' : 'Concluir' }}
                </Button>
            </div>
        </div>
    </div>
</template>

<style scoped>
@media (prefers-reduced-motion: reduce) {
    * {
        animation-duration: 0.01ms !important;
        transition-duration: 0.01ms !important;
    }
}
</style>
