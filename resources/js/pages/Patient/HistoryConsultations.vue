<script setup lang="ts">
import AppointmentStatusBadge from '@/components/AppointmentStatusBadge.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useRouteGuard } from '@/composables/auth';
import { useInitials } from '@/composables/useInitials';
import AppLayout from '@/layouts/AppLayout.vue';
import * as patientRoutes from '@/routes/patient';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { Calendar, Check, ChevronLeft, ChevronRight, Clock, FileText, Inbox, Plus, Search, Sparkles, Stethoscope, Video, X } from 'lucide-vue-next';
import { computed, onMounted, ref, watch } from 'vue';

type AppointmentStatus = 'scheduled' | 'in_progress' | 'completed' | 'cancelled' | 'rescheduled' | 'no_show';
type ActiveFilter = 'all' | 'upcoming' | 'completed' | 'cancelled';
type DateRange = 'all' | '30d' | '90d' | 'year';
type SortOption = 'recent-first' | 'soonest-first' | 'oldest-first';

interface Appointment {
    id: string;
    status: AppointmentStatus;
    scheduled_at: string | null;
    doctor: {
        id: string;
        user: {
            id: string;
            name: string;
            avatar?: string | null;
        };
        specializations: Array<{ id: string; name: string }>;
    };
}

interface PaginatedAppointments {
    data: Appointment[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

interface Props {
    appointments: PaginatedAppointments;
    stats: {
        total: number;
        upcoming: number;
        completed: number;
        cancelled: number;
    };
    filters?: {
        status?: string;
        search?: string;
        date_range?: string;
        sort?: string;
    };
}

const props = defineProps<Props>();

const { canAccessPatientRoute } = useRouteGuard();
const { getInitials } = useInitials();

const activeFilter = ref<ActiveFilter>((props.filters?.status as ActiveFilter) || 'all');
const searchQuery = ref(props.filters?.search ?? '');
const dateRange = ref<DateRange>((props.filters?.date_range as DateRange) || 'all');
const sort = ref<SortOption>((props.filters?.sort as SortOption) || 'recent-first');

const stats = computed(() => props.stats ?? { total: 0, upcoming: 0, completed: 0, cancelled: 0 });
const pagination = computed(() => props.appointments ?? { data: [], current_page: 1, last_page: 1, per_page: 10, total: 0 });
const consultations = computed(() => pagination.value.data.filter((consultation) => consultation?.id));
const totalResults = computed(() => pagination.value.total ?? consultations.value.length);

const activeChips = computed(() => {
    const chips: Array<{ kind: 'search' | 'date'; label: string }> = [];

    if (searchQuery.value.trim()) {
        chips.push({ kind: 'search', label: searchQuery.value.trim() });
    }

    if (dateRange.value !== 'all') {
        chips.push({ kind: 'date', label: dateRangeLabels[dateRange.value] });
    }

    return chips;
});

const hasFilters = computed(() => activeFilter.value !== 'all' || searchQuery.value.trim() || dateRange.value !== 'all');

const upcomingGroups = computed(() => {
    const groups = {
        today: [] as Appointment[],
        week: [] as Appointment[],
        later: [] as Appointment[],
    };

    const startToday = new Date();
    startToday.setHours(0, 0, 0, 0);

    consultations.value.forEach((consultation) => {
        if (!consultation.scheduled_at) {
            groups.later.push(consultation);
            return;
        }

        const scheduledAt = new Date(consultation.scheduled_at);
        const scheduledDay = new Date(scheduledAt);
        scheduledDay.setHours(0, 0, 0, 0);
        const diffDays = Math.floor((scheduledDay.getTime() - startToday.getTime()) / 86_400_000);

        if (diffDays <= 0) {
            groups.today.push(consultation);
        } else if (diffDays <= 6) {
            groups.week.push(consultation);
        } else {
            groups.later.push(consultation);
        }
    });

    return groups;
});

const statCards = computed(() => [
    {
        key: 'all' as ActiveFilter,
        label: 'Total',
        value: stats.value.total,
        icon: Inbox,
        accent: 'text-gray-900',
        activeClass: 'border-gray-900 bg-gray-50',
    },
    {
        key: 'upcoming' as ActiveFilter,
        label: 'Próximas',
        value: stats.value.upcoming,
        icon: Calendar,
        accent: 'text-teal-800',
        activeClass: 'border-teal-500 bg-teal-50',
    },
    {
        key: 'completed' as ActiveFilter,
        label: 'Concluídas',
        value: stats.value.completed,
        icon: Check,
        accent: 'text-emerald-700',
        activeClass: 'border-emerald-500 bg-emerald-50',
    },
    {
        key: 'cancelled' as ActiveFilter,
        label: 'Canceladas',
        value: stats.value.cancelled,
        icon: X,
        accent: 'text-rose-700',
        activeClass: 'border-rose-500 bg-rose-50',
    },
]);

const dateRangeLabels: Record<DateRange, string> = {
    all: 'Todo o período',
    '30d': 'Últimos 30 dias',
    '90d': 'Últimos 90 dias',
    year: 'Este ano',
};

onMounted(() => {
    canAccessPatientRoute();
});

const buildQueryParams = (page?: number) => {
    const queryParams: Record<string, string | number> = {};

    if (activeFilter.value !== 'all') {
        queryParams.status = activeFilter.value;
    }

    if (searchQuery.value.trim()) {
        queryParams.search = searchQuery.value.trim();
    }

    if (dateRange.value !== 'all') {
        queryParams.date_range = dateRange.value;
    }

    if (sort.value !== 'recent-first') {
        queryParams.sort = sort.value;
    }

    if (page) {
        queryParams.page = page;
    }

    return queryParams;
};

const applyFilters = (replace = false) => {
    router.get(patientRoutes.historyConsultations.url(), buildQueryParams(), {
        preserveState: true,
        preserveScroll: true,
        replace,
    });
};

const applyFilter = (filter: ActiveFilter) => {
    activeFilter.value = filter;
    applyFilters();
};

const goToPage = (page: number) => {
    router.get(patientRoutes.historyConsultations.url(), buildQueryParams(page), {
        preserveState: true,
        preserveScroll: true,
    });
};

const resetFilters = () => {
    activeFilter.value = 'all';
    searchQuery.value = '';
    dateRange.value = 'all';
    sort.value = 'recent-first';
    applyFilters();
};

const removeChip = (kind: 'search' | 'date') => {
    if (kind === 'search') {
        searchQuery.value = '';
    }

    if (kind === 'date') {
        dateRange.value = 'all';
    }
};

let searchTimeout: ReturnType<typeof setTimeout> | null = null;
watch(searchQuery, () => {
    if (searchTimeout) {
        clearTimeout(searchTimeout);
    }

    searchTimeout = setTimeout(() => applyFilters(true), 500);
});

watch(dateRange, () => applyFilters());
watch(sort, () => applyFilters());

const doctorName = (consultation: Appointment) => consultation.doctor?.user?.name ?? 'Médico não informado';
const doctorAvatar = (consultation: Appointment) => consultation.doctor?.user?.avatar ?? null;
const doctorSpecialization = (consultation: Appointment) => consultation.doctor?.specializations?.[0]?.name ?? 'Especialista';

const formatDate = (value: string | null) => {
    if (!value) {
        return 'Data não informada';
    }

    try {
        return new Intl.DateTimeFormat('pt-BR', {
            weekday: 'short',
            day: '2-digit',
            month: 'short',
            year: 'numeric',
        }).format(new Date(value));
    } catch {
        return value;
    }
};

const formatTime = (value: string | null) => {
    if (!value) {
        return '--:--';
    }

    try {
        return new Intl.DateTimeFormat('pt-BR', {
            hour: '2-digit',
            minute: '2-digit',
        }).format(new Date(value));
    } catch {
        return '--:--';
    }
};

const pageNumbers = computed(() => {
    const current = pagination.value.current_page;
    const last = pagination.value.last_page;
    const start = Math.max(1, current - 2);
    const end = Math.min(last, current + 2);
    return Array.from({ length: end - start + 1 }, (_, index) => start + index);
});

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: patientRoutes.dashboard().url,
    },
    {
        title: 'Histórico de Consultas',
        href: patientRoutes.historyConsultations().url,
    },
];
</script>

<template>
    <Head title="Minhas Consultas" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-full bg-[#f5f5f0] px-2 py-6 text-gray-950 sm:px-3 lg:px-4">
            <div class="flex w-full flex-col gap-5">
                <section
                    class="flex flex-col gap-4 rounded-lg border border-[#dedbd2] bg-white p-5 shadow-sm lg:flex-row lg:items-end lg:justify-between"
                >
                    <div class="max-w-3xl space-y-2">
                        <div class="text-xs font-bold text-gray-500">
                            <Link :href="patientRoutes.dashboard()" class="hover:text-teal-700">Início</Link>
                            <span class="mx-2">›</span>
                            <span class="font-extrabold text-gray-800">Minhas consultas</span>
                        </div>
                        <h1 class="text-3xl font-black text-gray-950 sm:text-4xl">Minhas consultas</h1>
                        <p class="text-base font-medium text-gray-600">Veja, gerencie e acompanhe todas as suas consultas, passadas e futuras.</p>
                    </div>

                    <Button as-child class="h-11 bg-teal-500 px-5 font-black text-gray-950 hover:bg-teal-400">
                        <Link :href="patientRoutes.searchConsultations()">
                            <Plus class="mr-2 h-4 w-4" />
                            Agendar nova consulta
                        </Link>
                    </Button>
                </section>

                <section class="rounded-lg border border-teal-100 bg-teal-50 p-4 shadow-sm">
                    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                        <div class="flex items-start gap-3">
                            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-white text-teal-700 shadow-sm">
                                <Sparkles class="h-5 w-5" />
                            </span>
                            <div>
                                <h2 class="text-base font-black text-teal-950">Resumo da sua semana</h2>
                                <p class="mt-1 text-sm font-semibold text-teal-800">
                                    Você tem {{ stats.upcoming }} consulta(s) próxima(s). Mantenha seus documentos e conexão preparados antes do
                                    atendimento.
                                </p>
                            </div>
                        </div>
                        <Button as-child variant="outline" class="border-teal-200 bg-white font-extrabold text-teal-900 hover:bg-teal-50">
                            <Link :href="patientRoutes.medicalRecords()">Ver prontuário</Link>
                        </Button>
                    </div>
                </section>

                <section class="grid grid-cols-2 gap-3 xl:grid-cols-4">
                    <button
                        v-for="card in statCards"
                        :key="card.key"
                        type="button"
                        class="rounded-lg border bg-white p-4 text-left shadow-sm transition hover:border-teal-200 hover:shadow-md"
                        :class="activeFilter === card.key ? card.activeClass : 'border-[#dedbd2]'"
                        @click="applyFilter(card.key)"
                    >
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-xs font-black text-gray-500 uppercase">{{ card.label }}</span>
                            <component :is="card.icon" class="h-4 w-4" :class="card.accent" />
                        </div>
                        <p class="mt-3 text-3xl font-black text-gray-950">{{ card.value }}</p>
                    </button>
                </section>

                <section class="rounded-lg border border-[#dedbd2] bg-white p-4 shadow-sm">
                    <div class="grid gap-3 xl:grid-cols-[minmax(260px,1fr)_220px_220px_auto] xl:items-center">
                        <div class="relative">
                            <Search class="pointer-events-none absolute top-1/2 left-4 h-4 w-4 -translate-y-1/2 text-gray-400" />
                            <Input
                                v-model="searchQuery"
                                placeholder="Buscar por médico ou especialidade"
                                class="h-11 rounded-lg border-[#d7d2c8] bg-[#fbfbf7] pl-11 font-semibold focus:border-teal-600 focus:ring-teal-600/20"
                            />
                        </div>

                        <select
                            v-model="dateRange"
                            class="h-11 rounded-lg border border-[#d7d2c8] bg-white px-3 text-sm font-semibold text-gray-800 focus:border-teal-600 focus:ring-2 focus:ring-teal-600/20 focus:outline-none"
                        >
                            <option value="all">Todo o período</option>
                            <option value="30d">Últimos 30 dias</option>
                            <option value="90d">Últimos 90 dias</option>
                            <option value="year">Este ano</option>
                        </select>

                        <select
                            v-model="sort"
                            class="h-11 rounded-lg border border-[#d7d2c8] bg-white px-3 text-sm font-semibold text-gray-800 focus:border-teal-600 focus:ring-2 focus:ring-teal-600/20 focus:outline-none"
                        >
                            <option value="recent-first">Mais recentes</option>
                            <option value="soonest-first">Mais próximas</option>
                            <option value="oldest-first">Mais antigas</option>
                        </select>

                        <Button variant="ghost" class="h-11 font-extrabold text-gray-600 hover:text-teal-700" @click="resetFilters">
                            Limpar filtros
                        </Button>
                    </div>

                    <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <p class="text-sm font-extrabold text-gray-600">{{ totalResults }} consulta(s) encontrada(s)</p>

                        <div v-if="activeChips.length" class="flex flex-wrap gap-2">
                            <button
                                v-for="chip in activeChips"
                                :key="`${chip.kind}-${chip.label}`"
                                type="button"
                                class="inline-flex items-center gap-2 rounded-full border border-teal-100 bg-teal-50 px-3 py-1.5 text-xs font-extrabold text-teal-900"
                                @click="removeChip(chip.kind)"
                            >
                                {{ chip.label }}
                                <X class="h-3.5 w-3.5" />
                            </button>
                        </div>
                    </div>
                </section>

                <section v-if="consultations.length > 0" class="space-y-5">
                    <template v-if="activeFilter === 'upcoming'">
                        <div
                            v-for="group in [
                                { title: 'Hoje', items: upcomingGroups.today },
                                { title: 'Esta semana', items: upcomingGroups.week },
                                { title: 'Mais tarde', items: upcomingGroups.later },
                            ]"
                            :key="group.title"
                            class="space-y-3"
                        >
                            <div v-if="group.items.length">
                                <h2 class="mb-2 inline-flex items-center gap-2 text-xs font-black tracking-wide text-gray-500 uppercase">
                                    {{ group.title }}
                                    <span class="rounded-full border border-[#dedbd2] bg-white px-2 py-0.5 text-[11px] text-gray-600">{{
                                        group.items.length
                                    }}</span>
                                </h2>

                                <article
                                    v-for="consultation in group.items"
                                    :key="consultation.id"
                                    class="rounded-lg border border-[#dedbd2] bg-white p-4 shadow-sm transition hover:border-teal-200 hover:shadow-md"
                                >
                                    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                                        <div class="flex min-w-0 gap-4">
                                            <Avatar class="h-14 w-14 shrink-0 border border-teal-100">
                                                <AvatarImage v-if="doctorAvatar(consultation)" :src="doctorAvatar(consultation) ?? undefined" />
                                                <AvatarFallback class="bg-teal-50 text-base font-black text-teal-800">
                                                    {{ getInitials(doctorName(consultation)) }}
                                                </AvatarFallback>
                                            </Avatar>
                                            <div class="min-w-0">
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <h3 class="text-lg font-black text-gray-950">{{ doctorName(consultation) }}</h3>
                                                    <AppointmentStatusBadge :status="consultation.status" />
                                                </div>
                                                <p class="mt-1 flex items-center gap-2 text-sm font-bold text-gray-600">
                                                    <Stethoscope class="h-4 w-4 text-teal-700" />
                                                    {{ doctorSpecialization(consultation) }}
                                                </p>
                                                <div class="mt-3 flex flex-wrap gap-2 text-xs font-extrabold text-gray-600">
                                                    <span class="inline-flex items-center gap-1 rounded-full bg-[#f5f5f0] px-3 py-1">
                                                        <Calendar class="h-3.5 w-3.5" />
                                                        {{ formatDate(consultation.scheduled_at) }}
                                                    </span>
                                                    <span class="inline-flex items-center gap-1 rounded-full bg-[#f5f5f0] px-3 py-1">
                                                        <Clock class="h-3.5 w-3.5" />
                                                        {{ formatTime(consultation.scheduled_at) }}
                                                    </span>
                                                    <span class="inline-flex items-center gap-1 rounded-full bg-sky-50 px-3 py-1 text-sky-700">
                                                        <Video class="h-3.5 w-3.5" />
                                                        Online
                                                    </span>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="flex flex-wrap gap-2">
                                            <Button
                                                v-if="['scheduled', 'rescheduled', 'in_progress'].includes(consultation.status)"
                                                as-child
                                                class="bg-teal-500 font-black text-gray-950 hover:bg-teal-400"
                                            >
                                                <Link :href="patientRoutes.videoCall()">Entrar</Link>
                                            </Button>
                                            <Button
                                                as-child
                                                variant="outline"
                                                class="border-[#d7d2c8] bg-white font-extrabold text-gray-700 hover:bg-gray-50"
                                            >
                                                <Link :href="patientRoutes.consultationDetails({ appointment: consultation.id })">Detalhes</Link>
                                            </Button>
                                        </div>
                                    </div>
                                </article>
                            </div>
                        </div>
                    </template>

                    <div v-else class="overflow-hidden rounded-lg border border-[#dedbd2] bg-white shadow-sm">
                        <article
                            v-for="(consultation, index) in consultations"
                            :key="consultation.id"
                            class="grid gap-4 p-4 transition hover:bg-[#fbfbf7] lg:grid-cols-[minmax(0,1fr)_180px_160px] lg:items-center"
                            :class="index > 0 ? 'border-t border-[#ebe7df]' : ''"
                        >
                            <div class="flex min-w-0 gap-4">
                                <Avatar class="h-12 w-12 shrink-0 border border-teal-100">
                                    <AvatarImage v-if="doctorAvatar(consultation)" :src="doctorAvatar(consultation) ?? undefined" />
                                    <AvatarFallback class="bg-teal-50 text-sm font-black text-teal-800">
                                        {{ getInitials(doctorName(consultation)) }}
                                    </AvatarFallback>
                                </Avatar>
                                <div class="min-w-0">
                                    <h3 class="truncate text-base font-black text-gray-950">{{ doctorName(consultation) }}</h3>
                                    <p class="mt-1 text-sm font-bold text-gray-600">{{ doctorSpecialization(consultation) }}</p>
                                    <p class="mt-2 flex items-center gap-2 text-xs font-extrabold text-gray-500">
                                        <Calendar class="h-3.5 w-3.5" />
                                        {{ formatDate(consultation.scheduled_at) }} às {{ formatTime(consultation.scheduled_at) }}
                                    </p>
                                </div>
                            </div>

                            <AppointmentStatusBadge :status="consultation.status" class="w-fit" />

                            <div class="flex justify-start gap-2 lg:justify-end">
                                <Button as-child variant="outline" class="border-[#d7d2c8] bg-white font-extrabold text-gray-700 hover:bg-gray-50">
                                    <Link :href="patientRoutes.consultationDetails({ appointment: consultation.id })">
                                        <FileText class="mr-2 h-4 w-4" />
                                        Detalhes
                                    </Link>
                                </Button>
                            </div>
                        </article>
                    </div>
                </section>

                <section v-else class="rounded-lg border border-dashed border-[#d7d2c8] bg-white px-6 py-14 text-center shadow-sm">
                    <Inbox class="mx-auto h-12 w-12 text-gray-300" />
                    <h2 class="mt-4 text-xl font-black text-gray-950">Nenhuma consulta encontrada</h2>
                    <p class="mx-auto mt-2 max-w-md text-sm font-medium text-gray-500">
                        Ajuste os filtros ou agende uma nova consulta para começar seu acompanhamento.
                    </p>
                    <div class="mt-6 flex flex-wrap justify-center gap-2">
                        <Button v-if="hasFilters" variant="outline" class="border-[#d7d2c8] font-extrabold" @click="resetFilters">
                            Limpar filtros
                        </Button>
                        <Button as-child class="bg-teal-500 font-black text-gray-950 hover:bg-teal-400">
                            <Link :href="patientRoutes.searchConsultations()">Agendar consulta</Link>
                        </Button>
                    </div>
                </section>

                <nav v-if="pagination.last_page > 1" class="flex flex-wrap items-center justify-center gap-2">
                    <button
                        type="button"
                        class="grid h-10 min-w-10 place-items-center rounded-lg border border-[#d7d2c8] bg-white px-3 text-sm font-black text-gray-700 transition hover:border-teal-200 hover:bg-teal-50 disabled:cursor-not-allowed disabled:opacity-45"
                        :disabled="pagination.current_page <= 1"
                        @click="goToPage(pagination.current_page - 1)"
                    >
                        <ChevronLeft class="h-4 w-4" />
                    </button>
                    <button
                        v-for="page in pageNumbers"
                        :key="page"
                        type="button"
                        class="grid h-10 min-w-10 place-items-center rounded-lg border px-3 text-sm font-black transition"
                        :class="
                            page === pagination.current_page
                                ? 'border-teal-500 bg-teal-500 text-gray-950'
                                : 'border-[#d7d2c8] bg-white text-gray-700 hover:border-teal-200 hover:bg-teal-50'
                        "
                        @click="goToPage(page)"
                    >
                        {{ page }}
                    </button>
                    <button
                        type="button"
                        class="grid h-10 min-w-10 place-items-center rounded-lg border border-[#d7d2c8] bg-white px-3 text-sm font-black text-gray-700 transition hover:border-teal-200 hover:bg-teal-50 disabled:cursor-not-allowed disabled:opacity-45"
                        :disabled="pagination.current_page >= pagination.last_page"
                        @click="goToPage(pagination.current_page + 1)"
                    >
                        <ChevronRight class="h-4 w-4" />
                    </button>
                </nav>

                <section
                    class="flex flex-col gap-4 rounded-lg border border-teal-100 bg-white p-5 shadow-sm sm:flex-row sm:items-center sm:justify-between"
                >
                    <div>
                        <h2 class="text-xl font-black text-gray-950">Precisa de um acompanhamento?</h2>
                        <p class="mt-1 text-sm font-semibold text-gray-600">Agende uma nova consulta para manter sua saúde em dia.</p>
                    </div>
                    <Button as-child class="bg-teal-500 font-black text-gray-950 hover:bg-teal-400">
                        <Link :href="patientRoutes.searchConsultations()">Agendar nova consulta</Link>
                    </Button>
                </section>
            </div>
        </div>
    </AppLayout>
</template>
