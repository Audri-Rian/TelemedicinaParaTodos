<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { Button } from '@/components/ui/button';
import { useAuth, useRoleRoutes } from '@/composables/auth';
import { home, login } from '@/routes';
import * as doctorRoutes from '@/routes/doctor';
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowRight,
    Calendar,
    ChevronDown,
    ChevronLeft,
    ChevronRight,
    Facebook,
    Instagram,
    Menu,
    Pause,
    Play,
    Quote,
    Search,
    ShieldCheck,
    Twitter,
    Video,
    X,
    Youtube,
} from 'lucide-vue-next';
import { onMounted, onUnmounted, ref } from 'vue';

const { isAuthenticated, isDoctor, isPatient } = useAuth();
const { dashboardRoute, searchConsultationsRoute, appointmentsRoute } = useRoleRoutes();

const loginUrlWithRedirect = (path: string) => login({ query: { redirect: path } }).url;

const dashboardDestinationUrl = () => (isAuthenticated.value ? dashboardRoute().url : loginUrlWithRedirect('/dashboard'));

const schedulingDestinationUrl = () => {
    if (!isAuthenticated.value) {
        return loginUrlWithRedirect('/patient/search-consultations');
    }
    if (isPatient.value) {
        const r = searchConsultationsRoute();
        return r ? r.url : dashboardRoute().url;
    }
    if (isDoctor.value) {
        const r = appointmentsRoute();
        return r ? r.url : dashboardRoute().url;
    }
    return dashboardRoute().url;
};

const discoverySectionCtaUrl = () => dashboardDestinationUrl();

const heroVisionUrl = () => dashboardDestinationUrl();

const linkToAppPath = (path: string, doctorOnly = false) => {
    if (!isAuthenticated.value) {
        return loginUrlWithRedirect(path);
    }
    if (doctorOnly && !isDoctor.value) {
        return dashboardRoute().url;
    }
    return path;
};
const patientRegisterUrl = '/register/patient';
const doctorRegisterUrl = '/register/doctor';
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
        rootMargin: '0px 0px -100px 0px',
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
    appPath?: string;
    doctorOnly?: boolean;
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
                    {
                        title: 'Telemedicina Hub',
                        description:
                            'Transforme a maneira como você cuida dos pacientes e alcance o sucesso nos negócios com nossa solução abrangente para o setor de saúde, nativa de IA.',
                    },
                ],
            },
            {
                title: 'Soluções Integradas',
                items: [
                    {
                        title: 'Prontuário Eletrônico de Saúde',
                        description: 'Simplifique a prestação de cuidados personalizados com nosso EHR inteligente e intuitivo.',
                    },
                    {
                        title: 'Software de Faturamento e Gestão',
                        description: 'Envie solicitações de reembolso mais claras e rápidas com suporte de faturamento nativo.',
                    },
                    {
                        title: 'Engajamento do Paciente',
                        description: 'Melhore a experiência do paciente com ferramentas modernas de autoatendimento e comunicação.',
                    },
                ],
            },
            {
                title: 'Benefícios do Sistema',
                items: [
                    {
                        title: 'Apoio ao Cuidado Especializado',
                        description: 'Melhore os resultados para os pacientes monitorando e recebendo o pagamento pelos cuidados prestados.',
                    },
                    {
                        title: 'Recursos de IA Avançados',
                        description: 'Ferramentas com inteligência artificial que ajudam a impulsionar a eficiência e o desempenho clínico.',
                        tag: 'Novo',
                    },
                    {
                        title: 'Interoperabilidade Total',
                        description: 'Obtenha os dados de que precisa, quando precisar, em toda a nossa rede.',
                        appPath: doctorRoutes.integrations().url,
                        doctorOnly: true,
                    },
                ],
            },
        ],
        featuredCard: {
            title: 'Inovação em Saúde',
            description: 'Descubra como nossa IA está salvando vidas diariamente.',
            image: '/images/solutions_dropdown_bg.png',
            linkText: 'Saiba mais',
            linkHref: '#inovacao',
        },
    },
    servimos: {
        columns: [
            {
                title: 'Público Alvo',
                items: [{ title: 'Clínicas e Consultórios', description: 'Otimize seu atendimento com nossa plataforma de ponta.' }],
            },
            {
                title: 'Especialidades',
                items: [
                    { title: 'Médicos de Família', description: 'Gestão completa para o cuidado contínuo.' },
                    { title: 'Especialistas', description: 'Ferramentas específicas para cada área médica.' },
                    { title: 'Hospitais', description: 'Integração de larga escala para sistemas complexos.' },
                ],
            },
            {
                title: 'Serviços por Perfil',
                items: [
                    { title: 'Sistemas de Saúde', description: 'Soluções corporativas para grandes redes de atendimento.' },
                    { title: 'Grupos Médicos', description: 'Colaboração eficiente e compartilhamento de dados.' },
                ],
            },
            {
                title: 'Suporte Regional',
                items: [
                    { title: 'Atendimento Local', description: 'Encontre suporte na sua região.' },
                    { title: 'Casos de Sucesso', description: 'Veja como estamos transformando a saúde localmente.' },
                ],
            },
        ],
    },
    recursos: {
        columns: [
            {
                title: 'Portal de Conhecimento',
                items: [{ title: 'Blog de Saúde Digital', description: 'Fique por dentro das últimas tendências e notícias.' }],
            },
            {
                title: 'Aprendizado',
                items: [
                    { title: 'Webinars ao Vivo', description: 'Aprenda com líderes do setor sobre o futuro da saúde.' },
                    { title: 'Guias e E-books', description: 'Materiais educativos profundos sobre gestão e clínica.' },
                    { title: 'Pesquisas de Mercado', description: 'Insights valiosos baseados em dados reais.' },
                ],
            },
            {
                title: 'Centro de Suporte',
                items: [
                    { title: 'Base de Conhecimento', description: 'Respostas rápidas para suas dúvidas técnicas.' },
                    { title: 'Treinamento Online', description: 'Capacite sua equipe com nossos cursos.' },
                ],
            },
            {
                title: 'Desenvolvedores',
                items: [
                    {
                        title: 'Documentação de API',
                        description: 'Tudo o que você precisa para integrar com nosso sistema.',
                        appPath: '/api/documentation',
                    },
                    { title: 'Comunidade Dev', description: 'Troque experiências com outros desenvolvedores.' },
                ],
            },
        ],
    },
    empresa: {
        columns: [
            {
                title: 'Sobre a Empresa',
                items: [{ title: 'Nossa História', description: 'Conheça a trajetória da Telemedicina Para Todos rumo à inovação na saúde.' }],
            },
            {
                title: 'Valores e Missão',
                items: [
                    { title: 'Impacto Social', description: 'Como democratizamos o acesso à saúde de qualidade em todo o país.' },
                    { title: 'Inovação Ética', description: 'Nosso compromisso com a tecnologia a serviço da vida.' },
                ],
            },
            {
                title: 'Trabalhe Conosco',
                items: [
                    { title: 'Oportunidades', description: 'Faça parte de um time que está transformando o setor de saúde.' },
                    { title: 'Cultura e Vida', description: 'Conheça nosso ambiente de trabalho e nossos pilares culturais.' },
                ],
            },
            {
                title: 'Canais de Contato',
                items: [
                    { title: 'Atendimento Geral', description: 'Fale conosco para dúvidas, sugestões ou suporte.' },
                    { title: 'Assessoria de Imprensa', description: 'Recursos e contatos para jornalistas e mídia.' },
                ],
            },
        ],
    },
    telemedicina: {
        columns: [
            {
                title: 'O Universo Online',
                items: [{ title: 'O que é Telemedicina?', description: 'Entenda como o atendimento remoto revolucionou o cuidado médico moderno.' }],
            },
            {
                title: 'Experiência Digital',
                items: [
                    {
                        title: 'Segurança de Dados',
                        description: 'Como protegemos suas informações com os mais altos padrões de criptografia.',
                        tag: 'Seguro',
                    },
                    { title: 'Qualidade Clínica', description: 'A excelência do atendimento presencial, agora em qualquer lugar.' },
                ],
            },
            {
                title: 'Regulamentação',
                items: [
                    { title: 'Padrões CFM', description: 'Em conformidade total com as resoluções do Conselho Federal de Medicina.' },
                    { title: 'LGPD na Saúde', description: 'Garantia de privacidade e proteção de dados sensíveis conforme a lei.' },
                ],
            },
            {
                title: 'Futuro da Saúde',
                items: [
                    { title: 'IA e Diagnóstico', description: 'Como a inteligência artificial está auxiliando médicos em decisões precisas.' },
                    { title: 'Monitoramento Remoto', description: 'O acompanhamento contínuo do paciente através da tecnologia.' },
                ],
            },
        ],
        featuredCard: {
            title: 'O Futuro é Agora',
            description: 'Veja como a telemedicina está encurtando distâncias geográficas.',
            image: '/images/telemedicine_dropdown_bg.png',
            linkText: 'Ver detalhes',
            linkHref: '#futuro',
        },
    },
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
        behavior: 'smooth',
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
        linkText: 'Ver depoimento',
    },
    {
        id: 2,
        type: 'quote',
        text: 'A saúde muda de verdade quando a tecnologia deixa de ser uma barreira e passa a trabalhar pelas pessoas.',
        name: 'Carlos Mendes',
        role: 'CTO & Co-fundador',
        company: 'Inovação Saúde Digital',
    },
    {
        id: 3,
        type: 'image-full',
        image: 'https://images.unsplash.com/photo-1594824476967-48c8b964273f?auto=format&fit=crop&w=800&q=80',
        name: 'Ana Beatriz',
        role: 'Paciente',
        company: 'Cuidado Contínuo',
        linkText: 'Ouvir sua história',
    },
    {
        id: 4,
        type: 'quote',
        text: 'A segurança dos dados e o prontuário eletrônico integrado facilitam muito o acompanhamento dos pacientes crônicos.',
        name: 'Letícia Ferraz',
        role: 'Enfermeira Chefe',
        company: 'Hospital Regional',
    },
    {
        id: 5,
        type: 'video',
        video: '/images/video/Vídeo_Telemedicina_Para_Todos_Transformação.mp4',
        image: 'https://images.unsplash.com/photo-1559839734-2b71f1e3c770?auto=format&fit=crop&w=800&q=80',
        name: 'Dra. Helena Martins',
        role: 'Diretora Clínica',
        company: 'Rede Global de Saúde',
        linkText: 'Ver transformação',
    },
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
                    <div
                        class="hidden h-full shrink-0 items-center gap-3 text-base font-medium text-foreground/90 md:flex xl:gap-6 xl:text-lg 2xl:gap-8"
                    >
                        <!-- Soluções -->
                        <div
                            class="group relative flex h-full items-center"
                            @mouseenter="handleMouseEnter('solucoes')"
                            @mouseleave="handleMouseLeave"
                        >
                            <Link
                                href="#especialidades"
                                class="flex items-center gap-1 text-base font-medium whitespace-nowrap text-muted-foreground transition-colors hover:text-foreground xl:text-lg"
                            >
                                Soluções
                                <ChevronDown class="h-4 w-4 transition-transform duration-300" :class="{ 'rotate-180': activeMenu === 'solucoes' }" />
                            </Link>
                            <div
                                class="absolute bottom-4 left-0 h-[2px] bg-primary transition-all duration-300"
                                :class="activeMenu === 'solucoes' ? 'w-full' : 'w-0'"
                            ></div>
                        </div>

                        <!-- A quem servimos -->
                        <div
                            class="group relative flex h-full items-center"
                            @mouseenter="handleMouseEnter('servimos')"
                            @mouseleave="handleMouseLeave"
                        >
                            <Link
                                href="#a-quem-servimos"
                                class="flex items-center gap-1 text-base font-medium whitespace-nowrap text-muted-foreground transition-colors hover:text-foreground xl:text-lg"
                            >
                                A quem servimos
                                <ChevronDown class="h-4 w-4 transition-transform duration-300" :class="{ 'rotate-180': activeMenu === 'servimos' }" />
                            </Link>
                            <div
                                class="absolute bottom-4 left-0 h-[2px] bg-primary transition-all duration-300"
                                :class="activeMenu === 'servimos' ? 'w-full' : 'w-0'"
                            ></div>
                        </div>

                        <!-- Recursos -->
                        <div
                            class="group relative flex h-full items-center"
                            @mouseenter="handleMouseEnter('recursos')"
                            @mouseleave="handleMouseLeave"
                        >
                            <Link
                                href="#recursos"
                                class="flex items-center gap-1 text-base font-medium whitespace-nowrap text-muted-foreground transition-colors hover:text-foreground xl:text-lg"
                            >
                                Recursos
                                <ChevronDown class="h-4 w-4 transition-transform duration-300" :class="{ 'rotate-180': activeMenu === 'recursos' }" />
                            </Link>
                            <div
                                class="absolute bottom-4 left-0 h-[2px] bg-primary transition-all duration-300"
                                :class="activeMenu === 'recursos' ? 'w-full' : 'w-0'"
                            ></div>
                        </div>

                        <!-- Empresa -->
                        <div class="group relative flex h-full items-center" @mouseenter="handleMouseEnter('empresa')" @mouseleave="handleMouseLeave">
                            <Link
                                href="#empresa"
                                class="flex items-center gap-1 text-base font-medium whitespace-nowrap text-muted-foreground transition-colors hover:text-foreground xl:text-lg"
                            >
                                Empresa
                                <ChevronDown class="h-4 w-4 transition-transform duration-300" :class="{ 'rotate-180': activeMenu === 'empresa' }" />
                            </Link>
                            <div
                                class="absolute bottom-4 left-0 h-[2px] bg-primary transition-all duration-300"
                                :class="activeMenu === 'empresa' ? 'w-full' : 'w-0'"
                            ></div>
                        </div>

                        <!-- Telemedicina -->
                        <div
                            class="group relative flex h-full items-center"
                            @mouseenter="handleMouseEnter('telemedicina')"
                            @mouseleave="handleMouseLeave"
                        >
                            <Link
                                href="#telemedicina"
                                class="flex items-center gap-1 text-base font-medium whitespace-nowrap text-muted-foreground transition-colors hover:text-foreground xl:text-lg"
                            >
                                Telemedicina
                                <ChevronDown
                                    class="h-4 w-4 transition-transform duration-300"
                                    :class="{ 'rotate-180': activeMenu === 'telemedicina' }"
                                />
                            </Link>
                            <div
                                class="absolute bottom-4 left-0 h-[2px] bg-primary transition-all duration-300"
                                :class="activeMenu === 'telemedicina' ? 'w-full' : 'w-0'"
                            ></div>
                        </div>
                    </div>

                    <!-- Mega Menu Dropdown -->
                    <transition
                        enter-active-class="transition duration-200 ease-out"
                        enter-from-class="translate-y-1 opacity-0"
                        enter-to-class="translate-y-0 opacity-100"
                        leave-active-class="transition duration-150 ease-in"
                        leave-from-class="translate-y-0 opacity-100"
                        leave-to-class="translate-y-1 opacity-0"
                    >
                        <div
                            v-if="activeMenu && megaMenuItems[activeMenu as keyof typeof megaMenuItems]"
                            class="absolute top-[80px] left-0 w-full border-b bg-white shadow-xl"
                            @mouseenter="handleMouseEnter(activeMenu!)"
                            @mouseleave="handleMouseLeave"
                        >
                            <!-- Top Content Container -->
                            <div class="container mx-auto px-4 py-8 sm:px-6 lg:px-8">
                                <div class="grid grid-cols-1 gap-12 md:grid-cols-2 lg:grid-cols-4">
                                    <div
                                        v-for="column in megaMenuItems[activeMenu as keyof typeof megaMenuItems].columns"
                                        :key="column.title"
                                        class="space-y-6"
                                    >
                                        <!-- Column Title -->
                                        <h3 class="text-sm font-semibold tracking-wider text-muted-foreground/70 uppercase">
                                            {{ column.title }}
                                        </h3>

                                        <!-- Column Items -->
                                        <div class="space-y-8">
                                            <template v-for="item in column.items" :key="item.title">
                                                <Link
                                                    v-if="item.appPath"
                                                    :href="linkToAppPath(item.appPath, !!item.doctorOnly)"
                                                    class="group/item block cursor-pointer rounded-lg ring-offset-2 transition-colors outline-none hover:bg-muted/40 focus-visible:ring-2 focus-visible:ring-primary"
                                                >
                                                    <div class="flex items-center gap-2">
                                                        <h4 class="text-lg font-bold text-foreground transition-colors group-hover/item:text-primary">
                                                            {{ item.title }}
                                                        </h4>
                                                        <span
                                                            v-if="item.tag"
                                                            class="rounded-full border border-orange-200 bg-orange-100 px-2 py-0.5 text-[10px] font-bold text-orange-600 uppercase"
                                                        >
                                                            {{ item.tag }}
                                                        </span>
                                                    </div>
                                                    <p
                                                        class="mt-2 text-sm leading-relaxed text-muted-foreground transition-colors group-hover/item:text-foreground"
                                                    >
                                                        {{ item.description }}
                                                    </p>
                                                </Link>
                                                <div v-else class="group/item cursor-default rounded-lg py-0.5">
                                                    <div class="flex items-center gap-2">
                                                        <h4 class="text-lg font-bold text-foreground">
                                                            {{ item.title }}
                                                        </h4>
                                                        <span
                                                            v-if="item.tag"
                                                            class="rounded-full border border-orange-200 bg-orange-100 px-2 py-0.5 text-[10px] font-bold text-orange-600 uppercase"
                                                        >
                                                            {{ item.tag }}
                                                        </span>
                                                    </div>
                                                    <p class="mt-2 text-sm leading-relaxed text-muted-foreground">
                                                        {{ item.description }}
                                                    </p>
                                                </div>
                                            </template>
                                        </div>
                                    </div>

                                    <!-- Featured Card -->
                                    <div
                                        v-if="megaMenuItems[activeMenu as keyof typeof megaMenuItems].featuredCard"
                                        class="group/card relative h-80 overflow-hidden rounded-2xl"
                                    >
                                        <div
                                            class="absolute inset-0 bg-cover bg-center transition-transform duration-500 group-hover/card:scale-110"
                                            :style="{
                                                backgroundImage: `url(${megaMenuItems[activeMenu as keyof typeof megaMenuItems].featuredCard!.image})`,
                                            }"
                                        ></div>
                                        <div class="absolute inset-0 bg-gradient-to-t from-primary/90 via-primary/40 to-transparent"></div>
                                        <div class="relative flex h-full flex-col justify-end p-6 text-left text-white">
                                            <h4 class="text-xl font-bold">
                                                {{ megaMenuItems[activeMenu as keyof typeof megaMenuItems].featuredCard!.title }}
                                            </h4>
                                            <p class="mt-2 text-sm leading-snug text-white/90">
                                                {{ megaMenuItems[activeMenu as keyof typeof megaMenuItems].featuredCard!.description }}
                                            </p>
                                            <Link
                                                :href="megaMenuItems[activeMenu as keyof typeof megaMenuItems].featuredCard!.linkHref"
                                                class="mt-4 inline-flex items-center gap-2 text-sm font-bold hover:underline"
                                            >
                                                {{ megaMenuItems[activeMenu as keyof typeof megaMenuItems].featuredCard!.linkText }}
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
                                        <Link
                                            href="#como-funciona"
                                            class="group/link flex items-center gap-2 text-sm font-medium text-muted-foreground transition-colors hover:text-primary"
                                        >
                                            <span>Como funciona Telemedicina Para Todos</span>
                                            <ArrowRight class="h-4 w-4 transition-transform group-hover/link:translate-x-1" />
                                        </Link>

                                        <!-- Right Side Social Icons -->
                                        <div class="flex items-center gap-4">
                                            <a href="#" class="text-muted-foreground transition-colors hover:text-primary" aria-label="X (Twitter)">
                                                <Twitter class="h-5 w-5" />
                                            </a>
                                            <a href="#" class="text-muted-foreground transition-colors hover:text-primary" aria-label="Instagram">
                                                <Instagram class="h-5 w-5" />
                                            </a>
                                            <a href="#" class="text-muted-foreground transition-colors hover:text-primary" aria-label="Facebook">
                                                <Facebook class="h-5 w-5" />
                                            </a>
                                            <a href="#" class="text-muted-foreground transition-colors hover:text-primary" aria-label="YouTube">
                                                <Youtube class="h-5 w-5" />
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </transition>

                    <!-- Desktop Auth Buttons & Search -->
                    <div class="hidden shrink-0 items-center gap-2 md:flex lg:gap-3 xl:gap-4">
                        <!-- Search Icon -->
                        <button type="button" class="hidden text-muted-foreground hover:text-foreground xl:block">
                            <Search class="h-5 w-5" />
                        </button>

                        <Link
                            v-if="isAuthenticated"
                            :href="dashboardRoute()"
                            class="rounded-full border-2 border-primary bg-primary px-4 py-2 text-sm font-bold text-primary-foreground shadow-sm transition-colors hover:bg-primary/90"
                        >
                            Dashboard
                        </Link>
                        <template v-else>
                            <Link :href="login()">
                                <span
                                    class="inline-flex items-center justify-center rounded-full border-2 border-primary bg-primary/10 px-4 py-2 text-sm font-bold text-primary shadow-sm transition-colors hover:bg-primary/20"
                                >
                                    Entrar
                                </span>
                            </Link>
                            <Link :href="patientRegisterUrl">
                                <Button
                                    size="sm"
                                    class="h-9 rounded-full bg-primary px-4 whitespace-nowrap text-primary-foreground hover:bg-primary/90 sm:px-5"
                                >
                                    Registre-se para pacientes
                                </Button>
                            </Link>
                            <Link :href="doctorRegisterUrl">
                                <Button
                                    size="sm"
                                    variant="outline"
                                    class="h-9 rounded-full border-2 border-primary bg-transparent px-4 whitespace-nowrap text-primary hover:bg-primary/10 sm:px-5"
                                >
                                    Faça parte da equipe
                                </Button>
                            </Link>
                        </template>
                    </div>

                    <!-- Mobile Menu Button -->
                    <button
                        @click="isMenuOpen = !isMenuOpen"
                        class="p-2 text-muted-foreground hover:text-foreground md:hidden"
                        aria-label="Toggle menu"
                    >
                        <Menu v-if="!isMenuOpen" class="h-6 w-6" />
                        <X v-else class="h-6 w-6" />
                    </button>
                </div>

                <!-- Mobile Menu -->
                <div v-if="isMenuOpen" class="border-t bg-white md:hidden">
                    <div class="space-y-1 px-2 pt-2 pb-3">
                        <Link
                            href="#especialidades"
                            class="block rounded-md px-3 py-2 text-base font-medium text-muted-foreground hover:bg-muted hover:text-foreground"
                            @click="isMenuOpen = false"
                        >
                            Soluções
                        </Link>
                        <Link
                            href="#a-quem-servimos"
                            class="block rounded-md px-3 py-2 text-base font-medium text-muted-foreground hover:bg-muted hover:text-foreground"
                            @click="isMenuOpen = false"
                        >
                            A quem servimos
                        </Link>
                        <Link
                            href="#recursos"
                            class="block rounded-md px-3 py-2 text-base font-medium text-muted-foreground hover:bg-muted hover:text-foreground"
                            @click="isMenuOpen = false"
                        >
                            Recursos
                        </Link>
                        <Link
                            href="#empresa"
                            class="block rounded-md px-3 py-2 text-base font-medium text-muted-foreground hover:bg-muted hover:text-foreground"
                            @click="isMenuOpen = false"
                        >
                            Empresa
                        </Link>
                        <Link
                            href="#telemedicina"
                            class="block rounded-md px-3 py-2 text-base font-medium text-muted-foreground hover:bg-muted hover:text-foreground"
                            @click="isMenuOpen = false"
                        >
                            Telemedicina
                        </Link>
                        <div class="mt-4 space-y-2 border-t pt-4">
                            <Link
                                v-if="isAuthenticated"
                                :href="dashboardRoute()"
                                class="block rounded-md px-3 py-2 text-base font-medium text-muted-foreground hover:bg-muted hover:text-foreground"
                                @click="isMenuOpen = false"
                            >
                                Dashboard
                            </Link>
                            <template v-else>
                                <Link
                                    :href="login()"
                                    class="block rounded-md px-3 py-2 text-base font-medium text-muted-foreground hover:bg-muted hover:text-foreground"
                                    @click="isMenuOpen = false"
                                >
                                    Entrar
                                </Link>
                                <Link
                                    :href="patientRegisterUrl"
                                    class="block rounded-md bg-primary px-3 py-2 text-center text-base font-medium text-primary-foreground hover:bg-primary/90"
                                    @click="isMenuOpen = false"
                                >
                                    Registre-se para pacientes
                                </Link>
                                <Link
                                    :href="doctorRegisterUrl"
                                    class="block rounded-md border border-primary px-3 py-2 text-center text-base font-medium text-primary hover:bg-primary/10"
                                    @click="isMenuOpen = false"
                                >
                                    Faça parte da equipe
                                </Link>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Hero Section Redesigned -->
        <section
            id="telemedicina"
            tabindex="-1"
            class="relative flex min-h-[700px] w-full scroll-mt-20 flex-col justify-center overflow-hidden pt-32 pb-16"
        >
            <!-- Background Video with Premium Overlay -->
            <div class="absolute inset-0 z-0">
                <video
                    ref="heroVideoRef"
                    src="/images/video/Criação_de_Vídeo_Institucional_Telemedicina.mp4"
                    class="h-full w-full object-cover"
                    autoplay
                    muted
                    loop
                    playsinline
                ></video>
                <div class="absolute inset-0 bg-gradient-to-r from-black/80 via-black/40 to-transparent"></div>
                <div class="absolute inset-0 bg-black/20"></div>
            </div>

            <div class="relative z-10 container mx-auto flex h-full flex-1 flex-col px-4 sm:px-6 lg:px-8">
                <!-- Top Section: Text Content -->
                <div class="flex flex-1 items-center py-12">
                    <div class="max-w-2xl space-y-6 text-left">
                        <div
                            id="inovacao"
                            tabindex="-1"
                            class="inline-flex scroll-mt-24 items-center gap-2 rounded-full border border-white/20 bg-white/10 px-4 py-1.5 text-xs font-bold text-primary backdrop-blur-md"
                        >
                            <span class="relative flex h-2 w-2">
                                <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-primary opacity-75"></span>
                                <span class="relative inline-flex h-2 w-2 rounded-full bg-primary"></span>
                            </span>
                            Atendimento Médico Online
                        </div>
                        <h1 class="text-4xl leading-[1.1] font-extrabold tracking-tight text-white sm:text-5xl lg:text-6xl">
                            Saúde de qualidade que
                            <span class="mt-2 block text-primary">chega até você</span>
                        </h1>
                        <p class="max-w-xl text-base leading-relaxed font-medium text-gray-200 opacity-90 sm:text-lg">
                            Conecte-se com médicos especialistas de qualquer lugar. A telemedicina elimina distâncias, reduz tempo de espera e oferece
                            cuidado médico profissional sem sair de casa. Consultas seguras, rápidas e humanizadas ao seu alcance.
                        </p>
                    </div>
                </div>

                <!-- Bottom Section: Actions Row -->
                <div class="mt-auto flex items-center justify-between gap-4 pt-8">
                    <!-- Left Side Buttons -->
                    <div class="flex flex-wrap items-center gap-4">
                        <Link :href="schedulingDestinationUrl()">
                            <Button
                                size="lg"
                                class="h-12 min-h-12 rounded-full bg-white px-8 text-base font-bold text-gray-900 shadow-xl transition-all hover:scale-105 hover:bg-gray-100"
                            >
                                Agendar agora
                            </Button>
                        </Link>

                        <Link
                            :href="heroVisionUrl()"
                            class="group inline-flex h-12 min-h-12 items-center justify-center gap-3 rounded-full border-2 border-white/30 bg-white/10 px-8 text-base font-bold text-white backdrop-blur-md transition-all hover:border-white/50 hover:bg-white/20"
                        >
                            Conheça nossa visão
                            <ArrowRight class="h-5 w-5 transition-transform group-hover:translate-x-1" />
                        </Link>
                    </div>

                    <!-- Right Side: Play/Pause Button -->
                    <div class="flex items-center">
                        <button
                            @click="toggleVideoPlayback"
                            class="group flex h-16 w-16 items-center justify-center rounded-full border-2 border-white/40 bg-white/5 text-white shadow-2xl backdrop-blur-md transition-all hover:scale-110 hover:border-white hover:bg-white/20"
                            :aria-label="isVideoPlaying ? 'Pausar vídeo' : 'Reproduzir vídeo'"
                        >
                            <Play v-if="!isVideoPlaying" class="ml-1 h-7 w-7 fill-white transition-transform group-hover:scale-110" />
                            <Pause v-else class="h-7 w-7 fill-white transition-transform group-hover:scale-110" />
                        </button>
                    </div>
                </div>
            </div>
        </section>

        <!-- Especialidades Section Redesigned -->
        <section
            id="especialidades"
            ref="specialtiesSectionRef"
            tabindex="-1"
            class="scroll-mt-20 overflow-hidden bg-[#fafafa] pt-16 pb-24 sm:pt-20 sm:pb-32"
        >
            <div class="container mx-auto px-4 sm:px-6 lg:px-8 lg:pl-32">
                <div class="flex flex-col gap-16 lg:flex-row lg:gap-40">
                    <!-- Left Side: Sticky Title -->
                    <div
                        ref="stickyTitleRef"
                        class="h-fit space-y-8 transition-transform duration-75 ease-out will-change-transform lg:w-1/3"
                        :style="{ transform: `translateY(${titleTranslateY}px)` }"
                    >
                        <div class="space-y-4">
                            <h2
                                id="a-quem-servimos"
                                tabindex="-1"
                                class="scroll-mt-24 text-4xl leading-[1.1] font-extrabold tracking-tight text-gray-900 sm:text-5xl lg:text-6xl"
                            >
                                Descubra por que a<br />
                                <span class="text-primary italic">Telemedicina Para Todos</span><br />
                                é a escolha certa
                            </h2>
                            <p class="max-w-md text-lg leading-relaxed text-gray-600">
                                Oferecemos excelência clínica e tecnológica para garantir que você tenha o melhor atendimento, onde quer que esteja.
                            </p>
                        </div>
                    </div>

                    <!-- Right Side: Cards List -->
                    <div class="space-y-16 lg:w-2/3 lg:pt-32">
                        <!-- Card 1: Clínica Geral -->
                        <div
                            ref="card1Ref"
                            :class="[
                                'group relative max-w-[540px] overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-[0_20px_50px_rgba(0,0,0,0.04)] transition-all duration-1000',
                                card1Visible ? 'translate-y-0 opacity-100' : 'translate-y-12 opacity-0',
                                'hover:-translate-y-2 hover:shadow-[0_40px_80px_rgba(0,0,0,0.06)]',
                            ]"
                            style="transition-delay: 100ms"
                        >
                            <!-- Image Header -->
                            <div class="h-64 w-full overflow-hidden sm:h-80">
                                <img
                                    src="/images/specialties/clinica_geral.png"
                                    alt="Clínica Geral"
                                    class="h-full w-full object-cover transition-transform duration-700 group-hover:scale-110"
                                />
                            </div>
                            <div class="p-8 sm:p-12">
                                <div class="space-y-6">
                                    <h3 class="text-3xl font-extrabold tracking-tight text-gray-900 sm:text-4xl">
                                        Clínica Geral: Sua porta de entrada para uma saúde melhor
                                    </h3>
                                    <p class="max-w-2xl text-lg leading-relaxed text-gray-600">
                                        Nossos clínicos gerais oferecem atendimento integral e preventivo, coordenando seu cuidado com humanização e
                                        precisão técnica, tudo no conforto da sua casa.
                                    </p>
                                    <div class="pt-4">
                                        <Link
                                            :href="discoverySectionCtaUrl()"
                                            class="group/link inline-flex items-center gap-3 text-lg font-bold text-primary transition-all hover:gap-5"
                                        >
                                            Descubra como cuidamos de você
                                            <ArrowRight class="h-5 w-5 transition-transform" />
                                        </Link>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Card 2: Saúde Mental -->
                        <div
                            ref="card2Ref"
                            :class="[
                                'group relative max-w-[540px] overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-[0_20px_50px_rgba(0,0,0,0.04)] transition-all duration-1000',
                                card2Visible ? 'translate-y-0 opacity-100' : 'translate-y-12 opacity-0',
                                'hover:-translate-y-2 hover:shadow-[0_40px_80px_rgba(0,0,0,0.06)]',
                            ]"
                            style="transition-delay: 200ms"
                        >
                            <!-- Image Header -->
                            <div class="h-64 w-full overflow-hidden sm:h-80">
                                <img
                                    src="/images/specialties/saude_mental.png"
                                    alt="Saúde Mental"
                                    class="h-full w-full object-cover transition-transform duration-700 group-hover:scale-110"
                                />
                            </div>
                            <div class="p-8 sm:p-12">
                                <div class="space-y-6">
                                    <h3 class="text-3xl font-extrabold tracking-tight text-gray-900 sm:text-4xl">
                                        Saúde Mental: Equilíbrio e suporte emocional onde você estiver
                                    </h3>
                                    <p class="max-w-2xl text-lg leading-relaxed text-gray-600">
                                        Acesso rápido a psicólogos e psiquiatras qualificados. Um ambiente seguro e acolhedor para tratar ansiedade,
                                        depressão e outros desafios da vida moderna.
                                    </p>
                                    <div class="pt-4">
                                        <Link
                                            :href="discoverySectionCtaUrl()"
                                            class="group/link inline-flex items-center gap-3 text-lg font-bold text-primary transition-all hover:gap-5"
                                        >
                                            Inicie sua jornada de autocuidado
                                            <ArrowRight class="h-5 w-5 transition-transform" />
                                        </Link>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Card 3: Especialidades Pediátricas -->
                        <div
                            ref="card3Ref"
                            :class="[
                                'group relative max-w-[540px] overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-[0_20px_50px_rgba(0,0,0,0.04)] transition-all duration-1000',
                                card3Visible ? 'translate-y-0 opacity-100' : 'translate-y-12 opacity-0',
                                'hover:-translate-y-2 hover:shadow-[0_40px_80px_rgba(0,0,0,0.06)]',
                            ]"
                            style="transition-delay: 300ms"
                        >
                            <!-- Image Header -->
                            <div class="h-64 w-full overflow-hidden sm:h-80">
                                <img
                                    src="/images/specialties/pediatria.png"
                                    alt="Pediatria"
                                    class="h-full w-full object-cover transition-transform duration-700 group-hover:scale-110"
                                />
                            </div>
                            <div class="p-8 sm:p-12">
                                <div class="space-y-6">
                                    <h3 class="text-3xl font-extrabold tracking-tight text-gray-900 sm:text-4xl">
                                        Pediatria: O melhor cuidado para quem você mais ama
                                    </h3>
                                    <p class="max-w-2xl text-lg leading-relaxed text-gray-600">
                                        Consultas pediátricas humanizadas e suporte aos pais em tempo real. Especialistas em todas as fases do
                                        desenvolvimento infantil.
                                    </p>
                                    <div class="pt-4">
                                        <Link
                                            :href="discoverySectionCtaUrl()"
                                            class="group/link inline-flex items-center gap-3 text-lg font-bold text-primary transition-all hover:gap-5"
                                        >
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
        <section id="como-funciona" tabindex="-1" class="scroll-mt-20 bg-white py-24 sm:py-32">
            <div class="container mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-20 space-y-4 text-center">
                    <h2 class="text-4xl font-extrabold tracking-tight text-[#0F172A] sm:text-5xl">Como funciona sua consulta online</h2>
                    <p class="mx-auto max-w-2xl text-lg text-gray-500">Cuide da sua saúde sem sair de casa em apenas 3 passos simples.</p>
                </div>

                <!-- Steps Container -->
                <div class="relative mx-auto max-w-5xl">
                    <!-- Dashed Connection Line (Desktop only) -->
                    <div class="absolute top-12 right-[15%] left-[15%] z-0 hidden h-[2px] border-t-2 border-dashed border-gray-200 lg:block"></div>

                    <div class="relative z-10 grid gap-12 lg:grid-cols-3">
                        <!-- Step 1: Escolha e Agende -->
                        <div class="group flex flex-col items-center text-center">
                            <div class="relative mb-8">
                                <div
                                    class="flex h-24 w-24 items-center justify-center rounded-2xl bg-[#EBF3FF] shadow-sm transition-transform duration-300 group-hover:scale-110"
                                >
                                    <Calendar class="h-10 w-10 text-primary" />
                                </div>
                            </div>
                            <div class="space-y-3">
                                <span class="text-xs font-bold tracking-widest text-primary uppercase">Passo 1</span>
                                <h3 class="text-2xl font-extrabold text-[#0F172A]">Escolha e Agende</h3>
                                <p class="mx-auto max-w-[280px] leading-relaxed text-gray-500">
                                    Selecione a especialidade e o melhor horário disponível na nossa agenda inteligente.
                                </p>
                            </div>
                        </div>

                        <!-- Step 2: Pagamento Seguro -->
                        <div class="group flex flex-col items-center text-center">
                            <div class="relative mb-8">
                                <div
                                    class="flex h-24 w-24 items-center justify-center rounded-2xl bg-[#EBF3FF] shadow-sm transition-transform duration-300 group-hover:scale-110"
                                >
                                    <ShieldCheck class="h-10 w-10 text-primary" />
                                </div>
                            </div>
                            <div class="space-y-3">
                                <span class="text-xs font-bold tracking-widest text-primary uppercase">Passo 2</span>
                                <h3 class="text-2xl font-extrabold text-[#0F172A]">Pagamento Seguro</h3>
                                <p class="mx-auto max-w-[280px] leading-relaxed text-gray-500">
                                    Realize o pagamento de forma transparente e totalmente segura através da plataforma.
                                </p>
                            </div>
                        </div>

                        <!-- Step 3: Videochamada -->
                        <div class="group flex flex-col items-center text-center">
                            <div class="relative mb-8">
                                <div
                                    class="flex h-24 w-24 items-center justify-center rounded-2xl bg-[#EBF3FF] shadow-sm transition-transform duration-300 group-hover:scale-110"
                                >
                                    <Video class="h-10 w-10 text-primary" />
                                </div>
                            </div>
                            <div class="space-y-3">
                                <span class="text-xs font-bold tracking-widest text-primary uppercase">Passo 3</span>
                                <h3 class="text-2xl font-extrabold text-[#0F172A]">Videochamada</h3>
                                <p class="mx-auto max-w-[280px] leading-relaxed text-gray-500">
                                    Conecte-se com seu médico no horário agendado através da nossa sala virtual criptografada.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <!-- Testimonials Carousel Section -->
        <section id="recursos" tabindex="-1" class="scroll-mt-20 overflow-hidden bg-[#fafafa] py-24">
            <div class="container mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header with Navigation -->
                <div class="mb-12 flex items-end justify-between">
                    <h2 id="futuro" tabindex="-1" class="max-w-2xl scroll-mt-24 text-4xl font-extrabold tracking-tight text-[#0F172A] sm:text-5xl">
                        Como nossos pacientes e parceiros se sentem
                    </h2>
                    <div class="flex gap-4">
                        <button
                            @click="scrollCarousel('left')"
                            class="flex h-12 w-12 items-center justify-center rounded-full border border-gray-200 text-gray-400 transition-all hover:border-primary hover:text-primary"
                        >
                            <ChevronLeft class="h-6 w-6" />
                        </button>
                        <button
                            @click="scrollCarousel('right')"
                            class="flex h-12 w-12 items-center justify-center rounded-full border border-gray-200 text-gray-400 transition-all hover:border-primary hover:text-primary"
                        >
                            <ChevronRight class="h-6 w-6" />
                        </button>
                    </div>
                </div>

                <!-- Carousel Container -->
                <div ref="carouselRef" class="no-scrollbar flex snap-x gap-6 overflow-x-auto pb-8">
                    <div v-for="testimonial in testimonials" :key="testimonial.id" class="h-[550px] min-w-[350px] snap-start md:min-w-[400px]">
                        <!-- Video Card Type -->
                        <div
                            v-if="testimonial.type === 'video'"
                            class="group relative flex h-full flex-col justify-between overflow-hidden rounded-[32px] bg-primary/20 p-8 text-[#0F172A]"
                        >
                            <div class="relative mb-6 h-64 w-full overflow-hidden rounded-2xl">
                                <video
                                    v-if="testimonial.video"
                                    :src="testimonial.video"
                                    class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
                                    autoplay
                                    muted
                                    loop
                                    playsinline
                                ></video>
                                <img
                                    v-else
                                    :src="testimonial.image"
                                    class="h-full w-full object-cover opacity-80 transition-transform duration-500 group-hover:scale-105"
                                />
                            </div>
                            <div>
                                <h4 class="mb-1 text-xl font-bold">{{ testimonial.name }}</h4>
                                <p class="text-sm text-[#0F172A]/70">{{ testimonial.role }}</p>
                                <p class="text-sm text-[#0F172A]/70">{{ testimonial.company }}</p>
                            </div>
                            <button
                                @click="testimonial.video ? openVideo(testimonial.video) : null"
                                class="mt-8 w-fit rounded-full border border-[#0F172A]/30 px-6 py-3 text-sm font-bold transition-colors hover:bg-[#0F172A]/10"
                            >
                                {{ testimonial.linkText }}
                            </button>
                        </div>

                        <!-- Quote Card Type -->
                        <div
                            v-else-if="testimonial.type === 'quote'"
                            class="flex h-full flex-col justify-between rounded-[32px] bg-primary/20 p-10 text-[#0F172A]"
                        >
                            <Quote class="h-10 w-10 text-[#0F172A]/20" />
                            <p class="text-2xl leading-tight font-bold">"{{ testimonial.text }}"</p>
                            <div class="mt-8 border-t border-[#0F172A]/10 pt-8">
                                <h4 class="mb-1 text-xl font-bold">{{ testimonial.name }}</h4>
                                <p class="text-sm text-[#0F172A]/70">{{ testimonial.role }}</p>
                                <p class="mt-2 text-sm font-medium text-primary">{{ testimonial.company }}</p>
                            </div>
                        </div>

                        <!-- Full Image Card Type -->
                        <div
                            v-else-if="testimonial.type === 'image-full'"
                            class="group relative flex h-full flex-col justify-between overflow-hidden rounded-[32px] p-8 text-[#0F172A]"
                        >
                            <img
                                :src="testimonial.image"
                                class="absolute inset-0 h-full w-full object-cover transition-transform duration-700 group-hover:scale-110"
                            />
                            <div class="absolute inset-0 bg-gradient-to-t from-primary/90 via-transparent to-transparent opacity-90"></div>

                            <div class="relative z-10">
                                <h4 class="mb-1 text-xl font-bold">{{ testimonial.name }}</h4>
                                <p class="text-sm text-[#0F172A]/80">{{ testimonial.role }}</p>
                                <p class="text-sm text-[#0F172A]/80">{{ testimonial.company }}</p>
                            </div>

                            <button class="relative z-10 mt-auto flex items-center gap-2 text-sm font-bold transition-all hover:gap-3">
                                {{ testimonial.linkText }}
                                <ArrowRight class="h-4 w-4" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <div id="agendar" tabindex="-1" class="container mx-auto mt-24 scroll-mt-20 px-4 sm:px-6 lg:px-8">
            <div class="rounded-3xl bg-white py-16 text-center">
                <h2 class="text-3xl font-bold text-foreground">Pronto para cuidar da sua saúde?</h2>
                <p class="mx-auto mt-4 max-w-2xl text-base text-muted-foreground">
                    Agende sua consulta hoje mesmo e experimente a conveniência de um atendimento médico de qualidade, sem sair de casa.
                </p>
                <Button as-child size="lg" class="mt-8 rounded-full px-8 font-semibold">
                    <Link :href="schedulingDestinationUrl()"> Agendar consulta agora </Link>
                </Button>
            </div>
        </div>

        <footer id="empresa" tabindex="-1" class="mt-24 scroll-mt-20 bg-sky-50">
            <div class="container mx-auto px-4 sm:px-6 lg:px-8">
                <div class="rounded-t-3xl px-6 py-16 sm:px-10 lg:px-16">
                    <div class="grid gap-12 lg:grid-cols-3">
                        <div>
                            <div class="flex items-center gap-3">
                                <AppLogoIcon class="h-10 w-10 text-primary" />
                                <span class="text-xl font-bold text-foreground"> Telemedicina Para Todos </span>
                            </div>
                            <p class="mt-4 text-sm leading-relaxed text-muted-foreground">
                                Oferecendo atendimento médico acessível, seguro e de qualidade, onde quer que você esteja. Nossa missão é democratizar
                                a saúde através da tecnologia.
                            </p>
                        </div>

                        <div>
                            <h3 class="text-lg font-bold text-foreground">Navegação</h3>
                            <ul class="mt-4 space-y-3 text-sm text-muted-foreground">
                                <li>
                                    <Link class="transition hover:text-primary" :href="schedulingDestinationUrl()"> Especialidades </Link>
                                </li>
                                <li>
                                    <Link class="transition hover:text-primary" :href="dashboardDestinationUrl()"> Como funciona </Link>
                                </li>
                                <li>
                                    <Link class="transition hover:text-primary" href="#telemedicina"> Sobre telemedicina </Link>
                                </li>
                                <li>
                                    <Link class="transition hover:text-primary" :href="dashboardDestinationUrl()"> Entrar no sistema </Link>
                                </li>
                            </ul>
                        </div>

                        <div>
                            <h3 class="text-lg font-bold text-foreground">Contato</h3>
                            <ul class="mt-4 space-y-3 text-sm text-muted-foreground">
                                <li class="flex items-center gap-2">
                                    <span class="font-medium text-foreground">Email:</span>
                                    <a href="mailto:audririan1@gmail.com" class="transition-colors hover:text-primary">audririan1@gmail.com</a>
                                </li>
                                <li class="flex items-center gap-2">
                                    <span class="font-medium text-foreground">Tel:</span>
                                    <a href="tel:+5581988964338" class="transition-colors hover:text-primary">(81) 9 8896-4338</a>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <div class="mt-12 border-t border-slate-200 pt-6">
                        <p class="text-center text-xs text-muted-foreground">
                            © 2026 Telemedicina Para Todos. Todos os direitos reservados. |
                            <Link class="mx-1 underline decoration-dotted hover:text-primary" href="/terms">Termos de Serviço</Link> |
                            <Link class="ml-1 underline decoration-dotted hover:text-primary" href="/privacy">Política de Privacidade</Link>
                        </p>
                    </div>
                </div>
            </div>
        </footer>

        <!-- Video Modal -->
        <transition
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-200 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="showVideoModal" class="fixed inset-0 z-[100] flex items-center justify-center bg-black/90 p-4" @click="closeVideo">
                <button @click="closeVideo" class="absolute top-6 right-6 z-[101] text-white transition-colors hover:text-primary">
                    <X class="h-8 w-8" />
                </button>
                <div class="relative aspect-video w-full max-w-5xl overflow-hidden rounded-2xl shadow-2xl" @click.stop>
                    <video :src="activeVideoUrl" class="h-full w-full" controls autoplay></video>
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
