<script setup lang="ts">
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { useRouteGuard } from '@/composables/auth';
import { useInitials } from '@/composables/useInitials';
import { useToast } from '@/composables/useToast';
import AppLayout from '@/layouts/AppLayout.vue';
import * as patientRoutes from '@/routes/patient';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import type { LucideIcon } from 'lucide-vue-next';
import {
    ArrowRight,
    Award,
    Bell,
    BookOpen,
    Bookmark,
    Briefcase,
    Calendar,
    CalendarPlus,
    Check,
    CheckCircle2,
    ChevronLeft,
    ChevronRight,
    Clock,
    Copy,
    ExternalLink,
    GraduationCap,
    Heart,
    Languages,
    MapPin,
    MessageCircle,
    Share2,
    ShieldCheck,
    Sparkles,
    Star,
    Stethoscope,
    Video,
    X,
} from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';

interface TimelineEvent {
    id: string;
    type: 'education' | 'course' | 'certificate' | 'project';
    type_label: string;
    title: string;
    subtitle?: string;
    start_date: string;
    end_date?: string;
    description?: string;
    media_url?: string;
    degree_type?: string;
    is_public: boolean;
    extra_data?: Record<string, unknown>;
    order_priority: number;
    formatted_start_date: string;
    formatted_end_date?: string;
    date_range: string;
    duration?: string;
    is_in_progress: boolean;
}

interface AvailableDate {
    date: string;
    formatted_date: string;
    day_of_week: string;
    day_of_week_label: string;
    available_slots: string[];
}

interface LanguageDetail {
    label: string;
    level?: string | null;
    flag: string;
}

interface ServiceLocation {
    id: string;
    name: string;
    type: string;
    type_label: string;
    address?: string | null;
    phone?: string | null;
    description?: string | null;
}

interface RelatedDoctor {
    id: string;
    name: string;
    avatar?: string | null;
    avatar_thumbnail?: string | null;
    primary_specialty: string;
    consultation_fee?: number | null;
    consultation_fee_formatted: string;
    has_online_service: boolean;
    has_presencial_service: boolean;
}

interface Doctor {
    id: string;
    name: string;
    email: string;
    avatar?: string | null;
    avatar_thumbnail?: string | null;
    crm?: string | null;
    biography?: string | null;
    languages: string;
    language_details: LanguageDetail[];
    consultation_fee?: number | null;
    consultation_fee_formatted: string;
    consultation_duration_minutes: number;
    specialties: string[];
    primary_specialty: string;
    has_online_service: boolean;
    has_presencial_service: boolean;
    modalities: string[];
    status: string;
    timeline_events: TimelineEvent[];
    timeline_completed: boolean;
    available_dates: AvailableDate[];
    service_locations: ServiceLocation[];
    completed_appointments_count: number;
    related_doctors: RelatedDoctor[];
}

interface Props {
    doctor: Doctor;
}

const props = defineProps<Props>();

const { canAccessPatientRoute } = useRouteGuard();
const { getInitials } = useInitials();
const toast = useToast();

const favorited = ref(false);
const bookmarked = ref(false);
const shareOpen = ref(false);
const selectedDate = ref<string | null>(null);
const selectedTime = ref<string | null>(null);
const currentMonthStart = ref(startOfMonth(new Date()));
const timelineFilter = ref<'all' | TimelineEvent['type']>('all');
const timelineFilters: Array<{ id: 'all' | TimelineEvent['type']; label: string }> = [
    { id: 'all', label: 'Tudo' },
    { id: 'education', label: 'Formação' },
    { id: 'certificate', label: 'Certificados' },
    { id: 'course', label: 'Cursos' },
    { id: 'project', label: 'Projetos' },
];
const weekdays = ['D', 'S', 'T', 'Q', 'Q', 'S', 'S'];
const selectedModality = ref<'online' | 'presencial'>(props.doctor.has_online_service ? 'online' : 'presencial');
const activeLocationId = ref<string | null>(props.doctor.service_locations[0]?.id ?? null);

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: patientRoutes.dashboard().url,
    },
    {
        title: 'Pesquisar Médicos',
        href: patientRoutes.searchConsultations().url,
    },
    {
        title: props.doctor.name,
        href: patientRoutes.doctorPerfil({ query: { doctor_id: props.doctor.id } }).url,
    },
];

const firstName = computed(() => props.doctor.name.split(' ')[0] ?? props.doctor.name);
const about = computed(() => props.doctor.biography || 'Este médico ainda não adicionou uma biografia pública.');
const priceLabel = computed(() => {
    if (!props.doctor.consultation_fee) {
        return 'A consultar';
    }

    return props.doctor.consultation_fee_formatted;
});
const modalityOptions = computed(() => [
    ...(props.doctor.has_online_service ? [{ value: 'online' as const, label: 'Online', icon: Video }] : []),
    ...(props.doctor.has_presencial_service ? [{ value: 'presencial' as const, label: 'Presencial', icon: MapPin }] : []),
]);
const availableDateMap = computed(() => {
    return new Map(props.doctor.available_dates.map((date) => [date.date, date.available_slots]));
});
const currentMonth = computed(() => {
    return currentMonthStart.value.toLocaleDateString('pt-BR', {
        month: 'long',
        year: 'numeric',
    });
});
const calendarDays = computed(() => {
    const year = currentMonthStart.value.getFullYear();
    const month = currentMonthStart.value.getMonth();
    const firstDay = new Date(year, month, 1);
    const start = new Date(year, month, 1 - firstDay.getDay());
    const today = startOfDay(new Date());

    return Array.from({ length: 42 }, (_, index) => {
        const date = new Date(start);
        date.setDate(start.getDate() + index);
        const iso = toIsoDate(date);

        return {
            date: iso,
            day: date.getDate(),
            inMonth: date.getMonth() === month,
            isToday: iso === toIsoDate(today),
            isPast: date < today,
            isAvailable: availableDateMap.value.has(iso),
        };
    });
});
const monthHasAvailability = computed(() => calendarDays.value.some((day) => day.inMonth && day.isAvailable && !day.isPast));
const availableTimes = computed(() => (selectedDate.value ? (availableDateMap.value.get(selectedDate.value) ?? []) : []));
const selectedDateLong = computed(() => (selectedDate.value ? formatDateLong(selectedDate.value) : 'Selecione uma data'));
const selectedDateShort = computed(() => (selectedDate.value ? formatDateShort(selectedDate.value) : null));
const scheduleButtonLabel = computed(() => {
    if (selectedDateShort.value && selectedTime.value) {
        return `Agendar ${selectedDateShort.value} às ${selectedTime.value}`;
    }

    return 'Agendar consulta';
});
const upcomingSlots = computed(() => {
    return props.doctor.available_dates.flatMap((date) => date.available_slots.map((slot) => ({ date: date.date, slot }))).slice(0, 3);
});
const filteredTimeline = computed(() => {
    const events = props.doctor.timeline_events ?? [];

    return events.filter((event) => timelineFilter.value === 'all' || event.type === timelineFilter.value);
});
const certificates = computed(() => {
    return props.doctor.timeline_events.filter((event) => event.type === 'certificate' || event.media_url).slice(0, 6);
});
const activeLocation = computed(() => {
    return props.doctor.service_locations.find((location) => location.id === activeLocationId.value) ?? props.doctor.service_locations[0] ?? null;
});
const shareUrl = computed(() => {
    if (typeof window === 'undefined') {
        return '';
    }

    return window.location.href;
});

onMounted(() => {
    canAccessPatientRoute();

    if (props.doctor.available_dates.length > 0) {
        selectedDate.value = props.doctor.available_dates[0].date;
    }

    if (modalityOptions.value.length > 0) {
        selectedModality.value = modalityOptions.value[0].value;
    }
});

const toggleFavorite = () => {
    favorited.value = !favorited.value;
    toast.info(favorited.value ? 'Médico adicionado aos favoritos.' : 'Médico removido dos favoritos.', { durationMs: 2400 });
};

const toggleBookmark = () => {
    bookmarked.value = !bookmarked.value;
    toast.info(bookmarked.value ? 'Perfil salvo em Meus médicos.' : 'Perfil removido de Meus médicos.', { durationMs: 2400 });
};

const notifyAvailability = () => {
    toast.info('Avisaremos quando novos horários forem publicados.', { title: 'Agenda monitorada', durationMs: 3200 });
};

const copyShareUrl = async () => {
    if (!shareUrl.value || typeof navigator === 'undefined' || !navigator.clipboard) {
        toast.warning('Não foi possível copiar o link automaticamente.');
        return;
    }

    await navigator.clipboard.writeText(shareUrl.value);
    toast.success('Link do perfil copiado.');
};

const goToPreviousMonth = () => {
    currentMonthStart.value = addMonths(currentMonthStart.value, -1);
};

const goToNextMonth = () => {
    currentMonthStart.value = addMonths(currentMonthStart.value, 1);
};

const selectDate = (date: string) => {
    selectedDate.value = date;
    selectedTime.value = null;
};

const selectQuickSlot = (date: string, slot: string) => {
    selectedDate.value = date;
    selectedTime.value = slot;
    currentMonthStart.value = startOfMonth(parseIsoDate(date));
};

const setTimelineFilter = (filter: 'all' | TimelineEvent['type']) => {
    timelineFilter.value = filter;
};

const scheduleHref = (date = selectedDate.value, time = selectedTime.value) => {
    return patientRoutes.scheduleConsultation({
        query: {
            doctor_id: props.doctor.id,
            date: date ?? undefined,
            time: time ?? undefined,
            type: selectedModality.value,
        },
    });
};

const relatedDoctorHref = (doctorId: string) => {
    return patientRoutes.doctorPerfil({ query: { doctor_id: doctorId } });
};

const timelineIcon = (type: TimelineEvent['type']): LucideIcon => {
    return {
        education: GraduationCap,
        certificate: Award,
        course: BookOpen,
        project: Briefcase,
    }[type];
};

const timelineTone = (type: TimelineEvent['type']) => {
    return {
        education: 'bg-sky-50 text-sky-700 ring-sky-200',
        certificate: 'bg-amber-50 text-amber-700 ring-amber-200',
        course: 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        project: 'bg-violet-50 text-violet-700 ring-violet-200',
    }[type];
};

const formatCurrency = (value?: number | null) => {
    if (!value) {
        return 'A consultar';
    }

    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL',
    }).format(value);
};

function parseIsoDate(value: string) {
    const [year, month, day] = value.split('-').map(Number);

    return new Date(year, month - 1, day);
}

function toIsoDate(date: Date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');

    return `${year}-${month}-${day}`;
}

function startOfDay(date: Date) {
    const next = new Date(date);
    next.setHours(0, 0, 0, 0);

    return next;
}

function startOfMonth(date: Date) {
    return new Date(date.getFullYear(), date.getMonth(), 1);
}

function addMonths(date: Date, amount: number) {
    return new Date(date.getFullYear(), date.getMonth() + amount, 1);
}

function formatDateLong(value: string) {
    return parseIsoDate(value).toLocaleDateString('pt-BR', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
    });
}

function formatDateShort(value: string) {
    const date = parseIsoDate(value);
    const weekday = date.toLocaleDateString('pt-BR', { weekday: 'short' }).replace('.', '');
    const month = date.toLocaleDateString('pt-BR', { month: 'short' }).replace('.', '');

    return `${weekday}, ${date.getDate()} ${month}`;
}

function quickSlotLabel(value: string) {
    const today = toIsoDate(startOfDay(new Date()));
    const tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1);

    if (value === today) {
        return 'Hoje';
    }

    if (value === toIsoDate(tomorrow)) {
        return 'Amanhã';
    }

    return formatDateShort(value);
}
</script>

<template>
    <Head :title="`Perfil de ${doctor.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-full bg-slate-50 px-3 py-6 text-slate-950 sm:px-4 lg:px-5 xl:px-6">
            <main class="flex w-full flex-col gap-5">
                <section class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_360px]">
                    <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                        <div class="flex flex-col gap-5 md:flex-row md:items-start">
                            <Avatar class="size-24 shrink-0 ring-4 ring-teal-50 sm:size-28">
                                <AvatarImage
                                    v-if="doctor.avatar || doctor.avatar_thumbnail"
                                    :src="doctor.avatar_thumbnail || doctor.avatar || undefined"
                                />
                                <AvatarFallback class="bg-teal-700 text-2xl font-semibold text-white">
                                    {{ getInitials(doctor.name) }}
                                </AvatarFallback>
                            </Avatar>

                            <div class="min-w-0 flex-1 space-y-4">
                                <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                                    <div class="min-w-0">
                                        <div class="mb-3 flex flex-wrap items-center gap-2">
                                            <span
                                                v-if="doctor.timeline_completed"
                                                class="inline-flex h-7 items-center gap-1.5 rounded-full bg-teal-50 px-3 text-xs font-semibold text-teal-800 ring-1 ring-teal-100"
                                            >
                                                <CheckCircle2 class="size-3.5" />
                                                Perfil completo
                                            </span>
                                            <span
                                                v-if="doctor.crm"
                                                class="inline-flex h-7 items-center gap-1.5 rounded-full bg-slate-50 px-3 text-xs font-semibold text-slate-700 ring-1 ring-slate-200"
                                            >
                                                <ShieldCheck class="size-3.5" />
                                                CRM verificado
                                            </span>
                                            <span
                                                class="inline-flex h-7 items-center gap-1.5 rounded-full bg-slate-50 px-3 text-xs font-semibold text-slate-700 ring-1 ring-slate-200"
                                            >
                                                <Clock class="size-3.5" />
                                                Responde em dias úteis
                                            </span>
                                        </div>

                                        <h1 class="text-3xl font-bold tracking-normal text-slate-950 sm:text-4xl">{{ doctor.name }}</h1>
                                        <div class="mt-2 flex flex-wrap items-center gap-2 text-sm text-slate-600">
                                            <Stethoscope class="size-4 text-teal-700" />
                                            <span class="font-semibold text-slate-900">{{ doctor.primary_specialty }}</span>
                                            <span v-if="doctor.crm" class="text-slate-300">|</span>
                                            <span v-if="doctor.crm">{{ doctor.crm }}</span>
                                        </div>
                                    </div>

                                    <div class="flex shrink-0 items-center gap-2">
                                        <button
                                            type="button"
                                            class="flex size-10 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-700 transition hover:bg-slate-50"
                                            :class="{ 'border-teal-300 bg-teal-50 text-teal-800': bookmarked }"
                                            aria-label="Salvar perfil"
                                            @click="toggleBookmark"
                                        >
                                            <Bookmark class="size-4" :class="{ 'fill-current': bookmarked }" />
                                        </button>
                                        <button
                                            type="button"
                                            class="flex size-10 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-700 transition hover:bg-slate-50"
                                            :class="{ 'border-rose-300 bg-rose-50 text-rose-700': favorited }"
                                            aria-label="Favoritar médico"
                                            @click="toggleFavorite"
                                        >
                                            <Heart class="size-4" :class="{ 'fill-current': favorited }" />
                                        </button>
                                        <button
                                            type="button"
                                            class="flex size-10 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-700 transition hover:bg-slate-50"
                                            aria-label="Compartilhar perfil"
                                            @click="shareOpen = true"
                                        >
                                            <Share2 class="size-4" />
                                        </button>
                                    </div>
                                </div>

                                <div class="flex flex-wrap items-center gap-2">
                                    <span
                                        v-if="doctor.has_online_service"
                                        class="inline-flex items-center gap-1.5 rounded-lg bg-teal-50 px-3 py-2 text-sm font-semibold text-teal-800 ring-1 ring-teal-100"
                                    >
                                        <Video class="size-4" />
                                        Atende online
                                    </span>
                                    <span
                                        v-if="doctor.has_presencial_service"
                                        class="inline-flex items-center gap-1.5 rounded-lg bg-sky-50 px-3 py-2 text-sm font-semibold text-sky-800 ring-1 ring-sky-100"
                                    >
                                        <MapPin class="size-4" />
                                        Atende presencial
                                    </span>
                                    <span
                                        class="inline-flex items-center gap-1.5 rounded-lg bg-white px-3 py-2 text-sm font-semibold text-slate-700 ring-1 ring-slate-200"
                                    >
                                        <Languages class="size-4" />
                                        {{ doctor.language_details.length || 1 }} idioma{{ (doctor.language_details.length || 1) > 1 ? 's' : '' }}
                                    </span>
                                </div>

                                <div class="flex flex-col gap-3 sm:flex-row">
                                    <Button as-child class="h-10 bg-teal-500 px-4 font-semibold text-slate-950 hover:bg-teal-400">
                                        <Link :href="patientRoutes.messages()">
                                            <MessageCircle class="mr-2 size-4" />
                                            Enviar mensagem
                                        </Link>
                                    </Button>
                                    <Button
                                        as-child
                                        variant="outline"
                                        class="h-10 border-slate-200 bg-white px-4 font-semibold text-slate-800 hover:bg-slate-50"
                                    >
                                        <Link :href="scheduleHref()">
                                            <CalendarPlus class="mr-2 size-4" />
                                            {{ scheduleButtonLabel }}
                                        </Link>
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <aside class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="mb-5">
                            <p class="text-xs font-bold tracking-[0.08em] text-slate-500 uppercase">Informações essenciais</p>
                            <h2 class="mt-1 text-xl font-bold text-slate-950">Antes de agendar</h2>
                        </div>

                        <div class="space-y-4">
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex items-center gap-2 text-sm text-slate-500">
                                    <Video class="size-4" />
                                    Modalidade
                                </div>
                                <div class="flex flex-wrap justify-end gap-1.5">
                                    <span
                                        v-for="option in modalityOptions"
                                        :key="option.value"
                                        class="inline-flex items-center gap-1 rounded-full border border-slate-200 px-2 py-1 text-xs font-semibold text-slate-700"
                                    >
                                        <component :is="option.icon" class="size-3" />
                                        {{ option.label }}
                                    </span>
                                    <span v-if="modalityOptions.length === 0" class="text-sm font-semibold text-slate-900">Não configurado</span>
                                </div>
                            </div>

                            <div class="flex items-center justify-between gap-3">
                                <div class="flex items-center gap-2 text-sm text-slate-500">
                                    <Clock class="size-4" />
                                    Duração
                                </div>
                                <span class="text-sm font-semibold text-slate-950">{{ doctor.consultation_duration_minutes }} minutos</span>
                            </div>

                            <div class="flex items-center justify-between gap-3">
                                <div class="flex items-center gap-2 text-sm text-slate-500">
                                    <Sparkles class="size-4" />
                                    Valor
                                </div>
                                <div class="text-right">
                                    <p class="text-lg font-bold text-slate-950">{{ priceLabel }}</p>
                                    <p class="text-xs text-slate-500">valor da consulta</p>
                                </div>
                            </div>
                        </div>

                        <div class="my-5 border-t border-slate-200"></div>

                        <div class="space-y-3">
                            <Button as-child class="h-11 w-full bg-teal-500 font-semibold text-slate-950 hover:bg-teal-400">
                                <Link :href="scheduleHref()">
                                    <CalendarPlus class="mr-2 size-4" />
                                    {{ scheduleButtonLabel }}
                                </Link>
                            </Button>
                            <div class="flex items-center justify-center gap-2 text-xs text-slate-500">
                                <ShieldCheck class="size-3.5" />
                                Pagamento seguro pelo fluxo de agendamento
                            </div>
                        </div>
                    </aside>
                </section>

                <section class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_360px]">
                    <div class="space-y-5">
                        <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                            <div class="mb-4">
                                <p class="text-xs font-bold tracking-[0.08em] text-slate-500 uppercase">Sobre</p>
                                <h2 class="mt-1 text-xl font-bold text-slate-950">Sobre {{ firstName }}</h2>
                            </div>

                            <p class="text-sm leading-7 text-slate-700">{{ about }}</p>

                            <div v-if="doctor.specialties.length > 0" class="mt-5 border-t border-slate-200 pt-5">
                                <p class="mb-3 text-xs font-bold tracking-[0.08em] text-slate-500 uppercase">Especialidades</p>
                                <div class="flex flex-wrap gap-2">
                                    <span
                                        v-for="specialty in doctor.specialties"
                                        :key="specialty"
                                        class="rounded-lg bg-slate-50 px-3 py-2 text-sm font-semibold text-slate-700 ring-1 ring-slate-200"
                                    >
                                        {{ specialty }}
                                    </span>
                                </div>
                            </div>

                            <div class="mt-5 grid gap-5 border-t border-slate-200 pt-5 md:grid-cols-2">
                                <div>
                                    <p class="mb-3 text-xs font-bold tracking-[0.08em] text-slate-500 uppercase">Idiomas</p>
                                    <div class="space-y-2">
                                        <div
                                            v-for="language in doctor.language_details"
                                            :key="language.label"
                                            class="flex items-center justify-between gap-3"
                                        >
                                            <div class="flex items-center gap-2">
                                                <span
                                                    class="grid size-7 place-items-center rounded-lg bg-slate-100 text-xs font-bold text-slate-600"
                                                    >{{ language.flag }}</span
                                                >
                                                <span class="text-sm font-semibold text-slate-800">{{ language.label }}</span>
                                            </div>
                                            <span v-if="language.level" class="text-xs text-slate-500">{{ language.level }}</span>
                                        </div>
                                        <p v-if="doctor.language_details.length === 0" class="text-sm text-slate-500">
                                            {{ doctor.languages || 'Português' }}
                                        </p>
                                    </div>
                                </div>

                                <div>
                                    <p class="mb-3 text-xs font-bold tracking-[0.08em] text-slate-500 uppercase">Registro profissional</p>
                                    <div class="space-y-2 text-sm">
                                        <div class="flex items-center gap-2">
                                            <span class="w-16 text-slate-500">CRM</span>
                                            <span class="font-semibold text-slate-900">{{ doctor.crm || 'Não informado' }}</span>
                                            <span
                                                v-if="doctor.crm"
                                                class="inline-flex items-center gap-1 rounded-full bg-teal-50 px-2 py-0.5 text-xs font-semibold text-teal-800"
                                            >
                                                <ShieldCheck class="size-3" />
                                                verificado
                                            </span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="w-16 text-slate-500">Consultas</span>
                                            <span class="font-semibold text-slate-900">{{ doctor.completed_appointments_count }} realizadas</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <section v-if="doctor.timeline_events.length > 0" class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                            <div class="mb-5 flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                                <div>
                                    <p class="text-xs font-bold tracking-[0.08em] text-slate-500 uppercase">Trajetória</p>
                                    <h2 class="mt-1 text-xl font-bold text-slate-950">Formação e certificações</h2>
                                </div>

                                <div class="flex flex-wrap gap-1 rounded-lg bg-slate-100 p-1">
                                    <button
                                        v-for="filter in timelineFilters"
                                        :key="filter.id"
                                        type="button"
                                        class="h-8 rounded-md px-3 text-xs font-semibold transition"
                                        :class="
                                            timelineFilter === filter.id ? 'bg-white text-slate-950 shadow-sm' : 'text-slate-500 hover:text-slate-900'
                                        "
                                        @click="setTimelineFilter(filter.id)"
                                    >
                                        {{ filter.label }}
                                    </button>
                                </div>
                            </div>

                            <div class="relative space-y-5">
                                <div class="absolute top-2 bottom-2 left-4 w-px bg-slate-200"></div>
                                <div v-for="event in filteredTimeline" :key="event.id" class="relative flex gap-4">
                                    <div
                                        :class="[
                                            'relative z-10 grid size-9 shrink-0 place-items-center rounded-full ring-1',
                                            timelineTone(event.type),
                                        ]"
                                    >
                                        <component :is="timelineIcon(event.type)" class="size-4" />
                                    </div>
                                    <div class="min-w-0 flex-1 rounded-lg border border-slate-200 bg-white p-4">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="text-sm font-bold text-slate-950">{{ event.title }}</span>
                                            <span
                                                v-if="event.is_in_progress"
                                                class="rounded-full bg-teal-50 px-2 py-0.5 text-xs font-semibold text-teal-800"
                                                >Em andamento</span
                                            >
                                            <a
                                                v-if="event.media_url"
                                                :href="event.media_url"
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                class="inline-flex items-center gap-1 rounded-full border border-slate-200 px-2 py-0.5 text-xs font-semibold text-slate-600 hover:bg-slate-50"
                                            >
                                                <ExternalLink class="size-3" />
                                                certificado
                                            </a>
                                        </div>
                                        <p class="mt-1 text-sm text-slate-500">
                                            <span v-if="event.subtitle">{{ event.subtitle }} | </span>{{ event.date_range }}
                                        </p>
                                        <p v-if="event.description" class="mt-3 text-sm leading-6 text-slate-700">{{ event.description }}</p>
                                    </div>
                                </div>
                                <p v-if="filteredTimeline.length === 0" class="pl-12 text-sm text-slate-500">Nenhum item nesse filtro.</p>
                            </div>
                        </section>

                        <section v-if="certificates.length > 0" class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                            <div class="mb-4">
                                <p class="text-xs font-bold tracking-[0.08em] text-slate-500 uppercase">Documentos</p>
                                <h2 class="mt-1 text-xl font-bold text-slate-950">Certificados</h2>
                            </div>

                            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                                <a
                                    v-for="certificate in certificates"
                                    :key="certificate.id"
                                    :href="certificate.media_url || '#'"
                                    class="rounded-lg border border-slate-200 bg-slate-50 p-4 transition hover:border-teal-200 hover:bg-teal-50/40"
                                    :class="{ 'pointer-events-none': !certificate.media_url }"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                >
                                    <div class="mb-5 grid size-11 place-items-center rounded-lg bg-white text-teal-700 ring-1 ring-slate-200">
                                        <Award class="size-5" />
                                    </div>
                                    <p class="text-sm leading-5 font-bold text-slate-950">{{ certificate.title }}</p>
                                    <p class="mt-1 text-xs text-slate-500">
                                        {{ certificate.subtitle || certificate.type_label }} | {{ certificate.formatted_start_date }}
                                    </p>
                                </a>
                            </div>
                        </section>

                        <section v-if="doctor.service_locations.length > 0" class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                            <div class="mb-4">
                                <p class="text-xs font-bold tracking-[0.08em] text-slate-500 uppercase">Atendimento presencial</p>
                                <h2 class="mt-1 text-xl font-bold text-slate-950">Locais e endereços</h2>
                            </div>

                            <div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_320px]">
                                <div class="relative min-h-56 overflow-hidden rounded-lg border border-slate-200 bg-slate-100">
                                    <div
                                        class="absolute inset-0 bg-[linear-gradient(90deg,rgba(148,163,184,0.18)_1px,transparent_1px),linear-gradient(rgba(148,163,184,0.18)_1px,transparent_1px)] bg-[size:36px_36px]"
                                    ></div>
                                    <div class="absolute top-12 left-8 h-20 w-28 rounded-lg bg-emerald-100/80"></div>
                                    <div class="absolute inset-x-0 top-32 h-3 rotate-[-2deg] bg-sky-200/80"></div>
                                    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-full text-teal-700">
                                        <div class="rounded-full border border-teal-600 bg-white px-3 py-1 text-xs font-bold shadow-sm">
                                            {{ activeLocation?.type_label }}
                                        </div>
                                        <MapPin class="mx-auto -mt-1 size-6 fill-teal-600 text-teal-600" />
                                    </div>
                                    <div
                                        class="absolute inset-x-4 bottom-4 rounded-lg border border-white/80 bg-white/90 p-3 shadow-sm backdrop-blur"
                                    >
                                        <p class="text-sm font-bold text-slate-950">{{ activeLocation?.name }}</p>
                                        <p class="mt-1 text-xs text-slate-600">{{ activeLocation?.address || 'Endereço não informado' }}</p>
                                    </div>
                                </div>

                                <div class="space-y-2">
                                    <button
                                        v-for="location in doctor.service_locations"
                                        :key="location.id"
                                        type="button"
                                        class="w-full rounded-lg border p-4 text-left transition"
                                        :class="
                                            activeLocationId === location.id
                                                ? 'border-teal-300 bg-teal-50'
                                                : 'border-slate-200 bg-white hover:bg-slate-50'
                                        "
                                        @click="activeLocationId = location.id"
                                    >
                                        <span
                                            class="mb-2 inline-flex rounded-full border border-slate-200 bg-white px-2 py-0.5 text-xs font-semibold text-slate-600"
                                            >{{ location.type_label }}</span
                                        >
                                        <p class="text-sm font-bold text-slate-950">{{ location.name }}</p>
                                        <p class="mt-1 text-xs leading-5 text-slate-500">{{ location.address || 'Endereço não informado' }}</p>
                                    </button>
                                </div>
                            </div>
                        </section>

                        <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                            <div class="mb-4">
                                <p class="text-xs font-bold tracking-[0.08em] text-slate-500 uppercase">O que esperar</p>
                                <h2 class="mt-1 text-xl font-bold text-slate-950">Sua consulta inclui</h2>
                            </div>

                            <div class="grid gap-5 lg:grid-cols-2">
                                <div class="space-y-3">
                                    <div
                                        v-for="item in [
                                            'Avaliação clínica completa',
                                            'Prontuário eletrônico e plano de cuidado',
                                            'Receitas e atestados digitais quando indicado',
                                            'Orientações para continuidade do cuidado',
                                        ]"
                                        :key="item"
                                        class="flex items-start gap-3"
                                    >
                                        <span class="mt-0.5 grid size-6 shrink-0 place-items-center rounded-lg bg-teal-50 text-teal-800">
                                            <Check class="size-3.5" />
                                        </span>
                                        <span class="text-sm leading-6 text-slate-700">{{ item }}</span>
                                    </div>
                                </div>
                                <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                                    <p class="mb-3 flex items-center gap-2 text-xs font-bold tracking-[0.08em] text-slate-500 uppercase">
                                        <ShieldCheck class="size-3.5" />
                                        Política
                                    </p>
                                    <div class="space-y-2 text-sm leading-6 text-slate-700">
                                        <p>Cancelamentos e reagendamentos seguem as regras exibidas no fluxo de confirmação.</p>
                                        <p>Consultas online liberam o acesso por vídeo próximo ao horário agendado.</p>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                            <div class="mb-4 flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-xs font-bold tracking-[0.08em] text-slate-500 uppercase">Pacientes</p>
                                    <h2 class="mt-1 text-xl font-bold text-slate-950">Avaliações</h2>
                                </div>
                                <div class="flex items-center gap-1 text-amber-500">
                                    <Star v-for="index in 5" :key="index" class="size-4" />
                                </div>
                            </div>
                            <div class="rounded-lg border border-dashed border-slate-300 bg-slate-50 p-5 text-sm leading-6 text-slate-600">
                                As avaliações verificadas deste médico aparecerão aqui quando estiverem disponíveis.
                            </div>
                        </section>

                        <section v-if="doctor.related_doctors.length > 0" class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                            <div class="mb-4 flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-xs font-bold tracking-[0.08em] text-slate-500 uppercase">Você também pode considerar</p>
                                    <h2 class="mt-1 text-xl font-bold text-slate-950">Médicos relacionados</h2>
                                </div>
                                <Button as-child variant="ghost" size="sm" class="text-slate-700">
                                    <Link :href="patientRoutes.searchConsultations()">
                                        Ver mais
                                        <ArrowRight class="ml-1 size-3.5" />
                                    </Link>
                                </Button>
                            </div>

                            <div class="grid gap-3 md:grid-cols-3">
                                <div
                                    v-for="relatedDoctor in doctor.related_doctors"
                                    :key="relatedDoctor.id"
                                    class="rounded-lg border border-slate-200 p-4"
                                >
                                    <div class="flex items-center gap-3">
                                        <Avatar class="size-11">
                                            <AvatarImage
                                                v-if="relatedDoctor.avatar || relatedDoctor.avatar_thumbnail"
                                                :src="relatedDoctor.avatar_thumbnail || relatedDoctor.avatar || undefined"
                                            />
                                            <AvatarFallback class="bg-teal-700 text-sm font-semibold text-white">{{
                                                getInitials(relatedDoctor.name)
                                            }}</AvatarFallback>
                                        </Avatar>
                                        <div class="min-w-0">
                                            <p class="truncate text-sm font-bold text-slate-950">{{ relatedDoctor.name }}</p>
                                            <p class="truncate text-xs text-slate-500">{{ relatedDoctor.primary_specialty }}</p>
                                        </div>
                                    </div>
                                    <div class="mt-3 flex items-center justify-between gap-2">
                                        <span class="text-sm font-semibold text-slate-900">{{ formatCurrency(relatedDoctor.consultation_fee) }}</span>
                                        <Button as-child variant="outline" size="sm" class="h-8">
                                            <Link :href="relatedDoctorHref(relatedDoctor.id)">Perfil</Link>
                                        </Button>
                                    </div>
                                </div>
                            </div>
                        </section>
                    </div>

                    <aside class="space-y-5 xl:sticky xl:top-6 xl:self-start">
                        <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                            <div class="mb-4 flex items-start justify-between gap-3">
                                <div>
                                    <h2 class="text-xl font-bold text-slate-950">Disponibilidade</h2>
                                    <p class="mt-1 text-xs text-slate-500">Próximos 30 dias</p>
                                </div>
                                <Button as-child variant="ghost" size="sm" class="text-slate-700">
                                    <Link :href="scheduleHref()">Ver agenda <ArrowRight class="ml-1 size-3.5" /></Link>
                                </Button>
                            </div>

                            <div v-if="modalityOptions.length > 1" class="mb-4 grid grid-cols-2 rounded-lg bg-slate-100 p-1">
                                <button
                                    v-for="option in modalityOptions"
                                    :key="option.value"
                                    type="button"
                                    class="flex h-9 items-center justify-center gap-2 rounded-md text-sm font-semibold transition"
                                    :class="
                                        selectedModality === option.value
                                            ? 'bg-white text-slate-950 shadow-sm'
                                            : 'text-slate-500 hover:text-slate-900'
                                    "
                                    @click="selectedModality = option.value"
                                >
                                    <component :is="option.icon" class="size-4" />
                                    {{ option.label }}
                                </button>
                            </div>

                            <div v-if="upcomingSlots.length > 0" class="mb-5 space-y-2">
                                <p class="text-xs font-bold tracking-[0.08em] text-slate-500 uppercase">Próximos horários</p>
                                <div class="flex flex-wrap gap-2">
                                    <button
                                        v-for="slot in upcomingSlots"
                                        :key="`${slot.date}-${slot.slot}`"
                                        type="button"
                                        class="h-8 rounded-lg border px-3 text-xs font-semibold transition"
                                        :class="
                                            selectedDate === slot.date && selectedTime === slot.slot
                                                ? 'border-teal-500 bg-teal-500 text-slate-950'
                                                : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50'
                                        "
                                        @click="selectQuickSlot(slot.date, slot.slot)"
                                    >
                                        {{ quickSlotLabel(slot.date) }} | {{ slot.slot }}
                                    </button>
                                </div>
                            </div>

                            <div class="space-y-3">
                                <div class="flex items-center justify-between">
                                    <button
                                        type="button"
                                        class="grid size-9 place-items-center rounded-lg border border-slate-200 text-slate-700 transition hover:bg-slate-50"
                                        aria-label="Mês anterior"
                                        @click="goToPreviousMonth"
                                    >
                                        <ChevronLeft class="size-4" />
                                    </button>
                                    <p class="text-sm font-bold text-slate-950 capitalize">{{ currentMonth }}</p>
                                    <button
                                        type="button"
                                        class="grid size-9 place-items-center rounded-lg border border-slate-200 text-slate-700 transition hover:bg-slate-50"
                                        aria-label="Próximo mês"
                                        @click="goToNextMonth"
                                    >
                                        <ChevronRight class="size-4" />
                                    </button>
                                </div>

                                <div class="grid grid-cols-7 gap-1">
                                    <div
                                        v-for="(day, index) in weekdays"
                                        :key="`${day}-${index}`"
                                        class="grid h-7 place-items-center text-xs font-bold text-slate-500"
                                    >
                                        {{ day }}
                                    </div>
                                    <button
                                        v-for="day in calendarDays"
                                        :key="day.date"
                                        type="button"
                                        class="grid h-9 place-items-center rounded-lg text-sm font-semibold transition"
                                        :class="[
                                            !day.inMonth ? 'text-slate-300' : 'text-slate-800',
                                            day.isToday ? 'ring-1 ring-teal-300' : '',
                                            day.isAvailable && !day.isPast ? 'hover:bg-teal-50' : 'cursor-not-allowed text-slate-300',
                                            selectedDate === day.date ? 'bg-teal-500 text-slate-950 hover:bg-teal-500' : '',
                                        ]"
                                        :disabled="day.isPast || !day.isAvailable"
                                        @click="selectDate(day.date)"
                                    >
                                        {{ day.day }}
                                    </button>
                                </div>
                            </div>

                            <div v-if="selectedDate && availableTimes.length > 0" class="mt-5 space-y-3">
                                <div class="flex items-center justify-between gap-3">
                                    <p class="text-sm font-semibold text-slate-950 capitalize">{{ selectedDateLong }}</p>
                                    <span class="text-xs text-slate-500">{{ availableTimes.length }} horários</span>
                                </div>
                                <div class="grid grid-cols-3 gap-2">
                                    <button
                                        v-for="time in availableTimes"
                                        :key="time"
                                        type="button"
                                        class="h-10 rounded-lg border text-sm font-semibold transition"
                                        :class="
                                            selectedTime === time
                                                ? 'border-teal-500 bg-teal-500 text-slate-950'
                                                : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50'
                                        "
                                        @click="selectedTime = time"
                                    >
                                        {{ time }}
                                    </button>
                                </div>
                                <Button v-if="selectedTime" as-child class="h-10 w-full bg-teal-500 font-semibold text-slate-950 hover:bg-teal-400">
                                    <Link :href="scheduleHref()">
                                        Continuar agendamento
                                        <ArrowRight class="ml-2 size-4" />
                                    </Link>
                                </Button>
                            </div>

                            <div
                                v-else-if="!monthHasAvailability"
                                class="mt-5 rounded-lg border border-dashed border-slate-300 bg-slate-50 p-5 text-center"
                            >
                                <div class="mx-auto mb-3 grid size-12 place-items-center rounded-lg bg-white text-slate-500 ring-1 ring-slate-200">
                                    <Calendar class="size-6" />
                                </div>
                                <p class="font-bold text-slate-950">Sem vagas neste mês</p>
                                <p class="mt-1 text-sm leading-6 text-slate-500">A agenda está completa para este período.</p>
                                <Button type="button" variant="outline" class="mt-4 h-9" @click="notifyAvailability">
                                    <Bell class="mr-2 size-4" />
                                    Avise-me quando abrir
                                </Button>
                            </div>

                            <p v-else class="mt-5 rounded-lg bg-slate-50 p-4 text-center text-sm text-slate-500">
                                Selecione uma data disponível para ver os horários.
                            </p>
                        </section>
                    </aside>
                </section>
            </main>
        </div>

        <teleport to="body">
            <div v-if="shareOpen" class="fixed inset-0 z-[120] grid place-items-center bg-slate-950/40 px-4" @click="shareOpen = false">
                <div class="w-full max-w-md rounded-lg border border-slate-200 bg-white shadow-xl" @click.stop>
                    <div class="flex items-center justify-between px-5 pt-5">
                        <h2 class="text-lg font-bold text-slate-950">Compartilhar perfil</h2>
                        <button
                            type="button"
                            class="grid size-9 place-items-center rounded-lg text-slate-500 hover:bg-slate-100"
                            aria-label="Fechar"
                            @click="shareOpen = false"
                        >
                            <X class="size-4" />
                        </button>
                    </div>
                    <div class="space-y-4 p-5">
                        <p class="text-sm text-slate-600">Envie este perfil para alguém que pode se beneficiar.</p>
                        <div class="flex items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 p-2">
                            <span class="min-w-0 flex-1 truncate font-mono text-xs text-slate-600">{{ shareUrl }}</span>
                            <Button type="button" variant="outline" size="sm" class="h-8" @click="copyShareUrl">
                                <Copy class="mr-1.5 size-3.5" />
                                Copiar
                            </Button>
                        </div>
                    </div>
                </div>
            </div>
        </teleport>
    </AppLayout>
</template>
