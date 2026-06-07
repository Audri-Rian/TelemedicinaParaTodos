<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { Button } from '@/components/ui/button';
import { useAuth, useRoleRoutes } from '@/composables/auth';
import { home, login } from '@/routes';
import badDoctorImage from '@images/baddoctor.png';
import { Link, usePage } from '@inertiajs/vue3';
import { ArrowLeft, Calendar, Heart, Home, LogIn, MessageCircle, Search } from 'lucide-vue-next';
import { computed } from 'vue';

const page = usePage();
const { isAuthenticated } = useAuth();
const { dashboardRoute } = useRoleRoutes();

// Verificar se está em modo de desenvolvimento
const isDev = import.meta.env.DEV;

// Informações do erro vindas do Laravel
const status = computed(() => (page.props as any).status || 500);
const message = computed(() => (page.props as any).message || 'Algo deu errado');

// Configurações por tipo de erro
const errorConfig = computed(() => {
    const configs: Record<
        number,
        {
            title: string;
            description: string;
            icon: string;
            color: string;
        }
    > = {
        404: {
            title: 'Ops! Página não encontrada 😢',
            description: 'Não conseguimos encontrar a página que você procurava. Ela pode ter sido movida ou removida.',
            icon: '🔍',
            color: 'text-blue-600',
        },
        401: {
            title: 'Autenticação necessária',
            description: 'Você precisa estar autenticado para acessar este recurso. Faça login para continuar.',
            icon: '🔐',
            color: 'text-amber-600',
        },
        403: {
            title: 'Acesso negado 🚫',
            description: 'Você não tem permissão para acessar esta página. Entre em contato com o suporte se acredita que isso é um erro.',
            icon: '🔒',
            color: 'text-orange-600',
        },
        419: {
            title: 'Sessão expirada ⏰',
            description: 'Sua sessão expirou por segurança. Por favor, faça login novamente para continuar.',
            icon: '⏰',
            color: 'text-yellow-600',
        },
        429: {
            title: 'Muitas tentativas 🐌',
            description: 'Você fez muitas tentativas muito rapidamente. Por favor, aguarde alguns instantes e tente novamente.',
            icon: '⏳',
            color: 'text-yellow-600',
        },
        500: {
            title: 'Ops! Algo deu errado 😢',
            description:
                'Encontramos um problema técnico. Nossa equipe foi notificada e está trabalhando para resolver. Tente novamente em alguns instantes.',
            icon: '⚠️',
            color: 'text-red-600',
        },
        503: {
            title: 'Serviço temporariamente indisponível 🔧',
            description: 'Estamos realizando manutenção para melhorar sua experiência. Voltaremos em breve!',
            icon: '🛠️',
            color: 'text-purple-600',
        },
    };

    return configs[status.value] || configs[500];
});

// Ações contextuais baseadas no tipo de usuário
const quickActions = computed(() => {
    const actions = [];

    if (status.value === 401 && !isAuthenticated.value) {
        actions.push({
            label: 'Fazer Login',
            href: login().url,
            icon: LogIn,
            primary: true,
        });
        actions.push({
            label: 'Voltar ao Início',
            href: home().url,
            icon: Home,
        });

        return actions;
    }

    if (isAuthenticated.value) {
        actions.push({
            label: 'Ir para o Dashboard',
            href: dashboardRoute().url,
            icon: Home,
            primary: true,
        });

        // Ações específicas para pacientes
        if ((page.props as any).auth?.isPatient) {
            actions.push(
                {
                    label: 'Pesquisar Médicos',
                    href: '/patient/search-consultations',
                    icon: Search,
                },
                {
                    label: 'Minhas Consultas',
                    href: '/patient/next-consultation',
                    icon: Calendar,
                },
                {
                    label: 'Meus Registros',
                    href: '/patient/medical-records',
                    icon: Heart,
                },
            );
        }

        // Ações específicas para médicos
        if ((page.props as any).auth?.isDoctor) {
            actions.push(
                {
                    label: 'Minha Agenda',
                    href: '/doctor/appointments',
                    icon: Calendar,
                },
                {
                    label: 'Mensagens',
                    href: '/doctor/messages',
                    icon: MessageCircle,
                },
            );
        }
    } else {
        actions.push({
            label: 'Voltar ao Início',
            href: home().url,
            icon: Home,
            primary: true,
        });
    }

    return actions;
});

// Função para voltar ao histórico anterior
const canGoBack = computed(() => {
    if (typeof window !== 'undefined') {
        return window.history.length > 1;
    }
    return false;
});

const goBack = () => {
    if (typeof window !== 'undefined') {
        if (window.history.length > 1) {
            window.history.back();
        } else {
            window.location.href = home().url;
        }
    }
};
</script>

<template>
    <div class="relative min-h-svh overflow-hidden bg-gradient-to-br from-background via-background to-muted/20">
        <!-- Elementos decorativos de fundo -->
        <div class="pointer-events-none absolute inset-0 overflow-hidden">
            <div class="absolute top-1/4 left-1/4 h-96 w-96 animate-pulse rounded-full bg-primary/5 blur-3xl"></div>
            <div class="absolute right-1/4 bottom-1/4 h-96 w-96 animate-pulse rounded-full bg-primary/5 blur-3xl" style="animation-delay: 1s"></div>
        </div>
        <!-- Header com logo -->
        <div class="absolute top-4 left-4 z-10 md:top-8 md:left-8">
            <Link :href="home().url" class="group flex items-center gap-2 font-medium transition-all duration-300 hover:scale-105 md:gap-4">
                <div
                    class="flex h-12 w-12 items-center justify-center rounded-lg border border-white/20 bg-gradient-to-br from-primary/20 to-primary/10 shadow-lg backdrop-blur-sm transition-all duration-300 group-hover:from-primary/30 group-hover:to-primary/20 group-hover:shadow-xl md:h-14 md:w-18 md:rounded-xl"
                >
                    <AppLogoIcon class="fill-current text-primary transition-colors duration-300 group-hover:text-primary/90" />
                </div>
                <div class="flex flex-col">
                    <span class="text-sm font-bold text-gray-800 transition-colors duration-300 group-hover:text-primary md:text-xl">
                        Telemedicina para Todos
                    </span>
                    <span class="hidden text-xs font-medium text-gray-500 md:block"> Cuidando da sua saúde </span>
                </div>
            </Link>
        </div>

        <!-- Conteúdo principal centralizado -->
        <div class="flex min-h-svh flex-col items-center justify-center gap-8 p-6 md:p-10">
            <div class="mx-auto w-full max-w-4xl space-y-6 text-center">
                <!-- Imagem e código do erro lado a lado -->
                <div class="flex flex-col items-center justify-center gap-8 lg:flex-row lg:gap-12">
                    <!-- Imagem do médico -->
                    <div class="relative flex-shrink-0">
                        <div class="relative">
                            <!-- Gradiente de fundo animado -->
                            <div
                                class="absolute inset-0 animate-pulse rounded-full bg-gradient-to-br from-primary/20 via-primary/10 to-primary/5 blur-3xl"
                            ></div>

                            <!-- Container da imagem com efeitos visuais -->
                            <div
                                class="relative z-10 flex items-center justify-center rounded-full border-2 border-dashed border-muted-foreground/20 bg-gradient-to-br from-muted/50 to-muted/30 p-4 shadow-lg backdrop-blur-sm md:p-6"
                            >
                                <img
                                    :src="badDoctorImage"
                                    alt="Erro"
                                    class="animate-bounce-slow h-48 w-48 object-contain drop-shadow-2xl md:h-64 md:w-64 lg:h-72 lg:w-72"
                                />
                            </div>

                            <!-- Decorativo: ícone pequeno no canto -->
                            <div
                                class="absolute -top-4 -right-4 flex h-16 w-16 items-center justify-center rounded-full border-2 border-destructive/20 bg-gradient-to-br from-destructive/20 to-destructive/10 shadow-lg backdrop-blur-sm md:h-20 md:w-20"
                            >
                                <span class="text-2xl md:text-3xl">{{ errorConfig.icon }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Texto do erro -->
                    <div class="flex-1 space-y-4 text-center lg:text-left">
                        <div class="space-y-2">
                            <h1
                                class="bg-gradient-to-r from-primary via-primary/80 to-primary/60 bg-clip-text text-6xl leading-none font-bold text-transparent md:text-8xl lg:text-9xl"
                            >
                                {{ status }}
                            </h1>
                            <h2 class="text-2xl font-bold text-foreground md:text-3xl lg:text-4xl">
                                {{ errorConfig.title }}
                            </h2>
                            <p class="mx-auto max-w-lg text-base text-muted-foreground md:text-lg lg:mx-0 lg:text-xl">
                                {{ errorConfig.description }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Mensagem técnica opcional (apenas em desenvolvimento) -->
                <div v-if="message && isDev" class="mt-4 rounded-lg border border-dashed border-muted-foreground/20 bg-muted/50 p-4">
                    <p class="font-mono text-sm break-all text-muted-foreground">
                        {{ message }}
                    </p>
                </div>

                <!-- Ações rápidas -->
                <div class="flex flex-col items-center justify-center gap-3 pt-4 sm:flex-row">
                    <template v-for="(action, index) in quickActions" :key="index">
                        <Button v-if="index === 0 && action.primary" :href="action.href" as-child size="lg">
                            <Link>
                                <component :is="action.icon" class="size-4" />
                                {{ action.label }}
                            </Link>
                        </Button>
                        <Button v-else-if="index === 0" :href="action.href" as-child variant="default" size="lg">
                            <Link>
                                <component :is="action.icon" class="size-4" />
                                {{ action.label }}
                            </Link>
                        </Button>
                        <Button v-else :href="action.href" as-child variant="outline" size="lg">
                            <Link>
                                <component :is="action.icon" class="size-4" />
                                {{ action.label }}
                            </Link>
                        </Button>
                    </template>

                    <Button v-if="canGoBack" @click="goBack" variant="ghost" size="lg">
                        <ArrowLeft class="size-4" />
                        Voltar
                    </Button>
                </div>

                <!-- Link de suporte -->
                <div class="border-t border-border pt-8">
                    <p class="mb-3 text-sm text-muted-foreground">Precisa de ajuda? Entre em contato com nosso suporte</p>
                    <Button variant="link" size="sm" as-child>
                        <Link href="mailto:suporte@telemedicinaparatodos.com.br">
                            <MessageCircle class="size-4" />
                            Contatar Suporte
                        </Link>
                    </Button>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
@keyframes bounce-slow {
    0%,
    100% {
        transform: translateY(0);
    }
    50% {
        transform: translateY(-10px);
    }
}

.animate-bounce-slow {
    animation: bounce-slow 3s ease-in-out infinite;
}
</style>
