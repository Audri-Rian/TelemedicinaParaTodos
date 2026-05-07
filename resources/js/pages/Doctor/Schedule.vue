<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import AvailabilityOverview from '@/pages/Doctor/AvailabilityOverview.vue';
import ScheduleManagement from '@/pages/Doctor/ScheduleManagement.vue';
import * as doctorRoutes from '@/routes/doctor';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { CalendarClock, Clock3, ListChecks, MapPin, ShieldCheck } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

interface ServiceLocation {
    id: string;
    name: string;
    type: string;
    type_label?: string;
    address?: string;
    phone?: string;
    description?: string;
}

interface AvailabilitySlot {
    id: string;
    day_of_week?: string;
    day_of_week_label?: string;
    specific_date?: string;
    start_time: string;
    end_time: string;
    location_id?: string;
    location?: {
        id: string;
        name: string;
        type: string;
    } | null;
}

interface SpecificSlotsByDate {
    date: string;
    formatted_date: string;
    slots: AvailabilitySlot[];
}

interface BlockedDate {
    id: string;
    blocked_date: string;
    formatted_date: string;
    reason?: string;
}

interface ScheduleConfig {
    locations: ServiceLocation[];
    recurring_slots: AvailabilitySlot[];
    specific_slots: SpecificSlotsByDate[];
    blocked_dates: BlockedDate[];
}

interface TimelineSlot {
    id: string;
    date: string;
    start_time: string;
    end_time: string;
    time_range: string;
    duration_minutes: number;
    location?: ServiceLocation | null;
    status: {
        code: string;
        label: string;
    };
    appointment?: {
        id: string;
        status: string;
        patient_name?: string;
        patient_avatar?: string;
    } | null;
    is_past: boolean;
    can_edit: boolean;
    can_delete: boolean;
}

interface TimelineDay {
    date: string;
    formatted_date: string;
    weekday: string;
    is_past: boolean;
    slots: TimelineSlot[];
}

interface Summary {
    next_session: {
        date: string;
        time: string;
        weekday: string;
        patient_name?: string;
        status: string;
    } | null;
    future_slots_count: number;
    available_this_week: number;
    next_seven_days: {
        total: number;
        available: number;
        busy: number;
    };
    past_slots_count: number;
    last_sessions: Array<{
        id: string;
        date: string;
        time: string;
        status: string;
        patient_name?: string;
    }>;
}

type ScheduleTab = 'configure' | 'overview';

interface Props {
    scheduleConfig?: ScheduleConfig;
    timeline?: TimelineDay[];
    summary?: Summary;
    meta?: {
        start: string;
        end: string;
    };
    locations?: ServiceLocation[];
    initialTab?: ScheduleTab;
}

const props = withDefaults(defineProps<Props>(), {
    initialTab: 'configure',
    scheduleConfig: () => ({
        locations: [],
        recurring_slots: [],
        specific_slots: [],
        blocked_dates: [],
    }),
    timeline: () => [],
    summary: () => ({
        next_session: null,
        future_slots_count: 0,
        available_this_week: 0,
        next_seven_days: { total: 0, available: 0, busy: 0 },
        past_slots_count: 0,
        last_sessions: [],
    }),
    meta: () => ({
        start: '',
        end: '',
    }),
    locations: () => [],
});

const activeTab = ref<ScheduleTab>(props.initialTab);

watch(
    () => props.initialTab,
    (tab) => {
        activeTab.value = tab;
    },
);

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: doctorRoutes.dashboard().url,
    },
    {
        title: 'Agenda',
        href: doctorRoutes.schedule().url,
    },
];

const tabs = [
    {
        value: 'configure',
        label: 'Configurar',
        description: 'Calendário, slots, recorrência, bloqueios e locais.',
        icon: CalendarClock,
    },
    {
        value: 'overview',
        label: 'Visão geral',
        description: 'Timeline, filtros, resumo e edição individual.',
        icon: ListChecks,
    },
] as const;

const pageTitle = computed(() => (activeTab.value === 'overview' ? 'Agenda - Visão geral' : 'Agenda - Configurar'));

const nextSessionLabel = computed(() => {
    if (!props.summary?.next_session) {
        return 'Sem sessão';
    }

    return `${props.summary.next_session.time} · ${props.summary.next_session.weekday}`;
});

const locationsCount = computed(() => props.scheduleConfig?.locations?.length ?? props.locations?.length ?? 0);

const setTab = (tab: ScheduleTab) => {
    if (activeTab.value === tab) return;

    const propsToLoad = tab === 'overview' ? ['initialTab', 'timeline', 'summary', 'meta', 'locations'] : ['initialTab', 'scheduleConfig'];

    activeTab.value = tab;
    router.get(
        doctorRoutes.schedule().url,
        { tab },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            only: propsToLoad,
        },
    );
};
</script>

<template>
    <Head :title="pageTitle" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="ag-shell flex h-full flex-1 flex-col overflow-x-auto">
            <div class="ag-frame flex w-full flex-col overflow-hidden">
                <section class="ag-hero">
                    <div class="flex flex-col gap-5 xl:flex-row xl:items-end xl:justify-between">
                        <div class="max-w-3xl space-y-3">
                            <div
                                class="inline-flex items-center gap-2 rounded-full border border-[var(--ag-border)] bg-white px-3 py-1 text-xs font-extrabold tracking-wide text-[var(--ag-primary-strong)] uppercase"
                            >
                                <ShieldCheck class="h-4 w-4" />
                                Agenda do Médico
                            </div>

                            <div class="space-y-2">
                                <h1 class="text-4xl leading-tight font-black text-[var(--ag-fg)] md:text-5xl">Agenda</h1>
                                <p class="text-base font-semibold text-[var(--ag-muted-strong)]">
                                    Configure sua disponibilidade e acompanhe horários, pacientes e locais em uma única área.
                                </p>
                            </div>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-3 xl:min-w-[560px]">
                            <div class="ag-kpi">
                                <Clock3 class="h-5 w-5 text-[var(--ag-primary-strong)]" />
                                <div>
                                    <p>Próxima sessão</p>
                                    <strong>{{ nextSessionLabel }}</strong>
                                </div>
                            </div>

                            <div class="ag-kpi">
                                <CalendarClock class="h-5 w-5 text-[var(--ag-primary-strong)]" />
                                <div>
                                    <p>Horários futuros</p>
                                    <strong>{{ summary.future_slots_count }}</strong>
                                </div>
                            </div>

                            <div class="ag-kpi">
                                <MapPin class="h-5 w-5 text-[var(--ag-primary-strong)]" />
                                <div>
                                    <p>Locais ativos</p>
                                    <strong>{{ locationsCount }}</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <div class="ag-tabbar">
                    <button
                        v-for="tab in tabs"
                        :key="tab.value"
                        type="button"
                        @click="setTab(tab.value)"
                        :class="['ag-tab', activeTab === tab.value ? 'ag-tab-active' : '']"
                    >
                        <span class="ag-tab-icon">
                            <component :is="tab.icon" class="h-5 w-5" />
                        </span>
                        <span class="min-w-0">
                            <span class="block text-sm font-black">{{ tab.label }}</span>
                            <span class="block text-xs leading-5 font-bold opacity-75">{{ tab.description }}</span>
                        </span>
                    </button>
                </div>

                <div class="ag-content">
                    <ScheduleManagement v-if="activeTab === 'configure'" :schedule-config="scheduleConfig" />

                    <AvailabilityOverview v-else :timeline="timeline" :summary="summary" :meta="meta" :locations="locations" />
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.ag-shell {
    --ag-bg: #f7f8f9;
    --ag-card: #ffffff;
    --ag-fg: #0f2837;
    --ag-muted: #8a99a4;
    --ag-muted-strong: #5c6f7b;
    --ag-border: #e6ebee;
    --ag-border-strong: #c8d2d7;
    --ag-primary: #40e0d0;
    --ag-primary-fg: #0f2837;
    --ag-primary-strong: #0ea89a;
    --ag-primary-soft: #e8fbf8;
    background: linear-gradient(180deg, rgba(255, 255, 255, 0.62), rgba(244, 246, 248, 0.88)), #f4f1ec;
    color: var(--ag-fg);
    font-family: Nunito, system-ui, sans-serif;
}

.ag-frame {
    background: var(--ag-card);
    border: 1px solid var(--ag-border);
    border-radius: 16px;
    box-shadow:
        0 1px 0 rgba(15, 40, 55, 0.04),
        0 12px 30px -12px rgba(15, 40, 55, 0.1);
}

.ag-hero {
    background: linear-gradient(135deg, rgba(232, 251, 248, 0.95), rgba(255, 255, 255, 0.92) 46%, rgba(244, 246, 248, 0.96)), var(--ag-card);
    border-bottom: 1px solid var(--ag-border);
    padding: 28px;
}

.ag-kpi {
    display: flex;
    min-height: 86px;
    align-items: center;
    gap: 12px;
    border: 1px solid var(--ag-border);
    border-radius: 14px;
    background: rgba(255, 255, 255, 0.86);
    padding: 16px;
    box-shadow: 0 10px 24px -18px rgba(15, 40, 55, 0.42);
}

.ag-kpi p {
    margin: 0 0 3px;
    color: var(--ag-muted);
    font-size: 12px;
    font-weight: 800;
    text-transform: uppercase;
}

.ag-kpi strong {
    display: block;
    color: var(--ag-fg);
    font-size: 18px;
    font-weight: 900;
    line-height: 1.15;
}

.ag-tabbar {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 10px;
    border-bottom: 1px solid var(--ag-border);
    background: var(--ag-bg);
    padding: 12px;
}

.ag-tab {
    display: flex;
    min-height: 74px;
    align-items: flex-start;
    gap: 12px;
    border: 1px solid transparent;
    border-radius: 12px;
    padding: 14px 16px;
    text-align: left;
    color: var(--ag-muted-strong);
    transition:
        border-color 0.2s ease,
        background 0.2s ease,
        box-shadow 0.2s ease,
        color 0.2s ease;
}

.ag-tab:hover {
    border-color: var(--ag-border-strong);
    background: rgba(255, 255, 255, 0.74);
}

.ag-tab-active {
    border-color: rgba(14, 168, 154, 0.22);
    background: var(--ag-card);
    color: var(--ag-fg);
    box-shadow: 0 12px 30px -22px rgba(15, 40, 55, 0.36);
}

.ag-tab-icon {
    display: inline-flex;
    height: 38px;
    width: 38px;
    flex-shrink: 0;
    align-items: center;
    justify-content: center;
    border-radius: 10px;
    background: var(--ag-primary-soft);
    color: var(--ag-primary-strong);
}

.ag-tab-active .ag-tab-icon {
    background: var(--ag-primary);
    color: var(--ag-primary-fg);
}

.ag-content {
    background: var(--ag-bg);
    padding: 22px;
}

@media (max-width: 768px) {
    .ag-hero {
        padding: 20px;
    }

    .ag-tabbar {
        grid-template-columns: 1fr;
    }

    .ag-content {
        padding: 14px;
    }
}
</style>
