<script setup lang="ts">
import { ref, watch, computed } from 'vue';
import axios from 'axios';
import { router, usePage } from '@inertiajs/vue3';
import { Dialog, DialogContent, DialogTitle, DialogDescription } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Calendar, Pen, Users, FileText } from 'lucide-vue-next';
import { useAuth } from '@/composables/auth/useAuth';

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
    'close': [];
}>();

const isOpen = ref(props.show);
const isLoading = ref(false);
const { isDoctor } = useAuth();

// Determinar a rota base baseado no tipo de usuário
const onboardingBaseRoute = computed(() => {
    return isDoctor.value ? '/doctor' : '/patient';
});

watch(() => props.show, (newValue) => {
    isOpen.value = newValue;
});

const startTour = async () => {
    isLoading.value = true;
    // Marcar welcome screen como visto antes de iniciar o tour
    try {
        await axios.post(`${onboardingBaseRoute.value}/onboarding/skip-welcome`, {
            action: 'tour',
        }, {
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
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
        }, {
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
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
        <DialogContent class="sm:max-w-lg p-0 overflow-hidden bg-gray-50">
            <!-- Título e Descrição ocultos para acessibilidade -->
            <DialogTitle class="sr-only">
                Bem-vindo à Telemedicina Para Todos!
            </DialogTitle>
            <DialogDescription class="sr-only">
                Estamos felizes em ter você aqui. Explore a plataforma com nosso tour guiado para conhecer os principais recursos.
            </DialogDescription>
            
            <!-- Background decorativo -->
            <div class="relative bg-white rounded-t-lg">
                <!-- Formas abstratas no fundo -->
                <div class="absolute top-0 left-0 w-32 h-32 bg-primary/10 rounded-full blur-3xl -translate-x-1/2 -translate-y-1/2" />
                <div class="absolute top-4 right-4 w-16 h-16 bg-blue-500/10 rounded-full blur-2xl" />
                
                <!-- Conteúdo principal -->
                <div class="relative px-6 pt-8 pb-6">
                    <!-- Container para foto (placeholder) -->
                    <div class="w-full h-48 bg-gray-100 rounded-lg mb-6 flex items-center justify-center">
                        <!-- Placeholder para foto futura -->
                        <div class="text-gray-400 text-sm">Foto aqui</div>
                    </div>
                    
                    <!-- Título -->
                    <h2 class="text-3xl font-bold text-gray-900 mb-4 text-left">
                        Bem-vindo à Telemedicina Para Todos!
                    </h2>
                    
                    <!-- Descrição -->
                    <p class="text-base text-gray-600 text-left mb-8">
                        Estamos felizes em ter você aqui. Explore a plataforma com nosso tour guiado para conhecer os principais recursos.
                    </p>
                    
                    <!-- Lista de recursos -->
                    <div class="space-y-4 mb-8">
                        <!-- Agendamento Fácil -->
                        <div class="flex items-start gap-4">
                            <div class="flex-shrink-0 w-12 h-12 bg-primary/10 rounded-lg flex items-center justify-center">
                                <div class="relative">
                                    <Calendar class="w-6 h-6 text-primary" />
                                    <Pen class="w-3 h-3 text-primary absolute -bottom-1 -right-1" />
                                </div>
                            </div>
                            <div class="flex-1">
                                <h3 class="font-semibold text-gray-900 mb-1">Agendamento Fácil</h3>
                                <p class="text-sm text-gray-600">Marque suas consultas de forma rápida e intuitiva.</p>
                            </div>
                        </div>
                        
                        <!-- Médicos Disponíveis -->
                        <div class="flex items-start gap-4">
                            <div class="flex-shrink-0 w-12 h-12 bg-primary/10 rounded-lg flex items-center justify-center">
                                <Users class="w-6 h-6 text-primary" />
                            </div>
                            <div class="flex-1">
                                <h3 class="font-semibold text-gray-900 mb-1">Médicos Disponíveis</h3>
                                <p class="text-sm text-gray-600">Acesso uma rede de profissionais qualificados.</p>
                            </div>
                        </div>
                        
                        <!-- Histórico e Receitas -->
                        <div class="flex items-start gap-4">
                            <div class="flex-shrink-0 w-12 h-12 bg-primary/10 rounded-lg flex items-center justify-center">
                                <FileText class="w-6 h-6 text-primary" />
                            </div>
                            <div class="flex-1">
                                <h3 class="font-semibold text-gray-900 mb-1">Histórico e Receitas</h3>
                                <p class="text-sm text-gray-600">Acesse seu histórico e receitas em um só lugar.</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Botões de ação -->
                    <div class="flex flex-col gap-3">
                        <Button
                            @click="startTour"
                            variant="default"
                            class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3"
                            :disabled="isLoading"
                        >
                            Fazer o Tour
                        </Button>
                        <Button
                            @click="exploreFreely"
                            variant="outline"
                            class="w-full bg-white border-2 border-gray-300 text-gray-700 font-semibold py-3 hover:bg-gray-50"
                            :disabled="isLoading"
                        >
                            Explorar por Conta
                        </Button>
                    </div>
                    
                    <!-- Texto do rodapé -->
                    <p class="text-xs text-gray-500 text-center mt-6">
                        Você pode acessar o tour a qualquer momento no menu de ajuda.
                    </p>
                </div>
            </div>
        </DialogContent>
    </Dialog>
</template>

<style scoped>
/* Garantir que o modal seja mobile-first */
@media (max-width: 640px) {
    :deep([data-slot="dialog-content"]) {
        max-width: calc(100% - 2rem);
        margin: 1rem;
    }
}
</style>
