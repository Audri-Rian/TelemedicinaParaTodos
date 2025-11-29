<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import * as doctorRoutes from '@/routes/doctor';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Input } from '@/components/ui/input';
import { useInitials } from '@/composables/useInitials';
import { Calendar, Clock, Filter, History, Search, UserCheck, UserPlus } from 'lucide-vue-next';
import { computed, ref } from 'vue';

type FilterOption = 'todos' | 'futuros' | 'historico';

interface PatientStats {
    totalPatients: number;
    activePatients: number;
    consultedThisWeek: number;
    upcomingAppointments: number;
}

interface UpcomingPatient {
    id: string | number;
    name: string;
    avatar?: string;
    initials?: string;
    reason?: string;
    scheduled_date?: string;
    scheduled_time?: string;
    status?: string;
    status_class?: string;
    channel?: string;
}

interface PatientHistory {
    id: string | number;
    name: string;
    avatar?: string;
    initials?: string;
    lastConsultation?: string;
    nextConsultation?: string;
    status?: string;
    status_class?: string;
    notes?: string;
}

interface Props {
    stats?: PatientStats;
    upcomingPatients?: UpcomingPatient[];
    patientHistory?: PatientHistory[];
}

const props = withDefaults(defineProps<Props>(), {
    stats: () => ({
        totalPatients: 0,
        activePatients: 0,
        consultedThisWeek: 0,
        upcomingAppointments: 0,
    }),
    upcomingPatients: () => [],
    patientHistory: () => [],
});

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: doctorRoutes.dashboard().url,
    },
    {
        title: 'Pacientes',
        href: doctorRoutes.patients().url,
    },
];

const { getInitials } = useInitials();

const searchQuery = ref('');
const statusFilter = ref<FilterOption>('todos');

const handleFilterChange = (option: FilterOption) => {
    statusFilter.value = option;
};

const matchesSearch = (name?: string) => {
    if (!name) return false;
    if (!searchQuery.value) return true;
    return name.toLowerCase().includes(searchQuery.value.toLowerCase());
};

const resolveStatusClass = (status?: string, fallback?: string) => {
    if (fallback) return fallback;

    const normalized = (status || '').toLowerCase();

    if (normalized.includes('agend') || normalized.includes('confirm') || normalized.includes('ativo')) {
        return 'bg-emerald-100 text-emerald-800';
    }

    if (normalized.includes('pend') || normalized.includes('aguard')) {
        return 'bg-amber-100 text-amber-800';
    }

    if (normalized.includes('cancel') || normalized.includes('inativ') || normalized.includes('ausente')) {
        return 'bg-rose-100 text-rose-800';
    }

    return 'bg-gray-100 text-gray-800';
};

const filteredUpcoming = computed(() => {
    if (statusFilter.value === 'historico') {
        return [];
    }

    return props.upcomingPatients.filter(patient => matchesSearch(patient.name));
});

const filteredHistory = computed(() => {
    if (statusFilter.value === 'futuros') {
        return [];
    }

    return props.patientHistory.filter(patient => matchesSearch(patient.name));
});

const hasResults = computed(() => filteredUpcoming.value.length > 0 || filteredHistory.value.length > 0);
</script>

<template>
    <Head title="Pacientes do Médico" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl bg-gray-50 p-6">
            <!-- Header + stats -->
            <div class="flex flex-col gap-4">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-wide text-primary">Central do médico</p>
                    <h1 class="text-3xl font-bold text-gray-900">Pacientes acompanhados</h1>
                    <p class="text-sm text-gray-600">
                        Visualize quem já passou em consulta e quem está agendado para os próximos dias.
                    </p>
                </div>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
                    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                        <div class="flex items-center gap-3">
                            <span class="rounded-xl bg-primary/15 p-3 text-primary">
                                <UserPlus class="h-5 w-5" />
                            </span>
                            <div>
                                <p class="text-xs uppercase text-gray-500">Pacientes ativos</p>
                                <p class="text-2xl font-bold text-gray-900">{{ props.stats.activePatients }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                        <div class="flex items-center gap-3">
                            <span class="rounded-xl bg-primary/15 p-3 text-primary">
                                <UserCheck class="h-5 w-5" />
                            </span>
                            <div>
                                <p class="text-xs uppercase text-gray-500">Consultados nesta semana</p>
                                <p class="text-2xl font-bold text-gray-900">{{ props.stats.consultedThisWeek }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                        <div class="flex items-center gap-3">
                            <span class="rounded-xl bg-primary/15 p-3 text-primary">
                                <Calendar class="h-5 w-5" />
                            </span>
                            <div>
                                <p class="text-xs uppercase text-gray-500">Consultas agendadas</p>
                                <p class="text-2xl font-bold text-gray-900">{{ props.stats.upcomingAppointments }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                        <div class="flex items-center gap-3">
                            <span class="rounded-xl bg-primary/15 p-3 text-primary">
                                <History class="h-5 w-5" />
                            </span>
                            <div>
                                <p class="text-xs uppercase text-gray-500">Total de pacientes</p>
                                <p class="text-2xl font-bold text-gray-900">{{ props.stats.totalPatients }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Search + filters -->
            <div class="flex flex-col gap-4 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm lg:flex-row lg:items-center lg:justify-between">
                <div class="flex flex-1 items-center gap-3">
                    <div class="relative flex-1">
                        <Search class="absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400" />
                        <Input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Buscar por nome do paciente..."
                            class="pl-10"
                        />
                    </div>
                    <div class="flex items-center gap-2 text-sm font-medium text-gray-500">
                        <Filter class="h-4 w-4" />
                        Filtrar visão
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <button
                        v-for="option in ['todos', 'futuros', 'historico']"
                        :key="option"
                        @click="handleFilterChange(option as FilterOption)"
                        :class="[
                            'px-4 py-2 rounded-xl text-sm font-semibold transition',
                            statusFilter === option
                                ? 'bg-primary text-gray-900 shadow-sm'
                                : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                        ]"
                    >
                        {{ option === 'todos' ? 'Todos' : option === 'futuros' ? 'Próximos' : 'Consultados' }}
                    </button>
                </div>
            </div>

            <!-- Upcoming consultations -->
            <div v-if="filteredUpcoming.length" class="space-y-4">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900">Consultas futuras</h2>
                        <p class="text-sm text-gray-600">Pacientes agendados para hoje e próximos dias.</p>
                    </div>
                    <Link
                        :href="doctorRoutes.appointments().url"
                        class="inline-flex items-center rounded-xl border border-gray-200 px-4 py-2 text-sm font-semibold text-gray-700 transition hover:border-primary/40 hover:text-primary"
                    >
                        Acessar agenda
                    </Link>
                </div>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                    <div
                        v-for="patient in filteredUpcoming"
                        :key="patient.id"
                        class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-primary/40 hover:shadow-md"
                    >
                        <div class="flex items-start gap-4">
                            <Avatar class="h-12 w-12">
                                <AvatarImage v-if="patient.avatar" :src="patient.avatar" :alt="patient.name" />
                                <AvatarFallback class="bg-primary/10 text-sm text-primary" :delay-ms="600">
                                    {{ patient.initials || getInitials(patient.name) }}
                                </AvatarFallback>
                            </Avatar>
                            <div class="flex-1">
                                <div class="flex items-center gap-2">
                                    <h3 class="text-lg font-semibold text-gray-900">{{ patient.name }}</h3>
                                    <span
                                        class="rounded-full px-2 py-0.5 text-xs font-semibold"
                                        :class="resolveStatusClass(patient.status, patient.status_class)"
                                    >
                                        {{ patient.status || 'Agendado' }}
                                    </span>
                                </div>
                                <p class="text-sm text-gray-600">
                                    {{ patient.reason || 'Consulta geral' }}
                                </p>
                            </div>
                        </div>

                        <div class="mt-4 space-y-2 rounded-xl bg-gray-50 p-4 text-sm">
                            <div class="flex items-center justify-between text-gray-700">
                                <span class="flex items-center gap-2 text-gray-500">
                                    <Calendar class="h-4 w-4" /> Data
                                </span>
                                <span class="font-semibold">{{ patient.scheduled_date || 'A confirmar' }}</span>
                            </div>
                            <div class="flex items-center justify-between text-gray-700">
                                <span class="flex items-center gap-2 text-gray-500">
                                    <Clock class="h-4 w-4" /> Horário
                                </span>
                                <span class="font-semibold">{{ patient.scheduled_time || patient.scheduled_date || '—' }}</span>
                            </div>
                            <div class="flex items-center justify-between text-gray-700">
                                <span class="flex items-center gap-2 text-gray-500">
                                    <History class="h-4 w-4" /> Canal
                                </span>
                                <span class="font-semibold">{{ patient.channel || 'Teleconsulta' }}</span>
                            </div>
                        </div>

                        <div class="mt-4 flex gap-2">
                            <Link
                                :href="doctorRoutes.videoCall?.().url ?? doctorRoutes.appointments().url"
                                class="flex-1 rounded-xl bg-primary px-4 py-2 text-center text-sm font-semibold text-gray-900 transition hover:bg-primary/90"
                            >
                                Iniciar vídeo
                            </Link>
                            <Link
                                :href="doctorRoutes.patient.details({ id: patient.id }).url"
                                class="flex-1 rounded-xl border border-gray-200 px-4 py-2 text-center text-sm font-semibold text-gray-700 transition hover:border-primary/40 hover:text-primary"
                            >
                                Prontuário
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <!-- History -->
            <div v-if="filteredHistory.length" class="space-y-4">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Pacientes consultados</h2>
                    <p class="text-sm text-gray-600">Histórico de atendimentos recentes e próximos retornos.</p>
                </div>

                <div class="rounded-2xl border border-gray-200 bg-white shadow-sm">
                    <div class="divide-y divide-gray-100">
                        <div
                            v-for="patient in filteredHistory"
                            :key="patient.id"
                            class="flex flex-col gap-4 p-5 md:flex-row md:items-center md:justify-between"
                        >
                            <div class="flex flex-1 items-center gap-4">
                                <Avatar class="h-12 w-12">
                                    <AvatarImage v-if="patient.avatar" :src="patient.avatar" :alt="patient.name" />
                                    <AvatarFallback class="bg-primary/10 text-sm text-primary" :delay-ms="600">
                                        {{ patient.initials || getInitials(patient.name) }}
                                    </AvatarFallback>
                                </Avatar>
                                <div>
                                    <p class="text-base font-semibold text-gray-900">{{ patient.name }}</p>
                                    <p class="text-sm text-gray-500">
                                        Última consulta em {{ patient.lastConsultation || '—' }}
                                    </p>
                                    <p v-if="patient.notes" class="text-xs text-gray-500">
                                        {{ patient.notes }}
                                    </p>
                                </div>
                            </div>
                            <div class="flex flex-1 flex-wrap items-center gap-3 text-sm text-gray-600">
                                <div class="flex flex-col">
                                    <span class="uppercase text-xs text-gray-400">Próximo retorno</span>
                                    <span class="font-semibold text-gray-800">{{ patient.nextConsultation || 'Sob demanda' }}</span>
                                </div>
                                <span
                                    class="rounded-full px-3 py-1 text-xs font-semibold"
                                    :class="resolveStatusClass(patient.status, patient.status_class)"
                                >
                                    {{ patient.status || 'Ativo' }}
                                </span>
                            </div>
                            <div class="flex gap-2">
                                <Link
                                    :href="doctorRoutes.patient.details({ id: patient.id }).url"
                                    class="rounded-xl bg-primary/20 px-4 py-2 text-sm font-semibold text-primary transition hover:bg-primary/30"
                                >
                                    Abrir prontuário
                                </Link>
                                <Link
                                    :href="doctorRoutes.appointments().url"
                                    class="rounded-xl border border-gray-200 px-4 py-2 text-sm font-semibold text-gray-700 transition hover:border-primary/40 hover:text-primary"
                                >
                                    Agendar retorno
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty state -->
            <div v-if="!hasResults" class="flex flex-1 flex-col items-center justify-center rounded-2xl border border-dashed border-gray-300 bg-white/70 p-12 text-center">
                <div class="mb-4 rounded-full bg-primary/10 p-4 text-primary">
                    <UserPlus class="h-8 w-8" />
                </div>
                <h3 class="text-xl font-semibold text-gray-900">Nenhum paciente encontrado</h3>
                <p class="mt-2 max-w-md text-sm text-gray-600">
                    Ajuste o termo de busca ou aguarde novos atendimentos para ver esta lista preenchida.
                </p>
            </div>
        </div>
    </AppLayout>
</template>




