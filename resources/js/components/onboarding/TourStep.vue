<script setup lang="ts">
import { computed, ref, onMounted, onUnmounted, nextTick } from 'vue';
import { Button } from '@/components/ui/button';
import { ChevronLeft, ChevronRight, X } from 'lucide-vue-next';

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

const emit = defineEmits<{
    'next': [];
    'previous': [];
    'close': [];
    'complete': [];
}>();

const tooltipRef = ref<HTMLElement | null>(null);
const tooltipStyle = ref<{ top?: string; left?: string; bottom?: string; right?: string }>({});

const position = computed(() => {
    if (props.isMobile) {
        return 'bottom';
    }
    return props.step.position;
});

const updateTooltipPosition = () => {
    nextTick(() => {
        const targetElement = document.querySelector(props.step.target);
        if (!targetElement || !tooltipRef.value) return;

        const targetRect = targetElement.getBoundingClientRect();
        const tooltipRect = tooltipRef.value.getBoundingClientRect();
        const viewportWidth = window.innerWidth;
        const viewportHeight = window.innerHeight;
        const spacing = 16;

        let top = '';
        let left = '';
        let bottom = '';
        let right = '';

        switch (position.value) {
            case 'top':
                top = `${targetRect.top - tooltipRect.height - spacing}px`;
                left = `${targetRect.left + (targetRect.width / 2) - (tooltipRect.width / 2)}px`;
                break;
            case 'bottom':
                bottom = `${viewportHeight - targetRect.bottom + spacing}px`;
                left = `${targetRect.left + (targetRect.width / 2) - (tooltipRect.width / 2)}px`;
                break;
            case 'left':
                top = `${targetRect.top + (targetRect.height / 2) - (tooltipRect.height / 2)}px`;
                right = `${viewportWidth - targetRect.left + spacing}px`;
                break;
            case 'right':
                top = `${targetRect.top + (targetRect.height / 2) - (tooltipRect.height / 2)}px`;
                left = `${targetRect.right + spacing}px`;
                break;
        }

        // Ajustar para não sair da viewport
        if (left && parseFloat(left) < spacing) {
            left = `${spacing}px`;
        }
        if (top && parseFloat(top) < spacing) {
            top = `${spacing}px`;
        }
        if (right && parseFloat(right) < spacing) {
            right = `${spacing}px`;
        }
        if (bottom && parseFloat(bottom) < spacing) {
            bottom = `${spacing}px`;
        }

        tooltipStyle.value = { top, left, bottom, right };
    });
};

const highlightTarget = () => {
    const targetElement = document.querySelector(props.step.target);
    if (targetElement) {
        targetElement.classList.add('tour-highlight');
    }
};

const removeHighlight = () => {
    const targetElement = document.querySelector(props.step.target);
    if (targetElement) {
        targetElement.classList.remove('tour-highlight');
    }
};

onMounted(() => {
    highlightTarget();
    updateTooltipPosition();
    window.addEventListener('resize', updateTooltipPosition);
    window.addEventListener('scroll', updateTooltipPosition, true);
});

onUnmounted(() => {
    removeHighlight();
    window.removeEventListener('resize', updateTooltipPosition);
    window.removeEventListener('scroll', updateTooltipPosition, true);
});
</script>

<template>
    <div
        ref="tooltipRef"
        :style="tooltipStyle"
        class="fixed z-50 w-full max-w-sm bg-white rounded-lg shadow-xl border border-gray-200 p-6 animate-in fade-in-0 zoom-in-95 duration-200"
        role="region"
        :aria-label="`Passo ${currentIndex + 1} de ${totalSteps}`"
    >
        <!-- Barra de progresso -->
        <div class="mb-4">
            <div class="flex items-center justify-between text-xs text-gray-600 mb-2">
                <span>Passo {{ currentIndex + 1 }} de {{ totalSteps }}</span>
                <span>{{ Math.round(progress) }}%</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2">
                <div
                    class="bg-primary h-2 rounded-full transition-all duration-300"
                    :style="{ width: `${progress}%` }"
                />
            </div>
        </div>

        <!-- Conteúdo -->
        <h3 id="tour-title" class="text-lg font-semibold text-gray-900 mb-2">
            {{ step.title }}
        </h3>
        <p id="tour-description" class="text-sm text-gray-600 mb-6">
            {{ step.description }}
        </p>

        <!-- Navegação -->
        <div class="flex items-center justify-between gap-3">
            <div class="flex items-center gap-2">
                <Button
                    v-if="canGoBack"
                    @click="$emit('previous')"
                    variant="outline"
                    size="sm"
                    aria-label="Passo anterior"
                    :disabled="isLoading"
                >
                    <ChevronLeft class="w-4 h-4 mr-1" />
                    Anterior
                </Button>
            </div>

            <div class="flex items-center gap-2">
                <Button
                    v-if="!isLastStep"
                    @click="$emit('next')"
                    variant="default"
                    size="sm"
                    aria-label="Próximo passo"
                    :disabled="isLoading"
                >
                    Próximo
                    <ChevronRight class="w-4 h-4 ml-1" />
                </Button>
                <Button
                    v-else
                    @click="$emit('complete')"
                    variant="default"
                    size="sm"
                    aria-label="Concluir tour"
                    :disabled="isLoading"
                >
                    {{ isLoading ? 'Salvando...' : 'Concluir' }}
                </Button>
            </div>

            <Button
                @click="$emit('close')"
                variant="ghost"
                size="sm"
                class="ml-auto"
                aria-label="Fechar tour"
                :disabled="isLoading"
            >
                <X class="w-4 h-4" />
                <span class="sr-only">Fechar</span>
            </Button>
        </div>
    </div>
</template>

<style scoped>
.tour-highlight {
    position: relative;
    z-index: 40;
    outline: 2px solid rgb(59 130 246);
    outline-offset: 2px;
    border-radius: 0.5rem;
}

.sr-only {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border-width: 0;
}

@media (prefers-reduced-motion: reduce) {
    * {
        animation-duration: 0.01ms !important;
        transition-duration: 0.01ms !important;
    }
}
</style>

