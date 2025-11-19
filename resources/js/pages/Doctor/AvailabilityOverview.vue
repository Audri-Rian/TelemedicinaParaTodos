<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import * as doctorRoutes from '@/routes/doctor';
import * as availabilityRoutes from '@/routes/doctor/availability';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, reactive, ref, watch } from 'vue';
import {
    Breadcrumb,
    BreadcrumbItem,
    BreadcrumbLink,
    BreadcrumbList,
    BreadcrumbPage,
    BreadcrumbSeparator,
} from '@/components/ui/breadcrumb';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select } from '@/components/ui/select';
import InputError from '@/components/InputError.vue';
import {
    AlertCircle,
    Calendar,
    CheckCircle2,
    Clock3,
    Loader2,
    MapPin,
    Pencil,
    Plus,
    Trash2,
} from 'lucide-vue-next';

interface ServiceLocation {
    id: string;
    name: string;
    type: string;
    type_label?: string;
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

interface Props {
    timeline: TimelineDay[];
    summary: Summary;
    meta: {
        start: string;
        end: string;
    };
    locations: ServiceLocation[];
}

const props = defineProps<Props>();

const breadcrumbs = [
    { title: 'Dashboard', href: doctorRoutes.dashboard().url },
    { title: 'Agenda', href: doctorRoutes.appointments().url },
    { title: 'Disponibilidade', href: doctorRoutes.availability().url },
];

const page = usePage();
const doctorId = computed(() => (page.props as any).auth?.profile?.id);

const timelineState = ref<TimelineDay[]>(props.timeline ?? []);
const summaryState = ref<Summary>(props.summary ?? {
    next_session: null,
    future_slots_count: 0,
    available_this_week: 0,
    next_seven_days: { total: 0, available: 0, busy: 0 },
    past_slots_count: 0,
    last_sessions: [],
});
const locationsState = ref<ServiceLocation[]>(props.locations ?? []);

watch(() => props.timeline, (value) => {
    timelineState.value = value ?? [];
});
watch(() => props.summary, (value) => {
    summaryState.value = value ?? summaryState.value;
});
watch(() => props.locations, (value) => {
    locationsState.value = value ?? [];
});

type ViewMode = 'all' | 'future' | 'past';
const viewMode = ref<ViewMode>('all');

const filteredTimeline = computed(() => {
    if (viewMode.value === 'all') {
        return timelineState.value;
    }

    const isPast = viewMode.value === 'past';

    return timelineState.value
        .map((day) => {
            const slots = day.slots.filter((slot) => slot.is_past === isPast);
            return { ...day, slots };
        })
        .filter((day) => day.slots.length > 0)
        .map((day) => ({ ...day, is_past: isPast }));
});

const statusStyles: Record<string, { badge: string; dot: string; text: string }> = {
    available: {
        badge: 'bg-emerald-50 text-emerald-700 border border-emerald-100',
        dot: 'bg-emerald-500',
        text: 'Disponível',
    },
    busy: {
        badge: 'bg-amber-50 text-amber-700 border border-amber-100',
        dot: 'bg-amber-500',
        text: 'Ocupado',
    },
    ongoing: {
        badge: 'bg-sky-50 text-sky-700 border border-sky-100',
        dot: 'bg-sky-500',
        text: 'Em andamento',
    },
    completed: {
        badge: 'bg-indigo-50 text-indigo-700 border border-indigo-100',
        dot: 'bg-indigo-500',
        text: 'Realizado',
    },
    cancelled: {
        badge: 'bg-rose-50 text-rose-700 border border-rose-100',
        dot: 'bg-rose-500',
        text: 'Cancelado',
    },
    no_show: {
        badge: 'bg-orange-50 text-orange-700 border border-orange-100',
        dot: 'bg-orange-500',
        text: 'Ausência',
    },
    expired: {
        badge: 'bg-slate-100 text-slate-500 border border-slate-200',
        dot: 'bg-slate-400',
        text: 'Expirado',
    },
};

const modalMode = ref<'create' | 'edit'>('create');
const isModalOpen = ref(false);
const isSubmitting = ref(false);
const isRefreshing = ref(false);
const selectedSlot = ref<TimelineSlot | null>(null);

const formState = reactive({
    date: new Date().toISOString().slice(0, 10),
    startTime: '09:00',
    endTime: '10:00',
    locationId: '',
});

const formErrors = ref<Record<string, string[]>>({});

const feedback = ref<{ type: 'success' | 'error'; message: string } | null>(null);

const resetForm = () => {
    formState.date = new Date().toISOString().slice(0, 10);
    formState.startTime = '09:00';
    formState.endTime = '10:00';
    formState.locationId = locationsState.value[0]?.id ?? '';
    formErrors.value = {};
};

const openCreateModal = () => {
    modalMode.value = 'create';
    selectedSlot.value = null;
    resetForm();
    isModalOpen.value = true;
};

const openEditModal = (slot: TimelineSlot) => {
    if (!slot.can_edit) return;
    modalMode.value = 'edit';
    selectedSlot.value = slot;
    formState.date = slot.date;
    formState.startTime = slot.start_time;
    formState.endTime = slot.end_time;
    formState.locationId = slot.location?.id ?? '';
    formErrors.value = {};
    isModalOpen.value = true;
};

const closeModal = () => {
    if (isSubmitting.value) return;
    isModalOpen.value = false;
    selectedSlot.value = null;
};

const refreshData = () => {
    if (!doctorId.value) return;
    isRefreshing.value = true;
    router.reload({
        only: ['timeline', 'summary', 'meta', 'locations'],
        onFinish: () => {
            isRefreshing.value = false;
        },
    });
};

const setFeedback = (type: 'success' | 'error', message: string) => {
    feedback.value = { type, message };
    setTimeout(() => {
        if (feedback.value?.message === message) {
            feedback.value = null;
        }
    }, 4500);
};

const handleSubmit = async () => {
    if (!doctorId.value) return;

    isSubmitting.value = true;
    formErrors.value = {};

    const payload = {
        type: 'specific',
        specific_date: formState.date,
        start_time: formState.startTime,
        end_time: formState.endTime,
        location_id: formState.locationId || null,
    };

    try {
        if (modalMode.value === 'create') {
            await axios.post(availabilityRoutes.store.url({ doctor: doctorId.value }), payload);
            setFeedback('success', 'Horário criado com sucesso.');
        } else if (selectedSlot.value) {
            await axios.put(
                availabilityRoutes.update.url({ doctor: doctorId.value, slot: selectedSlot.value.id }),
                payload,
            );
            setFeedback('success', 'Horário atualizado com sucesso.');
        }

        closeModal();
        refreshData();
    } catch (error: any) {
        const message = error.response?.data?.message || 'Não foi possível salvar o horário.';
        formErrors.value = error.response?.data?.errors ?? {};
        setFeedback('error', message);
    } finally {
        isSubmitting.value = false;
    }
};

const handleDelete = async (slot: TimelineSlot) => {
    if (!doctorId.value || !slot.can_delete) return;

    const confirmed = window.confirm('Deseja realmente remover este horário específico? Esta ação não pode ser desfeita.');
    if (!confirmed) return;

    isSubmitting.value = true;

    try {
        await axios.delete(availabilityRoutes.destroy.url({ doctor: doctorId.value, slot: slot.id }));
        setFeedback('success', 'Horário removido com sucesso.');
        refreshData();
    } catch (error: any) {
        const message = error.response?.data?.message || 'Não foi possível remover o horário.';
        setFeedback('error', message);
    } finally {
        isSubmitting.value = false;
    }
};

const modeFilters = [
    { label: 'Todos', value: 'all' },
    { label: 'Futuros', value: 'future' },
    { label: 'Passados', value: 'past' },
];

const windowLabel = computed(() => {
    if (!props.meta) return '';
    const start = new Date(props.meta.start);
    const end = new Date(props.meta.end);
    return `${start.toLocaleDateString('pt-BR', { day: '2-digit', month: 'short' })} • ${end.toLocaleDateString('pt-BR', { day: '2-digit', month: 'short' })}`;
});
</script>

<template>
    <AppLayout>
        <Head title="Disponibilidade do Médico" />

        <div class="flex flex-1 flex-col gap-6 p-4 md:p-8">
            <Breadcrumb>
                <BreadcrumbList>
                    <template v-for="(item, index) in breadcrumbs" :key="item.title">
                        <BreadcrumbItem>
                            <BreadcrumbLink :href="item.href">
                                {{ item.title }}
                            </BreadcrumbLink>
                        </BreadcrumbItem>
                        <BreadcrumbSeparator v-if="index < breadcrumbs.length - 1" />
                    </template>
                    <BreadcrumbItem>
                        <BreadcrumbPage>Disponibilidade</BreadcrumbPage>
                    </BreadcrumbItem>
                </BreadcrumbList>
            </Breadcrumb>

            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <div class="space-y-1.5">
                    <p class="text-sm font-medium text-sky-600 uppercase tracking-wide">Gestão de horários</p>
                    <h1 class="text-3xl font-semibold text-slate-900">Disponibilidade do médico</h1>
                    <p class="text-sm text-slate-500">
                        Visualize e ajuste seus horários específicos sem misturar com a agenda recorrente.
                        Horários passados ficam bloqueados apenas para consulta.
                    </p>
                </div>

                <div class="flex flex-wrap gap-3">
                    <Button variant="outline" as-child>
                        <Link :href="doctorRoutes.appointments().url">
                            Voltar para Agenda
                        </Link>
                    </Button>
                    <Button @click="openCreateModal" class="shadow-md">
                        <Plus class="h-4 w-4" />
                        Adicionar horário
                    </Button>
                </div>
            </div>

            <div v-if="feedback" :class="[
                'flex items-center gap-3 rounded-2xl border px-4 py-3 text-sm font-medium shadow-sm',
                feedback.type === 'success'
                    ? 'border-emerald-100 bg-emerald-50 text-emerald-700'
                    : 'border-rose-100 bg-rose-50 text-rose-700',
            ]">
                <CheckCircle2 v-if="feedback.type === 'success'" class="h-4 w-4" />
                <AlertCircle v-else class="h-4 w-4" />
                <span>{{ feedback.message }}</span>
            </div>

            <!-- Cards resumo -->
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <Card class="border-slate-200 shadow-sm">
                    <CardHeader>
                        <CardDescription>Próximas sessões</CardDescription>
                        <CardTitle class="text-2xl text-slate-900">
                            <template v-if="summaryState.next_session">
                                {{ summaryState.next_session.time }} · {{ summaryState.next_session.weekday }}
                            </template>
                            <template v-else>
                                Sem sessões agendadas
                            </template>
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-3 text-sm text-slate-600">
                        <p>
                            {{
                                summaryState.next_session?.patient_name
                                    ? `Paciente: ${summaryState.next_session.patient_name}`
                                    : 'Sem paciente vinculado'
                            }}
                        </p>
                        <div class="rounded-xl border border-slate-100 bg-slate-50 px-3 py-2">
                            <p class="text-xs uppercase text-slate-500 tracking-wide">
                                Próximos 7 dias
                            </p>
                            <div class="mt-1 flex items-center justify-between text-sm font-semibold text-slate-800">
                                <span>{{ summaryState.next_seven_days.total }} horários</span>
                                <span class="text-emerald-600">
                                    {{ summaryState.next_seven_days.available }} disponíveis
                                </span>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card class="border-slate-200 shadow-sm">
                    <CardHeader>
                        <CardDescription>Horários futuros</CardDescription>
                        <CardTitle class="text-2xl text-slate-900">
                            {{ summaryState.future_slots_count }}
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-3 text-sm text-slate-600">
                        <p>Todos os horários específicos que ainda podem ser editados.</p>
                        <div class="rounded-xl border border-emerald-100 bg-emerald-50 px-3 py-2">
                            <p class="text-xs uppercase text-emerald-600 tracking-wide">
                                Disponíveis esta semana
                            </p>
                            <p class="mt-1 text-lg font-semibold text-emerald-700">
                                {{ summaryState.available_this_week }}
                            </p>
                        </div>
                    </CardContent>
                </Card>

                <Card class="border-slate-200 shadow-sm">
                    <CardHeader>
                        <CardDescription>Últimas consultas</CardDescription>
                        <CardTitle class="text-2xl text-slate-900">
                            {{ summaryState.past_slots_count }}
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-3 text-sm text-slate-600">
                        <div class="space-y-2">
                            <div
                                v-for="session in summaryState.last_sessions"
                                :key="session.id"
                                class="flex items-center justify-between rounded-xl border border-slate-100 px-3 py-2"
                            >
                                <div>
                                    <p class="font-semibold text-slate-800">
                                        {{ session.time }} · {{ session.date }}
                                    </p>
                                    <p class="text-xs text-slate-500">
                                        {{ session.patient_name || 'Paciente não identificado' }}
                                    </p>
                                </div>
                                <span class="text-xs font-medium uppercase text-slate-500">
                                    {{ session.status }}
                                </span>
                            </div>
                            <p v-if="!summaryState.last_sessions.length" class="text-xs text-slate-400">
                                Nenhuma consulta recente registrada neste período.
                            </p>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Calendário / lista -->
            <Card class="border-slate-200 shadow-md">
                <CardHeader class="flex flex-col gap-3 border-b border-slate-100 md:flex-row md:items-center md:justify-between">
                    <div>
                        <CardTitle class="text-2xl text-slate-900">Linha do tempo de horários</CardTitle>
                        <CardDescription>
                            Monitoramento de {{ windowLabel }} — Horários passados são exibidos em cinza para consulta.
                        </CardDescription>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <div class="flex rounded-full border border-slate-200 bg-slate-50 p-1">
                            <button
                                v-for="filter in modeFilters"
                                :key="filter.value"
                                type="button"
                                @click="viewMode = filter.value as ViewMode"
                                :class="[
                                    'rounded-full px-3 py-1 text-xs font-semibold transition-all',
                                    viewMode === filter.value
                                        ? 'bg-white text-slate-900 shadow-sm'
                                        : 'text-slate-500',
                                ]"
                            >
                                {{ filter.label }}
                            </button>
                        </div>
                        <div
                            class="flex items-center gap-1 rounded-full border border-slate-200 bg-white px-3 py-1 text-xs text-slate-500"
                        >
                            <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                            Disponível
                            <span class="h-2 w-2 rounded-full bg-amber-500 ml-3"></span>
                            Ocupado
                            <span class="h-2 w-2 rounded-full bg-slate-400 ml-3"></span>
                            Passado
                        </div>
                    </div>
                </CardHeader>
                <CardContent class="space-y-6">
                    <div
                        v-if="isRefreshing"
                        class="flex items-center justify-center rounded-2xl border border-dashed border-slate-200 p-6 text-slate-500"
                    >
                        <Loader2 class="mr-2 h-4 w-4 animate-spin" />
                        Atualizando horários...
                    </div>

                    <div v-if="!filteredTimeline.length && !isRefreshing" class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 p-6 text-center text-slate-500">
                        Nenhum horário encontrado para este filtro. Clique em “Adicionar horário” para cadastrar o próximo atendimento.
                    </div>

                    <div v-for="day in filteredTimeline" :key="day.date" class="space-y-4">
                        <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                            <div class="flex items-center gap-3">
                                <div class="rounded-2xl bg-slate-100 p-3">
                                    <Calendar class="h-5 w-5 text-slate-600" />
                                </div>
                                <div>
                                    <p class="text-xs uppercase tracking-wide text-slate-500">
                                        {{ day.weekday }}
                                    </p>
                                    <p class="text-lg font-semibold text-slate-900">
                                        {{ day.formatted_date }}
                                    </p>
                                </div>
                            </div>
                            <span
                                :class="[
                                    'text-xs font-semibold uppercase tracking-wide',
                                    day.is_past ? 'text-slate-400' : 'text-sky-600',
                                ]"
                            >
                                {{ day.is_past ? 'Finalizado' : 'Próximo' }}
                            </span>
                        </div>

                        <div class="space-y-3">
                            <div
                                v-for="slot in day.slots"
                                :key="slot.id"
                                :class="[
                                    'rounded-2xl border border-slate-100 p-4 shadow-sm transition-all',
                                    slot.is_past ? 'bg-slate-50 opacity-80' : 'bg-white hover:shadow-md',
                                ]"
                            >
                                <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                                    <div class="flex flex-1 items-start gap-4">
                                        <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-2xl bg-sky-50 text-sky-600">
                                            <Clock3 class="h-5 w-5" />
                                        </div>
                                        <div class="space-y-1">
                                            <p class="text-lg font-semibold text-slate-900">
                                                {{ slot.time_range }}
                                                <span class="text-sm font-normal text-slate-500">
                                                    · {{ slot.duration_minutes }} min
                                                </span>
                                            </p>
                                            <p class="flex items-center gap-2 text-sm text-slate-500">
                                                <MapPin class="h-4 w-4 text-slate-400" />
                                                <span>
                                                    {{
                                                        slot.location
                                                            ? `${slot.location.name} · ${slot.location.type_label || slot.location.type}`
                                                            : 'Sem local definido'
                                                    }}
                                                </span>
                                            </p>
                                            <p class="text-sm text-slate-500" v-if="slot.appointment">
                                                Paciente:
                                                <span class="font-semibold text-slate-800">
                                                    {{ slot.appointment.patient_name || 'Não informado' }}
                                                </span>
                                            </p>
                                        </div>
                                    </div>

                                    <div class="flex flex-col gap-3 md:items-end">
                                        <span
                                            :class="[
                                                'inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-semibold',
                                                statusStyles[slot.status.code]?.badge || 'bg-slate-100 text-slate-600',
                                            ]"
                                        >
                                            <span
                                                class="h-2 w-2 rounded-full"
                                                :class="statusStyles[slot.status.code]?.dot || 'bg-slate-400'"
                                            ></span>
                                            {{ statusStyles[slot.status.code]?.text || slot.status.label }}
                                        </span>

                                        <div class="flex flex-wrap gap-2">
                                            <Button
                                                size="sm"
                                                variant="outline"
                                                :disabled="!slot.can_edit || isSubmitting"
                                                @click="openEditModal(slot)"
                                            >
                                                <Pencil class="h-4 w-4" />
                                                Editar
                                            </Button>
                                            <Button
                                                size="sm"
                                                variant="destructive"
                                                :disabled="!slot.can_delete || isSubmitting"
                                                @click="handleDelete(slot)"
                                            >
                                                <Trash2 class="h-4 w-4" />
                                                Excluir
                                            </Button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Seção de orientações -->
            <Card class="border-slate-200 bg-gradient-to-br from-white to-slate-50">
                <CardHeader>
                    <CardTitle class="text-xl text-slate-900">Orientações de uso</CardTitle>
                </CardHeader>
                <CardContent class="grid gap-4 md:grid-cols-3 text-sm text-slate-600">
                    <div class="rounded-2xl border border-slate-100 bg-white p-4 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-wide text-sky-600">
                            Edição segura
                        </p>
                        <p class="mt-2 text-slate-700">
                            Apenas horários futuros ficam habilitados para edição ou exclusão. Passados permanecem apenas para consulta.
                        </p>
                    </div>
                    <div class="rounded-2xl border border-slate-100 bg-white p-4 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-wide text-sky-600">
                            Feedback em tempo real
                        </p>
                        <p class="mt-2 text-slate-700">
                            Toda ação exibe alertas verdes de sucesso ou vermelhos em caso de erro, além de spinner durante carregamentos.
                        </p>
                    </div>
                    <div class="rounded-2xl border border-slate-100 bg-white p-4 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-wide text-sky-600">
                            Modal moderno
                        </p>
                        <p class="mt-2 text-slate-700">
                            Use o botão “Adicionar horário” ou os botões de cada linha para abrir o modal elegante de edição.
                        </p>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Botão flutuante -->
        <Button
            class="fixed bottom-6 right-6 shadow-xl"
            size="lg"
            @click="openCreateModal"
        >
            <Plus class="h-4 w-4" />
            Novo horário
        </Button>

        <!-- Modal de criação/edição -->
        <Dialog :open="isModalOpen" @update:open="(value) => !value && !isSubmitting ? closeModal() : null">
            <DialogContent class="sm:max-w-lg rounded-2xl">
                <DialogHeader>
                    <DialogTitle class="text-2xl text-slate-900">
                        {{ modalMode === 'create' ? 'Adicionar horário' : 'Editar horário' }}
                    </DialogTitle>
                    <DialogDescription>
                        Defina a data, horário e local da disponibilidade. Apenas horários futuros podem ser salvos.
                    </DialogDescription>
                </DialogHeader>

                <form class="space-y-4" @submit.prevent="handleSubmit">
                    <div class="grid gap-2">
                        <Label for="availability_date">Data</Label>
                        <Input
                            id="availability_date"
                            type="date"
                            v-model="formState.date"
                            required
                        />
                        <InputError :message="formErrors.date?.[0]" />
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="availability_start">Hora início</Label>
                            <Input
                                id="availability_start"
                                type="time"
                                v-model="formState.startTime"
                                required
                            />
                            <InputError :message="formErrors.start_time?.[0]" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="availability_end">Hora fim</Label>
                            <Input
                                id="availability_end"
                                type="time"
                                v-model="formState.endTime"
                                required
                            />
                            <InputError :message="formErrors.end_time?.[0]" />
                        </div>
                    </div>

                    <div class="grid gap-2">
                        <Label for="availability_location">Local</Label>
                        <Select
                            id="availability_location"
                            v-model="formState.locationId"
                        >
                            <option value="">
                                Selecionar posteriormente
                            </option>
                            <option
                                v-for="location in locationsState"
                                :key="location.id"
                                :value="location.id"
                            >
                                {{ location.name }} · {{ location.type_label || location.type }}
                            </option>
                        </Select>
                        <InputError :message="formErrors.location_id?.[0]" />
                    </div>

                    <DialogFooter class="flex flex-col gap-3 sm:flex-row sm:justify-end">
                        <Button variant="outline" type="button" @click="closeModal" :disabled="isSubmitting">
                            Cancelar
                        </Button>
                        <Button type="submit" :disabled="isSubmitting">
                            <Loader2 v-if="isSubmitting" class="mr-2 h-4 w-4 animate-spin" />
                            {{ modalMode === 'create' ? 'Salvar horário' : 'Salvar alterações' }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>

