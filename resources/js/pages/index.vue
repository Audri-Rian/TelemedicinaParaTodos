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
    Twitter, Instagram, Facebook, Youtube, ArrowRight, Play, Pause, Calendar, ChevronLeft, ChevronRight, Quote
} from 'lucide-vue-next';
import { ref, onMounted, onUnmounted } from 'vue';

const { isAuthenticated } = useAuth();
const { dashboardRoute } = useRoleRoutes();
const isMenuOpen = ref(false);
const activeMenu = ref<string | null>(null);
let closeTimer: ReturnType<typeof setTimeout> | null = null;

// Video controls
const heroVideoRef = ref<HTMLVideoElement | null>(null);
const isVideoPlaying = ref(true);

// Scroll animation for specialty cards
const card1Ref = ref<HTMLElement | null>(null);
const card2Ref = ref<HTMLElement | null>(null);
const card3Ref = ref<HTMLElement | null>(null);
const card1Visible = ref(false);
const card2Visible = ref(false);
const card3Visible = ref(false);

// Synchronized scroll for specialties title
const specialtiesSectionRef = ref<HTMLElement | null>(null);
const stickyTitleRef = ref<HTMLElement | null>(null);
const titleTranslateY = ref(0);

const handleScroll = () => {
    if (!specialtiesSectionRef.value || !stickyTitleRef.value || window.innerWidth < 1024) {
        titleTranslateY.value = 0;
        return;
    }

    const section = specialtiesSectionRef.value;
    const title = stickyTitleRef.value;
    const rect = section.getBoundingClientRect();

    // Calculate total scrollable distance within section
    // The title should start moving when section top enters view and finish when section bottom leaves
    const sectionTop = rect.top + window.scrollY;
    const sectionHeight = section.offsetHeight;
    const viewportHeight = window.innerHeight;

    // Start effect when section top reaches top of viewport
    // End effect when section bottom reaches bottom of viewport
    const startScroll = sectionTop;
    const endScroll = sectionTop + sectionHeight - viewportHeight;
    const currentScroll = window.scrollY;

    let progress = (currentScroll - startScroll) / (endScroll - startScroll);
    progress = Math.max(0, Math.min(1, progress));

    // Max travel is section height minus title height minus some padding
    const maxTravel = sectionHeight - title.offsetHeight - 160; // 160 is roughly the sum of pt/pb
    titleTranslateY.value = progress * maxTravel;
};

onMounted(() => {
    const observerOptions = {
        threshold: 0.2,
        rootMargin: '0px 0px -100px 0px'
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                if (entry.target === card1Ref.value) card1Visible.value = true;
                if (entry.target === card2Ref.value) card2Visible.value = true;
                if (entry.target === card3Ref.value) card3Visible.value = true;
            }
        });
    }, observerOptions);

    if (card1Ref.value) observer.observe(card1Ref.value);
    if (card2Ref.value) observer.observe(card2Ref.value);
    if (card3Ref.value) observer.observe(card3Ref.value);

    window.addEventListener('scroll', handleScroll);
    window.addEventListener('resize', handleScroll);

    onUnmounted(() => {
        observer.disconnect();
        window.removeEventListener('scroll', handleScroll);
        window.removeEventListener('resize', handleScroll);
    });
});

interface MegaMenuItem {
    title: string;
    description: string;
    tag?: string;
}

interface MegaMenuColumn {
    title: string;
    items: MegaMenuItem[];
}

interface MegaMenuContent {
    columns: MegaMenuColumn[];
    featuredCard?: {
        title: string;
        description: string;
        image: string;
        linkText: string;
        linkHref: string;
    };
}

const handleMouseEnter = (menuName: string) => {
    if (closeTimer) clearTimeout(closeTimer);
    activeMenu.value = menuName;
};

const handleMouseLeave = () => {
    closeTimer = setTimeout(() => {
        activeMenu.value = null;
    }, 150);
};

const toggleVideoPlayback = () => {
    if (!heroVideoRef.value) return;

    if (isVideoPlaying.value) {
        heroVideoRef.value.pause();
        isVideoPlaying.value = false;
    } else {
        heroVideoRef.value.play();
        isVideoPlaying.value = true;
    }
};

const megaMenuItems: Record<string, MegaMenuContent> = {
    solucoes: {
        columns: [
            {
                title: 'Nossa plataforma',
                items: [
                    { title: 'Telemedicina Hub', description: 'Transforme a maneira como você cuida dos pacientes e alcance o sucesso nos negócios com nossa solução abrangente para o setor de saúde, nativa de IA.' }
                ]
            },
            {
                title: 'Soluções Integradas',
                items: [
                    { title: 'Prontuário Eletrônico de Saúde', description: 'Simplifique a prestação de cuidados personalizados com nosso EHR inteligente e intuitivo.' },
                    { title: 'Software de Faturamento e Gestão', description: 'Envie solicitações de reembolso mais claras e rápidas com suporte de faturamento nativo.' },
                    { title: 'Engajamento do Paciente', description: 'Melhore a experiência do paciente com ferramentas modernas de autoatendimento e comunicação.' }
                ]
            },
            {
                title: 'Benefícios do Sistema',
                items: [
                    { title: 'Apoio ao Cuidado Especializado', description: 'Melhore os resultados para os pacientes monitorando e recebendo o pagamento pelos cuidados prestados.' },
                    { title: 'Recursos de IA Avançados', description: 'Ferramentas com inteligência artificial que ajudam a impulsionar a eficiência e o desempenho clínico.', tag: 'Novo' },
                    { title: 'Interoperabilidade Total', description: 'Obtenha os dados de que precisa, quando precisar, em toda a nossa rede.' }
                ]
            }
        ],
        featuredCard: {
            title: 'Inovação em Saúde',
            description: 'Descubra como nossa IA está salvando vidas diariamente.',
            image: '/images/feature-bg.png',
            linkText: 'Saiba mais',
            linkHref: '#inovacao'
        }
    },
    servimos: {
        columns: [
            {
                title: 'Público Alvo',
                items: [
                    { title: 'Clínicas e Consultórios', description: 'Otimize seu atendimento com nossa plataforma de ponta.' }
                ]
            },
            {
                title: 'Especialidades',
                items: [
                    { title: 'Médicos de Família', description: 'Gestão completa para o cuidado contínuo.' },
                    { title: 'Especialistas', description: 'Ferramentas específicas para cada área médica.' },
                    { title: 'Hospitais', description: 'Integração de larga escala para sistemas complexos.' }
                ]
            },
            {
                title: 'Serviços por Perfil',
                items: [
                    { title: 'Sistemas de Saúde', description: 'Soluções corporativas para grandes redes de atendimento.' },
                    { title: 'Grupos Médicos', description: 'Colaboração eficiente e compartilhamento de dados.' }
                ]
            },
            {
                title: 'Suporte Regional',
                items: [
                    { title: 'Atendimento Local', description: 'Encontre suporte na sua região.' },
                    { title: 'Casos de Sucesso', description: 'Veja como estamos transformando a saúde localmente.' }
                ]
            }
        ]
    },
    recursos: {
        columns: [
            {
                title: 'Portal de Conhecimento',
                items: [
                    { title: 'Blog de Saúde Digital', description: 'Fique por dentro das últimas tendências e notícias.' }
                ]
            },
            {
                title: 'Aprendizado',
                items: [
                    { title: 'Webinars ao Vivo', description: 'Aprenda com líderes do setor sobre o futuro da saúde.' },
                    { title: 'Guias e E-books', description: 'Materiais educativos profundos sobre gestão e clínica.' },
                    { title: 'Pesquisas de Mercado', description: 'Insights valiosos baseados em dados reais.' }
                ]
            },
            {
                title: 'Centro de Suporte',
                items: [
                    { title: 'Base de Conhecimento', description: 'Respostas rápidas para suas dúvidas técnicas.' },
                    { title: 'Treinamento Online', description: 'Capacite sua equipe com nossos cursos.' }
                ]
            },
            {
                title: 'Desenvolvedores',
                items: [
                    { title: 'Documentação de API', description: 'Tudo o que você precisa para integrar com nosso sistema.' },
                    { title: 'Comunidade Dev', description: 'Troque experiências com outros desenvolvedores.' }
                ]
            }
        ]
    },
    empresa: {
        columns: [
            {
                title: 'Sobre a Empresa',
                items: [
                    { title: 'Nossa História', description: 'Conheça a trajetória da Telemedicina Para Todos rumo à inovação na saúde.' }
                ]
            },
            {
                title: 'Valores e Missão',
                items: [
                    { title: 'Impacto Social', description: 'Como democratizamos o acesso à saúde de qualidade em todo o país.' },
                    { title: 'Inovação Ética', description: 'Nosso compromisso com a tecnologia a serviço da vida.' }
                ]
            },
            {
                title: 'Trabalhe Conosco',
                items: [
                    { title: 'Oportunidades', description: 'Faça parte de um time que está transformando o setor de saúde.' },
                    { title: 'Cultura e Vida', description: 'Conheça nosso ambiente de trabalho e nossos pilares culturais.' }
                ]
            },
            {
                title: 'Canais de Contato',
                items: [
                    { title: 'Atendimento Geral', description: 'Fale conosco para dúvidas, sugestões ou suporte.' },
                    { title: 'Assessoria de Imprensa', description: 'Recursos e contatos para jornalistas e mídia.' }
                ]
            }
        ]
    },
    telemedicina: {
        columns: [
            {
                title: 'O Universo Online',
                items: [
                    { title: 'O que é Telemedicina?', description: 'Entenda como o atendimento remoto revolucionou o cuidado médico moderno.' }
                ]
            },
            {
                title: 'Experiência Digital',
                items: [
                    { title: 'Segurança de Dados', description: 'Como protegemos suas informações com os mais altos padrões de criptografia.', tag: 'Seguro' },
                    { title: 'Qualidade Clínica', description: 'A excelência do atendimento presencial, agora em qualquer lugar.' }
                ]
            },
            {
                title: 'Regulamentação',
                items: [
                    { title: 'Padrões CFM', description: 'Em conformidade total com as resoluções do Conselho Federal de Medicina.' },
                    { title: 'LGPD na Saúde', description: 'Garantia de privacidade e proteção de dados sensíveis conforme a lei.' }
                ]
            },
            {
                title: 'Futuro da Saúde',
                items: [
                    { title: 'IA e Diagnóstico', description: 'Como a inteligência artificial está auxiliando médicos em decisões precisas.' },
                    { title: 'Monitoramento Remoto', description: 'O acompanhamento contínuo do paciente através da tecnologia.' }
                ]
            }
        ],
        featuredCard: {
            title: 'O Futuro é Agora',
            description: 'Veja como a telemedicina está encurtando distâncias geográficas.',
            image: '/images/feature-bg.png',
            linkText: 'Ver detalhes',
            linkHref: '#futuro'
        }
    }
};

const carouselRef = ref<HTMLElement | null>(null);
const showVideoModal = ref(false);
const activeVideoUrl = ref('');

const openVideo = (url: string) => {
    activeVideoUrl.value = url;
    showVideoModal.value = true;
    document.body.style.overflow = 'hidden';
};

const closeVideo = () => {
    showVideoModal.value = false;
    activeVideoUrl.value = '';
    document.body.style.overflow = 'auto';
};

const scrollCarousel = (direction: 'left' | 'right') => {
    if (!carouselRef.value) return;
    const scrollAmount = 400;
    carouselRef.value.scrollBy({
        left: direction === 'left' ? -scrollAmount : scrollAmount,
        behavior: 'smooth'
    });
};

const testimonials = [
    {
        id: 1,
        type: 'video',
        video: '/images/video/Criação_de_Vídeo_de_Depoimento_Telemedicina.mp4',
        image: 'https://images.unsplash.com/photo-1559839734-2b71f1e3c770?auto=format&fit=crop&w=800&q=80',
        name: 'Camila Oliveira',
        role: 'Diretora de Operações',
        company: 'HealthCare Plus',
        linkText: 'Ver depoimento'
    },
    {
        id: 2,
        type: 'quote',
        text: 'A saúde muda de verdade quando a tecnologia deixa de ser uma barreira e passa a trabalhar pelas pessoas.',
        name: 'Carlos Mendes',
        role: 'CTO & Co-fundador',
        company: 'Inovação Saúde Digital'
    },
    {
        id: 3,
        type: 'image-full',
        image: 'https://images.unsplash.com/photo-1594824476967-48c8b964273f?auto=format&fit=crop&w=800&q=80',
        name: 'Ana Beatriz',
        role: 'Paciente',
        company: 'Cuidado Contínuo',
        linkText: 'Ouvir sua história'
    },
    {
        id: 4,
        type: 'quote',
        text: 'A segurança dos dados e o prontuário eletrônico integrado facilitam muito o acompanhamento dos pacientes crônicos.',
        name: 'Letícia Ferraz',
        role: 'Enfermeira Chefe',
        company: 'Hospital Regional'
    },
    {
        id: 5,
        type: 'video',
        video: '/images/video/Vídeo_Telemedicina_Para_Todos_Transformação.mp4',
        image: 'https://images.unsplash.com/photo-1559839734-2b71f1e3c770?auto=format&fit=crop&w=800&q=80',
        name: 'Dra. Helena Martins',
        role: 'Diretora Clínica',
        company: 'Rede Global de Saúde',
        linkText: 'Ver transformação'
    }
];
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
                                <div class="grid grid-cols-1 gap-12 md:grid-cols-2 lg:grid-cols-4">
                                    <div v-for="column in megaMenuItems[activeMenu as keyof typeof megaMenuItems].columns"
                                        :key="column.title" class="space-y-6">
                                        <!-- Column Title -->
                                        <h3
                                            class="text-sm font-semibold uppercase tracking-wider text-muted-foreground/70">
                                            {{ column.title }}
                                        </h3>

                                        <!-- Column Items -->
                                        <div class="space-y-8">
                                            <div v-for="item in column.items" :key="item.title"
                                                class="group/item cursor-pointer">
                                                <div class="flex items-center gap-2">
                                                    <h4
                                                        class="text-lg font-bold text-foreground transition-colors group-hover/item:text-primary">
                                                        {{ item.title }}
                                                    </h4>
                                                    <span v-if="item.tag"
                                                        class="rounded-full bg-orange-100 px-2 py-0.5 text-[10px] font-bold text-orange-600 uppercase border border-orange-200">
                                                        {{ item.tag }}
                                                    </span>
                                                </div>
                                                <p
                                                    class="mt-2 text-sm text-muted-foreground leading-relaxed transition-colors group-hover/item:text-foreground">
                                                    {{ item.description }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Featured Card -->
                                    <div v-if="megaMenuItems[activeMenu as keyof typeof megaMenuItems].featuredCard"
                                        class="relative overflow-hidden rounded-2xl group/card">
                                        <div class="absolute inset-0 bg-cover bg-center transition-transform duration-500 group-hover/card:scale-110"
                                            :style="{ backgroundImage: `url(${megaMenuItems[activeMenu as keyof typeof megaMenuItems].featuredCard!.image})` }">
                                        </div>
                                        <div
                                            class="absolute inset-0 bg-gradient-to-t from-primary/90 via-primary/40 to-transparent">
                                        </div>
                                        <div class="relative h-full flex flex-col justify-end p-6 text-white text-left">
                                            <h4 class="text-xl font-bold">{{ megaMenuItems[activeMenu as keyof typeof
                                                megaMenuItems].featuredCard!.title }}</h4>
                                            <p class="mt-2 text-sm text-white/90 leading-snug">
                                                {{ megaMenuItems[activeMenu as keyof typeof
                                                    megaMenuItems].featuredCard!.description }}
                                            </p>
                                            <Link
                                                :href="megaMenuItems[activeMenu as keyof typeof megaMenuItems].featuredCard!.linkHref"
                                                class="mt-4 inline-flex items-center gap-2 text-sm font-bold hover:underline">
                                                {{ megaMenuItems[activeMenu as keyof typeof
                                                    megaMenuItems].featuredCard!.linkText }}
                                                <ArrowRight class="h-4 w-4" />
                                            </Link>
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

        <!-- Hero Section Redesigned -->
        <section class="relative min-h-[700px] w-full overflow-hidden flex flex-col pt-32 pb-16 justify-center">
            <!-- Background Video with Premium Overlay -->
            <div class="absolute inset-0 z-0">
                <video ref="heroVideoRef" src="/images/video/Criação_de_Vídeo_Institucional_Telemedicina.mp4"
                    class="h-full w-full object-cover" autoplay muted loop playsinline></video>
                <div class="absolute inset-0 bg-gradient-to-r from-black/80 via-black/40 to-transparent"></div>
                <div class="absolute inset-0 bg-black/20"></div>
            </div>

            <div class="container relative z-10 mx-auto px-4 sm:px-6 lg:px-8 flex-1 flex flex-col h-full">
                <!-- Top Section: Text Content -->
                <div class="flex-1 flex items-center py-12">
                    <div class="max-w-2xl space-y-6 text-left">
                        <div
                            class="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-4 py-1.5 text-xs font-bold text-primary backdrop-blur-md">
                            <span class="relative flex h-2 w-2">
                                <span
                                    class="absolute inline-flex h-full w-full animate-ping rounded-full bg-primary opacity-75"></span>
                                <span class="relative inline-flex h-2 w-2 rounded-full bg-primary"></span>
                            </span>
                            Atendimento Médico Online
                        </div>
                        <h1
                            class="text-4xl font-extrabold tracking-tight text-white sm:text-5xl lg:text-6xl leading-[1.1]">
                            Saúde de qualidade que
                            <span class="text-primary block mt-2">chega até você</span>
                        </h1>
                        <p class="text-base leading-relaxed text-gray-200 sm:text-lg max-w-xl font-medium opacity-90">
                            Conecte-se com médicos especialistas de qualquer lugar. A telemedicina elimina distâncias,
                            reduz tempo de espera e oferece cuidado médico profissional sem sair de casa.
                            Consultas seguras, rápidas e humanizadas ao seu alcance.
                        </p>
                    </div>
                </div>

                <!-- Bottom Section: Actions Row -->
                <div class="mt-auto flex items-center justify-between gap-4 pt-8">
                    <!-- Left Side Buttons -->
                    <div class="flex flex-wrap items-center gap-4">
                        <Link v-if="!isAuthenticated" :href="register()">
                            <Button size="lg"
                                class="rounded-full bg-white px-8 py-6 text-base font-bold text-gray-900 transition-all hover:bg-gray-100 hover:scale-105 shadow-xl">
                                Agendar agora
                            </Button>
                        </Link>
                        <Link v-else :href="dashboardRoute()">
                            <Button size="lg"
                                class="rounded-full bg-white px-8 py-6 text-base font-bold text-gray-900 transition-all hover:bg-gray-100 hover:scale-105 shadow-xl">
                                Ir para o Dashboard
                            </Button>
                        </Link>

                        <button
                            class="flex items-center gap-3 rounded-full border-2 border-white/30 bg-white/10 px-8 py-4 text-base font-bold text-white backdrop-blur-md transition-all hover:bg-white/20 hover:border-white/50 group">
                            Conheça nossa visão
                            <ArrowRight class="h-5 w-5 transition-transform group-hover:translate-x-1" />
                        </button>
                    </div>

                    <!-- Right Side: Play/Pause Button -->
                    <div class="flex items-center">
                        <button @click="toggleVideoPlayback"
                            class="flex h-16 w-16 items-center justify-center rounded-full border-2 border-white/40 bg-white/5 text-white backdrop-blur-md transition-all hover:bg-white/20 hover:scale-110 hover:border-white group shadow-2xl"
                            :aria-label="isVideoPlaying ? 'Pausar vídeo' : 'Reproduzir vídeo'">
                            <Play v-if="!isVideoPlaying"
                                class="h-7 w-7 ml-1 fill-white group-hover:scale-110 transition-transform" />
                            <Pause v-else class="h-7 w-7 fill-white group-hover:scale-110 transition-transform" />
                        </button>
                    </div>
                </div>
            </div>
        </section>



        <!-- Especialidades Section Redesigned -->
        <section id="especialidades" ref="specialtiesSectionRef"
            class="bg-[#fafafa] pt-16 pb-24 sm:pt-20 sm:pb-32 overflow-hidden">
            <div class="container mx-auto px-4 sm:px-6 lg:px-8 lg:pl-32">
                <div class="flex flex-col lg:flex-row gap-16 lg:gap-40">
                    <!-- Left Side: Sticky Title -->
                    <div ref="stickyTitleRef"
                        class="lg:w-1/3 h-fit space-y-8 will-change-transform transition-transform duration-75 ease-out"
                        :style="{ transform: `translateY(${titleTranslateY}px)` }">
                        <div class="space-y-4">
                            <h2
                                class="text-4xl font-extrabold tracking-tight text-gray-900 sm:text-5xl lg:text-6xl leading-[1.1]">
                                Descubra por que a<br />
                                <span class="text-primary italic">Telemedicina Para Todos</span><br />
                                é a escolha certa
                            </h2>
                            <p class="text-lg text-gray-600 max-w-md leading-relaxed">
                                Oferecemos excelência clínica e tecnológica para garantir que você tenha o melhor
                                atendimento,
                                onde quer que esteja.
                            </p>
                        </div>
                    </div>

                    <!-- Right Side: Cards List -->
                    <div class="lg:w-2/3 space-y-16 lg:pt-32">
                        <!-- Card 1: Clínica Geral -->
                        <div ref="card1Ref" :class="[
                            'group relative bg-white rounded-2xl overflow-hidden shadow-[0_20px_50px_rgba(0,0,0,0.04)] border border-gray-100 transition-all duration-1000 max-w-[540px]',
                            card1Visible ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-12',
                            'hover:shadow-[0_40px_80px_rgba(0,0,0,0.06)] hover:-translate-y-2'
                        ]" style="transition-delay: 100ms">
                            <!-- Image Header -->
                            <div class="h-64 sm:h-80 w-full overflow-hidden">
                                <img src="/images/specialties/clinica_geral.png" alt="Clínica Geral"
                                    class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110" />
                            </div>
                            <div class="p-8 sm:p-12">


                                <div class="space-y-6">
                                    <h3 class="text-3xl font-extrabold text-gray-900 tracking-tight sm:text-4xl">
                                        Clínica Geral: Sua porta de entrada para uma saúde melhor
                                    </h3>
                                    <p class="text-lg text-gray-600 leading-relaxed max-w-2xl">
                                        Nossos clínicos gerais oferecem atendimento integral e preventivo, coordenando
                                        seu
                                        cuidado
                                        com humanização e precisão técnica, tudo no conforto da sua casa.
                                    </p>
                                    <div class="pt-4">
                                        <Link href="#agendar"
                                            class="inline-flex items-center gap-3 text-primary font-bold text-lg group/link transition-all hover:gap-5">
                                            Descubra como cuidamos de você
                                            <ArrowRight class="h-5 w-5 transition-transform" />
                                        </Link>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Card 2: Saúde Mental -->
                        <div ref="card2Ref" :class="[
                            'group relative bg-white rounded-2xl overflow-hidden shadow-[0_20px_50px_rgba(0,0,0,0.04)] border border-gray-100 transition-all duration-1000 max-w-[540px]',
                            card2Visible ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-12',
                            'hover:shadow-[0_40px_80px_rgba(0,0,0,0.06)] hover:-translate-y-2'
                        ]" style="transition-delay: 200ms">
                            <!-- Image Header -->
                            <div class="h-64 sm:h-80 w-full overflow-hidden">
                                <img src="/images/specialties/saude_mental.png" alt="Saúde Mental"
                                    class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110" />
                            </div>
                            <div class="p-8 sm:p-12">


                                <div class="space-y-6">
                                    <h3 class="text-3xl font-extrabold text-gray-900 tracking-tight sm:text-4xl">
                                        Saúde Mental: Equilíbrio e suporte emocional onde você estiver
                                    </h3>
                                    <p class="text-lg text-gray-600 leading-relaxed max-w-2xl">
                                        Acesso rápido a psicólogos e psiquiatras qualificados. Um ambiente seguro e
                                        acolhedor para
                                        tratar ansiedade, depressão e outros desafios da vida moderna.
                                    </p>
                                    <div class="pt-4">
                                        <Link href="#agendar"
                                            class="inline-flex items-center gap-3 text-primary font-bold text-lg group/link transition-all hover:gap-5">
                                            Inicie sua jornada de autocuidado
                                            <ArrowRight class="h-5 w-5 transition-transform" />
                                        </Link>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Card 3: Especialidades Pediátricas -->
                        <div ref="card3Ref" :class="[
                            'group relative bg-white rounded-2xl overflow-hidden shadow-[0_20px_50px_rgba(0,0,0,0.04)] border border-gray-100 transition-all duration-1000 max-w-[540px]',
                            card3Visible ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-12',
                            'hover:shadow-[0_40px_80px_rgba(0,0,0,0.06)] hover:-translate-y-2'
                        ]" style="transition-delay: 300ms">
                            <!-- Image Header -->
                            <div class="h-64 sm:h-80 w-full overflow-hidden">
                                <img src="/images/specialties/pediatria.png" alt="Pediatria"
                                    class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110" />
                            </div>
                            <div class="p-8 sm:p-12">


                                <div class="space-y-6">
                                    <h3 class="text-3xl font-extrabold text-gray-900 tracking-tight sm:text-4xl">
                                        Pediatria: O melhor cuidado para quem você mais ama
                                    </h3>
                                    <p class="text-lg text-gray-600 leading-relaxed max-w-2xl">
                                        Consultas pediátricas humanizadas e suporte aos pais em tempo real.
                                        Especialistas em
                                        todas
                                        as fases do desenvolvimento infantil.
                                    </p>
                                    <div class="pt-4">
                                        <Link href="#agendar"
                                            class="inline-flex items-center gap-3 text-primary font-bold text-lg group/link transition-all hover:gap-5">
                                            Agende uma consulta para seu filho
                                            <ArrowRight class="h-5 w-5 transition-transform" />
                                        </Link>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Como Funciona Section Redesigned -->
        <section id="como-funciona" class="bg-white py-24 sm:py-32">
            <div class="container mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-20 text-center space-y-4">
                    <h2 class="text-4xl font-extrabold text-[#0F172A] sm:text-5xl tracking-tight">
                        Como funciona sua consulta online
                    </h2>
                    <p class="text-lg text-gray-500 max-w-2xl mx-auto">
                        Cuide da sua saúde sem sair de casa em apenas 3 passos simples.
                    </p>
                </div>

                <!-- Steps Container -->
                <div class="relative max-w-5xl mx-auto">
                    <!-- Dashed Connection Line (Desktop only) -->
                    <div
                        class="hidden lg:block absolute top-12 left-[15%] right-[15%] h-[2px] border-t-2 border-dashed border-gray-200 z-0">
                    </div>

                    <div class="grid gap-12 lg:grid-cols-3 relative z-10">
                        <!-- Step 1: Escolha e Agende -->
                        <div class="flex flex-col items-center text-center group">
                            <div class="mb-8 relative">
                                <div
                                    class="flex h-24 w-24 items-center justify-center rounded-2xl bg-[#EBF3FF] transition-transform duration-300 group-hover:scale-110 shadow-sm">
                                    <Calendar class="h-10 w-10 text-primary" />
                                </div>
                            </div>
                            <div class="space-y-3">
                                <span class="text-xs font-bold text-primary uppercase tracking-widest">Passo 1</span>
                                <h3 class="text-2xl font-extrabold text-[#0F172A]">
                                    Escolha e Agende
                                </h3>
                                <p class="text-gray-500 leading-relaxed max-w-[280px] mx-auto">
                                    Selecione a especialidade e o melhor horário disponível na nossa agenda inteligente.
                                </p>
                            </div>
                        </div>

                        <!-- Step 2: Pagamento Seguro -->
                        <div class="flex flex-col items-center text-center group">
                            <div class="mb-8 relative">
                                <div
                                    class="flex h-24 w-24 items-center justify-center rounded-2xl bg-[#EBF3FF] transition-transform duration-300 group-hover:scale-110 shadow-sm">
                                    <ShieldCheck class="h-10 w-10 text-primary" />
                                </div>
                            </div>
                            <div class="space-y-3">
                                <span class="text-xs font-bold text-primary uppercase tracking-widest">Passo 2</span>
                                <h3 class="text-2xl font-extrabold text-[#0F172A]">
                                    Pagamento Seguro
                                </h3>
                                <p class="text-gray-500 leading-relaxed max-w-[280px] mx-auto">
                                    Realize o pagamento de forma transparente e totalmente segura através da plataforma.
                                </p>
                            </div>
                        </div>

                        <!-- Step 3: Videochamada -->
                        <div class="flex flex-col items-center text-center group">
                            <div class="mb-8 relative">
                                <div
                                    class="flex h-24 w-24 items-center justify-center rounded-2xl bg-[#EBF3FF] transition-transform duration-300 group-hover:scale-110 shadow-sm">
                                    <Video class="h-10 w-10 text-primary" />
                                </div>
                            </div>
                            <div class="space-y-3">
                                <span class="text-xs font-bold text-primary uppercase tracking-widest">Passo 3</span>
                                <h3 class="text-2xl font-extrabold text-[#0F172A]">
                                    Videochamada
                                </h3>
                                <p class="text-gray-500 leading-relaxed max-w-[280px] mx-auto">
                                    Conecte-se com seu médico no horário agendado através da nossa sala virtual
                                    criptografada.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <!-- Testimonials Carousel Section -->
        <section class="py-24 bg-[#fafafa] overflow-hidden">
            <div class="container mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header with Navigation -->
                <div class="flex items-end justify-between mb-12">
                    <h2 class="text-4xl font-extrabold text-[#0F172A] sm:text-5xl tracking-tight max-w-2xl">
                        Como nossos pacientes e parceiros se sentem
                    </h2>
                    <div class="flex gap-4">
                        <button @click="scrollCarousel('left')"
                            class="flex h-12 w-12 items-center justify-center rounded-full border border-gray-200 text-gray-400 transition-all hover:border-primary hover:text-primary">
                            <ChevronLeft class="h-6 w-6" />
                        </button>
                        <button @click="scrollCarousel('right')"
                            class="flex h-12 w-12 items-center justify-center rounded-full border border-gray-200 text-gray-400 transition-all hover:border-primary hover:text-primary">
                            <ChevronRight class="h-6 w-6" />
                        </button>
                    </div>
                </div>

                <!-- Carousel Container -->
                <div ref="carouselRef" class="flex gap-6 overflow-x-auto pb-8 snap-x no-scrollbar">
                    <div v-for="testimonial in testimonials" :key="testimonial.id"
                        class="min-w-[350px] md:min-w-[400px] h-[550px] snap-start">

                        <!-- Video Card Type -->
                        <div v-if="testimonial.type === 'video'"
                            class="h-full bg-primary/20 rounded-[32px] p-8 flex flex-col justify-between text-[#0F172A] relative overflow-hidden group">
                            <div class="relative h-64 w-full rounded-2xl overflow-hidden mb-6">
                                <video v-if="testimonial.video" :src="testimonial.video"
                                    class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                                    autoplay muted loop playsinline></video>
                                <img v-else :src="testimonial.image"
                                    class="w-full h-full object-cover opacity-80 group-hover:scale-105 transition-transform duration-500" />
                            </div>
                            <div>
                                <h4 class="text-xl font-bold mb-1">{{ testimonial.name }}</h4>
                                <p class="text-sm text-[#0F172A]/70">{{ testimonial.role }}</p>
                                <p class="text-sm text-[#0F172A]/70">{{ testimonial.company }}</p>
                            </div>
                            <button @click="testimonial.video ? openVideo(testimonial.video) : null"
                                class="mt-8 px-6 py-3 rounded-full border border-[#0F172A]/30 w-fit text-sm font-bold hover:bg-[#0F172A]/10 transition-colors">
                                {{ testimonial.linkText }}
                            </button>
                        </div>

                        <!-- Quote Card Type -->
                        <div v-else-if="testimonial.type === 'quote'"
                            class="h-full bg-primary/20 rounded-[32px] p-10 flex flex-col justify-between text-[#0F172A]">
                            <Quote class="h-10 w-10 text-[#0F172A]/20" />
                            <p class="text-2xl font-bold leading-tight">
                                "{{ testimonial.text }}"
                            </p>
                            <div class="mt-8 pt-8 border-t border-[#0F172A]/10">
                                <h4 class="text-xl font-bold mb-1">{{ testimonial.name }}</h4>
                                <p class="text-sm text-[#0F172A]/70">{{ testimonial.role }}</p>
                                <p class="text-sm text-primary font-medium mt-2">{{ testimonial.company }}</p>
                            </div>
                        </div>

                        <!-- Full Image Card Type -->
                        <div v-else-if="testimonial.type === 'image-full'"
                            class="h-full rounded-[32px] relative overflow-hidden group p-8 flex flex-col justify-between text-[#0F172A]">
                            <img :src="testimonial.image"
                                class="absolute inset-0 w-full h-full object-cover group-hover:scale-110 transition-transform duration-700" />
                            <div
                                class="absolute inset-0 bg-gradient-to-t from-primary/90 via-transparent to-transparent opacity-90">
                            </div>

                            <div class="relative z-10">
                                <h4 class="text-xl font-bold mb-1">{{ testimonial.name }}</h4>
                                <p class="text-sm text-[#0F172A]/80">{{ testimonial.role }}</p>
                                <p class="text-sm text-[#0F172A]/80">{{ testimonial.company }}</p>
                            </div>

                            <button
                                class="relative z-10 mt-auto flex items-center gap-2 text-sm font-bold hover:gap-3 transition-all">
                                {{ testimonial.linkText }}
                                <ArrowRight class="h-4 w-4" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </section>

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

        <footer class="mt-24 bg-sky-50">
            <div class="container mx-auto px-4 sm:px-6 lg:px-8">
                <div class="rounded-t-3xl px-6 py-16 sm:px-10 lg:px-16">
                    <div class="grid gap-12 lg:grid-cols-3">
                        <div>
                            <div class="flex items-center gap-3">
                                <AppLogoIcon class="h-10 w-10 text-primary" />
                                <span class="text-xl font-bold text-foreground">
                                    Telemedicina Para Todos
                                </span>
                            </div>
                            <p class="mt-4 text-sm text-muted-foreground leading-relaxed">
                                Oferecendo atendimento médico acessível, seguro e de qualidade,
                                onde quer que você esteja. Nossa missão é democratizar a saúde através da tecnologia.
                            </p>
                        </div>

                        <div>
                            <h3 class="text-lg font-bold text-foreground">Navegação</h3>
                            <ul class="mt-4 space-y-3 text-sm text-muted-foreground">
                                <li>
                                    <Link class="transition hover:text-primary" href="#especialidades">Especialidades
                                    </Link>
                                </li>
                                <li>
                                    <Link class="transition hover:text-primary" href="#como-funciona">Como Funciona
                                    </Link>
                                </li>
                                <li>
                                    <Link class="transition hover:text-primary" href="#telemedicina">Sobre Telemedicina
                                    </Link>
                                </li>
                                <li>
                                    <Link class="transition hover:text-primary" :href="login()">Entrar no Sistema</Link>
                                </li>
                            </ul>
                        </div>

                        <div>
                            <h3 class="text-lg font-bold text-foreground">Contato</h3>
                            <ul class="mt-4 space-y-3 text-sm text-muted-foreground">
                                <li class="flex items-center gap-2">
                                    <span class="font-medium text-foreground">Email:</span>
                                    <a href="mailto:audririan1@gmail.com"
                                        class="hover:text-primary transition-colors">audririan1@gmail.com</a>
                                </li>
                                <li class="flex items-center gap-2">
                                    <span class="font-medium text-foreground">Tel:</span>
                                    <a href="tel:+5581988964338" class="hover:text-primary transition-colors">(81) 9
                                        8896-4338</a>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <div class="mt-12 border-t border-slate-200 pt-6">
                        <p class="text-center text-xs text-muted-foreground">
                            © 2026 Telemedicina Para Todos. Todos os direitos reservados. |
                            <Link class="mx-1 underline decoration-dotted hover:text-primary" href="/terms">Termos de
                                Serviço</Link> |
                            <Link class="ml-1 underline decoration-dotted hover:text-primary" href="/privacy">Política
                                de
                                Privacidade</Link>
                        </p>
                    </div>
                </div>
            </div>
        </footer>

        <!-- Video Modal -->
        <transition enter-active-class="transition duration-300 ease-out" enter-from-class="opacity-0"
            enter-to-class="opacity-100" leave-active-class="transition duration-200 ease-in"
            leave-from-class="opacity-100" leave-to-class="opacity-0">
            <div v-if="showVideoModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/90"
                @click="closeVideo">
                <button @click="closeVideo"
                    class="absolute top-6 right-6 text-white hover:text-primary transition-colors z-[101]">
                    <X class="h-8 w-8" />
                </button>
                <div class="relative w-full max-w-5xl aspect-video rounded-2xl overflow-hidden shadow-2xl" @click.stop>
                    <video :src="activeVideoUrl" class="w-full h-full" controls autoplay></video>
                </div>
            </div>
        </transition>
    </div>
</template>

<style scoped>
.no-scrollbar::-webkit-scrollbar {
    display: none;
}

.no-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}

/* Glassmorphism effect for the play button */
.glass-effect {
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
}

/* Custom snap alignment */
.snap-inline-start {
    scroll-snap-align: start;
}
</style>
