<script setup lang="ts">
import EmptyState from '@/components/EmptyState.vue';
import DashboardTour from '@/components/onboarding/DashboardTour.vue';
import WelcomeScreen from '@/components/onboarding/WelcomeScreen.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Input } from '@/components/ui/input';
import { useRouteGuard } from '@/composables/auth';
import { useInitials } from '@/composables/useInitials';
import AppLayout from '@/layouts/AppLayout.vue';
import * as patientRoutes from '@/routes/patient';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    Activity,
    Calendar,
    CheckCircle2,
    ChevronDown,
    Clock,
    FileCheck,
    FileText,
    HelpCircle,
    MoreVertical,
    RotateCcw,
    Search,
    Users,
    Video,
} from 'lucide-vue-next';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';

// Ref para o container de médicos
const doctorsContainer = ref<HTMLElement | null>(null);
const isDragging = ref(false);
const startX = ref(0);
const scrollLeft = ref(0);

interface UpcomingAppointment {
    id: string;
    doctor_name: string;
    doctor_specialty?: string;
    doctor_image?: string;
    scheduled_at: string;
    scheduled_date?: string;
    scheduled_time?: string;
    duration?: string;
    status: string;
    status_class: string;
}

interface RecentAppointment {
    id: string;
    doctor_name: string;
    scheduled_at: string;
    status: string;
}

interface Doctor {
    id: string;
    name: string;
    specialty: string;
    image?: string;
}

interface Reminder {
    id: string;
    title: string;
    time?: string;
    message?: string;
    icon: 'medication' | 'exam' | 'check';
    completed?: boolean;
}

interface HealthTip {
    id: string;
    title: string;
    description: string;
    image?: string;
}

interface Stats {
    total: number;
    completed: number;
}

interface Onboarding {
    showWelcome?: boolean;
    showTour?: boolean;
    userName?: string;
}

interface Props {
    upcomingAppointments?: UpcomingAppointment[];
    recentAppointments?: RecentAppointment[];
    stats?: Stats;
    doctors?: Doctor[];
    reminders?: Reminder[];
    healthTips?: HealthTip[];
    onboarding?: Onboarding;
}

const props = withDefaults(defineProps<Props>(), {
    upcomingAppointments: () => [],
    recentAppointments: () => [],
    stats: () => ({ total: 0, completed: 0 }),
    doctors: () => [],
    reminders: () => [],
    healthTips: () => [],
    onboarding: () => ({
        showWelcome: false,
        showTour: false,
        userName: '',
    }),
});

const showWelcomeScreen = ref(props.onboarding?.showWelcome ?? false);
const showTour = ref(props.onboarding?.showTour ?? false);

// Atualizar estados quando props mudarem
watch(
    () => props.onboarding?.showWelcome,
    (newValue) => {
        showWelcomeScreen.value = newValue ?? false;
    },
);

watch(
    () => props.onboarding?.showTour,
    (newValue) => {
        showTour.value = newValue ?? false;
    },
);

const handleStartTour = () => {
    showWelcomeScreen.value = false;
    showTour.value = true;
};

const handleWelcomeClose = () => {
    showWelcomeScreen.value = false;
};

const handleTourComplete = () => {
    showTour.value = false;
};

const handleTourClose = () => {
    showTour.value = false;
};

const { canAccessPatientRoute } = useRouteGuard();
const { getInitials } = useInitials();

// Obter usuário autenticado
const page = usePage();
const authUser = computed(() => {
    const props = page.props as any;
    return props?.auth?.user || null;
});

// Estados locais
const searchQuery = ref('');
const specialtyFilter = ref('');
const insuranceFilter = ref('');

// Funções para drag scroll
const handleMouseDown = (e: MouseEvent) => {
    if (!doctorsContainer.value) return;
    // Verificar se o clique foi em um link ou botão
    const target = e.target as HTMLElement;
    if (target.closest('a') || target.closest('button')) {
        return; // Não iniciar drag se clicou em um link/botão
    }
    isDragging.value = true;
    startX.value = e.pageX - doctorsContainer.value.offsetLeft;
    scrollLeft.value = doctorsContainer.value.scrollLeft;
    doctorsContainer.value.style.cursor = 'grabbing';
    e.preventDefault();
};

const handleMouseLeave = () => {
    if (!doctorsContainer.value) return;
    isDragging.value = false;
    doctorsContainer.value.style.cursor = 'grab';
};

const handleMouseUp = () => {
    if (!doctorsContainer.value) return;
    isDragging.value = false;
    doctorsContainer.value.style.cursor = 'grab';
};

const handleMouseMove = (e: MouseEvent) => {
    if (!isDragging.value || !doctorsContainer.value) return;
    e.preventDefault();
    const x = e.pageX - doctorsContainer.value.offsetLeft;
    const walk = (x - startX.value) * 2; // Velocidade do scroll
    doctorsContainer.value.scrollLeft = scrollLeft.value - walk;
};

// Verificar acesso ao montar componente
onMounted(() => {
    canAccessPatientRoute();
    // Adicionar event listener global para mouseup (caso solte fora do container)
    document.addEventListener('mouseup', handleMouseUp);
    // Configurar cursor inicial
    if (doctorsContainer.value) {
        doctorsContainer.value.style.cursor = 'grab';
    }
});

// Limpar event listener ao desmontar
onUnmounted(() => {
    document.removeEventListener('mouseup', handleMouseUp);
});

// Próxima consulta (primeira da lista ou null)
const nextAppointment = computed<UpcomingAppointment | null>(() => {
    return props.upcomingAppointments.length > 0 ? props.upcomingAppointments[0] : null;
});

// Filtrar médicos
const filteredDoctors = computed<Doctor[]>(() => {
    let result = [...props.doctors];

    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        result = result.filter((doctor) => doctor.name.toLowerCase().includes(query) || doctor.specialty.toLowerCase().includes(query));
    }

    if (specialtyFilter.value) {
        result = result.filter((doctor) => doctor.specialty === specialtyFilter.value);
    }

    return result;
});

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: patientRoutes.dashboard().url,
    },
];
</script>

<template>
    <Head title="Meu Painel" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl bg-gray-50 p-6">
            <!-- Seção de Boas-vindas e Próxima Consulta -->
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <!-- Seção de Boas-vindas (Esquerda) -->
                <div class="rounded-lg border border-gray-200 bg-linear-to-br from-primary/10 to-primary/5 p-8 shadow-sm lg:col-span-2">
                    <div class="flex h-full flex-col justify-between">
                        <div>
                            <div class="mb-3 flex items-start justify-between gap-4">
                                <h1 class="text-3xl font-bold text-gray-900">Olá, {{ authUser?.name?.split(' ')[0] || 'Bem-vindo' }}! 👋</h1>
                                <button
                                    @click="showTour = true"
                                    class="flex shrink-0 items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-sm text-gray-500 shadow-sm transition-colors hover:border-primary/40 hover:text-primary"
                                    title="Ver tour do dashboard"
                                >
                                    <HelpCircle class="h-4 w-4" />
                                    Tour
                                </button>
                            </div>
                            <p class="mb-4 text-lg text-gray-700">
                                Bem-vindo ao <span class="font-semibold text-primary">Telemedicina Para Todos</span>, sua plataforma completa de saúde
                                digital.
                            </p>
                            <p class="mb-6 text-base text-gray-600">
                                Agende consultas online, converse com médicos especialistas e gerencie sua saúde de forma prática e segura, tudo no
                                conforto da sua casa.
                            </p>

                            <!-- Lista de Médicos Disponíveis -->
                            <div v-if="doctors.length > 0" class="mb-6" data-tour="medicos-disponiveis">
                                <h3 class="mb-3 text-sm font-semibold text-gray-700">Médicos Disponíveis Agora:</h3>
                                <div class="grid grid-cols-2 gap-3 md:grid-cols-3">
                                    <Link
                                        v-for="doctor in doctors.slice(0, 6)"
                                        :key="doctor.id"
                                        :href="patientRoutes.searchConsultations()"
                                        class="group cursor-pointer rounded-lg border border-gray-200 bg-white/80 p-3 transition hover:border-primary/30 hover:bg-white hover:shadow-md"
                                    >
                                        <div class="flex items-center gap-3">
                                            <Avatar class="h-10 w-10 shrink-0">
                                                <AvatarImage v-if="doctor.image" :src="doctor.image" :alt="doctor.name" />
                                                <AvatarFallback
                                                    class="bg-primary/20 text-sm text-gray-900 transition group-hover:bg-primary/30"
                                                    :delay-ms="600"
                                                >
                                                    {{ getInitials(doctor.name) }}
                                                </AvatarFallback>
                                            </Avatar>
                                            <div class="min-w-0 flex-1">
                                                <p class="truncate text-sm font-semibold text-gray-900">{{ doctor.name }}</p>
                                                <p class="truncate text-xs text-emerald-700">{{ doctor.specialty }}</p>
                                            </div>
                                        </div>
                                    </Link>
                                </div>
                                <p class="mt-3 text-xs text-gray-500 italic">Clique em qualquer médico para agendar uma consulta</p>
                            </div>
                            <div v-else class="mb-6">
                                <EmptyState
                                    :icon="Users"
                                    title="Nenhum médico disponível no momento"
                                    description="Esta área mostra os médicos que estão disponíveis para consultas agora. Quando médicos estiverem online e com horários abertos, eles aparecerão aqui para acesso rápido."
                                    sub-description="Explore nossa lista completa de médicos especialistas e encontre o profissional ideal para sua necessidade."
                                    action-label="Ver todos os médicos"
                                    :action-href="patientRoutes.searchConsultations().url"
                                    :action-icon="Search"
                                    variant="minimal"
                                />
                            </div>
                        </div>

                        <Link
                            :href="patientRoutes.searchConsultations()"
                            data-tour="agendar-consulta"
                            class="inline-flex items-center justify-center rounded-lg bg-primary px-8 py-3 font-semibold text-gray-900 shadow-md transition hover:bg-primary/90 hover:shadow-lg"
                        >
                            <Calendar class="mr-2 h-5 w-5" />
                            <span>Agendar Nova Consulta</span>
                        </Link>
                    </div>
                </div>

                <!-- Próxima Consulta Section (Direita) -->
                <div v-if="nextAppointment" class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm" data-tour="proxima-consulta">
                    <!-- Foto do Médico (Seção Superior) -->
                    <div class="flex items-center justify-center bg-gray-100 px-4 py-4">
                        <Avatar class="h-24 w-24 md:h-28 md:w-28">
                            <AvatarImage v-if="nextAppointment.doctor_image" :src="nextAppointment.doctor_image" :alt="nextAppointment.doctor_name" />
                            <AvatarFallback class="bg-white text-3xl text-gray-900" :delay-ms="600">
                                {{ getInitials(nextAppointment.doctor_name) }}
                            </AvatarFallback>
                        </Avatar>
                    </div>

                    <!-- Informações da Consulta (Seção Inferior) -->
                    <div class="bg-white p-4">
                        <p class="mb-1 text-xs text-gray-500">Próxima Consulta</p>
                        <h2 class="mb-1 text-xl font-bold text-gray-900">Dr. {{ nextAppointment.doctor_name }}</h2>
                        <p class="mb-3 text-sm text-gray-700">
                            {{ nextAppointment.doctor_specialty || 'Especialista' }} •
                            {{ nextAppointment.scheduled_date || nextAppointment.scheduled_at }},
                            {{ nextAppointment.scheduled_time }}
                            <span>{{ nextAppointment.duration || ' (45 min)' }}</span>
                        </p>
                        <p class="mb-4 text-xs text-gray-500">Por videochamada</p>

                        <!-- Botões de Ação -->
                        <div class="flex flex-col gap-2">
                            <Link
                                :href="patientRoutes.videoCall()"
                                class="flex items-center justify-center space-x-2 rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-gray-900 transition hover:bg-primary/90"
                            >
                                <Video class="h-4 w-4" />
                                <span>Entrar na videochamada</span>
                            </Link>

                            <div class="flex gap-2">
                                <Link
                                    :href="patientRoutes.searchConsultations()"
                                    class="flex flex-1 items-center justify-center space-x-1 rounded-lg bg-primary/20 px-3 py-2 text-xs font-semibold text-gray-900 transition hover:bg-primary/30"
                                >
                                    <Calendar class="h-3 w-3" />
                                    <span>Reagendar</span>
                                </Link>

                                <button
                                    class="flex-1 rounded-lg px-3 py-2 text-xs font-semibold text-gray-600 transition hover:bg-gray-100 hover:text-gray-900"
                                >
                                    Cancelar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Estado vazio para próxima consulta -->
                <div v-else class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm" data-tour="proxima-consulta">
                    <EmptyState
                        :icon="Calendar"
                        title="Você ainda não tem consultas agendadas"
                        description="Este espaço mostra sua próxima consulta médica. Quando você agendar uma consulta, verá aqui os detalhes do médico, data, horário e poderá acessar a videochamada diretamente."
                        sub-description="Agende sua primeira consulta e comece a cuidar da sua saúde de forma prática, segura e no conforto da sua casa."
                        action-label="Agendar minha primeira consulta"
                        :action-href="patientRoutes.searchConsultations().url"
                        :action-icon="Calendar"
                        variant="subtle"
                    />
                </div>
            </div>

            <!-- Cards de Acesso Rápido -->
            <div class="grid grid-cols-1 gap-6 md:grid-cols-3" data-tour="documentos-medicos">
                <Link
                    :href="patientRoutes.searchConsultations()"
                    class="cursor-pointer rounded-lg border border-gray-200 bg-white p-6 shadow-sm transition hover:shadow-md"
                >
                    <div class="flex flex-col items-center text-center">
                        <div class="mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-gray-100">
                            <RotateCcw class="h-8 w-8 text-gray-700" />
                        </div>
                        <h3 class="mb-2 text-lg font-semibold text-gray-900">Histórico de Consultas</h3>
                        <p class="text-sm text-gray-600">Veja suas consultas passadas</p>
                    </div>
                </Link>

                <Link
                    :href="patientRoutes.medicalRecords()"
                    class="cursor-pointer rounded-lg border border-gray-200 bg-white p-6 shadow-sm transition hover:shadow-md"
                >
                    <div class="flex flex-col items-center text-center">
                        <div class="mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-gray-100">
                            <FileText class="h-8 w-8 text-gray-700" />
                        </div>
                        <h3 class="mb-2 text-lg font-semibold text-gray-900">Receitas Prescritas</h3>
                        <p class="text-sm text-gray-600">Acesse suas prescrições médicas</p>
                    </div>
                </Link>

                <Link
                    :href="patientRoutes.medicalRecords()"
                    class="cursor-pointer rounded-lg border border-gray-200 bg-white p-6 shadow-sm transition hover:shadow-md"
                >
                    <div class="flex flex-col items-center text-center">
                        <div class="mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-gray-100">
                            <FileCheck class="h-8 w-8 text-gray-700" />
                        </div>
                        <h3 class="mb-2 text-lg font-semibold text-gray-900">Laudos e Exames</h3>
                        <p class="text-sm text-gray-600">Visualize seus resultados</p>
                    </div>
                </Link>
            </div>

            <!-- Encontrar Médico Section -->
            <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm" data-tour="encontrar-medico">
                <h2 class="mb-6 text-2xl font-bold text-gray-900">Encontrar Médico</h2>

                <!-- Barra de Busca e Filtros -->
                <div class="mb-6 flex flex-col gap-4 md:flex-row">
                    <div class="relative flex-1">
                        <Search class="absolute top-1/2 left-3 h-5 w-5 -translate-y-1/2 transform text-gray-400" />
                        <Input v-model="searchQuery" type="text" placeholder="Buscar por nome ou especial" class="w-full pl-10" />
                    </div>

                    <div class="relative">
                        <select
                            v-model="specialtyFilter"
                            class="min-w-[180px] cursor-pointer appearance-none rounded-lg border border-gray-300 bg-white px-4 py-2 pr-10 text-gray-900 focus:border-transparent focus:ring-2 focus:ring-primary"
                        >
                            <option value="">Especialidade</option>
                            <option value="Cardiologista">Cardiologista</option>
                            <option value="Dermatologista">Dermatologista</option>
                            <option value="Clínico Geral">Clínico Geral</option>
                            <option value="Pediatra">Pediatra</option>
                            <option value="Ortopedista">Ortopedista</option>
                        </select>
                        <ChevronDown class="pointer-events-none absolute top-1/2 right-3 h-5 w-5 -translate-y-1/2 transform text-gray-400" />
                    </div>

                    <div class="relative">
                        <select
                            v-model="insuranceFilter"
                            class="min-w-[180px] cursor-pointer appearance-none rounded-lg border border-gray-300 bg-white px-4 py-2 pr-10 text-gray-900 focus:border-transparent focus:ring-2 focus:ring-primary"
                        >
                            <option value="">Convênio</option>
                            <option value="Unimed">Unimed</option>
                            <option value="Amil">Amil</option>
                            <option value="Bradesco">Bradesco</option>
                            <option value="SulAmérica">SulAmérica</option>
                        </select>
                        <ChevronDown class="pointer-events-none absolute top-1/2 right-3 h-5 w-5 -translate-y-1/2 transform text-gray-400" />
                    </div>
                </div>

                <!-- Lista de Médicos -->
                <div
                    v-if="filteredDoctors.length > 0"
                    ref="doctorsContainer"
                    class="scrollbar-hide flex cursor-grab gap-4 overflow-x-auto pb-2"
                    style="scrollbar-width: none; -ms-overflow-style: none"
                    @mousedown="handleMouseDown"
                    @mouseleave="handleMouseLeave"
                    @mouseup="handleMouseUp"
                    @mousemove="handleMouseMove"
                >
                    <!-- Médicos dinâmicos -->
                    <template v-for="doctor in filteredDoctors.slice(0, 6)" :key="doctor.id">
                        <div class="min-w-[280px] shrink-0 rounded-lg border border-gray-200 bg-white p-4 transition hover:shadow-md">
                            <div class="flex items-center gap-4">
                                <Avatar class="h-16 w-16 shrink-0">
                                    <AvatarImage v-if="doctor.image" :src="doctor.image" :alt="doctor.name" />
                                    <AvatarFallback class="bg-primary/20 text-lg text-gray-900" :delay-ms="600">
                                        {{ getInitials(doctor.name) }}
                                    </AvatarFallback>
                                </Avatar>
                                <div class="min-w-0 flex-1">
                                    <h3 class="mb-1 font-bold text-gray-900">{{ doctor.name }}</h3>
                                    <p class="text-sm text-emerald-700">{{ doctor.specialty }}</p>
                                </div>
                                <Link
                                    :href="patientRoutes.searchConsultations()"
                                    class="flex shrink-0 items-center justify-center rounded-lg bg-primary/30 p-3 text-gray-900 transition hover:bg-primary/40"
                                >
                                    <Calendar class="h-5 w-5" />
                                </Link>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Estado vazio para médicos -->
                <EmptyState
                    v-else
                    :icon="Search"
                    title="Nenhum médico encontrado com esses filtros"
                    description="Não encontramos médicos que correspondam à sua busca atual. Este espaço mostra os profissionais disponíveis para consulta, permitindo que você encontre o médico ideal para sua necessidade."
                    sub-description="Tente ajustar os filtros de especialidade ou convênio, ou explore nossa lista completa de médicos cadastrados."
                    action-label="Ver todos os médicos disponíveis"
                    :action-href="patientRoutes.searchConsultations().url"
                    :action-icon="Search"
                    variant="subtle"
                />
            </div>

            <!-- Lembretes & Dicas de Saúde -->
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <!-- Lembretes -->
                <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm lg:col-span-2">
                    <h2 class="mb-6 text-2xl font-bold text-gray-900">Lembretes & Dicas de Saúde</h2>

                    <div class="space-y-4">
                        <div
                            v-for="reminder in reminders.slice(0, 2)"
                            :key="reminder.id"
                            class="flex items-center gap-4 rounded-lg border border-gray-200 p-4 transition hover:bg-gray-50"
                        >
                            <div class="shrink-0">
                                <Clock v-if="reminder.icon === 'medication'" class="h-6 w-6 text-gray-700" />
                                <CheckCircle2 v-else-if="reminder.icon === 'exam'" class="h-6 w-6 text-emerald-600" />
                                <CheckCircle2 v-else class="h-6 w-6 text-gray-700" />
                            </div>
                            <div class="flex-1">
                                <p class="font-medium text-gray-900">{{ reminder.title }}</p>
                                <p v-if="reminder.time || reminder.message" class="mt-1 text-sm text-gray-600">
                                    {{ reminder.time || reminder.message }}
                                </p>
                            </div>
                            <button class="shrink-0 text-gray-400 hover:text-gray-600">
                                <MoreVertical class="h-5 w-5" />
                            </button>
                        </div>

                        <!-- Estado vazio para lembretes -->
                        <EmptyState
                            v-if="reminders.length === 0"
                            :icon="Clock"
                            title="Você ainda não tem lembretes configurados"
                            description="Esta área centraliza seus lembretes de saúde, como horários de medicamentos, exames agendados e outras atividades importantes relacionadas ao seu cuidado médico."
                            sub-description="Quando você tiver consultas com prescrições, exames marcados ou outros compromissos de saúde, os lembretes aparecerão automaticamente aqui para te ajudar a manter sua rotina de cuidados."
                            variant="minimal"
                        />
                    </div>
                </div>

                <!-- Dica de Saúde -->
                <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                    <h3 class="mb-4 text-xl font-semibold text-gray-900">Dica de Saúde</h3>

                    <div v-if="healthTips.length > 0" class="space-y-4">
                        <div v-for="tip in healthTips.slice(0, 1)" :key="tip.id" class="space-y-3">
                            <div v-if="tip.image" class="h-48 w-full overflow-hidden rounded-lg bg-gray-100">
                                <img :src="tip.image" :alt="tip.title" class="h-full w-full object-cover" />
                            </div>
                            <div v-else class="flex h-48 w-full items-center justify-center rounded-lg bg-linear-to-br from-primary/20 to-primary/5">
                                <Activity class="h-16 w-16 text-primary/50" />
                            </div>
                            <h4 class="font-semibold text-gray-900">{{ tip.title }}</h4>
                            <p class="line-clamp-3 text-sm text-gray-600">{{ tip.description }}</p>
                        </div>
                    </div>

                    <!-- Estado vazio para dicas de saúde -->
                    <EmptyState
                        v-else
                        :icon="Activity"
                        title="Suas dicas personalizadas de saúde"
                        description="Este espaço traz dicas e orientações de saúde personalizadas para você, baseadas no seu histórico de consultas, prescrições e necessidades específicas."
                        sub-description="Conforme você usa a plataforma e realiza consultas, receberá dicas relevantes para melhorar seu bem-estar e qualidade de vida."
                        variant="minimal"
                    />
                </div>
            </div>
        </div>

        <!-- Componentes de Onboarding -->
        <WelcomeScreen
            :show="showWelcomeScreen"
            :user-name="props.onboarding?.userName || ''"
            @start-tour="handleStartTour"
            @close="handleWelcomeClose"
        />
        <DashboardTour :show="showTour" @complete="handleTourComplete" @close="handleTourClose" />
    </AppLayout>
</template>
