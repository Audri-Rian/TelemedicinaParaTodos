<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, watch, nextTick } from 'vue';
import { useMediaQuery } from '@vueuse/core';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import { useAuth } from '@/composables/auth/useAuth';
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
    'complete': [];
    'close': [];
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

const tourSteps: TourStepConfig[] = props.steps.length > 0 
    ? props.steps 
    : [
        {
            id: 'agendar-consulta',
            title: 'Pronto para Começar?',
            description: 'Este é o botão principal para agendar sua primeira consulta médica online. Clique aqui para encontrar médicos disponíveis, escolher um horário e iniciar seu atendimento de saúde no conforto da sua casa. É rápido, seguro e você pode fazer isso agora mesmo!',
            target: '[data-tour="agendar-consulta"]',
            position: 'bottom',
        },
        {
            id: 'medicos-disponiveis',
            title: 'Médicos à Sua Disposição',
            description: 'Aqui você vê os médicos que estão disponíveis para consulta agora mesmo. Cada card mostra o nome e a especialidade do profissional. Clique em qualquer médico para agendar uma consulta rapidamente. Esta área é atualizada em tempo real, então você sempre verá quem está online.',
            target: '[data-tour="medicos-disponiveis"]',
            position: 'bottom',
        },
        {
            id: 'proxima-consulta',
            title: 'Sua Próxima Consulta',
            description: 'Este card mostra os detalhes da sua próxima consulta agendada: médico, data, horário e especialidade. Quando chegar o momento, você poderá entrar na videochamada diretamente daqui. Se ainda não tem consultas agendadas, este espaço ficará disponível para quando você agendar.',
            target: '[data-tour="proxima-consulta"]',
            position: 'left',
        },
        {
            id: 'documentos-medicos',
            title: 'Seus Documentos Médicos',
            description: 'Estes três cards dão acesso rápido ao seu histórico médico completo: consultas passadas, receitas prescritas e resultados de exames. Tudo fica organizado e acessível aqui no seu dashboard. Você pode revisar qualquer informação médica a qualquer momento.',
            target: '[data-tour="documentos-medicos"]',
            position: 'top',
        },
        {
            id: 'encontrar-medico',
            title: 'Encontre o Médico Ideal',
            description: 'Use esta seção para buscar médicos por nome, especialidade ou convênio. Os filtros ajudam você a encontrar exatamente o profissional que precisa. Você pode rolar horizontalmente para ver mais opções e clicar no ícone de calendário para agendar diretamente.',
            target: '[data-tour="encontrar-medico"]',
            position: 'top',
        },
    ];

const currentStep = computed(() => tourSteps[currentStepIndex.value]);
const totalSteps = computed(() => tourSteps.length);
const isLastStep = computed(() => currentStepIndex.value === totalSteps.value - 1);
const canGoBack = computed(() => currentStepIndex.value > 0);
const progress = computed(() => ((currentStepIndex.value + 1) / totalSteps.value) * 100);

watch(() => props.show, (newValue) => {
    isActive.value = newValue;
    if (newValue) {
        currentStepIndex.value = 0;
        nextTick(() => {
            scrollToTarget();
        });
    }
});

const scrollToTarget = () => {
    nextTick(() => {
        const targetElement = document.querySelector(currentStep.value.target);
        if (targetElement) {
            targetElement.scrollIntoView({
                behavior: 'smooth',
                block: 'center',
            });
        }
    });
};

const nextStep = () => {
    if (isLastStep.value) {
        completeTour();
    } else {
        currentStepIndex.value++;
        scrollToTarget();
    }
};

const previousStep = () => {
    if (canGoBack.value) {
        currentStepIndex.value--;
        scrollToTarget();
    }
};

const completeTour = async () => {
    isLoading.value = true;
    try {
        await axios.post(`${onboardingBaseRoute.value}/tour/completed`, {}, {
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
        });
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
    if (isActive.value) {
        scrollToTarget();
    }
});

onUnmounted(() => {
    document.removeEventListener('keydown', handleKeydown);
});
</script>

<template>
    <Teleport to="body">
        <div
            v-if="isActive"
            role="dialog"
            aria-labelledby="tour-title"
            aria-describedby="tour-description"
            aria-modal="true"
            class="fixed inset-0 z-50"
            @keydown.esc="closeTour"
        >
            <!-- Overlay escuro com foco no elemento -->
            <div
                class="absolute inset-0 bg-black/60 backdrop-blur-sm transition-opacity"
                @click="closeTour"
                aria-hidden="true"
            />
            
            <!-- Tooltip do tour -->
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
            
            <!-- Região de anúncio para leitores de tela -->
            <div
                role="status"
                aria-live="polite"
                aria-atomic="true"
                class="sr-only"
            >
                Passo {{ currentStepIndex + 1 }} de {{ totalSteps }}: {{ currentStep.title }}. {{ currentStep.description }}
            </div>
        </div>
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

