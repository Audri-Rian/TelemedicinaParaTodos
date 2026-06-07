<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogTitle } from '@/components/ui/dialog';
import { useAuth } from '@/composables/auth/useAuth';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import { Calendar, FileText, Pen, Users } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

interface Props {
    show?: boolean;
    userName?: string;
}

const props = withDefaults(defineProps<Props>(), {
    show: false,
    userName: '',
});

const emit = defineEmits<{
    'start-tour': [];
    close: [];
}>();

const isOpen = ref(props.show);
const isLoading = ref(false);
const { isDoctor } = useAuth();

// Determinar a rota base baseado no tipo de usuário
const onboardingBaseRoute = computed(() => {
    return isDoctor.value ? '/doctor' : '/patient';
});

watch(
    () => props.show,
    (newValue) => {
        isOpen.value = newValue;
    },
);

const startTour = async () => {
    isLoading.value = true;
    // Marcar welcome screen como visto antes de iniciar o tour
    try {
        await axios.post(`${onboardingBaseRoute.value}/onboarding/skip-welcome`, {
            action: 'tour',
        });
        isOpen.value = false;
        emit('start-tour');
        // Recarregar apenas os dados de onboarding após fechar
        router.reload({ only: ['onboarding'], preserveScroll: true });
    } catch (error) {
        console.error('Erro ao marcar welcome screen como visto:', error);
        isLoading.value = false;
    }
};

const exploreFreely = async () => {
    isLoading.value = true;
    try {
        await axios.post(`${onboardingBaseRoute.value}/onboarding/skip-welcome`, {
            action: 'explore',
        });
        isOpen.value = false;
        emit('close');
        // Recarregar apenas os dados de onboarding após fechar
        router.reload({ only: ['onboarding'], preserveScroll: true });
    } catch (error) {
        console.error('Erro ao pular welcome screen:', error);
    } finally {
        isLoading.value = false;
    }
};

const handleOpenChange = (open: boolean) => {
    if (!open && !isLoading.value) {
        exploreFreely();
    }
};
</script>

<template>
    <Dialog :open="isOpen" @update:open="handleOpenChange">
        <DialogContent class="overflow-hidden bg-gray-50 p-0 sm:max-w-lg">
            <!-- Título e Descrição ocultos para acessibilidade -->
            <DialogTitle class="sr-only"> Bem-vindo à Telemedicina Para Todos! </DialogTitle>
            <DialogDescription class="sr-only">
                Estamos felizes em ter você aqui. Explore a plataforma com nosso tour guiado para conhecer os principais recursos.
            </DialogDescription>

            <!-- Background decorativo -->
            <div class="relative rounded-t-lg bg-white">
                <!-- Formas abstratas no fundo -->
                <div class="absolute top-0 left-0 h-32 w-32 -translate-x-1/2 -translate-y-1/2 rounded-full bg-primary/10 blur-3xl" />
                <div class="absolute top-4 right-4 h-16 w-16 rounded-full bg-blue-500/10 blur-2xl" />

                <!-- Conteúdo principal -->
                <div class="relative px-6 pt-8 pb-6">
                    <!-- Container para foto (placeholder) -->
                    <div class="mb-6 h-48 w-full overflow-hidden rounded-lg bg-gray-100">
                        <img src="/images/GuideGemini.png" alt="Bem-vindo à Telemedicina" class="h-full w-full object-cover" />
                    </div>

                    <!-- Título -->
                    <h2 class="mb-4 text-left text-3xl font-bold text-gray-900">Bem-vindo à Telemedicina Para Todos!</h2>

                    <!-- Descrição -->
                    <p class="mb-8 text-left text-base text-gray-600">
                        Estamos felizes em ter você aqui. Explore a plataforma com nosso tour guiado para conhecer os principais recursos.
                    </p>

                    <!-- Lista de recursos -->
                    <div class="mb-8 space-y-4">
                        <!-- Agendamento Fácil -->
                        <div class="flex items-start gap-4">
                            <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-lg bg-primary/10">
                                <div class="relative">
                                    <Calendar class="h-6 w-6 text-primary" />
                                    <Pen class="absolute -right-1 -bottom-1 h-3 w-3 text-primary" />
                                </div>
                            </div>
                            <div class="flex-1">
                                <h3 class="mb-1 font-semibold text-gray-900">Agendamento Fácil</h3>
                                <p class="text-sm text-gray-600">Marque suas consultas de forma rápida e intuitiva.</p>
                            </div>
                        </div>

                        <!-- Médicos Disponíveis -->
                        <div class="flex items-start gap-4">
                            <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-lg bg-primary/10">
                                <Users class="h-6 w-6 text-primary" />
                            </div>
                            <div class="flex-1">
                                <h3 class="mb-1 font-semibold text-gray-900">Médicos Disponíveis</h3>
                                <p class="text-sm text-gray-600">Acesso uma rede de profissionais qualificados.</p>
                            </div>
                        </div>

                        <!-- Histórico e Receitas -->
                        <div class="flex items-start gap-4">
                            <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-lg bg-primary/10">
                                <FileText class="h-6 w-6 text-primary" />
                            </div>
                            <div class="flex-1">
                                <h3 class="mb-1 font-semibold text-gray-900">Histórico e Receitas</h3>
                                <p class="text-sm text-gray-600">Acesse seu histórico e receitas em um só lugar.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Botões de ação -->
                    <div class="flex flex-col gap-3">
                        <Button
                            @click="startTour"
                            variant="default"
                            class="w-full bg-blue-600 py-3 font-semibold text-white hover:bg-blue-700"
                            :disabled="isLoading"
                        >
                            Fazer o Tour
                        </Button>
                        <Button
                            @click="exploreFreely"
                            variant="outline"
                            class="w-full border-2 border-gray-300 bg-white py-3 font-semibold text-gray-700 hover:bg-gray-50"
                            :disabled="isLoading"
                        >
                            Explorar por Conta
                        </Button>
                    </div>

                    <!-- Texto do rodapé -->
                    <p class="mt-6 text-center text-xs text-gray-500">Você pode acessar o tour a qualquer momento no menu de ajuda.</p>
                </div>
            </div>
        </DialogContent>
    </Dialog>
</template>

<style scoped>
/* Garantir que o modal seja mobile-first */
@media (max-width: 640px) {
    :deep([data-slot='dialog-content']) {
        max-width: calc(100% - 2rem);
        margin: 1rem;
    }
}
</style>
