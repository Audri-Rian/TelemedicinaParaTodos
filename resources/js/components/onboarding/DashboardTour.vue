<script setup lang="ts">
import { useAuth } from '@/composables/auth/useAuth';
import { router } from '@inertiajs/vue3';
import { useMediaQuery } from '@vueuse/core';
import axios from 'axios';
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue';
import TourStep from './TourStep.vue';

interface TourStepConfig {
    id: string;
    title: string;
    description: string;
    target: string;
    position: 'top' | 'bottom' | 'left' | 'right';
}

interface Props {
    show?: boolean;
    steps?: TourStepConfig[];
}

const props = withDefaults(defineProps<Props>(), {
    show: false,
    steps: () => [],
});

const emit = defineEmits<{
    complete: [];
    close: [];
}>();

const isActive = ref(props.show);
const currentStepIndex = ref(0);
const isLoading = ref(false);
const { isDoctor } = useAuth();

const isMobile = useMediaQuery('(max-width: 640px)');

// Determinar a rota base baseado no tipo de usuário
const onboardingBaseRoute = computed(() => {
    return isDoctor.value ? '/doctor' : '/patient';
});

const tourSteps: TourStepConfig[] =
    props.steps.length > 0
        ? props.steps
        : [
              {
                  id: 'agendar-consulta',
                  title: 'Pronto para Começar?',
                  description:
                      'Este é o botão principal para agendar sua primeira consulta médica online. Clique aqui para encontrar médicos disponíveis, escolher um horário e iniciar seu atendimento de saúde no conforto da sua casa. É rápido, seguro e você pode fazer isso agora mesmo!',
                  target: '[data-tour="agendar-consulta"]',
                  position: 'bottom',
              },
              {
                  id: 'medicos-disponiveis',
                  title: 'Médicos à Sua Disposição',
                  description:
                      'Aqui você vê os médicos que estão disponíveis para consulta agora mesmo. Cada card mostra o nome e a especialidade do profissional. Clique em qualquer médico para agendar uma consulta rapidamente. Esta área é atualizada em tempo real, então você sempre verá quem está online.',
                  target: '[data-tour="medicos-disponiveis"]',
                  position: 'bottom',
              },
              {
                  id: 'proxima-consulta',
                  title: 'Sua Próxima Consulta',
                  description:
                      'Este card mostra os detalhes da sua próxima consulta agendada: médico, data, horário e especialidade. Quando chegar o momento, você poderá entrar na videochamada diretamente daqui. Se ainda não tem consultas agendadas, este espaço ficará disponível para quando você agendar.',
                  target: '[data-tour="proxima-consulta"]',
                  position: 'left',
              },
              {
                  id: 'documentos-medicos',
                  title: 'Seus Documentos Médicos',
                  description:
                      'Estes três cards dão acesso rápido ao seu histórico médico completo: consultas passadas, receitas prescritas e resultados de exames. Tudo fica organizado e acessível aqui no seu dashboard. Você pode revisar qualquer informação médica a qualquer momento.',
                  target: '[data-tour="documentos-medicos"]',
                  position: 'top',
              },
              {
                  id: 'encontrar-medico',
                  title: 'Encontre o Médico Ideal',
                  description:
                      'Use esta seção para buscar médicos por nome, especialidade ou convênio. Os filtros ajudam você a encontrar exatamente o profissional que precisa. Você pode rolar horizontalmente para ver mais opções e clicar no ícone de calendário para agendar diretamente.',
                  target: '[data-tour="encontrar-medico"]',
                  position: 'top',
              },
          ];

const currentStep = computed(() => tourSteps[currentStepIndex.value]);
const totalSteps = computed(() => tourSteps.length);
const isLastStep = computed(() => currentStepIndex.value === totalSteps.value - 1);
const canGoBack = computed(() => currentStepIndex.value > 0);
const progress = computed(() => ((currentStepIndex.value + 1) / totalSteps.value) * 100);

// Spotlight: clip-path com buraco no elemento alvo
const spotlightStyle = ref<Record<string, string>>({});

const updateSpotlight = () => {
    const target = document.querySelector(currentStep.value.target);
    if (!target) {
        spotlightStyle.value = {};
        return;
    }

    const rect = target.getBoundingClientRect();
    const pad = 6;
    const vw = window.innerWidth;
    const vh = window.innerHeight;

    const x1 = Math.max(0, rect.left - pad);
    const y1 = Math.max(0, rect.top - pad);
    const x2 = Math.min(vw, rect.right + pad);
    const y2 = Math.min(vh, rect.bottom + pad);

    // Retângulo exterior (horário) + retângulo interior (anti-horário) = buraco
    const path = `M 0 0 L ${vw} 0 L ${vw} ${vh} L 0 ${vh} Z ` + `M ${x1} ${y1} L ${x1} ${y2} L ${x2} ${y2} L ${x2} ${y1} Z`;

    spotlightStyle.value = { clipPath: `path('${path}')` };
};

watch(
    () => props.show,
    (newValue) => {
        isActive.value = newValue;
        if (newValue) {
            currentStepIndex.value = 0;
            nextTick(() => {
                updateSpotlight();
            });
        }
    },
);

watch(currentStepIndex, () => {
    // Aguarda scroll suave completar antes de recalcular o spotlight
    setTimeout(updateSpotlight, 350);
});

const nextStep = () => {
    if (isLastStep.value) {
        completeTour();
    } else {
        currentStepIndex.value++;
    }
};

const previousStep = () => {
    if (canGoBack.value) {
        currentStepIndex.value--;
    }
};

const completeTour = async () => {
    isLoading.value = true;
    try {
        await axios.post(`${onboardingBaseRoute.value}/tour/completed`, {});
        isActive.value = false;
        emit('complete');
        // Recarregar apenas os dados de onboarding após fechar
        router.reload({ only: ['onboarding'], preserveScroll: true });
    } catch (error) {
        console.error('Erro ao completar tour:', error);
    } finally {
        isLoading.value = false;
    }
};

const closeTour = () => {
    isActive.value = false;
    emit('close');
};

const handleKeydown = (e: KeyboardEvent) => {
    if (!isActive.value) return;

    if (e.key === 'Escape') {
        closeTour();
    } else if (e.key === 'ArrowRight' && !isLastStep.value) {
        nextStep();
    } else if (e.key === 'ArrowLeft' && canGoBack.value) {
        previousStep();
    }
};

onMounted(() => {
    document.addEventListener('keydown', handleKeydown);
    window.addEventListener('resize', updateSpotlight);
    window.addEventListener('scroll', updateSpotlight, { passive: true, capture: true });
    if (isActive.value) {
        nextTick(updateSpotlight);
    }
});

onUnmounted(() => {
    document.removeEventListener('keydown', handleKeydown);
    window.removeEventListener('resize', updateSpotlight);
    window.removeEventListener('scroll', updateSpotlight, true);
});
</script>

<template>
    <Teleport to="body">
        <template v-if="isActive">
            <!--
                Hierarquia de z-index:
                  z-40 → overlay escuro (fundo)
                  z-41 → elemento destacado (definido via inline style em TourStep.vue)
                  z-[52] → tooltip (definido em TourStep.vue)
                O container não tem z-index para não criar stacking context próprio.
            -->

            <!-- Overlay com buraco no elemento alvo (spotlight) -->
            <div
                class="fixed inset-0 z-40 bg-black/50 transition-[clip-path] duration-300"
                :style="spotlightStyle"
                @click="closeTour"
                aria-hidden="true"
            />

            <!-- Tooltip posicionado dinamicamente -->
            <TourStep
                :step="currentStep"
                :current-index="currentStepIndex"
                :total-steps="totalSteps"
                :progress="progress"
                :is-last-step="isLastStep"
                :can-go-back="canGoBack"
                :is-mobile="isMobile"
                :is-loading="isLoading"
                @next="nextStep"
                @previous="previousStep"
                @close="closeTour"
                @complete="completeTour"
            />

            <!-- Anúncio para leitores de tela -->
            <div role="status" aria-live="polite" aria-atomic="true" class="sr-only">
                Passo {{ currentStepIndex + 1 }} de {{ totalSteps }}: {{ currentStep.title }}. {{ currentStep.description }}
            </div>
        </template>
    </Teleport>
</template>

<style scoped>
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
