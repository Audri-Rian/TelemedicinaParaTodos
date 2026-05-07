<script setup lang="ts">
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { useRouteGuard } from '@/composables/auth';
import { useInitials } from '@/composables/useInitials';
import AppLayout from '@/layouts/AppLayout.vue';
import * as patientRoutes from '@/routes/patient';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    AlertTriangle,
    Calendar,
    Camera,
    Check,
    Clock,
    FileText,
    Home,
    Lock,
    Mic,
    MonitorUp,
    RefreshCw,
    ShieldCheck,
    Video,
    VideoOff,
    Wifi,
} from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';

interface Appointment {
    id: string;
    scheduled_at: string;
    formatted_date: string;
    formatted_time: string;
    status: string;
}

interface User {
    id: number;
    name: string;
    email: string;
    hasAppointment?: boolean;
    canStartCall?: boolean;
    appointment?: Appointment | null;
    allAppointments?: Appointment[];
    timeWindowMessage?: string | null;
}

type StatusTone = 'live' | 'go' | 'wait' | 'warn' | 'muted';

const { canAccessPatientRoute } = useRouteGuard();
const { getInitials } = useInitials();
const page = usePage();

const users = ((page.props.users as User[]) || []).map((user) => ({
    ...user,
    allAppointments: user.allAppointments ?? [],
}));

const selectedUser = ref<User | null>(users[0] ?? null);
const isMobileDetail = ref(false);

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: patientRoutes.dashboard().url,
    },
    {
        title: 'Videoconferência',
        href: patientRoutes.videoCall().url,
    },
];

onMounted(() => {
    canAccessPatientRoute();
});

const selectedAppointment = computed(() => selectedUser.value?.appointment ?? null);
const hasMultipleAppointments = computed(() => (selectedUser.value?.allAppointments?.length ?? 0) > 1);

const selectUser = (user: User) => {
    selectedUser.value = user;
    isMobileDetail.value = true;
};

const updateSelectedAppointment = () => {
    if (!selectedUser.value?.allAppointments || !selectedUser.value.appointment) {
        return;
    }

    const newAppointment = selectedUser.value.allAppointments.find((appointment) => appointment.id === selectedUser.value?.appointment?.id);

    if (newAppointment) {
        selectedUser.value.appointment = { ...newAppointment };
        updateAppointmentStatus(newAppointment);
    }
};

const updateAppointmentStatus = (appointment: Appointment) => {
    if (!selectedUser.value) {
        return;
    }

    const now = new Date();
    const scheduledAt = new Date(appointment.scheduled_at);
    const diffMinutes = Math.round((scheduledAt.getTime() - now.getTime()) / 60_000);

    if (appointment.status === 'in_progress') {
        selectedUser.value.canStartCall = true;
        selectedUser.value.timeWindowMessage = 'Consulta em andamento';
        return;
    }

    if (appointment.status === 'completed') {
        selectedUser.value.canStartCall = false;
        selectedUser.value.timeWindowMessage = 'Consulta finalizada';
        return;
    }

    if (appointment.status === 'no_show') {
        selectedUser.value.canStartCall = false;
        selectedUser.value.timeWindowMessage = 'Consulta não comparecida';
        return;
    }

    if (['scheduled', 'rescheduled'].includes(appointment.status)) {
        if (diffMinutes >= -10 && diffMinutes <= 10) {
            selectedUser.value.canStartCall = true;
            selectedUser.value.timeWindowMessage =
                diffMinutes === 0
                    ? 'Horário da consulta'
                    : diffMinutes < 0
                      ? `Tempo restante: ${Math.abs(diffMinutes)} min`
                      : `Início em ${diffMinutes} min`;
            return;
        }

        selectedUser.value.canStartCall = false;
        if (diffMinutes < -10) {
            selectedUser.value.timeWindowMessage = 'Janela de tempo expirada';
            return;
        }

        const daysUntil = Math.floor(diffMinutes / (24 * 60));
        selectedUser.value.timeWindowMessage =
            daysUntil > 0 ? `Agendado para ${daysUntil} ${daysUntil === 1 ? 'dia' : 'dias'}` : `Início em ${Math.floor(diffMinutes / 60)} hora(s)`;
    }
};

const getStatusLabel = (status: string): string => {
    const statusLabels: Record<string, string> = {
        scheduled: 'Agendado',
        rescheduled: 'Reagendado',
        in_progress: 'Em andamento',
        completed: 'Finalizado',
        cancelled: 'Cancelado',
        no_show: 'Não compareceu',
    };

    return statusLabels[status] || status;
};

const statusTone = (user: User | null): StatusTone => {
    if (!user?.hasAppointment || !user.appointment) {
        return 'muted';
    }

    if (user.appointment.status === 'in_progress') {
        return 'live';
    }

    if (user.canStartCall) {
        return 'go';
    }

    if (['completed'].includes(user.appointment.status)) {
        return 'muted';
    }

    if (['no_show'].includes(user.appointment.status) || user.timeWindowMessage?.includes('expirada')) {
        return 'warn';
    }

    return 'wait';
};

const statusClasses = (tone: StatusTone) => {
    const map: Record<StatusTone, string> = {
        live: 'bg-rose-50 text-rose-700',
        go: 'bg-emerald-50 text-emerald-700',
        wait: 'bg-amber-50 text-amber-700',
        warn: 'bg-orange-50 text-orange-700',
        muted: 'bg-gray-100 text-gray-600',
    };

    return map[tone];
};

const statusDotClasses = (tone: StatusTone) => {
    const map: Record<StatusTone, string> = {
        live: 'bg-rose-500 animate-pulse',
        go: 'bg-emerald-500',
        wait: 'bg-amber-500',
        warn: 'bg-orange-500',
        muted: 'bg-gray-400',
    };

    return map[tone];
};

const ctaMode = computed<'maintenance' | 'enabled' | 'disabled-window' | 'disabled-noappt'>(() => {
    if (!selectedUser.value?.hasAppointment) {
        return 'disabled-noappt';
    }

    if (!selectedUser.value.canStartCall) {
        return 'disabled-window';
    }

    return 'maintenance';
});

const ctaConfig = computed(() => {
    const map = {
        enabled: {
            label: 'Entrar na videochamada',
            description: 'Disponível agora. A janela da consulta está aberta.',
            icon: Video,
            class: 'bg-teal-500 text-gray-950 hover:bg-teal-400',
        },
        'disabled-window': {
            label: 'Fora da janela de tempo',
            description: 'A chamada abre 10 minutos antes do horário e fecha 10 minutos depois.',
            icon: Clock,
            class: 'cursor-not-allowed bg-gray-200 text-gray-500',
        },
        'disabled-noappt': {
            label: 'Sem agendamento disponível',
            description: 'Agende uma consulta para acessar a sala de vídeo.',
            icon: VideoOff,
            class: 'cursor-not-allowed bg-gray-200 text-gray-500',
        },
        maintenance: {
            label: 'Videoconferência em atualização',
            description: 'Estamos atualizando a infraestrutura SFU/MediaSoup. Em breve a entrada será liberada.',
            icon: Lock,
            class: 'cursor-not-allowed bg-gray-200 text-gray-500',
        },
    };

    return map[ctaMode.value];
});

const checklist = computed(() => [
    {
        label: 'Câmera e microfone permitidos',
        description: 'Verifique permissões do navegador antes da consulta.',
        icon: Mic,
        ok: true,
    },
    {
        label: 'Conexão estável',
        description: 'Prefira Wi-Fi ou conexão cabeada.',
        icon: Wifi,
        ok: true,
    },
    {
        label: 'Documento ou exames em mãos',
        description: 'Tenha arquivos e resultados por perto.',
        icon: FileText,
        ok: false,
    },
    {
        label: 'Ambiente privado',
        description: 'Use um local reservado e bem iluminado.',
        icon: Home,
        ok: false,
    },
]);
</script>

<template>
    <Head title="Videoconferência" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex min-h-0 flex-1 bg-[#f4f6f8] p-0 text-gray-950">
            <div class="flex min-h-0 flex-1 flex-col overflow-hidden rounded-lg border border-[#dde5ea] bg-white shadow-sm">
                <header class="border-b border-[#dde5ea] px-5 py-4">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                        <div>
                            <div
                                class="inline-flex items-center gap-2 rounded-full border border-[#dde5ea] bg-[#f4f6f8] px-3 py-1 text-xs font-black text-gray-600"
                            >
                                <ShieldCheck class="h-3.5 w-3.5 text-[#0f6e78]" />
                                Sala segura de teleconsulta
                            </div>
                            <h1 class="mt-2 text-3xl font-black text-gray-950">Videoconferência</h1>
                            <p class="mt-1 text-sm font-semibold text-gray-600">
                                Selecione o médico vinculado à consulta e revise o agendamento antes de entrar.
                            </p>
                        </div>

                        <div class="grid grid-cols-2 gap-2 sm:flex">
                            <div class="rounded-lg border border-[#dde5ea] bg-[#f4f6f8] px-4 py-2">
                                <p class="text-[11px] font-black text-gray-500 uppercase">Médicos</p>
                                <p class="text-xl font-black text-gray-950">{{ users.length }}</p>
                            </div>
                            <div class="rounded-lg border border-[#dde5ea] bg-[#e5f1f2] px-4 py-2">
                                <p class="text-[11px] font-black text-gray-500 uppercase">Janela</p>
                                <p class="text-xl font-black text-gray-950">10 min</p>
                            </div>
                            <Button as-child class="col-span-2 h-11 bg-[#0f6e78] font-black text-white hover:bg-[#0a4f57] sm:col-span-1">
                                <Link :href="patientRoutes.historyConsultations()">Minhas consultas</Link>
                            </Button>
                        </div>
                    </div>
                </header>

                <div class="grid min-h-0 flex-1 lg:grid-cols-[380px_minmax(0,1fr)]">
                    <aside class="min-h-0 flex-col border-r border-[#dde5ea] bg-white" :class="isMobileDetail ? 'hidden lg:flex' : 'flex'">
                        <div class="border-b border-[#dde5ea] p-4">
                            <h2 class="text-lg font-black text-gray-950">Médicos vinculados</h2>
                            <p class="mt-1 text-sm font-semibold text-gray-500">Consultas ativas, futuras e recentes.</p>
                        </div>

                        <div class="min-h-0 flex-1 overflow-y-auto p-3">
                            <div v-if="users.length === 0" class="flex h-full flex-col items-center justify-center px-6 text-center">
                                <VideoOff class="h-12 w-12 text-gray-300" />
                                <h3 class="mt-4 text-lg font-black text-gray-950">Nenhuma consulta encontrada</h3>
                                <p class="mt-2 text-sm font-medium text-gray-500">Agende uma consulta para habilitar a sala de vídeo.</p>
                                <Button as-child class="mt-5 bg-teal-500 font-black text-gray-950 hover:bg-teal-400">
                                    <Link :href="patientRoutes.searchConsultations()">Buscar consulta</Link>
                                </Button>
                            </div>

                            <button
                                v-for="user in users"
                                v-else
                                :key="user.id"
                                type="button"
                                class="mb-2 flex w-full items-center gap-3 rounded-lg border p-3 text-left transition hover:border-[#0f6e78]/30 hover:bg-[#f4f6f8]"
                                :class="selectedUser?.id === user.id ? 'border-[#0f6e78] bg-[#e5f1f2]' : 'border-[#e6ebee] bg-white'"
                                @click="selectUser(user)"
                            >
                                <Avatar class="h-12 w-12 border border-[#dde5ea]">
                                    <AvatarFallback class="bg-[#e5f1f2] text-sm font-black text-[#0f6e78]">
                                        {{ getInitials(user.name) }}
                                    </AvatarFallback>
                                </Avatar>
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center justify-between gap-2">
                                        <h3 class="truncate text-sm font-black text-gray-950">{{ user.name }}</h3>
                                        <Check v-if="selectedUser?.id === user.id" class="h-4 w-4 shrink-0 text-[#0f6e78]" />
                                    </div>
                                    <p class="mt-0.5 truncate text-xs font-semibold text-gray-500">{{ user.email }}</p>
                                    <span
                                        class="mt-2 inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-black"
                                        :class="statusClasses(statusTone(user))"
                                    >
                                        <span class="h-1.5 w-1.5 rounded-full" :class="statusDotClasses(statusTone(user))" />
                                        {{ user.timeWindowMessage || (user.hasAppointment ? 'Agendado' : 'Sem agendamento') }}
                                    </span>
                                </div>
                            </button>
                        </div>
                    </aside>

                    <main class="min-h-0 flex-col bg-[#f4f6f8]" :class="selectedUser ? 'flex' : 'hidden lg:flex'">
                        <div v-if="!selectedUser" class="flex flex-1 items-center justify-center px-6 text-center">
                            <div class="max-w-md">
                                <div class="mx-auto grid h-20 w-20 place-items-center rounded-2xl bg-[#e5f1f2] text-[#0f6e78]">
                                    <Video class="h-10 w-10" />
                                </div>
                                <h2 class="mt-5 text-2xl font-black text-gray-950">Selecione uma consulta</h2>
                                <p class="mt-2 text-sm font-semibold text-gray-600">
                                    Escolha um médico da lista para revisar horário, status e checklist pré-chamada.
                                </p>
                            </div>
                        </div>

                        <template v-else>
                            <div class="flex items-center gap-3 border-b border-[#dde5ea] bg-white px-4 py-3 lg:hidden">
                                <Button variant="outline" class="h-9 border-[#dde5ea] px-3 font-extrabold" @click="isMobileDetail = false">
                                    Voltar
                                </Button>
                                <p class="truncate text-sm font-black text-gray-950">{{ selectedUser.name }}</p>
                            </div>

                            <div class="min-h-0 flex-1 overflow-y-auto p-4 lg:p-5">
                                <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_360px]">
                                    <section class="space-y-5">
                                        <div class="rounded-lg border border-[#dde5ea] bg-white p-5 shadow-sm">
                                            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                                <div class="flex gap-4">
                                                    <Avatar class="h-16 w-16 border border-[#dde5ea]">
                                                        <AvatarFallback class="bg-[#e5f1f2] text-lg font-black text-[#0f6e78]">
                                                            {{ getInitials(selectedUser.name) }}
                                                        </AvatarFallback>
                                                    </Avatar>
                                                    <div>
                                                        <p class="text-xs font-black text-gray-500 uppercase">Médico selecionado</p>
                                                        <h2 class="mt-1 text-2xl font-black text-gray-950">{{ selectedUser.name }}</h2>
                                                        <p class="mt-1 text-sm font-semibold text-gray-500">{{ selectedUser.email }}</p>
                                                        <span
                                                            class="mt-3 inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-black"
                                                            :class="statusClasses(statusTone(selectedUser))"
                                                        >
                                                            <span class="h-2 w-2 rounded-full" :class="statusDotClasses(statusTone(selectedUser))" />
                                                            {{ selectedUser.timeWindowMessage || 'Status do agendamento' }}
                                                        </span>
                                                    </div>
                                                </div>

                                                <div class="rounded-lg border border-[#dde5ea] bg-[#f4f6f8] px-4 py-3">
                                                    <p class="text-[11px] font-black text-gray-500 uppercase">Status do serviço</p>
                                                    <p class="mt-1 flex items-center gap-2 text-sm font-black text-gray-700">
                                                        <RefreshCw class="h-4 w-4" />
                                                        Em atualização
                                                    </p>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="rounded-lg border border-[#dde5ea] bg-white p-5 shadow-sm">
                                            <div class="mb-4 flex items-center gap-2">
                                                <Calendar class="h-5 w-5 text-[#0f6e78]" />
                                                <h2 class="text-lg font-black text-gray-950">Agendamento</h2>
                                            </div>

                                            <template v-if="selectedUser.hasAppointment && selectedAppointment">
                                                <label v-if="hasMultipleAppointments" class="mb-4 block space-y-2">
                                                    <span class="text-sm font-extrabold text-gray-700">Selecionar consulta</span>
                                                    <select
                                                        v-model="selectedUser.appointment.id"
                                                        class="h-11 w-full rounded-lg border border-[#dde5ea] bg-white px-3 text-sm font-semibold text-gray-800 outline-none focus:border-[#0f6e78] focus:ring-2 focus:ring-[#0f6e78]/20"
                                                        @change="updateSelectedAppointment"
                                                    >
                                                        <option
                                                            v-for="appointment in selectedUser.allAppointments"
                                                            :key="appointment.id"
                                                            :value="appointment.id"
                                                        >
                                                            {{ appointment.formatted_date }} às {{ appointment.formatted_time }} -
                                                            {{ getStatusLabel(appointment.status) }}
                                                        </option>
                                                    </select>
                                                </label>

                                                <div class="grid gap-3 sm:grid-cols-3">
                                                    <div class="rounded-lg border border-[#e6ebee] bg-[#f7f8f9] p-4">
                                                        <p class="text-[11px] font-black text-gray-500 uppercase">Data</p>
                                                        <p class="mt-1 text-lg font-black text-gray-950">{{ selectedAppointment.formatted_date }}</p>
                                                    </div>
                                                    <div class="rounded-lg border border-[#e6ebee] bg-[#f7f8f9] p-4">
                                                        <p class="text-[11px] font-black text-gray-500 uppercase">Horário</p>
                                                        <p class="mt-1 text-lg font-black text-gray-950">{{ selectedAppointment.formatted_time }}</p>
                                                    </div>
                                                    <div class="rounded-lg border border-[#e6ebee] bg-[#f7f8f9] p-4">
                                                        <p class="text-[11px] font-black text-gray-500 uppercase">Situação</p>
                                                        <p class="mt-1 text-lg font-black text-gray-950">
                                                            {{ getStatusLabel(selectedAppointment.status) }}
                                                        </p>
                                                    </div>
                                                </div>
                                            </template>

                                            <div v-else class="rounded-lg border border-amber-100 bg-amber-50 p-4">
                                                <div class="flex gap-3">
                                                    <AlertTriangle class="h-5 w-5 shrink-0 text-amber-700" />
                                                    <div>
                                                        <h3 class="font-black text-amber-900">Sem agendamento com este médico</h3>
                                                        <p class="mt-1 text-sm font-semibold text-amber-800">
                                                            É necessário ter uma consulta marcada para entrar na videochamada.
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="rounded-lg border border-[#dde5ea] bg-white p-5 shadow-sm">
                                            <h2 class="text-lg font-black text-gray-950">Antes de entrar</h2>
                                            <p class="mt-1 text-sm font-semibold text-gray-500">
                                                Revise permissões e ambiente para evitar interrupções.
                                            </p>

                                            <div class="mt-4 grid gap-3 md:grid-cols-2">
                                                <div
                                                    v-for="item in checklist"
                                                    :key="item.label"
                                                    class="flex gap-3 rounded-lg border border-[#e6ebee] bg-[#f7f8f9] p-3"
                                                >
                                                    <span
                                                        class="grid h-9 w-9 shrink-0 place-items-center rounded-lg"
                                                        :class="
                                                            item.ok
                                                                ? 'bg-emerald-50 text-emerald-700'
                                                                : 'bg-white text-gray-500 ring-1 ring-[#dde5ea]'
                                                        "
                                                    >
                                                        <Check v-if="item.ok" class="h-4 w-4" />
                                                        <component :is="item.icon" v-else class="h-4 w-4" />
                                                    </span>
                                                    <div>
                                                        <p class="text-sm font-black text-gray-950">{{ item.label }}</p>
                                                        <p class="mt-0.5 text-xs font-semibold text-gray-500">{{ item.description }}</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </section>

                                    <aside class="space-y-4">
                                        <div class="rounded-lg border border-[#dde5ea] bg-white p-5 shadow-sm">
                                            <div class="grid aspect-video place-items-center rounded-lg bg-[#0b2030] text-white">
                                                <div class="text-center">
                                                    <Camera class="mx-auto h-10 w-10 text-[#40e0d0]" />
                                                    <p class="mt-3 text-sm font-black">Prévia indisponível</p>
                                                    <p class="mt-1 px-6 text-xs font-semibold text-white/60">
                                                        A prévia será exibida quando a nova sala de vídeo estiver ativa.
                                                    </p>
                                                </div>
                                            </div>

                                            <button
                                                type="button"
                                                disabled
                                                class="mt-4 flex h-12 w-full items-center justify-center gap-2 rounded-lg border-none px-4 text-sm font-black"
                                                :class="ctaConfig.class"
                                            >
                                                <component :is="ctaConfig.icon" class="h-4 w-4" />
                                                {{ ctaConfig.label }}
                                            </button>
                                            <p class="mt-2 text-center text-xs font-semibold text-gray-500">{{ ctaConfig.description }}</p>
                                        </div>

                                        <div class="rounded-lg border border-[#dde5ea] bg-white p-5 shadow-sm">
                                            <h2 class="flex items-center gap-2 text-base font-black text-gray-950">
                                                <MonitorUp class="h-4 w-4 text-[#0f6e78]" />
                                                Orientações rápidas
                                            </h2>
                                            <ul class="mt-3 space-y-3 text-sm font-semibold text-gray-600">
                                                <li class="flex gap-2">
                                                    <span class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-[#0f6e78]" />
                                                    A chamada só abre na janela de 10 minutos antes/depois do horário.
                                                </li>
                                                <li class="flex gap-2">
                                                    <span class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-[#0f6e78]" />
                                                    Use um local privado e mantenha documentos próximos.
                                                </li>
                                                <li class="flex gap-2">
                                                    <span class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-[#0f6e78]" />
                                                    Permita câmera e microfone no navegador quando solicitado.
                                                </li>
                                            </ul>
                                        </div>
                                    </aside>
                                </div>
                            </div>
                        </template>
                    </main>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
