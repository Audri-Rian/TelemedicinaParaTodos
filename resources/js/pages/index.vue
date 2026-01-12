<script setup lang="ts">
import { login, register, home } from '@/routes';
import { Head, Link } from '@inertiajs/vue3';
import { useAuth, useRoleRoutes } from '@/composables/auth';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import LottieAnimation from '@/components/LottieAnimation.vue';
import { Button } from '@/components/ui/button';
import {
    Menu, X, ShieldCheck, Lock, Rocket, Stethoscope, Baby, Brain, Heart, Hand, UserCircle, Search,
    ChevronDown, Video, ClipboardList, Users, Building2, BookOpen, Newspaper, Info, Phone, Activity,
    Twitter, Instagram, Facebook, Youtube, ArrowRight
} from 'lucide-vue-next';
import { ref } from 'vue';

const { isAuthenticated } = useAuth();
const { dashboardRoute } = useRoleRoutes();
const isMenuOpen = ref(false);
const activeMenu = ref<string | null>(null);
let closeTimer: ReturnType<typeof setTimeout> | null = null;

const handleMouseEnter = (menuName: string) => {
    if (closeTimer) clearTimeout(closeTimer);
    activeMenu.value = menuName;
};

const handleMouseLeave = () => {
    closeTimer = setTimeout(() => {
        activeMenu.value = null;
    }, 150);
};

const megaMenuItems = {
    solucoes: {
        title: 'Soluções',
        items: [
            { title: 'Consultas Online', description: 'Atendimento médico por vídeo 24/7', icon: Video },
            { title: 'Prescrições Digitais', description: 'Receitas enviadas direto para seu celular', icon: ClipboardList },
            { title: 'Prontuário Eletrônico', description: 'Seu histórico médico sempre seguro', icon: ShieldCheck },
            { title: 'Exames', description: 'Solicitação e acompanhamento de exames', icon: Activity }
        ]
    },
    servimos: {
        title: 'A quem servimos',
        items: [
            { title: 'Para Pacientes', description: 'Saúde de qualidade sem sair de casa', icon: UserCircle },
            { title: 'Para Médicos', description: 'Plataforma completa para seus atendimentos', icon: Stethoscope },
            { title: 'Para Clínicas', description: 'Gestão eficiente e teleconsultas', icon: Building2 },
            { title: 'Para Empresas', description: 'Cuidado completo para seus colaboradores', icon: Users }
        ]
    },
    recursos: {
        title: 'Recursos',
        items: [
            { title: 'Blog da Saúde', description: 'Dicas e novidades sobre bem-estar', icon: Newspaper },
            { title: 'Central de Ajuda', description: 'Tire suas dúvidas sobre o sistema', icon: Info },
            { title: 'Guia de Saúde', description: 'Aprenda a cuidar melhor de você', icon: BookOpen },
            { title: 'Suporte', description: 'Canal direto com nossa equipe', icon: Phone }
        ]
    },
    empresa: {
        title: 'Empresa',
        items: [
            { title: 'Sobre Nós', description: 'Nossa história e compromisso', icon: Info },
            { title: 'Carreiras', description: 'Venha transformar a saúde conosco', icon: Rocket },
            { title: 'Contato', description: 'Fale com nosso time comercial', icon: Phone },
            { title: 'Imprensa', description: 'Novidades e press releases', icon: Newspaper }
        ]
    },
    telemedicina: {
        title: 'Telemedicina',
        items: [
            { title: 'O que é?', description: 'Entenda como funciona o atendimento online', icon: Video },
            { title: 'Vantagens', description: 'Economia, agilidade e segurança', icon: Heart },
            { title: 'Regulamentação', description: 'Conformidade com CFM e LGPD', icon: ShieldCheck },
            { title: 'Segurança', description: 'Sua privacidade em primeiro lugar', icon: Lock }
        ]
    }
};
</script>

<template>

    <Head title="Telemedicina Para Todos - Cuidando da sua saúde">
        <link rel="preconnect" href="https://rsms.me/" />
        <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
    </Head>

    <div class="min-h-screen bg-white">
        <!-- Navbar -->
        <nav class="sticky top-0 z-50 w-full border-b bg-white backdrop-blur supports-backdrop-filter:bg-white">
            <div class="container mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex h-20 items-center justify-between">
                    <!-- Logo -->
                    <Link :href="home()" class="flex items-center gap-2">
                        <AppLogoIcon class="h-8 w-8" />
                        <span class="text-xl font-bold text-foreground">Telemedicina Para Todos</span>
                    </Link>

                    <!-- Desktop Navigation (Centered) -->
                    <div class="hidden h-full items-center gap-8 md:flex">
                        <!-- Soluções -->
                        <div class="group relative flex h-full items-center" @mouseenter="handleMouseEnter('solucoes')"
                            @mouseleave="handleMouseLeave">
                            <Link href="#solucoes"
                                class="flex items-center gap-1 text-lg font-medium text-muted-foreground transition-colors hover:text-foreground">
                                Soluções
                                <ChevronDown class="h-4 w-4 transition-transform duration-300"
                                    :class="{ 'rotate-180': activeMenu === 'solucoes' }" />
                            </Link>
                            <div class="absolute bottom-4 left-0 h-[2px] bg-primary transition-all duration-300"
                                :class="activeMenu === 'solucoes' ? 'w-full' : 'w-0'"></div>
                        </div>

                        <!-- A quem servimos -->
                        <div class="group relative flex h-full items-center" @mouseenter="handleMouseEnter('servimos')"
                            @mouseleave="handleMouseLeave">
                            <Link href="#a-quem-servimos"
                                class="flex items-center gap-1 text-lg font-medium text-muted-foreground transition-colors hover:text-foreground">
                                A quem servimos
                                <ChevronDown class="h-4 w-4 transition-transform duration-300"
                                    :class="{ 'rotate-180': activeMenu === 'servimos' }" />
                            </Link>
                            <div class="absolute bottom-4 left-0 h-[2px] bg-primary transition-all duration-300"
                                :class="activeMenu === 'servimos' ? 'w-full' : 'w-0'"></div>
                        </div>

                        <!-- Recursos -->
                        <div class="group relative flex h-full items-center" @mouseenter="handleMouseEnter('recursos')"
                            @mouseleave="handleMouseLeave">
                            <Link href="#recursos"
                                class="flex items-center gap-1 text-lg font-medium text-muted-foreground transition-colors hover:text-foreground">
                                Recursos
                                <ChevronDown class="h-4 w-4 transition-transform duration-300"
                                    :class="{ 'rotate-180': activeMenu === 'recursos' }" />
                            </Link>
                            <div class="absolute bottom-4 left-0 h-[2px] bg-primary transition-all duration-300"
                                :class="activeMenu === 'recursos' ? 'w-full' : 'w-0'"></div>
                        </div>

                        <!-- Empresa -->
                        <div class="group relative flex h-full items-center" @mouseenter="handleMouseEnter('empresa')"
                            @mouseleave="handleMouseLeave">
                            <Link href="#empresa"
                                class="flex items-center gap-1 text-lg font-medium text-muted-foreground transition-colors hover:text-foreground">
                                Empresa
                                <ChevronDown class="h-4 w-4 transition-transform duration-300"
                                    :class="{ 'rotate-180': activeMenu === 'empresa' }" />
                            </Link>
                            <div class="absolute bottom-4 left-0 h-[2px] bg-primary transition-all duration-300"
                                :class="activeMenu === 'empresa' ? 'w-full' : 'w-0'"></div>
                        </div>

                        <!-- Telemedicina -->
                        <div class="group relative flex h-full items-center"
                            @mouseenter="handleMouseEnter('telemedicina')" @mouseleave="handleMouseLeave">
                            <Link href="#telemedicina"
                                class="flex items-center gap-1 text-lg font-medium text-muted-foreground transition-colors hover:text-foreground">
                                Telemedicina
                                <ChevronDown class="h-4 w-4 transition-transform duration-300"
                                    :class="{ 'rotate-180': activeMenu === 'telemedicina' }" />
                            </Link>
                            <div class="absolute bottom-4 left-0 h-[2px] bg-primary transition-all duration-300"
                                :class="activeMenu === 'telemedicina' ? 'w-full' : 'w-0'"></div>
                        </div>
                    </div>

                    <!-- Mega Menu Dropdown -->
                    <transition enter-active-class="transition duration-200 ease-out"
                        enter-from-class="translate-y-1 opacity-0" enter-to-class="translate-y-0 opacity-100"
                        leave-active-class="transition duration-150 ease-in"
                        leave-from-class="translate-y-0 opacity-100" leave-to-class="translate-y-1 opacity-0">
                        <div v-if="activeMenu && megaMenuItems[activeMenu as keyof typeof megaMenuItems]"
                            class="absolute left-0 top-[80px] w-full border-b bg-white shadow-xl"
                            @mouseenter="handleMouseEnter(activeMenu!)" @mouseleave="handleMouseLeave">
                            <!-- Top Content Container -->
                            <div class="container mx-auto px-4 py-8 sm:px-6 lg:px-8">
                                <div class="grid grid-cols-1 gap-8 md:grid-cols-2 lg:grid-cols-4">
                                    <div v-for="item in megaMenuItems[activeMenu as keyof typeof megaMenuItems].items"
                                        :key="item.title"
                                        class="group/item flex items-start gap-4 rounded-xl p-4 transition-colors hover:bg-muted/50">
                                        <div
                                            class="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary transition-colors group-hover/item:bg-primary group-hover/item:text-white">
                                            <component :is="item.icon" class="h-6 w-6" />
                                        </div>
                                        <div>
                                            <h4 class="font-bold text-foreground">{{ item.title }}</h4>
                                            <p class="mt-1 text-sm text-muted-foreground leading-snug">{{
                                                item.description }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Full-Width Horizontal Divider Section -->
                            <div class="border-t border-gray-300 py-6">
                                <div class="container mx-auto px-4 sm:px-6 lg:px-8">
                                    <div class="flex items-center justify-between">
                                        <!-- Left Side Link -->
                                        <Link href="#como-funciona"
                                            class="flex items-center gap-2 group/link text-sm font-medium text-muted-foreground transition-colors hover:text-primary">
                                            <span>Como funciona Telemedicina Para Todos</span>
                                            <ArrowRight
                                                class="h-4 w-4 transition-transform group-hover/link:translate-x-1" />
                                        </Link>

                                        <!-- Right Side Social Icons -->
                                        <div class="flex items-center gap-4">
                                            <a href="#"
                                                class="text-muted-foreground transition-colors hover:text-primary"
                                                aria-label="X (Twitter)">
                                                <Twitter class="h-5 w-5" />
                                            </a>
                                            <a href="#"
                                                class="text-muted-foreground transition-colors hover:text-primary"
                                                aria-label="Instagram">
                                                <Instagram class="h-5 w-5" />
                                            </a>
                                            <a href="#"
                                                class="text-muted-foreground transition-colors hover:text-primary"
                                                aria-label="Facebook">
                                                <Facebook class="h-5 w-5" />
                                            </a>
                                            <a href="#"
                                                class="text-muted-foreground transition-colors hover:text-primary"
                                                aria-label="YouTube">
                                                <Youtube class="h-5 w-5" />
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </transition>

                    <!-- Desktop Auth Buttons & Search -->
                    <div class="hidden items-center gap-6 md:flex">
                        <!-- Search Icon -->
                        <button class="text-muted-foreground hover:text-foreground">
                            <Search class="h-5 w-5" />
                        </button>

                        <Link v-if="isAuthenticated" :href="dashboardRoute()"
                            class="text-sm font-bold text-muted-foreground hover:text-foreground">
                            Dashboard
                        </Link>
                        <template v-else>
                            <Link :href="login()" class="text-sm font-bold text-muted-foreground hover:text-foreground">
                                Entrar
                            </Link>
                            <Link :href="register()">
                                <Button size="sm"
                                    class="rounded-full bg-primary px-6 text-primary-foreground hover:bg-primary/90 font-bold">
                                    Registre-se
                                </Button>
                            </Link>
                        </template>
                    </div>

                    <!-- Mobile Menu Button -->
                    <button @click="isMenuOpen = !isMenuOpen"
                        class="md:hidden p-2 text-muted-foreground hover:text-foreground" aria-label="Toggle menu">
                        <Menu v-if="!isMenuOpen" class="h-6 w-6" />
                        <X v-else class="h-6 w-6" />
                    </button>
                </div>

                <!-- Mobile Menu -->
                <div v-if="isMenuOpen" class="border-t md:hidden bg-white">
                    <div class="space-y-1 px-2 pb-3 pt-2">
                        <Link href="#solucoes"
                            class="block rounded-md px-3 py-2 text-base font-medium text-muted-foreground hover:bg-muted hover:text-foreground"
                            @click="isMenuOpen = false">
                            Soluções
                        </Link>
                        <Link href="#a-quem-servimos"
                            class="block rounded-md px-3 py-2 text-base font-medium text-muted-foreground hover:bg-muted hover:text-foreground"
                            @click="isMenuOpen = false">
                            A quem servimos
                        </Link>
                        <Link href="#recursos"
                            class="block rounded-md px-3 py-2 text-base font-medium text-muted-foreground hover:bg-muted hover:text-foreground"
                            @click="isMenuOpen = false">
                            Recursos
                        </Link>
                        <Link href="#empresa"
                            class="block rounded-md px-3 py-2 text-base font-medium text-muted-foreground hover:bg-muted hover:text-foreground"
                            @click="isMenuOpen = false">
                            Empresa
                        </Link>
                        <Link href="#telemedicina"
                            class="block rounded-md px-3 py-2 text-base font-medium text-muted-foreground hover:bg-muted hover:text-foreground"
                            @click="isMenuOpen = false">
                            Telemedicina
                        </Link>
                        <div class="mt-4 space-y-2 border-t pt-4">
                            <Link v-if="isAuthenticated" :href="dashboardRoute()"
                                class="block rounded-md px-3 py-2 text-base font-medium text-muted-foreground hover:bg-muted hover:text-foreground"
                                @click="isMenuOpen = false">
                                Dashboard
                            </Link>
                            <template v-else>
                                <Link :href="login()"
                                    class="block rounded-md px-3 py-2 text-base font-medium text-muted-foreground hover:bg-muted hover:text-foreground"
                                    @click="isMenuOpen = false">
                                    Entrar
                                </Link>
                                <Link :href="register()"
                                    class="block rounded-md bg-primary px-3 py-2 text-center text-base font-medium text-primary-foreground hover:bg-primary/90"
                                    @click="isMenuOpen = false">
                                    Registre-se
                                </Link>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Hero Section -->
        <section class="bg-gray-50 py-12 sm:py-16 lg:py-20">
            <div class="container mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid gap-8 lg:grid-cols-2 lg:items-center lg:gap-12">
                    <!-- Left Side - Text Content -->
                    <div class="space-y-6">
                        <h1 class="text-4xl font-bold leading-tight text-foreground sm:text-5xl lg:text-6xl">
                            Atendimento Médico Online, Seguro e Imediato
                        </h1>
                        <p class="text-lg leading-relaxed text-muted-foreground sm:text-xl">
                            Conecte-se com médicos verificados no conforto da sua casa, com total segurança e
                            praticidade.
                            Sem
                            filas, sem deslocamento.
                        </p>
                        <div>
                            <Link v-if="!isAuthenticated" :href="register()">
                                <Button size="lg" class="bg-primary text-primary-foreground hover:bg-primary/90">
                                    Agendar Consulta Agora
                                </Button>
                            </Link>
                            <Link v-else :href="dashboardRoute()">
                                <Button size="lg" class="bg-primary text-primary-foreground hover:bg-primary/90">
                                    Agendar Consulta Agora
                                </Button>
                            </Link>
                        </div>
                    </div>

                    <!-- Right Side - Visual Element -->
                    <div class="flex items-center justify-center">
                        <div class="relative w-full max-w-md">
                            <div class="rounded-2xl bg-orange-100 p-8 shadow-lg">
                                <div class="flex items-center justify-center">
                                    <LottieAnimation src="/animations/Doctor.lottie" :width="300" :height="300"
                                        :autoplay="true" :loop="true" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Feature Cards -->
                <div class="mt-16 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    <!-- Card 1: Criptografia -->
                    <div class="rounded-lg border bg-card p-6 shadow-sm">
                        <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-lg bg-primary/10">
                            <ShieldCheck class="h-6 w-6 text-primary" />
                        </div>
                        <p class="text-sm text-muted-foreground">Criptografia de Ponta</p>
                        <p class="mt-1 text-lg font-bold text-foreground">Segurança Total</p>
                    </div>

                    <!-- Card 2: Profissionais Verificados -->
                    <div class="rounded-lg border bg-card p-6 shadow-sm">
                        <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-lg bg-primary/10">
                            <Lock class="h-6 w-6 text-primary" />
                        </div>
                        <p class="text-sm text-muted-foreground">Profissionais Verificados</p>
                        <p class="mt-1 text-lg font-bold text-foreground">Confiança Garantida</p>
                    </div>

                    <!-- Card 3: Acesso Rápido -->
                    <div class="rounded-lg border bg-card p-6 shadow-sm">
                        <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-lg bg-primary/10">
                            <Rocket class="h-6 w-6 text-primary" />
                        </div>
                        <p class="text-sm text-muted-foreground">Sem Instalar</p>
                        <p class="mt-1 text-lg font-bold text-foreground">Acesso Rápido</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Especialidades Section -->
        <section id="especialidades" class="bg-white py-16 sm:py-20">
            <div class="container mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-12 text-center">
                    <h2 class="text-3xl font-bold text-foreground sm:text-4xl lg:text-5xl">
                        Nossas Especialidades
                    </h2>
                    <p class="mt-4 text-lg text-muted-foreground sm:text-xl">
                        Encontre o cuidado certo para você. Atendimento especializado em diversas áreas da saúde, a um
                        clique de
                        distância.
                    </p>
                </div>

                <!-- Specialties Grid -->
                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    <!-- Clínica Geral -->
                    <div class="rounded-lg border border-gray-200 bg-white p-6 text-center shadow-sm">
                        <div class="mb-4 flex justify-center">
                            <div class="flex h-16 w-16 items-center justify-center rounded-lg bg-primary/10">
                                <Stethoscope class="h-8 w-8 text-primary" />
                            </div>
                        </div>
                        <h3 class="mb-2 text-xl font-bold text-foreground">Clínica Geral</h3>
                        <p class="text-sm text-muted-foreground">
                            Consultas de rotina e cuidados primários.
                        </p>
                    </div>

                    <!-- Pediatria -->
                    <div class="rounded-lg border border-gray-200 bg-white p-6 text-center shadow-sm">
                        <div class="mb-4 flex justify-center">
                            <div class="flex h-16 w-16 items-center justify-center rounded-lg bg-primary/10">
                                <Baby class="h-8 w-8 text-primary" />
                            </div>
                        </div>
                        <h3 class="mb-2 text-xl font-bold text-foreground">Pediatria</h3>
                        <p class="text-sm text-muted-foreground">
                            Cuidado especializado para crianças e adolescentes.
                        </p>
                    </div>

                    <!-- Psicologia -->
                    <div class="rounded-lg border border-gray-200 bg-white p-6 text-center shadow-sm">
                        <div class="mb-4 flex justify-center">
                            <div class="flex h-16 w-16 items-center justify-center rounded-lg bg-primary/10">
                                <Brain class="h-8 w-8 text-primary" />
                            </div>
                        </div>
                        <h3 class="mb-2 text-xl font-bold text-foreground">Psicologia</h3>
                        <p class="text-sm text-muted-foreground">
                            Apoio à saúde mental e bem-estar.
                        </p>
                    </div>

                    <!-- Cardiologia -->
                    <div class="rounded-lg border border-gray-200 bg-white p-6 text-center shadow-sm">
                        <div class="mb-4 flex justify-center">
                            <div class="flex h-16 w-16 items-center justify-center rounded-lg bg-primary/10">
                                <Heart class="h-8 w-8 text-primary" />
                            </div>
                        </div>
                        <h3 class="mb-2 text-xl font-bold text-foreground">Cardiologia</h3>
                        <p class="text-sm text-muted-foreground">
                            Prevenção e tratamento de doenças do coração.
                        </p>
                    </div>

                    <!-- Dermatologia -->
                    <div class="rounded-lg border border-gray-200 bg-white p-6 text-center shadow-sm">
                        <div class="mb-4 flex justify-center">
                            <div class="flex h-16 w-16 items-center justify-center rounded-lg bg-primary/10">
                                <Hand class="h-8 w-8 text-primary" />
                            </div>
                        </div>
                        <h3 class="mb-2 text-xl font-bold text-foreground">Dermatologia</h3>
                        <p class="text-sm text-muted-foreground">
                            Cuidados com a pele, cabelos e unhas.
                        </p>
                    </div>

                    <!-- Ginecologia -->
                    <div class="rounded-lg border border-gray-200 bg-white p-6 text-center shadow-sm">
                        <div class="mb-4 flex justify-center">
                            <div class="flex h-16 w-16 items-center justify-center rounded-lg bg-primary/10">
                                <UserCircle class="h-8 w-8 text-primary" />
                            </div>
                        </div>
                        <h3 class="mb-2 text-xl font-bold text-foreground">Ginecologia</h3>
                        <p class="text-sm text-muted-foreground">
                            Saúde integral da mulher.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Como Funciona Section -->
        <section id="como-funciona" class="bg-white py-16 sm:py-20">
            <div class="container mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-12 text-center">
                    <h2 class="text-3xl font-bold text-foreground sm:text-4xl lg:text-5xl">
                        Como Funciona
                    </h2>
                </div>

                <!-- Steps Grid -->
                <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
                    <!-- Step 1: Escolha e Agende -->
                    <div class="text-center">
                        <div class="mb-6 flex justify-center">
                            <div
                                class="flex h-16 w-16 items-center justify-center rounded-full bg-primary text-white shadow-lg">
                                <span class="text-2xl font-bold">1</span>
                            </div>
                        </div>
                        <h3 class="mb-3 text-xl font-bold text-foreground">
                            Escolha e Agende
                        </h3>
                        <p class="text-muted-foreground">
                            Escolha a especialidade, o médico de sua preferência e agende o melhor horário na nossa
                            plataforma
                            online.
                        </p>
                    </div>

                    <!-- Step 2: Pague com Segurança -->
                    <div class="text-center">
                        <div class="mb-6 flex justify-center">
                            <div
                                class="flex h-16 w-16 items-center justify-center rounded-full bg-primary text-white shadow-lg">
                                <span class="text-2xl font-bold">2</span>
                            </div>
                        </div>
                        <h3 class="mb-3 text-xl font-bold text-foreground">
                            Pague com Segurança
                        </h3>
                        <p class="text-muted-foreground">
                            Realize o pagamento da sua consulta de forma rápida e segura através da nossa plataforma
                            100%
                            digital.
                        </p>
                    </div>

                    <!-- Step 3: Realize a Consulta -->
                    <div class="text-center">
                        <div class="mb-6 flex justify-center">
                            <div
                                class="flex h-16 w-16 items-center justify-center rounded-full bg-primary text-white shadow-lg">
                                <span class="text-2xl font-bold">3</span>
                            </div>
                        </div>
                        <h3 class="mb-3 text-xl font-bold text-foreground">
                            Realize a Consulta
                        </h3>
                        <p class="text-muted-foreground">
                            Conecte-se com seu médico por videochamada no dia e hora marcados, de onde você estiver.
                        </p>
                    </div>
                </div>
            </div>

            <div class="container mx-auto px-4 sm:px-6 lg:px-8 ">
                <div class="mt-36 rounded-3xl bg-sky-50 px-6 py-12 sm:px-10 lg:px-16">
                    <div class="text-center max-w-3xl mx-auto">
                        <h2 class="text-3xl font-bold text-foreground">
                            Médicos Verificados e Qualificados
                        </h2>
                        <p class="mt-4 text-lg text-muted-foreground">
                            Nossa equipe é composta por profissionais experientes e dedicados, prontos para oferecer o
                            melhor
                            atendimento.
                        </p>
                    </div>

                    <div class="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                        <div class="rounded-2xl bg-white p-6 text-center shadow-lg shadow-slate-100">
                            <img class="mx-auto mb-4 h-24 w-24 rounded-full object-cover"
                                src="https://images.unsplash.com/photo-1544033527-00f19b81bb5d?auto=format&fit=crop&w=200&q=80"
                                alt="Dra. Ana Beatriz">
                            <h3 class="text-lg font-semibold text-foreground">Dra. Ana Beatriz</h3>
                            <p class="mt-1 text-sm text-muted-foreground">Clínica Geral · CRM 123456</p>
                            <span
                                class="mt-4 inline-block rounded-full bg-emerald-100 px-4 py-1 text-sm font-medium text-emerald-700">
                                Verificado
                            </span>
                        </div>

                        <div class="rounded-2xl bg-white p-6 text-center shadow-lg shadow-slate-100">
                            <img class="mx-auto mb-4 h-24 w-24 rounded-full object-cover"
                                src="https://images.unsplash.com/photo-1537368910025-700350fe46c7?auto=format&fit=crop&w=200&q=80"
                                alt="Dr. Carlos Eduardo">
                            <h3 class="text-lg font-semibold text-foreground">Dr. Carlos Eduardo</h3>
                            <p class="mt-1 text-sm text-muted-foreground">Cardiologista · CRM 789012</p>
                            <span
                                class="mt-4 inline-block rounded-full bg-emerald-100 px-4 py-1 text-sm font-medium text-emerald-700">
                                Verificado
                            </span>
                        </div>

                        <div class="rounded-2xl bg-white p-6 text-center shadow-lg shadow-slate-100">
                            <img class="mx-auto mb-4 h-24 w-24 rounded-full object-cover"
                                src="https://images.unsplash.com/photo-1524504388940-b1c1722653e1?auto=format&fit=crop&w=200&q=80"
                                alt="Dr. Ricardo Gomes">
                            <h3 class="text-lg font-semibold text-foreground">Dr. Ricardo Gomes</h3>
                            <p class="mt-1 text-sm text-muted-foreground">Pediatra · CRM 345678</p>
                            <span
                                class="mt-4 inline-block rounded-full bg-emerald-100 px-4 py-1 text-sm font-medium text-emerald-700">
                                Verificado
                            </span>
                        </div>

                        <div class="rounded-2xl bg-white p-6 text-center shadow-lg shadow-slate-100">
                            <img class="mx-auto mb-4 h-24 w-24 rounded-full object-cover"
                                src="https://images.unsplash.com/photo-1544723795-3fb6469f5b39?auto=format&fit=crop&w=200&q=80"
                                alt="Dra. Juliana Ferraz">
                            <h3 class="text-lg font-semibold text-foreground">Dra. Juliana Ferraz</h3>
                            <p class="mt-1 text-sm text-muted-foreground">Psicóloga · CRP 901234</p>
                            <span
                                class="mt-4 inline-block rounded-full bg-emerald-100 px-4 py-1 text-sm font-medium text-emerald-700">
                                Verificado
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="container mx-auto mt-24 px-4 sm:px-6 lg:px-8">
                <div class="rounded-3xl bg-white py-16 text-center ">
                    <h2 class="text-3xl font-bold text-foreground">
                        Pronto para cuidar da sua saúde?
                    </h2>
                    <p class="mx-auto mt-4 max-w-2xl text-base text-muted-foreground">
                        Agende sua consulta hoje mesmo e experimente a conveniência de um atendimento médico de
                        qualidade, sem
                        sair de casa.
                    </p>
                    <button type="button"
                        class="mt-8 inline-flex items-center justify-center rounded-full bg-primary px-8 py-3 text-base font-semibold text-white transition hover:bg-primary/90">
                        Agendar Consulta Agora
                    </button>
                </div>
            </div>

        </section>

        <footer class="mt-24 bg-sky-50">
            <div class="container mx-auto px-4 sm:px-6 lg:px-8">
                <div class="rounded-t-3xl px-6 py-16 sm:px-10 lg:px-16">
                    <div class="grid gap-12 lg:grid-cols-3">
                        <div>
                            <div class="flex items-center gap-3 text-primary">
                                <span class="flex h-12 w-12 items-center justify-center rounded-full bg-primary/10">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24"
                                        fill="none" stroke="currentColor" stroke-width="1.8">
                                        <path d="M12 21s7-4.35 7-10.5S15.5 3 12 3 5 5.5 5 10.5 12 21 12 21Z" />
                                        <path d="M9.5 10.5h5M12 8v5" />
                                    </svg>
                                </span>
                                <span class="text-xl font-semibold text-foreground">
                                    Telemedicina Para Todos
                                </span>
                            </div>
                            <p class="mt-4 text-sm text-muted-foreground">
                                Oferecendo atendimento médico acessível, seguro e de qualidade,
                                onde quer que você esteja.
                            </p>
                        </div>

                        <div>
                            <h3 class="text-lg font-semibold text-foreground">Navegação</h3>
                            <ul class="mt-4 space-y-2 text-sm text-muted-foreground">
                                <li><a class="transition hover:text-primary" href="#">Especialidades</a></li>
                                <li><a class="transition hover:text-primary" href="#">Como Funciona</a></li>
                                <li><a class="transition hover:text-primary" href="#">Médicos</a></li>
                                <li><a class="transition hover:text-primary" href="#">Login</a></li>
                            </ul>
                        </div>

                        <div>
                            <h3 class="text-lg font-semibold text-foreground">Contato</h3>
                            <ul class="mt-4 space-y-2 text-sm text-muted-foreground">
                                <li>contato@telemedicinaparatodos.com.br</li>
                                <li>(11) 99999-9999</li>
                            </ul>
                        </div>
                    </div>

                    <div class="mt-12 border-t border-slate-200 pt-6">
                        <p class="text-center text-xs text-muted-foreground">
                            © 2024 Telemedicina Para Todos. Todos os direitos reservados. |
                            <a class="mx-1 underline decoration-dotted hover:text-primary" href="#">Termos de
                                Serviço</a> |
                            <a class="ml-1 underline decoration-dotted hover:text-primary" href="#">Política de
                                Privacidade</a>
                        </p>
                    </div>
                </div>
            </div>
        </footer>
    </div>
</template>
