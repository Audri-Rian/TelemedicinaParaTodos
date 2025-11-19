<script setup lang="ts">
import { login, register, home } from '@/routes';
import { Head, Link } from '@inertiajs/vue3';
import { useAuth, useRoleRoutes } from '@/composables/auth';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import LottieAnimation from '@/components/LottieAnimation.vue';
import { Button } from '@/components/ui/button';
import { Menu, X, ShieldCheck, Lock, Rocket, Stethoscope, Baby, Brain, Heart, Hand, UserCircle } from 'lucide-vue-next';
import { ref } from 'vue';

const { isAuthenticated } = useAuth();
const { dashboardRoute } = useRoleRoutes();
const isMenuOpen = ref(false);
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
                <div class="flex h-16 items-center justify-between">
                    <!-- Logo -->
                    <Link :href="home()" class="flex items-center gap-2">
                    <AppLogoIcon class="h-8 w-8" />
                    <span class="text-xl font-bold text-foreground">Telemedicina Para Todos</span>
                    </Link>

                    <!-- Desktop Navigation -->
                    <div class="hidden items-center gap-8 md:flex">
                        <Link href="#especialidades"
                            class="text-sm font-medium text-muted-foreground transition-colors hover:text-foreground">
                        Especialidades
                        </Link>
                        <Link href="#como-funciona"
                            class="text-sm font-medium text-muted-foreground transition-colors hover:text-foreground">
                        Como Funciona
                        </Link>
                        <Link href="#medicos"
                            class="text-sm font-medium text-muted-foreground transition-colors hover:text-foreground">
                        Médicos
                        </Link>
                    </div>

                    <!-- Desktop Auth Buttons -->
                    <div class="hidden items-center gap-4 md:flex">
                        <Link v-if="isAuthenticated" :href="dashboardRoute()">
                        <Button variant="outline" size="sm">
                            Dashboard
                        </Button>
                        </Link>
                        <template v-else>
                            <Link :href="login()">
                            <Button variant="ghost" size="sm">
                                Entrar
                            </Button>
                            </Link>
                            <Link :href="register()">
                            <Button size="sm" class="bg-primary text-primary-foreground hover:bg-primary/90">
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
                <div v-if="isMenuOpen" class="border-t md:hidden">
                    <div class="space-y-1 px-2 pb-3 pt-2">
                        <Link href="#especialidades"
                            class="block rounded-md px-3 py-2 text-base font-medium text-muted-foreground hover:bg-muted hover:text-foreground"
                            @click="isMenuOpen = false">
                        Especialidades
                        </Link>
                        <Link href="#como-funciona"
                            class="block rounded-md px-3 py-2 text-base font-medium text-muted-foreground hover:bg-muted hover:text-foreground"
                            @click="isMenuOpen = false">
                        Como Funciona
                        </Link>
                        <Link href="#medicos"
                            class="block rounded-md px-3 py-2 text-base font-medium text-muted-foreground hover:bg-muted hover:text-foreground"
                            @click="isMenuOpen = false">
                        Médicos
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
                                Área do Paciente
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
                        Conecte-se com médicos verificados no conforto da sua casa, com total segurança e praticidade.
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
                            Nossa equipe é composta por profissionais experientes e dedicados, prontos para oferecer o melhor atendimento.
                        </p>
                    </div>

                    <div class="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                        <div class="rounded-2xl bg-white p-6 text-center shadow-lg shadow-slate-100">
                            <img
                                class="mx-auto mb-4 h-24 w-24 rounded-full object-cover"
                                src="https://images.unsplash.com/photo-1544033527-00f19b81bb5d?auto=format&fit=crop&w=200&q=80"
                                alt="Dra. Ana Beatriz"
                            >
                            <h3 class="text-lg font-semibold text-foreground">Dra. Ana Beatriz</h3>
                            <p class="mt-1 text-sm text-muted-foreground">Clínica Geral · CRM 123456</p>
                            <span class="mt-4 inline-block rounded-full bg-emerald-100 px-4 py-1 text-sm font-medium text-emerald-700">
                                Verificado
                            </span>
                        </div>

                        <div class="rounded-2xl bg-white p-6 text-center shadow-lg shadow-slate-100">
                            <img
                                class="mx-auto mb-4 h-24 w-24 rounded-full object-cover"
                                src="https://images.unsplash.com/photo-1537368910025-700350fe46c7?auto=format&fit=crop&w=200&q=80"
                                alt="Dr. Carlos Eduardo"
                            >
                            <h3 class="text-lg font-semibold text-foreground">Dr. Carlos Eduardo</h3>
                            <p class="mt-1 text-sm text-muted-foreground">Cardiologista · CRM 789012</p>
                            <span class="mt-4 inline-block rounded-full bg-emerald-100 px-4 py-1 text-sm font-medium text-emerald-700">
                                Verificado
                            </span>
                        </div>

                        <div class="rounded-2xl bg-white p-6 text-center shadow-lg shadow-slate-100">
                            <img
                                class="mx-auto mb-4 h-24 w-24 rounded-full object-cover"
                                src="https://images.unsplash.com/photo-1524504388940-b1c1722653e1?auto=format&fit=crop&w=200&q=80"
                                alt="Dr. Ricardo Gomes"
                            >
                            <h3 class="text-lg font-semibold text-foreground">Dr. Ricardo Gomes</h3>
                            <p class="mt-1 text-sm text-muted-foreground">Pediatra · CRM 345678</p>
                            <span class="mt-4 inline-block rounded-full bg-emerald-100 px-4 py-1 text-sm font-medium text-emerald-700">
                                Verificado
                            </span>
                        </div>

                        <div class="rounded-2xl bg-white p-6 text-center shadow-lg shadow-slate-100">
                            <img
                                class="mx-auto mb-4 h-24 w-24 rounded-full object-cover"
                                src="https://images.unsplash.com/photo-1544723795-3fb6469f5b39?auto=format&fit=crop&w=200&q=80"
                                alt="Dra. Juliana Ferraz"
                            >
                            <h3 class="text-lg font-semibold text-foreground">Dra. Juliana Ferraz</h3>
                            <p class="mt-1 text-sm text-muted-foreground">Psicóloga · CRP 901234</p>
                            <span class="mt-4 inline-block rounded-full bg-emerald-100 px-4 py-1 text-sm font-medium text-emerald-700">
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
                        Agende sua consulta hoje mesmo e experimente a conveniência de um atendimento médico de qualidade, sem sair de casa.
                    </p>
                    <button
                        type="button"
                        class="mt-8 inline-flex items-center justify-center rounded-full bg-primary px-8 py-3 text-base font-semibold text-white transition hover:bg-primary/90"
                    >
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
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                        <path d="M12 21s7-4.35 7-10.5S15.5 3 12 3 5 5.5 5 10.5 12 21 12 21Z"/>
                                        <path d="M9.5 10.5h5M12 8v5"/>
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
                            <a class="mx-1 underline decoration-dotted hover:text-primary" href="#">Termos de Serviço</a> |
                            <a class="ml-1 underline decoration-dotted hover:text-primary" href="#">Política de Privacidade</a>
                        </p>
                    </div>
                </div>
            </div>
        </footer>
    </div>
</template>
