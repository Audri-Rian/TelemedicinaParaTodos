<script setup lang="ts">
import DataGridSkeleton from '@/components/skeletons/DataGridSkeleton.vue';
import { useLoadState } from '@/composables/useLoadState';
import AppLayout from '@/layouts/AppLayout.vue';
import * as doctorRoutes from '@/routes/doctor';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { CalendarClock, ChevronDown, Eye, FileClock, FileText, Plus, RefreshCw, Search, SlidersHorizontal, StickyNote } from 'lucide-vue-next';
import { computed, onMounted } from 'vue';

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

type AppointmentStatus = 'confirmed' | 'concluded' | 'missed' | 'in_progress';

type Appointment = {
    id: number;
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
    count30Days: number;
    latest: DocumentItem[];
};

interface Props {
    dayGroups?: DayGroup[];
    documentsSummary?: DocumentsSummary;
}

const props = defineProps<Props>();

const statusClassMap: Record<AppointmentStatus, string> = {
    confirmed: 'border-emerald-200 bg-emerald-50/80 text-emerald-700',
    concluded: 'border-zinc-200 bg-zinc-100 text-zinc-700',
    missed: 'border-amber-200 bg-amber-50 text-amber-700',
    in_progress: 'border-cyan-200 bg-cyan-50 text-cyan-700',
};

const markerClassMap: Record<AppointmentStatus, string> = {
    confirmed: 'bg-emerald-500',
    concluded: 'bg-zinc-400',
    missed: 'bg-amber-500',
    in_progress: 'bg-cyan-500',
};

const initialsClassMap: Record<AppointmentStatus, string> = {
    confirmed: 'bg-emerald-100 text-emerald-700',
    concluded: 'bg-zinc-100 text-zinc-700',
    missed: 'bg-amber-100 text-amber-700',
    in_progress: 'bg-cyan-100 text-cyan-700',
};

const dayGroups = computed(() => props.dayGroups ?? []);
const documentsSummary = computed<DocumentsSummary>(() => props.documentsSummary ?? { count30Days: 0, latest: [] });
const hasAppointments = computed(() => dayGroups.value.some((group) => group.appointments.length > 0));

const documentsHistoryUrl = computed(() => '/doctor/documents/history?period_days=30');

const { status, showSkeleton, errorMessage, startLoading, completeSuccess, completeError, isLoading } = useLoadState({
    hasInitialData: Boolean(props.dayGroups),
    defaultErrorMessage: 'Não foi possível carregar o histórico de consultas.',
});

const reloadHistory = (options?: { forceSkeleton?: boolean; minLoadingMs?: number }) => {
    startLoading(options);

    router.reload({
        only: ['dayGroups'],
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => completeSuccess(),
        onError: () => completeError(),
        onCancel: () => completeSuccess(),
    });
};

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
        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl bg-[#f6f7f4] p-6">
            <div class="flex flex-col gap-5">
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
                            @click="reloadHistory"
                            class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-zinc-200 bg-white text-zinc-500 transition hover:bg-zinc-50"
                        >
                            <RefreshCw :class="isLoading ? 'size-4 animate-spin' : 'size-4'" />
                        </button>
                        <button
                            type="button"
                            class="inline-flex items-center gap-2 rounded-xl border border-zinc-200 bg-white px-4 py-2.5 text-sm font-medium text-zinc-600 transition hover:bg-zinc-50"
                        >
                            <SlidersHorizontal class="size-4" />
                            Mais filtros
                        </button>
                        <button
                            type="button"
                            class="inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-zinc-900 transition hover:bg-primary/90"
                        >
                            <Plus class="size-4" />
                            Nova consulta
                        </button>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <span class="text-xs font-semibold tracking-wider text-zinc-500 uppercase">Período</span>

                    <button type="button" class="rounded-full border border-zinc-200 bg-white px-4 py-1.5 text-sm font-medium text-zinc-600">
                        Hoje
                    </button>
                    <button type="button" class="rounded-full bg-zinc-900 px-4 py-1.5 text-sm font-medium text-white">7 dias</button>
                    <button type="button" class="rounded-full border border-zinc-200 bg-white px-4 py-1.5 text-sm font-medium text-zinc-600">
                        30 dias
                    </button>
                    <button
                        type="button"
                        class="inline-flex items-center gap-2 rounded-full border border-zinc-200 bg-white px-4 py-1.5 text-sm font-medium text-zinc-600"
                    >
                        <CalendarClock class="size-4" />
                        26 mar - 26 abr
                    </button>

                    <span class="ml-4 text-xs font-semibold tracking-wider text-zinc-500 uppercase">Status</span>
                    <button type="button" class="rounded-full bg-zinc-900 px-4 py-1.5 text-sm font-medium text-white">
                        Todas <span class="ml-1 rounded-full bg-white/20 px-1.5 py-0.5 text-xs">18</span>
                    </button>
                    <button type="button" class="rounded-full border border-zinc-200 bg-white px-4 py-1.5 text-sm font-medium text-zinc-600">
                        Confirmadas <span class="ml-1 text-zinc-400">2</span>
                    </button>
                    <button type="button" class="rounded-full border border-zinc-200 bg-white px-4 py-1.5 text-sm font-medium text-zinc-600">
                        Concluídas <span class="ml-1 text-zinc-400">11</span>
                    </button>
                    <button type="button" class="rounded-full border border-zinc-200 bg-white px-4 py-1.5 text-sm font-medium text-zinc-600">
                        Faltas <span class="ml-1 text-zinc-400">2</span>
                    </button>
                    <button
                        type="button"
                        class="inline-flex items-center gap-1 rounded-full border border-zinc-200 bg-white px-4 py-1.5 text-sm font-medium text-zinc-600"
                    >
                        Mais
                        <ChevronDown class="size-3.5" />
                    </button>
                </div>
            </div>

            <DataGridSkeleton v-if="showSkeleton" :row-count="5" :show-sidebar="true" :sidebar-stats-cards="4" :sidebar-lines="3" />

            <section
                v-else-if="status === 'error'"
                class="flex min-h-[520px] items-center justify-center rounded-2xl border border-zinc-200 bg-white px-6 py-12 text-center shadow-sm"
            >
                <div class="max-w-md space-y-3">
                    <h2 class="text-xl font-semibold text-zinc-900">Falha ao carregar histórico</h2>
                    <p class="text-sm text-zinc-500">{{ errorMessage }}</p>
                    <button
                        type="button"
                        @click="reloadHistory"
                        class="inline-flex items-center gap-2 rounded-xl bg-primary px-5 py-2.5 text-sm font-semibold text-zinc-900 transition hover:bg-primary/90"
                    >
                        <RefreshCw class="size-4" />
                        Tentar novamente
                    </button>
                </div>
            </section>

            <div v-else-if="hasAppointments" class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_320px]">
                <section class="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm">
                    <div v-for="dayGroup in dayGroups" :key="dayGroup.id" class="rounded-2xl border border-zinc-100 bg-zinc-50/70 p-3">
                        <div class="mb-3 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-semibold text-cyan-700">{{ dayGroup.label }}</span>
                                <span class="text-xs text-zinc-500">{{ dayGroup.dateLabel }}</span>
                            </div>
                            <p class="text-xs text-zinc-500">{{ dayGroup.summary }}</p>
                        </div>

                        <div class="space-y-2">
                            <div v-for="appointment in dayGroup.appointments" :key="appointment.id" class="group flex gap-3">
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
                                    class="flex flex-1 items-center justify-between rounded-2xl border px-4 py-3 transition group-hover:border-zinc-300"
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
                                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-zinc-200 bg-white text-zinc-500 transition hover:bg-zinc-50"
                                        >
                                            <Eye class="size-4" />
                                        </button>
                                    </div>
                                </article>
                            </div>
                        </div>
                    </div>
                </section>

                <aside class="space-y-4">
                    <section class="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm">
                        <h2 class="text-xs font-semibold tracking-wider text-zinc-500 uppercase">Resumo do período</h2>
                        <div class="mt-3 grid grid-cols-2 gap-2">
                            <div class="rounded-xl border border-zinc-100 bg-zinc-50 p-3">
                                <p class="text-3xl font-bold text-zinc-900">142</p>
                                <p class="text-xs text-zinc-500">Atendimentos</p>
                            </div>
                            <div class="rounded-xl border border-zinc-100 bg-cyan-50 p-3">
                                <p class="text-3xl font-bold text-cyan-700">98%</p>
                                <p class="text-xs text-zinc-500">Confirmação</p>
                            </div>
                            <div class="rounded-xl border border-zinc-100 bg-amber-50 p-3">
                                <p class="text-3xl font-bold text-amber-700">7</p>
                                <p class="text-xs text-zinc-500">Faltas</p>
                            </div>
                            <div class="rounded-xl border border-zinc-100 bg-zinc-50 p-3">
                                <p class="text-3xl font-bold text-zinc-900">12 min</p>
                                <p class="text-xs text-zinc-500">Tempo médio</p>
                            </div>
                        </div>
                    </section>

                    <section class="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm">
                        <div class="flex items-center justify-between gap-2">
                            <h2 class="text-xs font-semibold tracking-wider text-zinc-500 uppercase">Documentos emitidos</h2>
                            <FileClock class="size-4 text-zinc-400" />
                        </div>
                        <p class="mt-2 text-3xl font-bold text-zinc-900">{{ documentsSummary.count30Days }}</p>
                        <p class="text-xs text-zinc-500">Últimos 30 dias</p>

                        <ul class="mt-3 space-y-2">
                            <li
                                v-for="document in documentsSummary.latest"
                                :key="document.id"
                                class="rounded-xl border border-zinc-100 bg-zinc-50 px-3 py-2"
                            >
                                <p class="truncate text-xs font-semibold text-zinc-900">{{ document.name }}</p>
                                <p class="text-[11px] text-zinc-500">{{ document.patient_name }} · {{ formatDate(document.created_at) }}</p>
                            </li>
                        </ul>

                        <a
                            :href="documentsHistoryUrl"
                            class="mt-3 inline-flex items-center justify-center rounded-xl border border-zinc-200 bg-white px-3 py-2 text-xs font-semibold text-zinc-700 transition hover:bg-zinc-50"
                        >
                            Ver histórico completo
                        </a>
                    </section>

                    <section class="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm">
                        <h2 class="text-xs font-semibold tracking-wider text-zinc-500 uppercase">Pendências</h2>
                        <div class="mt-2 divide-y divide-zinc-100">
                            <button type="button" class="flex w-full items-center justify-between gap-3 py-3 text-left">
                                <span class="inline-flex items-center gap-2 text-sm text-zinc-700">
                                    <FileText class="size-4 text-zinc-400" />
                                    3 prontuários a finalizar
                                </span>
                                <ChevronDown class="size-4 -rotate-90 text-zinc-400" />
                            </button>
                            <button type="button" class="flex w-full items-center justify-between gap-3 py-3 text-left">
                                <span class="inline-flex items-center gap-2 text-sm text-zinc-700">
                                    <StickyNote class="size-4 text-zinc-400" />
                                    2 prescrições em rascunho
                                </span>
                                <ChevronDown class="size-4 -rotate-90 text-zinc-400" />
                            </button>
                            <button type="button" class="flex w-full items-center justify-between gap-3 py-3 text-left">
                                <span class="inline-flex items-center gap-2 text-sm text-zinc-700">
                                    <CalendarClock class="size-4 text-zinc-400" />
                                    1 reagendamento aguardando
                                </span>
                                <ChevronDown class="size-4 -rotate-90 text-zinc-400" />
                            </button>
                        </div>
                    </section>
                </aside>
            </div>

            <section
                v-else
                class="flex min-h-[520px] items-center justify-center rounded-2xl border border-zinc-200 bg-white px-6 py-12 text-center shadow-sm"
            >
                <div class="max-w-md">
                    <div class="mx-auto mb-6 flex h-18 w-18 items-center justify-center rounded-2xl bg-cyan-100 text-cyan-700">
                        <Search class="size-8" />
                    </div>

                    <h2 class="text-4 mb-2 font-semibold text-zinc-900">Nenhuma consulta encontrada</h2>
                    <p class="text-sm leading-6 text-zinc-500">
                        Tente ajustar o período, limpar os filtros de status ou revisar o termo de busca.
                        <br />
                        Você está vendo: <span class="font-semibold text-zinc-700">Hoje · Faltas</span>.
                    </p>

                    <div class="mt-6 flex items-center justify-center gap-2">
                        <button
                            type="button"
                            class="inline-flex items-center rounded-xl border border-zinc-200 bg-white px-5 py-2.5 text-sm font-semibold text-zinc-700 transition hover:bg-zinc-50"
                        >
                            Limpar filtros
                        </button>
                        <button
                            type="button"
                            class="inline-flex items-center gap-2 rounded-xl bg-primary px-5 py-2.5 text-sm font-semibold text-zinc-900 transition hover:bg-primary/90"
                        >
                            <Plus class="size-4" />
                            Nova consulta
                        </button>
                    </div>

                    <p class="mt-6 text-xs text-zinc-400">
                        Dica: pressione
                        <span
                            class="mx-1 inline-flex items-center rounded-md border border-zinc-200 bg-zinc-50 px-1.5 py-0.5 font-semibold text-zinc-500"
                        >
                            ⌘ K
                        </span>
                        para abrir a busca de qualquer lugar
                    </p>
                </div>
            </section>
        </div>
    </AppLayout>
</template>
