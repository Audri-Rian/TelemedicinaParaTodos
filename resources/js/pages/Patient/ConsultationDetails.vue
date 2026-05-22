<script setup lang="ts">
import CancelAppointmentModal from '@/components/modals/CancelAppointmentModal.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { useRouteGuard } from '@/composables/auth';
import AppLayout from '@/layouts/AppLayout.vue';
import * as appointmentsRoutes from '@/routes/appointments';
import * as patientRoutes from '@/routes/patient';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import Echo from 'laravel-echo';
import type { LucideIcon } from 'lucide-vue-next';
import {
    AlertCircle,
    ArrowRight,
    Calendar,
    CalendarPlus,
    Check,
    CheckCircle2,
    ChevronRight,
    Clock,
    Copy,
    ExternalLink,
    FileText,
    Info,
    MessageSquare,
    Play,
    ShieldCheck,
    Stethoscope,
    Video,
    Wifi,
    X,
} from 'lucide-vue-next';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';

interface Props {
    appointment: {
        id: string;
        status: 'scheduled' | 'in_progress' | 'completed' | 'cancelled' | 'rescheduled' | 'no_show';
        scheduled_at: string;
        started_at: string | null;
        ended_at: string | null;
        access_code: string;
        video_recording_url: string | null;
        notes: string | null;
        metadata?: Record<string, any> | null;
        doctor: {
            id: string;
            crm: string;
            user: {
                name: string;
                email: string;
                avatar?: string | null;
            };
            specializations: Array<{ id: string; name: string }>;
        };
        patient: {
            id: string;
            user: {
                name: string;
                email: string;
            };
        };
        logs: Array<{
            id: string;
            event: string;
            payload: Record<string, any> | null;
            created_at: string;
            user: {
                id: string;
                name: string;
            } | null;
        }>;
        can: {
            start: boolean;
            cancel: boolean;
            is_active: boolean;
            is_upcoming: boolean;
        };
    };
}

const props = defineProps<Props>();

const appointment = computed(() => props.appointment);

const videoCallHref = computed(() => patientRoutes.videoCall({ query: { appointment: appointment.value.id } }).url);

const doctorName = computed(() => appointment.value.doctor.user.name);
const doctorInitials = computed(
    () =>
        doctorName.value
            .split(' ')
            .filter(Boolean)
            .slice(0, 2)
            .map((part) => part.charAt(0).toUpperCase())
            .join('') || 'DR',
);
const primarySpecialization = computed(() => appointment.value.doctor.specializations[0]?.name || 'Especialista');
const shortAppointmentId = computed(() => `#${appointment.value.id.slice(0, 8).toUpperCase()}`);
const doctorProfileHref = computed(() => patientRoutes.doctorPerfil({ query: { doctor_id: appointment.value.doctor.id } }).url);

const statusMeta = computed(() => {
    const map: Record<string, { label: string; heroClass: string; pillClass: string; icon: LucideIcon }> = {
        scheduled: {
            label: 'Agendada',
            heroClass:
                'border-teal-100 bg-[radial-gradient(circle_at_top_left,rgba(20,184,166,0.12),transparent_38%),linear-gradient(180deg,#ffffff_0%,#f0fdfa_100%)]',
            pillClass: 'border-teal-200 bg-teal-50 text-teal-800',
            icon: Clock,
        },
        rescheduled: {
            label: 'Reagendada',
            heroClass:
                'border-blue-100 bg-[radial-gradient(circle_at_top_left,rgba(37,99,235,0.1),transparent_38%),linear-gradient(180deg,#ffffff_0%,#eff6ff_100%)]',
            pillClass: 'border-blue-200 bg-blue-50 text-blue-700',
            icon: Calendar,
        },
        in_progress: {
            label: 'Em andamento',
            heroClass:
                'border-rose-100 bg-[radial-gradient(circle_at_top_left,rgba(225,29,72,0.12),transparent_38%),linear-gradient(180deg,#ffffff_0%,#fff1f2_100%)]',
            pillClass: 'border-rose-200 bg-rose-50 text-rose-700',
            icon: Video,
        },
        completed: {
            label: 'Concluída',
            heroClass:
                'border-emerald-100 bg-[radial-gradient(circle_at_top_left,rgba(22,163,74,0.11),transparent_38%),linear-gradient(180deg,#ffffff_0%,#f0fdf4_100%)]',
            pillClass: 'border-emerald-200 bg-emerald-50 text-emerald-700',
            icon: CheckCircle2,
        },
        cancelled: {
            label: 'Cancelada',
            heroClass:
                'border-slate-200 bg-[radial-gradient(circle_at_top_left,rgba(15,23,42,0.06),transparent_38%),linear-gradient(180deg,#ffffff_0%,#f8fafc_100%)]',
            pillClass: 'border-slate-200 bg-slate-100 text-slate-600',
            icon: X,
        },
        no_show: {
            label: 'Não compareceu',
            heroClass:
                'border-amber-100 bg-[radial-gradient(circle_at_top_left,rgba(217,119,6,0.1),transparent_38%),linear-gradient(180deg,#ffffff_0%,#fffbeb_100%)]',
            pillClass: 'border-amber-200 bg-amber-50 text-amber-700',
            icon: AlertCircle,
        },
    };

    return (
        map[appointment.value.status] ?? {
            label: appointment.value.status,
            heroClass: 'border-slate-200 bg-white',
            pillClass: 'border-slate-200 bg-slate-100 text-slate-700',
            icon: Info,
        }
    );
});

const modality = computed(() => {
    const metadata = appointment.value.metadata ?? {};
    const rawValue = metadata.modality ?? metadata.type ?? metadata.consultation_type ?? metadata.location_type ?? 'online';
    const value = String(rawValue).toLowerCase();

    return ['presential', 'presencial', 'office', 'clinic', 'hospital'].includes(value) ? 'presential' : 'online';
});

const modalityLabel = computed(() => (modality.value === 'online' ? 'Consulta por vídeo' : 'Presencial'));
const canStartCall = computed(() => Boolean(appointment.value.can.start));
const canCancelAppointment = computed(() => Boolean(appointment.value.can.cancel));
const isFutureConsultation = computed(() => ['scheduled', 'rescheduled'].includes(appointment.value.status));
const isCompletedConsultation = computed(() => appointment.value.status === 'completed');

const now = ref(new Date());
const countdown = computed(() => {
    const target = new Date(appointment.value.scheduled_at).getTime();
    let diff = Math.max(0, target - now.value.getTime());
    const days = Math.floor(diff / 86400000);
    diff -= days * 86400000;
    const hours = Math.floor(diff / 3600000);
    diff -= hours * 3600000;
    const minutes = Math.floor(diff / 60000);
    diff -= minutes * 60000;
    const seconds = Math.floor(diff / 1000);

    return { days, hours, minutes, seconds };
});

const countdownItems = computed(() => [
    { label: 'dias', value: countdown.value.days },
    { label: 'horas', value: countdown.value.hours },
    { label: 'min', value: countdown.value.minutes },
    { label: 'seg', value: countdown.value.seconds },
]);

const padTime = (value: number) => String(value).padStart(2, '0');

// Formatar data e hora
const formattedDate = computed(() => {
    const date = new Date(appointment.value.scheduled_at);
    return date.toLocaleDateString('pt-BR', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });
});

const formattedTime = computed(() => {
    const date = new Date(appointment.value.scheduled_at);
    return date.toLocaleTimeString('pt-BR', {
        hour: '2-digit',
        minute: '2-digit',
    });
});

const formattedDateTime = computed(() => `${formattedDate.value}, ${formattedTime.value}`);

const formatDateTime = (value: string) => {
    const date = new Date(value);

    return (
        date.toLocaleDateString('pt-BR', {
            day: 'numeric',
            month: 'short',
            year: 'numeric',
        }) + ` · ${date.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' })}`
    );
};

// Construir timeline a partir dos logs
const timeline = computed(() => {
    return [...appointment.value.logs]
        .sort((a, b) => new Date(a.created_at).getTime() - new Date(b.created_at).getTime())
        .map((log) => {
            const eventNames: Record<string, string> = {
                created: 'Consulta Agendada',
                started: 'Consulta Iniciada',
                ended: 'Consulta Finalizada',
                cancelled: 'Consulta Cancelada',
                rescheduled: 'Consulta Reagendada',
                no_show: 'Não Compareceu',
            };

            const eventName = eventNames[log.event] || log.event;
            const time = new Date(log.created_at).toLocaleTimeString('pt-BR', {
                hour: '2-digit',
                minute: '2-digit',
            });

            let description = '';
            if (log.event === 'created') {
                description = 'Consulta agendada com sucesso.';
            } else if (log.event === 'started') {
                description = 'Consulta iniciada.';
            } else if (log.event === 'ended') {
                description = `Consulta finalizada. Duração: ${log.payload?.duration_minutes || 'N/A'} minutos.`;
            } else if (log.event === 'cancelled') {
                description = log.payload?.reason ? `Motivo: ${log.payload.reason}` : 'Consulta cancelada.';
            } else if (log.event === 'rescheduled') {
                const newDate = log.payload?.new_scheduled_at ? new Date(log.payload.new_scheduled_at).toLocaleString('pt-BR') : 'N/A';
                description = `Reagendada para: ${newDate}`;
            }

            return {
                rawEvent: log.event,
                time,
                dateTime: formatDateTime(log.created_at),
                event: eventName,
                description,
                actor: log.user?.name || 'Sistema',
            };
        });
});

// Duração da consulta
const duration = computed(() => {
    if (appointment.value.started_at && appointment.value.ended_at) {
        const start = new Date(appointment.value.started_at);
        const end = new Date(appointment.value.ended_at);
        const minutes = Math.floor((end.getTime() - start.getTime()) / 60000);
        const hours = Math.floor(minutes / 60);
        const mins = minutes % 60;
        if (hours > 0) {
            return `${hours}h ${mins}min`;
        }
        return `${mins}min`;
    }
    // Duração padrão de 45 minutos quando não houver dados de início/fim
    return '45 min';
});

// Estado da modal de cancelamento
const showCancelModal = ref(false);
const isCancelling = ref(false);
const showSuccessMessage = ref(false);
const successMessage = ref('');

const page = usePage();

// Verificar mensagens de sucesso do backend
watch(
    () => (page.props as any).flash,
    (flash: any) => {
        if (flash?.success) {
            showSuccessMessage.value = true;
            successMessage.value = flash.success;
            // Auto-ocultar após 5 segundos
            setTimeout(() => {
                showSuccessMessage.value = false;
            }, 5000);
        }
    },
    { immediate: true },
);

const openCancelModal = () => {
    showCancelModal.value = true;
};

const closeCancelModal = () => {
    showCancelModal.value = false;
};

const showTemporaryMessage = (message: string) => {
    showSuccessMessage.value = true;
    successMessage.value = message;

    setTimeout(() => {
        showSuccessMessage.value = false;
    }, 5000);
};

const copyAccessCode = async () => {
    if (typeof navigator !== 'undefined' && navigator.clipboard) {
        await navigator.clipboard.writeText(appointment.value.access_code);
    }

    showTemporaryMessage('Código de acesso copiado.');
};

const cancelAppointment = async (reason: string | null) => {
    isCancelling.value = true;
    try {
        const response = await axios.post(appointmentsRoutes.cancel.url({ appointment: appointment.value.id }), {
            reason: reason || null,
        });
        showCancelModal.value = false;

        // Mostrar mensagem de sucesso
        showSuccessMessage.value = true;
        successMessage.value = response.data?.message || 'Consulta cancelada com sucesso.';

        // Auto-ocultar após 5 segundos
        setTimeout(() => {
            showSuccessMessage.value = false;
        }, 5000);

        // Recarregar página para atualizar status
        router.reload();
    } catch (error: any) {
        console.error('Erro ao cancelar consulta:', error);
        alert(error.response?.data?.message || 'Erro ao cancelar consulta.');
    } finally {
        isCancelling.value = false;
    }
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: patientRoutes.dashboard().url,
    },
    {
        title: 'Histórico de Consultas',
        href: patientRoutes.historyConsultations().url,
    },
    {
        title: 'Detalhes da Consulta',
        href: patientRoutes.consultationDetails({ appointment: appointment.value.id }).url,
    },
];

const { canAccessPatientRoute } = useRouteGuard();

let echoInstance: any = null;
let clockTimer: ReturnType<typeof setInterval> | null = null;

const connectEcho = () => {
    if (typeof window === 'undefined' || echoInstance) {
        return;
    }

    const reverbConfig = (page.props as any)?.reverb;

    if (!reverbConfig?.key) {
        return;
    }

    const echo = new Echo({
        broadcaster: 'reverb',
        key: reverbConfig.key,
        wsHost: reverbConfig.host,
        wsPort: reverbConfig.port,
        wssPort: reverbConfig.port,
        forceTLS: reverbConfig.scheme === 'https',
        enabledTransports: ['ws', 'wss'],
    });

    echo.private(`appointment.${appointment.value.patient.id}`).listen('.status.changed', (event: any) => {
        if (event?.appointment?.id === appointment.value.id) {
            router.reload({ only: ['appointment'] });
        }
    });

    echoInstance = echo;
};

// Verificar acesso ao montar componente
onMounted(() => {
    canAccessPatientRoute();
    connectEcho();
    clockTimer = setInterval(() => {
        now.value = new Date();
    }, 1000);
});

onUnmounted(() => {
    if (echoInstance) {
        echoInstance.disconnect();
        echoInstance = null;
    }

    if (clockTimer) {
        clearInterval(clockTimer);
        clockTimer = null;
    }
});

const timelineIcon = (event: string): LucideIcon => {
    const map: Record<string, LucideIcon> = {
        created: CalendarPlus,
        started: Video,
        ended: CheckCircle2,
        cancelled: X,
        rescheduled: Calendar,
        no_show: AlertCircle,
    };

    return map[event] ?? Info;
};

const timelineIconClass = (event: string) => {
    const map: Record<string, string> = {
        created: 'border-blue-200 bg-blue-50 text-blue-700',
        started: 'border-rose-200 bg-rose-50 text-rose-700',
        ended: 'border-emerald-200 bg-emerald-50 text-emerald-700',
        cancelled: 'border-slate-200 bg-slate-100 text-slate-600',
        rescheduled: 'border-blue-200 bg-blue-50 text-blue-700',
        no_show: 'border-amber-200 bg-amber-50 text-amber-700',
    };

    return map[event] ?? 'border-slate-200 bg-white text-slate-500';
};

const qrCells = computed(() => {
    const text = appointment.value.access_code || appointment.value.id;
    const cells: Array<{ row: number; col: number }> = [];

    for (let row = 0; row < 21; row += 1) {
        for (let col = 0; col < 21; col += 1) {
            const seed = (row * 7 + col * 11 + text.charCodeAt((row + col) % text.length)) % 7;
            const isMarker = (row < 8 && col < 8) || (row < 8 && col > 12) || (row > 12 && col < 8);

            if (seed < 3 && !isMarker) {
                cells.push({ row, col });
            }
        }
    }

    return cells;
});
</script>

<template>
    <Head title="Detalhes da Consulta" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-full flex-1 bg-slate-50 px-4 py-6 text-slate-950 sm:px-6 lg:px-8">
            <div class="mx-auto w-full max-w-7xl">
                <!-- Mensagem de Sucesso -->
                <Transition
                    enter-active-class="transition ease-out duration-300"
                    enter-from-class="opacity-0 translate-y-2"
                    enter-to-class="opacity-100 translate-y-0"
                    leave-active-class="transition ease-in duration-200"
                    leave-from-class="opacity-100 translate-y-0"
                    leave-to-class="opacity-0 translate-y-2"
                >
                    <div
                        v-if="showSuccessMessage"
                        class="mb-4 flex items-start gap-3 rounded-xl border border-emerald-100 bg-emerald-50 px-4 py-3 text-emerald-800 shadow-sm"
                        role="alert"
                    >
                        <span class="grid size-7 shrink-0 place-items-center rounded-lg bg-white">
                            <CheckCircle2 class="size-4" />
                        </span>
                        <p class="flex-1 text-sm font-medium">{{ successMessage }}</p>
                        <button
                            @click="showSuccessMessage = false"
                            class="grid size-7 shrink-0 place-items-center rounded-lg text-emerald-700 hover:bg-white"
                            aria-label="Fechar aviso"
                        >
                            <X class="size-4" />
                        </button>
                    </div>
                </Transition>

                <section :class="['overflow-hidden rounded-2xl border p-5 shadow-sm sm:p-6 lg:p-7', statusMeta.heroClass]">
                    <div class="flex flex-col gap-6 xl:flex-row xl:items-start xl:justify-between">
                        <div class="min-w-0 flex-1 space-y-5">
                            <div class="flex flex-wrap items-center gap-2">
                                <span
                                    :class="[
                                        'inline-flex h-8 items-center gap-2 rounded-full border px-3 text-xs font-semibold',
                                        statusMeta.pillClass,
                                    ]"
                                >
                                    <span
                                        v-if="appointment.status === 'in_progress'"
                                        class="size-2 rounded-full bg-rose-600 shadow-[0_0_0_5px_rgba(225,29,72,0.12)]"
                                    ></span>
                                    <component :is="statusMeta.icon" v-else class="size-3.5" />
                                    {{ statusMeta.label }}
                                </span>
                                <span
                                    class="inline-flex h-7 items-center gap-1.5 rounded-full border border-slate-200 bg-white px-3 text-xs font-medium text-slate-700"
                                >
                                    <Video v-if="modality === 'online'" class="size-3.5 text-teal-700" />
                                    <Stethoscope v-else class="size-3.5 text-teal-700" />
                                    {{ modalityLabel }}
                                </span>
                                <span
                                    class="inline-flex h-7 items-center rounded-full border border-slate-200 bg-white px-3 font-mono text-[11px] font-medium text-slate-700"
                                >
                                    {{ shortAppointmentId }}
                                </span>
                            </div>

                            <div>
                                <h1 class="text-2xl font-semibold tracking-normal text-slate-950 sm:text-3xl">
                                    <template v-if="canStartCall">Sua consulta está pronta para começar</template>
                                    <template v-else-if="appointment.status === 'in_progress'">Consulta em andamento</template>
                                    <template v-else-if="appointment.status === 'completed'">Consulta concluída</template>
                                    <template v-else-if="appointment.status === 'cancelled'">Consulta cancelada</template>
                                    <template v-else-if="appointment.status === 'no_show'">Você não compareceu</template>
                                    <template v-else>Sua próxima consulta</template>
                                </h1>
                                <p class="mt-1 text-sm font-medium text-slate-500">{{ formattedDate }} · {{ formattedTime }}</p>
                            </div>

                            <div v-if="isFutureConsultation && !canStartCall" class="space-y-2">
                                <p class="text-[11px] font-semibold tracking-wider text-slate-500 uppercase">A consulta começa em</p>
                                <div class="flex flex-wrap items-center gap-2">
                                    <template v-for="(item, index) in countdownItems" :key="item.label">
                                        <div
                                            class="flex min-w-14 flex-col items-center rounded-lg border border-slate-200 bg-white px-3 py-2 shadow-xs"
                                        >
                                            <span class="font-mono text-xl font-semibold text-slate-950">{{ padTime(item.value) }}</span>
                                            <span class="text-[10px] font-semibold tracking-wider text-slate-500 uppercase">{{ item.label }}</span>
                                        </div>
                                        <span v-if="index < countdownItems.length - 1" class="text-lg font-semibold text-slate-300">:</span>
                                    </template>
                                </div>
                            </div>

                            <div v-else-if="canStartCall" class="flex max-w-2xl items-start gap-3 rounded-xl border border-teal-200 bg-white p-4">
                                <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-teal-700 text-white">
                                    <Video class="size-5" />
                                </span>
                                <div>
                                    <p class="font-semibold text-slate-950">A sala já está aberta</p>
                                    <p class="mt-0.5 text-sm text-slate-500">
                                        Entre quando estiver pronto. O médico poderá recebê-lo pela sala segura.
                                    </p>
                                </div>
                            </div>

                            <div
                                v-else-if="appointment.status === 'completed'"
                                class="flex max-w-2xl flex-col gap-3 rounded-xl border border-slate-200 bg-white p-4 sm:flex-row sm:items-center"
                            >
                                <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-amber-50 text-amber-600">
                                    <CheckCircle2 class="size-5" />
                                </span>
                                <div class="flex-1">
                                    <p class="font-semibold text-slate-950">Documentos disponíveis no prontuário</p>
                                    <p class="mt-0.5 text-sm text-slate-500">Resumo, gravação e materiais da consulta ficam reunidos abaixo.</p>
                                </div>
                                <Button
                                    class="bg-teal-700 text-white hover:bg-teal-800"
                                    @click="showTemporaryMessage('Avaliação registrada localmente para demonstração.')"
                                >
                                    Avaliar consulta
                                </Button>
                            </div>

                            <div
                                v-else-if="appointment.status === 'cancelled'"
                                class="max-w-2xl rounded-xl border border-slate-200 bg-white p-4 text-sm text-slate-600"
                            >
                                Esta consulta estava marcada para <span class="font-semibold text-slate-950">{{ formattedDateTime }}</span
                                >.
                            </div>

                            <div
                                v-else-if="appointment.status === 'no_show'"
                                class="max-w-2xl rounded-xl border border-amber-200 bg-white p-4 text-sm text-amber-800"
                            >
                                A tolerância para entrada na sala foi encerrada. Você pode agendar uma nova consulta quando desejar.
                            </div>

                            <div class="flex flex-wrap gap-2">
                                <Button v-if="canStartCall" as-child class="h-12 bg-teal-700 px-5 text-white hover:bg-teal-800">
                                    <Link :href="videoCallHref">
                                        <Video class="size-4" />
                                        Entrar na sala
                                    </Link>
                                </Button>
                                <Button
                                    v-if="canCancelAppointment"
                                    variant="outline"
                                    class="h-12 border-rose-200 bg-white px-5 text-rose-700 hover:bg-rose-50"
                                    :disabled="isCancelling"
                                    @click="openCancelModal"
                                >
                                    <X class="size-4" />
                                    {{ isCancelling ? 'Cancelando...' : 'Cancelar consulta' }}
                                </Button>
                                <Button
                                    v-if="!isFutureConsultation"
                                    as-child
                                    variant="outline"
                                    class="h-12 border-slate-200 bg-white px-5 text-slate-800 hover:bg-slate-100"
                                >
                                    <Link :href="patientRoutes.searchConsultations()">
                                        <CalendarPlus class="size-4" />
                                        Agendar novamente
                                    </Link>
                                </Button>
                            </div>
                        </div>

                        <aside class="w-full shrink-0 rounded-xl border border-slate-200 bg-white/90 p-4 shadow-xs xl:w-80">
                            <div class="flex items-center gap-3">
                                <Avatar class="size-12 border border-slate-200">
                                    <AvatarImage v-if="appointment.doctor.user.avatar" :src="appointment.doctor.user.avatar" :alt="doctorName" />
                                    <AvatarFallback class="bg-slate-100 text-sm font-semibold text-slate-700">{{ doctorInitials }}</AvatarFallback>
                                </Avatar>
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-slate-950">{{ doctorName }}</p>
                                    <p class="truncate text-xs text-slate-500">{{ primarySpecialization }}</p>
                                    <p class="mt-0.5 text-xs text-slate-500">CRM {{ appointment.doctor.crm }}</p>
                                </div>
                            </div>
                            <div class="mt-4 grid grid-cols-2 gap-2 text-sm">
                                <div class="rounded-lg bg-slate-50 p-3">
                                    <p class="text-[11px] font-semibold tracking-wider text-slate-500 uppercase">Paciente</p>
                                    <p class="mt-1 truncate font-medium text-slate-950">{{ appointment.patient.user.name }}</p>
                                </div>
                                <div class="rounded-lg bg-slate-50 p-3">
                                    <p class="text-[11px] font-semibold tracking-wider text-slate-500 uppercase">Duração</p>
                                    <p class="mt-1 font-medium text-slate-950">{{ duration }}</p>
                                </div>
                            </div>
                        </aside>
                    </div>
                </section>

                <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-[minmax(0,2fr)_minmax(320px,1fr)]">
                    <main class="space-y-5">
                        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                            <p class="text-[11px] font-semibold tracking-wider text-slate-500 uppercase">Informações gerais</p>
                            <div class="mt-5 grid grid-cols-1 gap-x-8 gap-y-5 sm:grid-cols-2">
                                <div class="space-y-1">
                                    <p class="text-[11px] font-semibold tracking-wider text-slate-500 uppercase">Médico</p>
                                    <p class="font-medium text-slate-950">
                                        {{ doctorName }} <span class="text-slate-400">·</span>
                                        <span class="text-slate-600">{{ primarySpecialization }}</span>
                                    </p>
                                </div>
                                <div class="space-y-1">
                                    <p class="text-[11px] font-semibold tracking-wider text-slate-500 uppercase">CRM</p>
                                    <p class="font-medium text-slate-950">{{ appointment.doctor.crm }}</p>
                                </div>
                                <div class="space-y-1">
                                    <p class="text-[11px] font-semibold tracking-wider text-slate-500 uppercase">Data e horário</p>
                                    <p class="font-medium text-slate-950">{{ formattedDateTime }}</p>
                                </div>
                                <div class="space-y-1">
                                    <p class="text-[11px] font-semibold tracking-wider text-slate-500 uppercase">Duração</p>
                                    <p class="font-medium text-slate-950">{{ duration }}</p>
                                </div>
                                <div class="space-y-1">
                                    <p class="text-[11px] font-semibold tracking-wider text-slate-500 uppercase">Modalidade</p>
                                    <p class="inline-flex items-center gap-2 font-medium text-slate-950">
                                        <Video v-if="modality === 'online'" class="size-4 text-teal-700" />
                                        <Stethoscope v-else class="size-4 text-teal-700" />
                                        {{ modalityLabel }}
                                    </p>
                                </div>
                                <div class="space-y-1">
                                    <p class="text-[11px] font-semibold tracking-wider text-slate-500 uppercase">Protocolo</p>
                                    <p class="font-mono font-medium text-slate-950">{{ shortAppointmentId }}</p>
                                </div>
                            </div>

                            <div
                                v-if="modality === 'online' && (isFutureConsultation || appointment.status === 'in_progress')"
                                class="mt-6 border-t border-slate-200 pt-5"
                            >
                                <p class="text-[11px] font-semibold tracking-wider text-slate-500 uppercase">Código de acesso da sala</p>
                                <div class="mt-3 flex flex-col gap-4 rounded-xl border border-slate-200 bg-slate-50 p-4 sm:flex-row sm:items-center">
                                    <div class="grid size-20 shrink-0 place-items-center rounded-xl border border-slate-200 bg-white">
                                        <svg width="64" height="64" viewBox="0 0 21 21" aria-hidden="true">
                                            <g
                                                v-for="marker in [
                                                    { x: 0, y: 0 },
                                                    { x: 14, y: 0 },
                                                    { x: 0, y: 14 },
                                                ]"
                                                :key="`${marker.x}-${marker.y}`"
                                            >
                                                <rect
                                                    :x="marker.x"
                                                    :y="marker.y"
                                                    width="7"
                                                    height="7"
                                                    fill="none"
                                                    stroke="#0f172a"
                                                    stroke-width="0.8"
                                                />
                                                <rect :x="marker.x + 2" :y="marker.y + 2" width="3" height="3" fill="#0f172a" />
                                            </g>
                                            <rect
                                                v-for="cell in qrCells"
                                                :key="`${cell.row}-${cell.col}`"
                                                :x="cell.col"
                                                :y="cell.row"
                                                width="1"
                                                height="1"
                                                fill="#0f172a"
                                            />
                                        </svg>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="font-mono text-2xl font-semibold tracking-widest break-all text-slate-950">
                                            {{ appointment.access_code }}
                                        </p>
                                        <p class="mt-1 text-sm text-slate-500">Use este código se precisar abrir a sala em outro dispositivo.</p>
                                    </div>
                                    <Button variant="outline" class="border-slate-200 bg-white" @click="copyAccessCode">
                                        <Copy class="size-4" />
                                        Copiar
                                    </Button>
                                </div>
                            </div>
                        </section>

                        <section
                            v-if="isFutureConsultation && modality === 'online'"
                            class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6"
                        >
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <p class="text-[11px] font-semibold tracking-wider text-slate-500 uppercase">Verificação técnica</p>
                                <span
                                    class="inline-flex h-6 items-center gap-1.5 rounded-full border border-emerald-200 bg-emerald-50 px-2.5 text-xs font-medium text-emerald-700"
                                >
                                    <Check class="size-3" />
                                    Tudo certo
                                </span>
                            </div>
                            <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
                                <div
                                    v-for="item in ['Câmera', 'Microfone', 'Internet', 'Navegador']"
                                    :key="item"
                                    class="flex items-center gap-3 rounded-xl border border-slate-200 bg-white p-3"
                                >
                                    <span class="grid size-6 shrink-0 place-items-center rounded-full bg-emerald-50 text-emerald-700">
                                        <Check class="size-3.5" />
                                    </span>
                                    <div>
                                        <p class="text-sm font-medium text-slate-950">{{ item }}</p>
                                        <p class="text-xs text-slate-500">{{ item === 'Internet' ? 'Estável' : 'Compatível' }}</p>
                                    </div>
                                </div>
                            </div>
                            <p class="mt-4 inline-flex items-center gap-2 text-sm text-slate-500">
                                <Wifi class="size-4" />
                                Recomendamos uma conexão estável para vídeo em alta qualidade.
                            </p>
                        </section>

                        <section
                            v-if="isCompletedConsultation && appointment.video_recording_url"
                            class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6"
                        >
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <p class="text-[11px] font-semibold tracking-wider text-slate-500 uppercase">Gravação da consulta</p>
                                <span
                                    class="inline-flex h-6 items-center gap-1.5 rounded-full border border-slate-200 bg-white px-2.5 text-xs font-medium text-slate-600"
                                >
                                    <ShieldCheck class="size-3" />
                                    Privado
                                </span>
                            </div>
                            <a
                                :href="appointment.video_recording_url"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="mt-4 grid aspect-video place-items-center overflow-hidden rounded-xl bg-[linear-gradient(135deg,#0f172a_0%,#1e293b_100%)] text-white"
                            >
                                <span class="grid size-16 place-items-center rounded-full border border-white/30 bg-white/20">
                                    <Play class="ml-1 size-7 fill-white" />
                                </span>
                            </a>
                        </section>

                        <section v-if="isCompletedConsultation" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div>
                                    <p class="text-[11px] font-semibold tracking-wider text-slate-500 uppercase">Documentos da consulta</p>
                                    <p class="mt-1 text-sm text-slate-500">Materiais emitidos permanecem acessíveis pelo prontuário.</p>
                                </div>
                                <Button as-child variant="ghost" class="text-slate-800">
                                    <Link :href="patientRoutes.medicalRecords()">
                                        Ver prontuário
                                        <ArrowRight class="size-4" />
                                    </Link>
                                </Button>
                            </div>
                            <div class="mt-4 space-y-2">
                                <div
                                    v-for="doc in [
                                        {
                                            title: 'Receita médica',
                                            kind: 'Prescrição',
                                            subtitle: 'PDF salvo no prontuário',
                                            color: 'text-violet-700 bg-violet-50',
                                        },
                                        {
                                            title: 'Pedido de exames',
                                            kind: 'Laboratório',
                                            subtitle: 'Solicitações e orientações',
                                            color: 'text-blue-700 bg-blue-50',
                                        },
                                        {
                                            title: 'Atestado de comparecimento',
                                            kind: 'Documento',
                                            subtitle: 'Comprovante da consulta',
                                            color: 'text-emerald-700 bg-emerald-50',
                                        },
                                    ]"
                                    :key="doc.title"
                                    class="flex items-center gap-3 rounded-xl border border-slate-200 p-3"
                                >
                                    <span :class="['grid size-10 shrink-0 place-items-center rounded-xl', doc.color]">
                                        <FileText class="size-5" />
                                    </span>
                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <p class="font-semibold text-slate-950">{{ doc.title }}</p>
                                            <span class="rounded-full border border-slate-200 px-2 py-0.5 text-[11px] font-medium text-slate-500">{{
                                                doc.kind
                                            }}</span>
                                        </div>
                                        <p class="text-sm text-slate-500">{{ doc.subtitle }}</p>
                                    </div>
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        class="border-slate-200 bg-white"
                                        @click="showTemporaryMessage('Documento demonstrativo. Integração futura.')"
                                    >
                                        <ExternalLink class="size-3.5" />
                                        Abrir
                                    </Button>
                                </div>
                            </div>
                        </section>

                        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                                <p class="text-[11px] font-semibold tracking-wider text-slate-500 uppercase">
                                    {{ isCompletedConsultation ? 'Resumo clínico' : 'Observações' }}
                                </p>
                                <span
                                    v-if="isCompletedConsultation"
                                    class="inline-flex h-6 items-center gap-1.5 rounded-full border border-teal-200 bg-teal-50 px-2.5 text-xs font-medium text-teal-800"
                                >
                                    <ShieldCheck class="size-3" />
                                    Confidencial
                                </span>
                            </div>
                            <p v-if="appointment.notes" class="text-sm leading-6 text-slate-700">{{ appointment.notes }}</p>
                            <p v-else class="text-sm leading-6 text-slate-500">Nenhuma observação registrada.</p>
                        </section>
                    </main>

                    <aside class="space-y-5 lg:sticky lg:top-20 lg:self-start">
                        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                            <div class="mb-5 flex items-center justify-between gap-3">
                                <p class="text-[11px] font-semibold tracking-wider text-slate-500 uppercase">Linha do tempo</p>
                                <span class="rounded-full border border-slate-200 px-2 py-0.5 text-[11px] font-medium text-slate-500">
                                    {{ timeline.length }} evento{{ timeline.length !== 1 ? 's' : '' }}
                                </span>
                            </div>
                            <ol
                                v-if="timeline.length > 0"
                                class="relative space-y-5 pl-8 before:absolute before:top-2 before:bottom-2 before:left-3 before:w-px before:bg-slate-200"
                            >
                                <li v-for="item in timeline" :key="`${item.rawEvent}-${item.dateTime}`" class="relative">
                                    <span
                                        :class="[
                                            'absolute top-0 -left-8 grid size-6 place-items-center rounded-full border bg-white',
                                            timelineIconClass(item.rawEvent),
                                        ]"
                                    >
                                        <component :is="timelineIcon(item.rawEvent)" class="size-3.5" />
                                    </span>
                                    <p class="text-sm font-semibold text-slate-950">{{ item.event }}</p>
                                    <p class="mt-1 text-sm leading-5 text-slate-500">{{ item.description }}</p>
                                    <p class="mt-2 text-xs text-slate-400">{{ item.dateTime }} · {{ item.actor }}</p>
                                </li>
                            </ol>
                            <p v-else class="text-sm text-slate-500">Nenhum evento registrado ainda.</p>
                        </section>

                        <section v-if="isFutureConsultation" class="rounded-2xl border border-slate-200 bg-slate-100/70 p-5">
                            <div class="flex items-start gap-3">
                                <span class="grid size-8 shrink-0 place-items-center rounded-lg bg-white text-teal-700">
                                    <ShieldCheck class="size-4" />
                                </span>
                                <div>
                                    <p class="text-sm font-semibold text-slate-950">Política de cancelamento</p>
                                    <p class="mt-1 text-sm leading-5 text-slate-500">
                                        Cancele gratuitamente até 6h antes. Após esse prazo, podem existir regras específicas de reembolso.
                                    </p>
                                </div>
                            </div>
                        </section>

                        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                            <p class="mb-3 text-[11px] font-semibold tracking-wider text-slate-500 uppercase">Ações</p>
                            <div class="space-y-1">
                                <button
                                    v-if="isFutureConsultation"
                                    class="flex w-full items-center gap-3 rounded-lg px-2 py-2.5 text-left text-sm font-medium text-slate-800 hover:bg-slate-50"
                                    @click="showTemporaryMessage('Lembrete de calendário preparado localmente.')"
                                >
                                    <CalendarPlus class="size-4 text-slate-500" />
                                    <span class="flex-1">Adicionar ao calendário</span>
                                    <ChevronRight class="size-4 text-slate-400" />
                                </button>
                                <button
                                    class="flex w-full items-center gap-3 rounded-lg px-2 py-2.5 text-left text-sm font-medium text-slate-800 hover:bg-slate-50"
                                    @click="showTemporaryMessage('Mensagens com o médico serão abertas por integração futura.')"
                                >
                                    <MessageSquare class="size-4 text-slate-500" />
                                    <span class="flex-1">Enviar mensagem ao médico</span>
                                    <ChevronRight class="size-4 text-slate-400" />
                                </button>
                                <Link
                                    :href="doctorProfileHref"
                                    class="flex w-full items-center gap-3 rounded-lg px-2 py-2.5 text-sm font-medium text-slate-800 hover:bg-slate-50"
                                >
                                    <ExternalLink class="size-4 text-slate-500" />
                                    <span class="flex-1">Ver perfil do médico</span>
                                    <ChevronRight class="size-4 text-slate-400" />
                                </Link>
                                <Link
                                    :href="patientRoutes.historyConsultations()"
                                    class="flex w-full items-center gap-3 rounded-lg px-2 py-2.5 text-sm font-medium text-slate-800 hover:bg-slate-50"
                                >
                                    <Clock class="size-4 text-slate-500" />
                                    <span class="flex-1">Histórico de consultas</span>
                                    <ChevronRight class="size-4 text-slate-400" />
                                </Link>
                                <Link
                                    :href="patientRoutes.searchConsultations()"
                                    class="flex w-full items-center gap-3 rounded-lg px-2 py-2.5 text-sm font-medium text-slate-800 hover:bg-slate-50"
                                >
                                    <CalendarPlus class="size-4 text-slate-500" />
                                    <span class="flex-1">Agendar nova consulta</span>
                                    <ChevronRight class="size-4 text-slate-400" />
                                </Link>
                            </div>
                        </section>
                    </aside>
                </div>
            </div>
        </div>

        <!-- Modal de Cancelamento -->
        <CancelAppointmentModal
            :is-open="showCancelModal"
            :appointment-date="appointment.scheduled_at"
            :appointment-time="formattedTime"
            :is-submitting="isCancelling"
            @close="closeCancelModal"
            @confirm="cancelAppointment"
        />
    </AppLayout>
</template>
