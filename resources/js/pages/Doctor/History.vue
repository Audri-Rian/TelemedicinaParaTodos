<script setup lang="ts">
import DataGridSkeleton from '@/components/skeletons/DataGridSkeleton.vue';
import { useLoadState } from '@/composables/useLoadState';
import AppLayout from '@/layouts/AppLayout.vue';
import * as doctorRoutes from '@/routes/doctor';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { CalendarClock, ChevronDown, Eye, FileClock, FileText, Plus, RefreshCw, Search, SlidersHorizontal, StickyNote } from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: doctorRoutes.dashboard().url,
    },
    {
        title: 'Histórico',
        href: '/doctor/history',
    },
];

type AppointmentStatus = 'confirmed' | 'concluded' | 'missed' | 'in_progress' | 'cancelled';
type FilterStatus = AppointmentStatus | 'all' | 'rescheduled';
type FilterPeriod = 'today' | '7d' | '30d';

type Appointment = {
    id: string;
    time: string;
    patient: string;
    detail: string;
    duration: string;
    age: string;
    gender: string;
    initials: string;
    status: AppointmentStatus;
    statusLabel: string;
};

type DayGroup = {
    id: string;
    label: string;
    dateLabel: string;
    summary: string;
    appointments: Appointment[];
};

type DocumentItem = {
    id: string;
    name: string;
    category: string;
    patient_name: string;
    created_at?: string | null;
};

type DocumentsSummary = {
    count?: number;
    count30Days?: number;
    latest: DocumentItem[];
};

type PeriodSummary = {
    total: number;
    confirmationRate: number;
    missed: number;
    averageDuration: string;
    statusCounts: Record<FilterStatus, number>;
};

type PendingSummary = {
    unfinishedRecords: number;
    unsignedPrescriptions: number;
    reschedulesWaiting: number;
};

type Filters = {
    period: FilterPeriod;
    status: FilterStatus;
    periodLabel: string;
    documentsPeriodDays: number;
};

interface Props {
    dayGroups?: DayGroup[];
    documentsSummary?: DocumentsSummary;
    periodSummary?: PeriodSummary;
    pendingSummary?: PendingSummary;
    filters?: Filters;
}

const props = defineProps<Props>();

const statusClassMap: Record<AppointmentStatus, string> = {
    confirmed: 'border-emerald-200 bg-emerald-50/80 text-emerald-700',
    concluded: 'border-zinc-200 bg-zinc-100 text-zinc-700',
    missed: 'border-amber-200 bg-amber-50 text-amber-700',
    in_progress: 'border-cyan-200 bg-cyan-50 text-cyan-700',
    cancelled: 'border-rose-200 bg-rose-50 text-rose-700',
};

const markerClassMap: Record<AppointmentStatus, string> = {
    confirmed: 'bg-emerald-500',
    concluded: 'bg-zinc-400',
    missed: 'bg-amber-500',
    in_progress: 'bg-cyan-500',
    cancelled: 'bg-rose-500',
};

const initialsClassMap: Record<AppointmentStatus, string> = {
    confirmed: 'bg-emerald-100 text-emerald-700',
    concluded: 'bg-zinc-100 text-zinc-700',
    missed: 'bg-amber-100 text-amber-700',
    in_progress: 'bg-cyan-100 text-cyan-700',
    cancelled: 'bg-rose-100 text-rose-700',
};

const dayGroups = computed(() => props.dayGroups ?? []);
const documentsSummary = computed<DocumentsSummary>(() => props.documentsSummary ?? { count: 0, latest: [] });
const documentCount = computed(() => documentsSummary.value.count ?? documentsSummary.value.count30Days ?? 0);
const periodSummary = computed<PeriodSummary>(
    () =>
        props.periodSummary ?? {
            total: 0,
            confirmationRate: 0,
            missed: 0,
            averageDuration: '0 min',
            statusCounts: {
                all: 0,
                confirmed: 0,
                concluded: 0,
                missed: 0,
                in_progress: 0,
                cancelled: 0,
                rescheduled: 0,
            },
        },
);
const pendingSummary = computed<PendingSummary>(
    () =>
        props.pendingSummary ?? {
            unfinishedRecords: 0,
            unsignedPrescriptions: 0,
            reschedulesWaiting: 0,
        },
);
const filters = computed<Filters>(
    () =>
        props.filters ?? {
            period: '30d',
            status: 'all',
            periodLabel: '30 dias',
            documentsPeriodDays: 30,
        },
);
const hasAppointments = computed(() => dayGroups.value.some((group) => group.appointments.length > 0));

const documentsHistoryUrl = computed(() => `/doctor/documents/history?period_days=${filters.value.documentsPeriodDays}`);
const documentsPeriodLabel = computed(() => (filters.value.documentsPeriodDays === 1 ? 'Hoje' : `Últimos ${filters.value.documentsPeriodDays} dias`));
const scheduleUrl = computed(() => `${doctorRoutes.schedule().url}?tab=configure`);

const periodOptions: Array<{ value: FilterPeriod; label: string }> = [
    { value: 'today', label: 'Hoje' },
    { value: '7d', label: '7 dias' },
    { value: '30d', label: '30 dias' },
];

const statusOptions: Array<{ value: FilterStatus; label: string }> = [
    { value: 'all', label: 'Todas' },
    { value: 'confirmed', label: 'Confirmadas' },
    { value: 'concluded', label: 'Concluídas' },
    { value: 'missed', label: 'Faltas' },
    { value: 'in_progress', label: 'Em andamento' },
    { value: 'rescheduled', label: 'Reagendadas' },
    { value: 'cancelled', label: 'Canceladas' },
];

const { status, showSkeleton, errorMessage, startLoading, completeSuccess, completeError, isLoading } = useLoadState({
    hasInitialData: Boolean(props.dayGroups),
    defaultErrorMessage: 'Não foi possível carregar o histórico de consultas.',
});
const loadingTarget = ref<string | null>(null);
const showPartialSkeleton = computed(() => isLoading.value && !showSkeleton.value && status.value !== 'error');

const completeLoadingSuccess = () => {
    loadingTarget.value = null;
    completeSuccess();
};

const completeLoadingError = () => {
    loadingTarget.value = null;
    completeError();
};

const reloadHistory = (options?: { forceSkeleton?: boolean; minLoadingMs?: number }) => {
    loadingTarget.value = 'refresh';
    startLoading(options);

    router.reload({
        only: ['dayGroups', 'documentsSummary', 'periodSummary', 'pendingSummary', 'filters'],
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => completeLoadingSuccess(),
        onError: () => completeLoadingError(),
        onCancel: () => completeLoadingSuccess(),
    });
};

const applyFilters = (nextFilters: Partial<Pick<Filters, 'period' | 'status'>>) => {
    loadingTarget.value = nextFilters.period ? `period:${nextFilters.period}` : nextFilters.status ? `status:${nextFilters.status}` : 'filters';
    startLoading({ minLoadingMs: 180 });

    router.get(
        '/doctor/history',
        {
            period: nextFilters.period ?? filters.value.period,
            status: nextFilters.status ?? filters.value.status,
        },
        {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => completeLoadingSuccess(),
            onError: () => completeLoadingError(),
            onCancel: () => completeLoadingSuccess(),
        },
    );
};

const clearFilters = () => {
    applyFilters({ period: '30d', status: 'all' });
};

const openConsultation = (appointmentId: string) => {
    router.get(`/doctor/consultations/${appointmentId}?from=history`);
};

const openDocuments = () => {
    router.get('/doctor/documents');
};

const pendingText = (count: number, singular: string, plural: string) => `${count} ${count === 1 ? singular : plural}`;

const filterButtonClass = (isActive: boolean) =>
    isActive
        ? 'filter-chip inline-flex items-center gap-1.5 rounded-full bg-zinc-900 px-4 py-1.5 text-sm font-medium text-white'
        : 'filter-chip inline-flex items-center gap-1.5 rounded-full border border-zinc-200 bg-white px-4 py-1.5 text-sm font-medium text-zinc-600 transition hover:bg-zinc-50';

const isFilterLoading = (kind: 'period' | 'status', value: FilterPeriod | FilterStatus) => loadingTarget.value === `${kind}:${value}`;

const activeStatusLabel = computed(() => statusOptions.find((option) => option.value === filters.value.status)?.label ?? 'Todas');
const activeFilterDescription = computed(() => `${filters.value.periodLabel} · ${activeStatusLabel.value}`);

onMounted(() => {
    if (!props.dayGroups) {
        reloadHistory();
    }
});

const formatDate = (date?: string | null) => {
    if (!date) {
        return 'Data indisponível';
    }

    const parsed = new Date(date);
    if (Number.isNaN(parsed.getTime())) {
        return 'Data indisponível';
    }

    return parsed.toLocaleDateString('pt-BR', {
        day: '2-digit',
        month: '2-digit',
    });
};
</script>

<template>
    <Head title="Histórico de Consultas" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="history-shell flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl bg-[#f6f7f4] p-6">
            <div class="history-header flex flex-col gap-5">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <h1 class="text-3xl font-bold tracking-tight text-zinc-900">Histórico de consultas</h1>
                        <p class="mt-1 text-sm text-zinc-600">
                            Localize teleconsultas anteriores e futuras. Abra detalhes, reagende ou retome notas.
                        </p>
                    </div>

                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            :disabled="isLoading"
                            @click="reloadHistory()"
                            class="interactive-button inline-flex h-10 w-10 items-center justify-center rounded-xl border border-zinc-200 bg-white text-zinc-500 transition hover:bg-zinc-50 disabled:cursor-wait disabled:opacity-70"
                        >
                            <RefreshCw :class="isLoading ? 'size-4 animate-spin' : 'size-4'" />
                        </button>
                        <Link
                            href="/doctor/consultations"
                            class="interactive-button inline-flex items-center gap-2 rounded-xl border border-zinc-200 bg-white px-4 py-2.5 text-sm font-medium text-zinc-600 transition hover:bg-zinc-50"
                        >
                            <SlidersHorizontal class="size-4" />
                            Consultas ativas
                        </Link>
                        <Link
                            :href="scheduleUrl"
                            class="interactive-button inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-zinc-900 transition hover:bg-primary/90"
                        >
                            <Plus class="size-4" />
                            Nova consulta
                        </Link>
                    </div>
                </div>

                <div class="history-filter-bar flex flex-wrap items-center gap-2">
                    <span class="text-xs font-semibold tracking-wider text-zinc-500 uppercase">Período</span>

                    <button
                        v-for="periodOption in periodOptions"
                        :key="periodOption.value"
                        type="button"
                        :disabled="isLoading"
                        :class="[filterButtonClass(filters.period === periodOption.value), isLoading ? 'cursor-wait opacity-70' : '']"
                        @click="applyFilters({ period: periodOption.value })"
                    >
                        <RefreshCw v-if="isFilterLoading('period', periodOption.value)" class="size-3 animate-spin" />
                        {{ periodOption.label }}
                    </button>

                    <span class="ml-4 text-xs font-semibold tracking-wider text-zinc-500 uppercase">Status</span>
                    <button
                        v-for="statusOption in statusOptions"
                        :key="statusOption.value"
                        type="button"
                        :disabled="isLoading"
                        :class="[filterButtonClass(filters.status === statusOption.value), isLoading ? 'cursor-wait opacity-70' : '']"
                        @click="applyFilters({ status: statusOption.value })"
                    >
                        <RefreshCw v-if="isFilterLoading('status', statusOption.value)" class="size-3 animate-spin" />
                        {{ statusOption.label }}
                        <span
                            :class="filters.status === statusOption.value ? 'bg-white/20 text-white' : 'text-zinc-400'"
                            class="ml-1 rounded-full px-1.5 py-0.5 text-xs"
                        >
                            {{ periodSummary.statusCounts[statusOption.value] ?? 0 }}
                        </span>
                    </button>
                </div>
            </div>

            <Transition name="history-panel" mode="out-in" appear>
                <DataGridSkeleton v-if="showSkeleton" :row-count="5" :show-sidebar="true" :sidebar-stats-cards="4" :sidebar-lines="3" />

                <section
                    v-else-if="status === 'error'"
                    class="history-state-panel flex min-h-[520px] items-center justify-center rounded-2xl border border-zinc-200 bg-white px-6 py-12 text-center shadow-sm"
                >
                    <div class="max-w-md space-y-3">
                        <h2 class="text-xl font-semibold text-zinc-900">Falha ao carregar histórico</h2>
                        <p class="text-sm text-zinc-500">{{ errorMessage }}</p>
                        <button
                            type="button"
                            @click="reloadHistory()"
                            class="interactive-button inline-flex items-center gap-2 rounded-xl bg-primary px-5 py-2.5 text-sm font-semibold text-zinc-900 transition hover:bg-primary/90"
                        >
                            <RefreshCw class="size-4" />
                            Tentar novamente
                        </button>
                    </div>
                </section>

                <div v-else-if="hasAppointments" class="relative grid gap-4 lg:grid-cols-[minmax(0,1fr)_320px]" :aria-busy="showPartialSkeleton">
                    <section class="history-card rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm">
                        <div
                            v-for="(dayGroup, dayGroupIndex) in dayGroups"
                            :key="dayGroup.id"
                            class="day-card rounded-2xl border border-zinc-100 bg-zinc-50/70 p-3"
                            :style="{ animationDelay: `${Math.min(dayGroupIndex * 60, 240)}ms` }"
                        >
                            <div class="mb-3 flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-semibold text-cyan-700">{{ dayGroup.label }}</span>
                                    <span class="text-xs text-zinc-500">{{ dayGroup.dateLabel }}</span>
                                </div>
                                <p class="text-xs text-zinc-500">{{ dayGroup.summary }}</p>
                            </div>

                            <TransitionGroup name="timeline-item" tag="div" class="space-y-2" appear>
                                <div
                                    v-for="(appointment, appointmentIndex) in dayGroup.appointments"
                                    :key="appointment.id"
                                    class="group flex gap-3"
                                    :style="{ transitionDelay: `${Math.min(appointmentIndex * 35, 175)}ms` }"
                                >
                                    <div class="flex w-14 flex-col items-end">
                                        <span class="text-sm font-medium text-zinc-600">{{ appointment.time }}</span>
                                    </div>
                                    <div class="relative flex w-5 justify-center pt-1.5">
                                        <span class="h-full w-px bg-zinc-200" />
                                        <span
                                            :class="markerClassMap[appointment.status]"
                                            class="absolute top-2 h-2.5 w-2.5 rounded-full ring-2 ring-white"
                                        />
                                    </div>

                                    <article
                                        :class="appointment.status === 'in_progress' ? 'border-cyan-300 bg-cyan-50/60' : 'border-zinc-200 bg-white'"
                                        class="appointment-card flex flex-1 items-center justify-between rounded-2xl border px-4 py-3 transition group-hover:border-zinc-300"
                                    >
                                        <div class="flex items-center gap-3">
                                            <div
                                                :class="initialsClassMap[appointment.status]"
                                                class="flex size-10 items-center justify-center rounded-xl text-sm font-semibold"
                                            >
                                                {{ appointment.initials }}
                                            </div>
                                            <div>
                                                <div class="flex items-center gap-2">
                                                    <h3 class="text-sm font-semibold text-zinc-900">{{ appointment.patient }}</h3>
                                                    <span class="text-xs text-zinc-400">{{ appointment.age }} · {{ appointment.gender }}</span>
                                                </div>
                                                <p class="text-sm text-zinc-600">{{ appointment.detail }} · {{ appointment.duration }}</p>
                                            </div>
                                        </div>

                                        <div class="flex items-center gap-2">
                                            <span
                                                :class="statusClassMap[appointment.status]"
                                                class="inline-flex items-center rounded-full border px-3 py-1 text-xs font-semibold"
                                            >
                                                {{ appointment.statusLabel }}
                                            </span>
                                            <button
                                                type="button"
                                                :aria-label="`Abrir detalhes da consulta de ${appointment.patient}`"
                                                title="Abrir detalhes da consulta"
                                                @click="openConsultation(appointment.id)"
                                                class="interactive-button inline-flex h-8 w-8 items-center justify-center rounded-lg border border-zinc-200 bg-white text-zinc-500 transition hover:bg-zinc-50"
                                            >
                                                <Eye class="size-4" />
                                            </button>
                                        </div>
                                    </article>
                                </div>
                            </TransitionGroup>
                        </div>
                    </section>

                    <aside class="space-y-4">
                        <section class="sidebar-card rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm">
                            <h2 class="text-xs font-semibold tracking-wider text-zinc-500 uppercase">Resumo do período</h2>
                            <div class="mt-3 grid grid-cols-2 gap-2">
                                <div class="rounded-xl border border-zinc-100 bg-zinc-50 p-3">
                                    <p class="text-3xl font-bold text-zinc-900">{{ periodSummary.total }}</p>
                                    <p class="text-xs text-zinc-500">Atendimentos</p>
                                </div>
                                <div class="rounded-xl border border-zinc-100 bg-cyan-50 p-3">
                                    <p class="text-3xl font-bold text-cyan-700">{{ periodSummary.confirmationRate }}%</p>
                                    <p class="text-xs text-zinc-500">Confirmação</p>
                                </div>
                                <div class="rounded-xl border border-zinc-100 bg-amber-50 p-3">
                                    <p class="text-3xl font-bold text-amber-700">{{ periodSummary.missed }}</p>
                                    <p class="text-xs text-zinc-500">Faltas</p>
                                </div>
                                <div class="rounded-xl border border-zinc-100 bg-zinc-50 p-3">
                                    <p class="text-3xl font-bold text-zinc-900">{{ periodSummary.averageDuration }}</p>
                                    <p class="text-xs text-zinc-500">Tempo médio</p>
                                </div>
                            </div>
                        </section>

                        <section class="sidebar-card rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm">
                            <div class="flex items-center justify-between gap-2">
                                <h2 class="text-xs font-semibold tracking-wider text-zinc-500 uppercase">Documentos emitidos</h2>
                                <FileClock class="size-4 text-zinc-400" />
                            </div>
                            <p class="mt-2 text-3xl font-bold text-zinc-900">{{ documentCount }}</p>
                            <p class="text-xs text-zinc-500">{{ documentsPeriodLabel }}</p>

                            <ul class="mt-3 space-y-2">
                                <li
                                    v-for="document in documentsSummary.latest"
                                    :key="document.id"
                                    class="document-row rounded-xl border border-zinc-100 bg-zinc-50 px-3 py-2"
                                >
                                    <p class="truncate text-xs font-semibold text-zinc-900">{{ document.name }}</p>
                                    <p class="text-[11px] text-zinc-500">{{ document.patient_name }} · {{ formatDate(document.created_at) }}</p>
                                </li>
                            </ul>

                            <a
                                :href="documentsHistoryUrl"
                                class="interactive-button mt-3 inline-flex items-center justify-center rounded-xl border border-zinc-200 bg-white px-3 py-2 text-xs font-semibold text-zinc-700 transition hover:bg-zinc-50"
                            >
                                Ver histórico completo
                            </a>
                        </section>

                        <section class="sidebar-card rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm">
                            <h2 class="text-xs font-semibold tracking-wider text-zinc-500 uppercase">Pendências</h2>
                            <div class="mt-2 divide-y divide-zinc-100">
                                <button
                                    type="button"
                                    class="pending-row flex w-full items-center justify-between gap-3 py-3 text-left transition hover:text-zinc-900"
                                    @click="applyFilters({ period: '30d', status: 'in_progress' })"
                                >
                                    <span class="inline-flex items-center gap-2 text-sm text-zinc-700">
                                        <FileText class="size-4 text-zinc-400" />
                                        {{ pendingText(pendingSummary.unfinishedRecords, 'prontuário a finalizar', 'prontuários a finalizar') }}
                                    </span>
                                    <ChevronDown class="size-4 -rotate-90 text-zinc-400" />
                                </button>
                                <button
                                    type="button"
                                    class="pending-row flex w-full items-center justify-between gap-3 py-3 text-left transition hover:text-zinc-900"
                                    @click="openDocuments"
                                >
                                    <span class="inline-flex items-center gap-2 text-sm text-zinc-700">
                                        <StickyNote class="size-4 text-zinc-400" />
                                        {{
                                            pendingText(
                                                pendingSummary.unsignedPrescriptions,
                                                'prescrição sem assinatura',
                                                'prescrições sem assinatura',
                                            )
                                        }}
                                    </span>
                                    <ChevronDown class="size-4 -rotate-90 text-zinc-400" />
                                </button>
                                <button
                                    type="button"
                                    class="pending-row flex w-full items-center justify-between gap-3 py-3 text-left transition hover:text-zinc-900"
                                    @click="applyFilters({ period: '30d', status: 'rescheduled' })"
                                >
                                    <span class="inline-flex items-center gap-2 text-sm text-zinc-700">
                                        <CalendarClock class="size-4 text-zinc-400" />
                                        {{ pendingText(pendingSummary.reschedulesWaiting, 'reagendamento aguardando', 'reagendamentos aguardando') }}
                                    </span>
                                    <ChevronDown class="size-4 -rotate-90 text-zinc-400" />
                                </button>
                            </div>
                        </section>
                    </aside>

                    <Transition name="partial-skeleton">
                        <div
                            v-if="showPartialSkeleton"
                            class="partial-skeleton-overlay absolute inset-0 z-20 grid gap-4 rounded-2xl bg-[#f6f7f4]/55 backdrop-blur-[1px] lg:grid-cols-[minmax(0,1fr)_320px]"
                            aria-hidden="true"
                        >
                            <section class="rounded-2xl border border-zinc-200 bg-white/95 p-4 shadow-sm">
                                <div
                                    v-for="groupIndex in 3"
                                    :key="`timeline-skeleton-${groupIndex}`"
                                    class="rounded-2xl border border-zinc-100 bg-zinc-50/80 p-3"
                                >
                                    <div class="mb-3 flex items-center justify-between">
                                        <span class="skeleton-block h-4 w-24 rounded-full" />
                                        <span class="skeleton-block h-3 w-28 rounded-full" />
                                    </div>

                                    <div class="space-y-2">
                                        <div v-for="rowIndex in 3" :key="`timeline-skeleton-${groupIndex}-${rowIndex}`" class="flex gap-3">
                                            <div class="flex w-14 justify-end pt-2">
                                                <span class="skeleton-block h-3 w-9 rounded-full" />
                                            </div>
                                            <div class="relative flex w-5 justify-center pt-1.5">
                                                <span class="h-full w-px bg-zinc-200" />
                                                <span class="skeleton-block absolute top-2 h-2.5 w-2.5 rounded-full" />
                                            </div>
                                            <div
                                                class="flex flex-1 items-center justify-between rounded-2xl border border-zinc-200 bg-white px-4 py-3"
                                            >
                                                <div class="flex items-center gap-3">
                                                    <span class="skeleton-block size-10 rounded-xl" />
                                                    <div class="space-y-2">
                                                        <span class="skeleton-block block h-3.5 w-40 rounded-full" />
                                                        <span class="skeleton-block block h-3 w-56 rounded-full" />
                                                    </div>
                                                </div>
                                                <div class="flex items-center gap-2">
                                                    <span class="skeleton-block h-7 w-24 rounded-full" />
                                                    <span class="skeleton-block h-8 w-8 rounded-lg" />
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </section>

                            <aside class="space-y-4">
                                <section
                                    v-for="cardIndex in 3"
                                    :key="`sidebar-skeleton-${cardIndex}`"
                                    class="rounded-2xl border border-zinc-200 bg-white/95 p-4 shadow-sm"
                                >
                                    <span class="skeleton-block mb-4 block h-3 w-36 rounded-full" />
                                    <div v-if="cardIndex === 1" class="grid grid-cols-2 gap-2">
                                        <div
                                            v-for="metricIndex in 4"
                                            :key="`metric-skeleton-${metricIndex}`"
                                            class="rounded-xl border border-zinc-100 bg-zinc-50 p-3"
                                        >
                                            <span class="skeleton-block mb-2 block h-8 w-14 rounded-full" />
                                            <span class="skeleton-block block h-3 w-20 rounded-full" />
                                        </div>
                                    </div>
                                    <div v-else class="space-y-2">
                                        <span class="skeleton-block block h-8 w-16 rounded-full" />
                                        <span class="skeleton-block block h-3 w-28 rounded-full" />
                                        <span class="skeleton-block block h-10 w-full rounded-xl" />
                                        <span class="skeleton-block block h-10 w-full rounded-xl" />
                                    </div>
                                </section>
                            </aside>
                        </div>
                    </Transition>
                </div>

                <DataGridSkeleton v-else-if="showPartialSkeleton" :row-count="5" :show-sidebar="true" :sidebar-stats-cards="4" :sidebar-lines="3" />

                <section
                    v-else
                    class="history-state-panel flex min-h-[520px] items-center justify-center rounded-2xl border border-zinc-200 bg-white px-6 py-12 text-center shadow-sm"
                >
                    <div class="max-w-md">
                        <div class="empty-icon mx-auto mb-6 flex h-18 w-18 items-center justify-center rounded-2xl bg-cyan-100 text-cyan-700">
                            <Search class="size-8" />
                        </div>

                        <h2 class="text-4 mb-2 font-semibold text-zinc-900">Nenhuma consulta encontrada</h2>
                        <p class="text-sm leading-6 text-zinc-500">
                            Tente ajustar o período ou limpar os filtros de status.
                            <br />
                            Você está vendo: <span class="font-semibold text-zinc-700">{{ activeFilterDescription }}</span
                            >.
                        </p>

                        <div class="mt-6 flex items-center justify-center gap-2">
                            <button
                                type="button"
                                @click="clearFilters"
                                class="interactive-button inline-flex items-center rounded-xl border border-zinc-200 bg-white px-5 py-2.5 text-sm font-semibold text-zinc-700 transition hover:bg-zinc-50"
                            >
                                Limpar filtros
                            </button>
                            <Link
                                :href="scheduleUrl"
                                class="interactive-button inline-flex items-center gap-2 rounded-xl bg-primary px-5 py-2.5 text-sm font-semibold text-zinc-900 transition hover:bg-primary/90"
                            >
                                <Plus class="size-4" />
                                Nova consulta
                            </Link>
                        </div>
                    </div>
                </section>
            </Transition>
        </div>
    </AppLayout>
</template>

<style scoped>
.history-shell {
    animation: history-fade-up 220ms ease-out both;
}

.history-header,
.history-filter-bar,
.history-card,
.history-state-panel {
    animation: history-fade-up 260ms ease-out both;
}

.history-filter-bar {
    animation-delay: 80ms;
}

.history-card {
    animation-delay: 120ms;
}

.day-card,
.sidebar-card,
.document-row {
    animation: history-fade-up 300ms ease-out both;
}

.sidebar-card:nth-child(1) {
    animation-delay: 120ms;
}

.sidebar-card:nth-child(2) {
    animation-delay: 180ms;
}

.sidebar-card:nth-child(3) {
    animation-delay: 240ms;
}

.appointment-card,
.interactive-button,
.filter-chip,
.pending-row,
.document-row {
    will-change: transform;
}

.appointment-card {
    transition:
        transform 160ms ease,
        box-shadow 160ms ease,
        border-color 160ms ease,
        background-color 160ms ease;
}

.group:hover .appointment-card {
    transform: translateY(-1px);
    box-shadow: 0 10px 28px rgb(24 24 27 / 0.08);
}

.interactive-button,
.filter-chip,
.pending-row,
.document-row {
    transition:
        transform 150ms ease,
        box-shadow 150ms ease,
        background-color 150ms ease,
        border-color 150ms ease,
        color 150ms ease;
}

.interactive-button:hover,
.filter-chip:hover,
.pending-row:hover,
.document-row:hover {
    transform: translateY(-1px);
}

.interactive-button:active,
.filter-chip:active,
.pending-row:active {
    transform: translateY(0) scale(0.98);
}

.empty-icon {
    animation: empty-pulse 1800ms ease-in-out infinite;
}

.history-panel-enter-active,
.history-panel-leave-active {
    transition:
        opacity 180ms ease,
        transform 180ms ease;
}

.history-panel-enter-from,
.history-panel-leave-to {
    opacity: 0;
    transform: translateY(8px);
}

.timeline-item-enter-active,
.timeline-item-leave-active,
.timeline-item-move {
    transition:
        opacity 180ms ease,
        transform 180ms ease;
}

.timeline-item-enter-from,
.timeline-item-leave-to {
    opacity: 0;
    transform: translateY(8px);
}

.timeline-item-leave-active {
    position: absolute;
}

.partial-skeleton-enter-active,
.partial-skeleton-leave-active {
    transition:
        opacity 160ms ease,
        transform 160ms ease;
}

.partial-skeleton-enter-from,
.partial-skeleton-leave-to {
    opacity: 0;
    transform: translateY(4px);
}

.skeleton-block {
    position: relative;
    display: inline-block;
    overflow: hidden;
    background: #e4e7ec;
}

.skeleton-block::after {
    position: absolute;
    inset: 0;
    content: '';
    background: linear-gradient(90deg, transparent, rgb(255 255 255 / 0.7), transparent);
    animation: skeleton-shimmer 1200ms ease-in-out infinite;
    transform: translateX(-100%);
}

@keyframes history-fade-up {
    from {
        opacity: 0;
        transform: translateY(10px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes skeleton-shimmer {
    100% {
        transform: translateX(100%);
    }
}

@keyframes empty-pulse {
    0%,
    100% {
        transform: scale(1);
        box-shadow: 0 0 0 0 rgb(8 145 178 / 0.12);
    }

    50% {
        transform: scale(1.03);
        box-shadow: 0 0 0 10px rgb(8 145 178 / 0);
    }
}

@media (prefers-reduced-motion: reduce) {
    *,
    *::before,
    *::after {
        animation-duration: 1ms !important;
        animation-iteration-count: 1 !important;
        scroll-behavior: auto !important;
        transition-duration: 1ms !important;
    }
}
</style>
