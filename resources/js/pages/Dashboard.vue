<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import * as doctorRoutes from '@/routes/doctor';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    Calendar,
    History,
    Video,
    FileText,
    Users,
    TrendingUp,
    Clock
} from 'lucide-vue-next';
import { computed, onMounted, ref, watch } from 'vue';
import { useRouteGuard } from '@/composables/auth';
import { useInitials } from '@/composables/useInitials';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import EmptyState from '@/components/EmptyState.vue';
import WelcomeScreen from '@/components/onboarding/WelcomeScreen.vue';
import DashboardTour from '@/components/onboarding/DashboardTour.vue';

interface UpcomingAppointment {
    id: string;
    patient_name: string;
    patient_avatar?: string;
    patient_initials?: string;
    reason?: string;
    scheduled_at: string;
    scheduled_date?: string;
    scheduled_time?: string;
    duration?: string;
    location?: string;
    status: string;
    status_class?: string;
}

interface WeeklyStats {
    total: number;
    period: string;
}

interface MonthlyStats {
    total: number;
    period: string;
}

interface AppointmentData {
    day?: string;
    week?: string;
    count: number;
    max: number;
}

interface Onboarding {
    showWelcome?: boolean;
    showTour?: boolean;
    userName?: string;
}

interface Props {
    upcomingAppointments?: UpcomingAppointment[];
    weeklyStats?: WeeklyStats;
    monthlyStats?: MonthlyStats;
    weeklyAppointments?: AppointmentData[];
    monthlyAppointments?: AppointmentData[];
    onboarding?: Onboarding;
}

const props = withDefaults(defineProps<Props>(), {
    upcomingAppointments: () => [],
    weeklyStats: () => ({ total: 0, period: 'Esta Semana' }),
    monthlyStats: () => ({ total: 0, period: 'Este Mês' }),
    weeklyAppointments: () => [],
    monthlyAppointments: () => [],
    onboarding: () => ({
        showWelcome: false,
        showTour: false,
        userName: '',
    }),
});

const showWelcomeScreen = ref(props.onboarding?.showWelcome ?? false);
const showTour = ref(props.onboarding?.showTour ?? false);

// Atualizar estados quando props mudarem
watch(() => props.onboarding?.showWelcome, (newValue) => {
    showWelcomeScreen.value = newValue ?? false;
});

watch(() => props.onboarding?.showTour, (newValue) => {
    showTour.value = newValue ?? false;
});

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

const { canAccessDoctorRoute } = useRouteGuard();
const { getInitials } = useInitials();
const page = usePage();

const authUser = computed(() => {
    const pageProps = page.props as any;
    return pageProps?.auth?.user || null;
});

const doctorFirstName = computed(() => {
    if (!authUser.value?.name) return 'Doutor';
    return authUser.value.name.split(' ')[0];
});

const upcomingAppointments = computed(() => props.upcomingAppointments ?? []);
const nextAppointment = computed(() => upcomingAppointments.value[0] ?? null);
const weeklyStats = computed(() => props.weeklyStats ?? { total: 0, period: 'Esta Semana' });
const monthlyStats = computed(() => props.monthlyStats ?? { total: 0, period: 'Este Mês' });
const weeklyAppointments = computed(() => props.weeklyAppointments ?? []);
const monthlyAppointments = computed(() => props.monthlyAppointments ?? []);

const totalUpcomingAppointments = computed(() => upcomingAppointments.value.length);
const completionRate = computed(() => {
    const monthTotal = monthlyStats.value.total || 0;
    if (!monthTotal) return 0;
    return Math.min(100, Math.round((weeklyStats.value.total / monthTotal) * 100));
});

const resolveStatusClass = (status?: string, statusClass?: string) => {
    if (statusClass) return statusClass;

    const normalized = (status || '').toLowerCase();

    if (normalized.includes('confirm')) {
        return 'bg-emerald-100 text-emerald-800';
    }

    if (normalized.includes('pend') || normalized.includes('aguard')) {
        return 'bg-amber-100 text-amber-800';
    }

    if (normalized.includes('cancel')) {
        return 'bg-rose-100 text-rose-800';
    }

    return 'bg-gray-100 text-gray-800';
};

// Verificar acesso ao montar componente
onMounted(() => {
    canAccessDoctorRoute();
});

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: doctorRoutes.dashboard().url,
    },
];

// Passos do tour para médico
const doctorTourSteps = [
    {
        id: 'gerenciar-agenda',
        title: 'Gerenciar Sua Agenda',
        description: 'Este é o botão principal para gerenciar sua agenda de consultas. Clique aqui para visualizar todos os seus compromissos, ajustar horários disponíveis e configurar sua disponibilidade para receber novos agendamentos.',
        target: '[data-tour="gerenciar-agenda"]',
        position: 'bottom' as const,
    },
    {
        id: 'estatisticas',
        title: 'Suas Estatísticas',
        description: 'Aqui você vê um resumo rápido das suas atividades: consultas da semana, taxa de cumprimento e pacientes agendados. Essas métricas ajudam você a acompanhar seu desempenho e planejar melhor sua agenda.',
        target: '[data-tour="estatisticas"]',
        position: 'bottom' as const,
    },
    {
        id: 'proxima-consulta',
        title: 'Sua Próxima Consulta',
        description: 'Este card mostra os detalhes da sua próxima consulta agendada: paciente, data, horário e motivo. Quando chegar o momento, você poderá iniciar a videochamada diretamente daqui. Se ainda não tem consultas agendadas, este espaço ficará disponível para quando receber agendamentos.',
        target: '[data-tour="proxima-consulta"]',
        position: 'left' as const,
    },
    {
        id: 'acessos-rapidos',
        title: 'Acessos Rápidos',
        description: 'Estes três cards dão acesso rápido às principais funcionalidades: agenda diária, prontuários recentes e salas de vídeo. Tudo fica organizado e acessível aqui no seu dashboard para otimizar seu trabalho.',
        target: '[data-tour="acessos-rapidos"]',
        position: 'top' as const,
    },
    {
        id: 'consultas-futuras',
        title: 'Consultas Futuras',
        description: 'Use esta seção para visualizar todas as suas consultas agendadas em uma tabela completa. Você pode ver os detalhes de cada paciente, horários e status das consultas. Clique em "Ver agenda completa" para gerenciar todos os seus compromissos.',
        target: '[data-tour="consultas-futuras"]',
        position: 'top' as const,
    },
];
</script>

<template>

    <Head title="Painel de Controle" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl bg-gray-50 p-6">
            <!-- Seção de boas-vindas + Próxima consulta -->
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <div
                    class="flex flex-col justify-between rounded-2xl border border-gray-200 bg-linear-to-br from-primary/15 via-white to-white p-8 shadow-sm lg:col-span-2">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-wide text-primary">Dashboard Médico</p>
                        <h1 class="mt-2 text-3xl font-bold text-gray-900">
                            Olá, Dr(a). {{ doctorFirstName }}! 👋
                        </h1>
                        <p class="mt-3 text-lg text-gray-700">
                            Acompanhe suas consultas, otimize o atendimento e mantenha seus pacientes informados em um
                            único lugar.
                        </p>
                    </div>

                    <div class="mt-8 grid grid-cols-1 gap-4 md:grid-cols-3" data-tour="estatisticas">
                        <div class="rounded-xl border border-white/60 bg-white/70 p-4 shadow-sm backdrop-blur">
                            <div class="flex items-center gap-3">
                                <span class="rounded-lg bg-primary/20 p-3 text-primary">
                                    <Calendar class="h-5 w-5" />
                                </span>
                                <div>
                                    <p class="text-sm text-gray-500">Consultas na semana</p>
                                    <p class="text-2xl font-bold text-gray-900">{{ weeklyStats.total }}</p>
                                    <p class="text-xs text-gray-500">{{ weeklyStats.period }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="rounded-xl border border-white/60 bg-white/70 p-4 shadow-sm backdrop-blur">
                            <div class="flex items-center gap-3">
                                <span class="rounded-lg bg-primary/20 p-3 text-primary">
                                    <TrendingUp class="h-5 w-5" />
                                </span>
                                <div>
                                    <p class="text-sm text-gray-500">Taxa de cumprimento</p>
                                    <p class="text-2xl font-bold text-gray-900">{{ completionRate }}%</p>
                                    <p class="text-xs text-gray-500">Baseado no mês atual</p>
                                </div>
                            </div>
                        </div>

                        <div class="rounded-xl border border-white/60 bg-white/70 p-4 shadow-sm backdrop-blur">
                            <div class="flex items-center gap-3">
                                <span class="rounded-lg bg-primary/20 p-3 text-primary">
                                    <Users class="h-5 w-5" />
                                </span>
                                <div>
                                    <p class="text-sm text-gray-500">Pacientes agendados</p>
                                    <p class="text-2xl font-bold text-gray-900">{{ totalUpcomingAppointments }}</p>
                                    <p class="text-xs text-gray-500">Próximas 24h</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-8 flex flex-wrap gap-3">
                        <Link :href="doctorRoutes.appointments()"
                            class="inline-flex items-center justify-center rounded-xl bg-primary px-6 py-3 font-semibold text-gray-900 transition hover:bg-primary/90">
                            <Calendar class="mr-2 h-5 w-5" />
                            Gerenciar agenda
                        </Link>
                        <Link :href="doctorRoutes.medicalRecords?.() ?? doctorRoutes.appointments()"
                            class="inline-flex items-center justify-center rounded-xl border border-primary/40 px-6 py-3 font-semibold text-primary transition hover:bg-primary/10">
                            <FileText class="mr-2 h-5 w-5" />
                            Revisar prontuários
                        </Link>
                    </div>
                </div>

                <!-- Próxima Consulta -->
                <div v-if="nextAppointment"
                    class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm"
                    data-tour="proxima-consulta">
                    <div class="bg-gray-100 px-6 py-6 text-center">
                        <Avatar class="mx-auto h-24 w-24">
                            <AvatarImage v-if="nextAppointment.patient_avatar" :src="nextAppointment.patient_avatar"
                                :alt="nextAppointment.patient_name" />
                            <AvatarFallback class="bg-white text-3xl text-gray-900" :delay-ms="600">
                                {{ nextAppointment.patient_initials || getInitials(nextAppointment.patient_name) }}
                            </AvatarFallback>
                        </Avatar>
                    </div>

                    <div class="space-y-4 px-6 py-6">
                        <div>
                            <p class="text-xs uppercase tracking-wide text-gray-500">Próxima consulta</p>
                            <h2 class="text-2xl font-bold text-gray-900">
                                {{ nextAppointment.patient_name }}
                            </h2>
                            <p class="text-sm text-gray-600">
                                {{ nextAppointment.reason || 'Consulta online' }}
                            </p>
                        </div>

                        <div class="space-y-2 rounded-xl bg-gray-50 p-4 text-sm">
                            <div class="flex items-center justify-between text-gray-700">
                                <span>Data</span>
                                <span class="font-semibold">
                                    {{ nextAppointment.scheduled_date || nextAppointment.scheduled_at }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between text-gray-700">
                                <span>Horário</span>
                                <span class="font-semibold">
                                    {{ nextAppointment.scheduled_time || nextAppointment.scheduled_at }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between text-gray-700">
                                <span>Duração</span>
                                <span class="font-semibold">{{ nextAppointment.duration || '45 min' }}</span>
                            </div>
                            <div class="flex items-center justify-between text-gray-700">
                                <span>Local</span>
                                <span class="font-semibold">{{ nextAppointment.location || 'Teleconsulta' }}</span>
                            </div>
                        </div>

                        <div class="flex flex-col gap-2">
                            <Link :href="doctorRoutes.videoCall?.() ?? doctorRoutes.appointments()"
                                class="flex items-center justify-center rounded-xl bg-primary px-4 py-2 font-semibold text-gray-900 transition hover:bg-primary/90">
                                <Video class="mr-2 h-4 w-4" />
                                Iniciar videochamada
                            </Link>
                            <div class="flex gap-2">
                                <Link :href="doctorRoutes.appointments()"
                                    class="flex flex-1 items-center justify-center rounded-xl bg-primary/20 px-4 py-2 text-xs font-semibold text-primary transition hover:bg-primary/30">
                                    <History class="mr-1 h-4 w-4" />
                                    Reagendar
                                </Link>
                                <button type="button"
                                    class="flex flex-1 items-center justify-center rounded-xl border border-gray-200 px-4 py-2 text-xs font-semibold text-gray-700 transition hover:bg-gray-50">
                                    Cancelar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Estado vazio para próxima consulta -->
                <div v-else class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm p-6"
                    data-tour="proxima-consulta">
                    <EmptyState :icon="Calendar" title="Nenhuma consulta agendada"
                        description="Quando pacientes agendarem consultas com você, elas aparecerão aqui. Você pode começar configurando sua disponibilidade para receber agendamentos."
                        action-label="Configurar disponibilidade" :action-href="doctorRoutes.availability().url"
                        :action-icon="Calendar" variant="subtle" />
                </div>
            </div>

            <!-- Acessos rápidos -->
            <div class="grid grid-cols-1 gap-6 md:grid-cols-3" data-tour="acessos-rapidos">
                <Link :href="doctorRoutes.appointments()"
                    class="rounded-2xl border border-gray-200 bg-white p-6 text-center shadow-sm transition hover:-translate-y-0.5 hover:border-primary/40 hover:shadow-md">
                    <div
                        class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-primary/15 text-primary">
                        <Calendar class="h-8 w-8" />
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900">Agenda diária</h3>
                    <p class="mt-1 text-sm text-gray-600">Visualize e ajuste todos os compromissos</p>
                </Link>

                <Link :href="doctorRoutes.medicalRecords?.() ?? doctorRoutes.appointments()"
                    class="rounded-2xl border border-gray-200 bg-white p-6 text-center shadow-sm transition hover:-translate-y-0.5 hover:border-primary/40 hover:shadow-md">
                    <div
                        class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-primary/15 text-primary">
                        <FileText class="h-8 w-8" />
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900">Prontuários recentes</h3>
                    <p class="mt-1 text-sm text-gray-600">Revise notas clínicas e prescrições</p>
                </Link>

                <Link :href="doctorRoutes.videoCall?.() ?? doctorRoutes.appointments()"
                    class="rounded-2xl border border-gray-200 bg-white p-6 text-center shadow-sm transition hover:-translate-y-0.5 hover:border-primary/40 hover:shadow-md">
                    <div
                        class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-primary/15 text-primary">
                        <Video class="h-8 w-8" />
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900">Salas de vídeo</h3>
                    <p class="mt-1 text-sm text-gray-600">Crie ou entre em uma sala segura</p>
                </Link>
            </div>

            <!-- Conteúdo principal -->
            <div class="grid flex-1 grid-cols-1 gap-6 lg:grid-cols-3">
                <div class="space-y-6 rounded-2xl border border-gray-200 bg-white p-6 shadow-sm lg:col-span-2"
                    data-tour="consultas-futuras">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <h2 class="text-2xl font-bold text-gray-900">Consultas futuras</h2>
                            <p class="text-sm text-gray-600">Acompanhe os próximos atendimentos em fila</p>
                        </div>
                        <Link :href="doctorRoutes.appointments()"
                            class="inline-flex items-center rounded-xl border border-gray-200 px-4 py-2 text-sm font-semibold text-gray-700 transition hover:border-primary/40 hover:text-primary">
                            Ver agenda completa
                        </Link>
                    </div>

                    <div v-if="upcomingAppointments.length" class="overflow-hidden rounded-2xl border border-gray-200">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-primary/10">
                                <tr>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-600">
                                        Paciente
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-600">
                                        Horário
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-600">
                                        Status
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-600">
                                        Ações
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                                <tr v-for="appointment in upcomingAppointments" :key="appointment.id"
                                    class="hover:bg-gray-50">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <Avatar class="h-10 w-10">
                                                <AvatarImage v-if="appointment.patient_avatar"
                                                    :src="appointment.patient_avatar" :alt="appointment.patient_name" />
                                                <AvatarFallback class="bg-primary/10 text-sm text-primary"
                                                    :delay-ms="600">
                                                    {{ appointment.patient_initials ||
                                                        getInitials(appointment.patient_name) }}
                                                </AvatarFallback>
                                            </Avatar>
                                            <div>
                                                <p class="text-sm font-semibold text-gray-900">{{
                                                    appointment.patient_name }}</p>
                                                <p class="text-xs text-gray-500">
                                                    {{ appointment.reason || 'Consulta online' }}
                                                </p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600">
                                        {{ appointment.scheduled_time || appointment.scheduled_at }}
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold"
                                            :class="resolveStatusClass(appointment.status, appointment.status_class)">
                                            {{ appointment.status || '—' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex gap-2">
                                            <Link :href="doctorRoutes.videoCall?.() ?? doctorRoutes.appointments()"
                                                class="rounded-lg bg-primary/20 px-3 py-1 text-xs font-semibold text-primary transition hover:bg-primary/30">
                                                Entrar
                                            </Link>
                                            <Link :href="doctorRoutes.appointments()"
                                                class="rounded-lg border border-gray-200 px-3 py-1 text-xs font-semibold text-gray-600 transition hover:border-primary/30 hover:text-primary">
                                                Detalhes
                                            </Link>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <EmptyState v-else :icon="Calendar" title="Nenhuma consulta futura"
                        description="Ainda não há consultas agendadas. Quando pacientes agendarem horários com você, eles aparecerão nesta lista. Configure sua disponibilidade para começar a receber agendamentos."
                        action-label="Ver agenda completa" :action-href="doctorRoutes.appointments().url"
                        :action-icon="Calendar" />
                </div>

                <div class="space-y-6">
                    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-semibold text-gray-900">Fluxo semanal</h3>
                            <Clock class="h-5 w-5 text-gray-400" />
                        </div>
                        <p class="text-sm text-gray-600">{{ weeklyStats.period }}</p>

                        <div v-if="weeklyAppointments.length" class="mt-6 space-y-3">
                            <div v-for="day in weeklyAppointments" :key="day.day" class="flex items-center gap-3">
                                <span class="w-10 text-xs font-semibold uppercase text-gray-500">
                                    {{ day.day }}
                                </span>
                                <div class="h-2 flex-1 rounded-full bg-gray-100">
                                    <div class="h-full rounded-full bg-primary transition-all"
                                        :style="{ width: `${Math.max(8, (day.count / day.max) * 100)}%` }"></div>
                                </div>
                                <span class="w-6 text-xs font-semibold text-gray-600">{{ day.count }}</span>
                            </div>
                        </div>
                        <EmptyState v-else :icon="Clock" title="Sem dados desta semana"
                            description="Ainda não há consultas agendadas para esta semana. Quando você começar a receber agendamentos, o gráfico será atualizado automaticamente."
                            variant="minimal" />
                    </div>

                    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                        <h3 class="text-lg font-semibold text-gray-900">Volume mensal</h3>
                        <p class="text-sm text-gray-600">{{ monthlyStats.period }}</p>

                        <div v-if="monthlyAppointments.length" class="mt-6 space-y-4">
                            <div v-for="week in monthlyAppointments" :key="week.week"
                                class="space-y-2 rounded-xl border border-gray-100 p-3">
                                <div class="flex items-center justify-between text-xs font-semibold text-gray-600">
                                    <span>Semana {{ week.week }}</span>
                                    <span>{{ week.count }} / {{ week.max }}</span>
                                </div>
                                <div class="h-2 rounded-full bg-gray-100">
                                    <div class="h-full rounded-full bg-primary transition-all"
                                        :style="{ width: `${Math.min(100, Math.max(8, (week.count / week.max) * 100))}%` }">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <EmptyState v-else :icon="TrendingUp" title="Sem dados mensais"
                            description="Ainda não há consultas suficientes para gerar estatísticas mensais. Continue atendendo pacientes para ver seus dados de volume mensal aqui."
                            variant="minimal" />
                    </div>
                </div>
            </div>
        </div>

        <!-- Componentes de Onboarding -->
        <WelcomeScreen :show="showWelcomeScreen" :user-name="props.onboarding?.userName || ''"
            @start-tour="handleStartTour" @close="handleWelcomeClose" />
        <DashboardTour :show="showTour" :steps="doctorTourSteps" @complete="handleTourComplete"
            @close="handleTourClose" />
    </AppLayout>
</template>
