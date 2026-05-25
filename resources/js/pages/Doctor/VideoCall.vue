<script setup lang="ts">
import VideoControls from '@/components/VideoControls.vue';
import VideoGrid from '@/components/VideoGrid.vue';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { useRouteGuard } from '@/composables/auth';
import { useInitials } from '@/composables/useInitials';
import { useVideoCall } from '@/composables/useVideoCall';
import AppLayout from '@/layouts/AppLayout.vue';
import * as doctorRoutes from '@/routes/doctor';
import { useVideoCallStore } from '@/stores/videoCall';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { AlertTriangle, Calendar, Check, Clock, Loader2, MonitorUp, Phone, PhoneOff, RefreshCw, ShieldCheck, Video, VideoOff } from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';

interface ActiveCall {
    id: string;
    status: string;
    call_type?: 'scheduled' | 'ad_hoc';
}

interface AppointmentProp {
    id: string;
    scheduled_at: string;
    formatted_date: string;
    formatted_time: string;
    status: string;
    can_start_call: boolean;
    time_window_message: string;
    active_call: ActiveCall | null;
    patient: {
        id: number;
        name: string;
    };
}

type CtaMode = 'join' | 'join-scheduled' | 'ringing' | 'in-call' | 'ended' | 'waiting' | 'disabled-window';
type StatusTone = 'live' | 'go' | 'wait' | 'warn' | 'muted';

const { canAccessDoctorRoute } = useRouteGuard();
const { getInitials } = useInitials();
const page = usePage();
const store = useVideoCallStore();

const { callState, currentCall, isLoading, sfu, joinActiveCall, acceptCall, rejectCall, endCall } = useVideoCall();

const appointments = (page.props.appointments as AppointmentProp[]) ?? [];
const selectedAppointment = ref<AppointmentProp | null>(appointments[0] ?? null);
const isMobileDetail = ref(false);
const isEndingCall = ref(false);
const videoRoomRef = ref<HTMLElement | null>(null);

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: doctorRoutes.dashboard().url },
    { title: 'Videoconferência', href: doctorRoutes.videoCall().url },
];

onMounted(() => {
    canAccessDoctorRoute();
});

const isInCall = computed(() => callState.value === 'accepted');

const selectAppointment = (appointment: AppointmentProp) => {
    selectedAppointment.value = appointment;
    isMobileDetail.value = true;
};

const appointmentCtaMode = (appointment: AppointmentProp): CtaMode => {
    const isSelected = selectedAppointment.value?.id === appointment.id;
    if (isSelected && callState.value === 'accepted') return 'in-call';
    if (isSelected && callState.value === 'ended') return 'ended';

    // Ad-hoc entrante: médico recebe solicitação
    if (isSelected && callState.value === 'ringing' && store.callType === 'ad_hoc') return 'ringing';

    // Scheduled: sala provisionada pelo sistema, entrar diretamente
    if (
        appointment.active_call?.call_type === 'scheduled' ||
        (store.isActive && store.callType === 'scheduled' && store.appointmentId === appointment.id)
    ) {
        return 'join-scheduled';
    }

    // Ad-hoc ativa: entrar
    if (appointment.active_call || (store.isActive && store.appointmentId === appointment.id)) return 'join';

    if (appointment.can_start_call) return 'waiting';

    return 'disabled-window';
};

const selectedCtaMode = computed<CtaMode>(() => {
    if (!selectedAppointment.value) return 'disabled-window';
    return appointmentCtaMode(selectedAppointment.value);
});

const handleJoinCall = async () => {
    if (!selectedAppointment.value || isLoading.value) return;

    // Scheduled: conectar diretamente via token já no store (de /calls/active)
    if (selectedCtaMode.value === 'join-scheduled') {
        await joinActiveCall();
        return;
    }

    // Ad-hoc ringing: aceitar chamada
    if (selectedCtaMode.value === 'ringing' && store.callId) {
        await acceptCall(store.callId);
        return;
    }

    // Ad-hoc já aceita com call ativa no appointment
    const appointment = selectedAppointment.value;
    const callId = store.isActive && store.appointmentId === appointment.id ? store.callId : appointment.active_call?.id;
    if (!callId) return;
    await acceptCall(callId);
};

const handleEndCall = async () => {
    if (!currentCall.value || isEndingCall.value) return;
    isEndingCall.value = true;
    await endCall(currentCall.value.callId);
    isEndingCall.value = false;
};

const handleFullscreen = () => {
    videoRoomRef.value?.requestFullscreen?.();
};

const getStatusLabel = (status: string): string => {
    const labels: Record<string, string> = {
        scheduled: 'Agendado',
        rescheduled: 'Reagendado',
        in_progress: 'Em andamento',
        completed: 'Finalizado',
        cancelled: 'Cancelado',
        no_show: 'Não compareceu',
    };
    return labels[status] || status;
};

const statusTone = (appointment: AppointmentProp): StatusTone => {
    if (appointment.status === 'in_progress') return 'live';
    if (appointment.active_call || (store.isActive && store.appointmentId === appointment.id)) return 'go';
    if (['completed', 'cancelled'].includes(appointment.status)) return 'muted';
    if (appointment.status === 'no_show' || appointment.time_window_message?.includes('expirada')) return 'warn';
    if (appointment.can_start_call) return 'go';
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

const callStatusLabel = computed(() => {
    if (callState.value === 'ringing') return 'Paciente aguardando...';
    if (callState.value === 'accepted') return 'Em chamada';
    if (callState.value === 'ended') return 'Chamada encerrada';
    if (callState.value === 'rejected') return 'Chamada recusada';
    if (callState.value === 'error') return 'Erro na chamada';
    return 'Disponível';
});

const ctaLabel = computed(() => {
    if (isLoading.value) return 'Entrando...';
    if (selectedCtaMode.value === 'ringing') return 'Atender chamada';
    if (selectedCtaMode.value === 'join-scheduled') return 'Entrar na consulta';
    if (selectedCtaMode.value === 'join') return 'Entrar na chamada';
    if (selectedCtaMode.value === 'in-call') return 'Chamada em andamento';
    if (selectedCtaMode.value === 'ended') return 'Chamada finalizada';
    if (selectedCtaMode.value === 'waiting') return 'Aguardando o horário';
    return 'Fora da janela de tempo';
});
</script>

<template>
    <Head title="Videoconferência" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div v-if="isInCall" ref="videoRoomRef" class="flex min-h-0 flex-1 flex-col bg-[#0b2030]">
            <VideoGrid
                :local-stream="sfu.localStream.value"
                :remote-streams="sfu.remoteStreams.value"
                :is-mic-enabled="sfu.isMicEnabled.value"
                :is-camera-enabled="sfu.isCameraEnabled.value"
                class="min-h-0 flex-1"
            />
            <VideoControls
                :is-mic-enabled="sfu.isMicEnabled.value"
                :is-camera-enabled="sfu.isCameraEnabled.value"
                :is-ending="isEndingCall"
                @toggle-mic="sfu.toggleMic()"
                @toggle-camera="sfu.toggleCamera()"
                @end="handleEndCall"
                @fullscreen="handleFullscreen"
            />
        </div>

        <div v-else class="flex min-h-0 flex-1 bg-[#f4f6f8] p-0 text-gray-950">
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
                                Consultas na janela de tempo. Aguarde o início automático ou entre na chamada ativa.
                            </p>
                        </div>
                        <div class="grid grid-cols-2 gap-2 sm:flex">
                            <div class="rounded-lg border border-[#dde5ea] bg-[#f4f6f8] px-4 py-2">
                                <p class="text-[11px] font-black text-gray-500 uppercase">Consultas</p>
                                <p class="text-xl font-black text-gray-950">{{ appointments.length }}</p>
                            </div>
                            <div class="rounded-lg border border-[#dde5ea] bg-[#e5f1f2] px-4 py-2">
                                <p class="text-[11px] font-black text-gray-500 uppercase">Janela</p>
                                <p class="text-xl font-black text-gray-950">10 min</p>
                            </div>
                            <Button as-child class="col-span-2 h-11 bg-[#0f6e78] font-black text-white hover:bg-[#0a4f57] sm:col-span-1">
                                <Link :href="doctorRoutes.appointments().url">Minha agenda</Link>
                            </Button>
                        </div>
                    </div>
                </header>

                <div class="grid min-h-0 flex-1 lg:grid-cols-[380px_minmax(0,1fr)]">
                    <aside class="min-h-0 flex-col border-r border-[#dde5ea] bg-white" :class="isMobileDetail ? 'hidden lg:flex' : 'flex'">
                        <div class="border-b border-[#dde5ea] p-4">
                            <h2 class="text-lg font-black text-gray-950">Pacientes na janela</h2>
                            <p class="mt-1 text-sm font-semibold text-gray-500">Consultas ativas no momento.</p>
                        </div>
                        <div class="min-h-0 flex-1 overflow-y-auto p-3">
                            <div v-if="appointments.length === 0" class="flex h-full flex-col items-center justify-center px-6 text-center">
                                <VideoOff class="h-12 w-12 text-gray-300" />
                                <h3 class="mt-4 text-lg font-black text-gray-950">Nenhuma consulta na janela</h3>
                                <p class="mt-2 text-sm font-medium text-gray-500">
                                    Consultas aparecem aqui 10 minutos antes e depois do horário agendado.
                                </p>
                            </div>

                            <button
                                v-for="appointment in appointments"
                                v-else
                                :key="appointment.id"
                                type="button"
                                class="mb-2 flex w-full items-center gap-3 rounded-lg border p-3 text-left transition hover:border-[#0f6e78]/30 hover:bg-[#f4f6f8]"
                                :class="selectedAppointment?.id === appointment.id ? 'border-[#0f6e78] bg-[#e5f1f2]' : 'border-[#e6ebee] bg-white'"
                                @click="selectAppointment(appointment)"
                            >
                                <Avatar class="h-12 w-12 border border-[#dde5ea]">
                                    <AvatarFallback class="bg-[#e5f1f2] text-sm font-black text-[#0f6e78]">
                                        {{ getInitials(appointment.patient.name) }}
                                    </AvatarFallback>
                                </Avatar>
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center justify-between gap-2">
                                        <h3 class="truncate text-sm font-black text-gray-950">{{ appointment.patient.name }}</h3>
                                        <Check v-if="selectedAppointment?.id === appointment.id" class="h-4 w-4 shrink-0 text-[#0f6e78]" />
                                    </div>
                                    <p class="mt-0.5 truncate text-xs font-semibold text-gray-500">
                                        {{ appointment.formatted_date }} às {{ appointment.formatted_time }}
                                    </p>
                                    <span
                                        class="mt-2 inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-black"
                                        :class="statusClasses(statusTone(appointment))"
                                    >
                                        <span class="h-1.5 w-1.5 rounded-full" :class="statusDotClasses(statusTone(appointment))" />
                                        {{ appointment.time_window_message || getStatusLabel(appointment.status) }}
                                    </span>
                                </div>
                            </button>
                        </div>
                    </aside>

                    <main class="min-h-0 flex-col bg-[#f4f6f8]" :class="selectedAppointment ? 'flex' : 'hidden lg:flex'">
                        <div v-if="!selectedAppointment" class="flex flex-1 items-center justify-center px-6 text-center">
                            <div class="max-w-md">
                                <div class="mx-auto grid h-20 w-20 place-items-center rounded-2xl bg-[#e5f1f2] text-[#0f6e78]">
                                    <Video class="h-10 w-10" />
                                </div>
                                <h2 class="mt-5 text-2xl font-black text-gray-950">Selecione uma consulta</h2>
                                <p class="mt-2 text-sm font-semibold text-gray-600">
                                    Escolha um paciente da lista para ver o status e entrar na chamada.
                                </p>
                            </div>
                        </div>

                        <template v-else>
                            <div class="flex items-center gap-3 border-b border-[#dde5ea] bg-white px-4 py-3 lg:hidden">
                                <Button variant="outline" class="h-9 border-[#dde5ea] px-3 font-extrabold" @click="isMobileDetail = false">
                                    Voltar
                                </Button>
                                <p class="truncate text-sm font-black text-gray-950">{{ selectedAppointment.patient.name }}</p>
                            </div>

                            <div class="min-h-0 flex-1 overflow-y-auto p-4 lg:p-5">
                                <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_360px]">
                                    <section class="space-y-5">
                                        <div class="rounded-lg border border-[#dde5ea] bg-white p-5 shadow-sm">
                                            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                                <div class="flex gap-4">
                                                    <Avatar class="h-16 w-16 border border-[#dde5ea]">
                                                        <AvatarFallback class="bg-[#e5f1f2] text-lg font-black text-[#0f6e78]">
                                                            {{ getInitials(selectedAppointment.patient.name) }}
                                                        </AvatarFallback>
                                                    </Avatar>
                                                    <div>
                                                        <p class="text-xs font-black text-gray-500 uppercase">Paciente</p>
                                                        <h2 class="mt-1 text-2xl font-black text-gray-950">
                                                            {{ selectedAppointment.patient.name }}
                                                        </h2>
                                                        <span
                                                            class="mt-3 inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-black"
                                                            :class="statusClasses(statusTone(selectedAppointment))"
                                                        >
                                                            <span
                                                                class="h-2 w-2 rounded-full"
                                                                :class="statusDotClasses(statusTone(selectedAppointment))"
                                                            />
                                                            {{
                                                                selectedAppointment.time_window_message || getStatusLabel(selectedAppointment.status)
                                                            }}
                                                        </span>
                                                    </div>
                                                </div>
                                                <div class="rounded-lg border border-[#dde5ea] bg-[#f4f6f8] px-4 py-3">
                                                    <p class="text-[11px] font-black text-gray-500 uppercase">Status da chamada</p>
                                                    <p class="mt-1 flex items-center gap-2 text-sm font-black text-gray-700">
                                                        <Loader2 v-if="callState === 'ringing'" class="h-4 w-4 animate-spin" />
                                                        <RefreshCw v-else-if="callState === 'accepted'" class="h-4 w-4 text-emerald-600" />
                                                        <Video v-else class="h-4 w-4" />
                                                        {{ callStatusLabel }}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="rounded-lg border border-[#dde5ea] bg-white p-5 shadow-sm">
                                            <div class="mb-4 flex items-center gap-2">
                                                <Calendar class="h-5 w-5 text-[#0f6e78]" />
                                                <h2 class="text-lg font-black text-gray-950">Agendamento</h2>
                                            </div>

                                            <div class="grid gap-3 sm:grid-cols-3">
                                                <div class="rounded-lg border border-[#e6ebee] bg-[#f7f8f9] p-4">
                                                    <p class="text-[11px] font-black text-gray-500 uppercase">Data</p>
                                                    <p class="mt-1 text-lg font-black text-gray-950">
                                                        {{ selectedAppointment.formatted_date }}
                                                    </p>
                                                </div>
                                                <div class="rounded-lg border border-[#e6ebee] bg-[#f7f8f9] p-4">
                                                    <p class="text-[11px] font-black text-gray-500 uppercase">Horário</p>
                                                    <p class="mt-1 text-lg font-black text-gray-950">
                                                        {{ selectedAppointment.formatted_time }}
                                                    </p>
                                                </div>
                                                <div class="rounded-lg border border-[#e6ebee] bg-[#f7f8f9] p-4">
                                                    <p class="text-[11px] font-black text-gray-500 uppercase">Situação</p>
                                                    <p class="mt-1 text-lg font-black text-gray-950">
                                                        {{ getStatusLabel(selectedAppointment.status) }}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Modal inline ad-hoc: chamada entrante -->
                                        <div
                                            v-if="callState === 'ringing' && store.callType === 'ad_hoc'"
                                            class="rounded-lg border border-emerald-100 bg-emerald-50 p-4"
                                        >
                                            <div class="flex items-center justify-between gap-3">
                                                <div class="flex items-center gap-3">
                                                    <Phone class="h-5 w-5 shrink-0 text-emerald-700" />
                                                    <div>
                                                        <h3 class="font-black text-emerald-900">Chamada recebida</h3>
                                                        <p class="mt-1 text-sm font-semibold text-emerald-800">Paciente quer falar com você agora.</p>
                                                    </div>
                                                </div>
                                                <div class="flex gap-2">
                                                    <Button
                                                        class="bg-emerald-600 font-black text-white hover:bg-emerald-500"
                                                        :disabled="isLoading"
                                                        @click="store.callId && acceptCall(store.callId)"
                                                    >
                                                        <Phone class="mr-1.5 h-4 w-4" />
                                                        Atender
                                                    </Button>
                                                    <Button
                                                        variant="outline"
                                                        class="font-black text-red-700 hover:bg-red-50"
                                                        @click="store.callId && rejectCall(store.callId)"
                                                    >
                                                        <PhoneOff class="mr-1.5 h-4 w-4" />
                                                        Recusar
                                                    </Button>
                                                </div>
                                            </div>
                                        </div>

                                        <div v-if="callState === 'rejected'" class="rounded-lg border border-orange-100 bg-orange-50 p-4">
                                            <div class="flex items-center gap-3">
                                                <AlertTriangle class="h-5 w-5 shrink-0 text-orange-700" />
                                                <div>
                                                    <h3 class="font-black text-orange-900">Paciente recusou a chamada</h3>
                                                    <p class="mt-1 text-sm font-semibold text-orange-800">
                                                        O paciente rejeitou a chamada. Aguarde ou entre em contato.
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </section>

                                    <aside class="space-y-4">
                                        <div class="rounded-lg border border-[#dde5ea] bg-white p-5 shadow-sm">
                                            <div class="grid aspect-video place-items-center rounded-lg bg-[#0b2030] text-white">
                                                <div v-if="selectedCtaMode === 'in-call'" class="text-center">
                                                    <RefreshCw class="mx-auto h-10 w-10 animate-spin text-[#40e0d0]" />
                                                    <p class="mt-3 text-sm font-black">Em chamada</p>
                                                </div>
                                                <div v-else-if="selectedCtaMode === 'ringing'" class="text-center">
                                                    <Phone class="mx-auto h-10 w-10 animate-pulse text-[#40e0d0]" />
                                                    <p class="mt-3 text-sm font-black">Chamada recebida</p>
                                                    <p class="mt-1 px-6 text-xs font-semibold text-white/60">Paciente aguarda seu atendimento.</p>
                                                </div>
                                                <div
                                                    v-else-if="selectedCtaMode === 'join-scheduled' || selectedCtaMode === 'join'"
                                                    class="text-center"
                                                >
                                                    <Phone class="mx-auto h-10 w-10 animate-pulse text-[#40e0d0]" />
                                                    <p class="mt-3 text-sm font-black">Consulta disponível</p>
                                                </div>
                                                <div v-else-if="selectedCtaMode === 'ended'" class="text-center">
                                                    <PhoneOff class="mx-auto h-10 w-10 text-gray-400" />
                                                    <p class="mt-3 text-sm font-black">Chamada encerrada</p>
                                                </div>
                                                <div v-else-if="selectedCtaMode === 'waiting'" class="text-center">
                                                    <Clock class="mx-auto h-10 w-10 text-[#40e0d0]/70" />
                                                    <p class="mt-3 text-sm font-black">Aguardando o horário</p>
                                                    <p class="mt-1 px-6 text-xs font-semibold text-white/60">A chamada é iniciada automaticamente.</p>
                                                </div>
                                                <div v-else class="text-center">
                                                    <Clock class="mx-auto h-10 w-10 text-gray-400" />
                                                    <p class="mt-3 text-sm font-black">Fora da janela</p>
                                                    <p class="mt-1 px-6 text-xs font-semibold text-white/60">
                                                        Disponível 10 min antes/depois do horário.
                                                    </p>
                                                </div>
                                            </div>

                                            <button
                                                type="button"
                                                class="mt-4 flex h-12 w-full items-center justify-center gap-2 rounded-lg border-none px-4 text-sm font-black disabled:cursor-not-allowed"
                                                :class="
                                                    ['join', 'join-scheduled', 'ringing'].includes(selectedCtaMode)
                                                        ? 'bg-teal-500 text-gray-950 hover:bg-teal-400'
                                                        : selectedCtaMode === 'in-call'
                                                          ? 'bg-emerald-100 text-emerald-800'
                                                          : 'cursor-not-allowed bg-gray-200 text-gray-500'
                                                "
                                                :disabled="!['join', 'join-scheduled', 'ringing'].includes(selectedCtaMode) || isLoading"
                                                @click="handleJoinCall"
                                            >
                                                <Loader2 v-if="isLoading" class="h-4 w-4 animate-spin" />
                                                <Phone v-else-if="['join', 'join-scheduled', 'ringing'].includes(selectedCtaMode)" class="h-4 w-4" />
                                                <Video v-else-if="selectedCtaMode === 'in-call'" class="h-4 w-4" />
                                                <PhoneOff v-else-if="selectedCtaMode === 'ended'" class="h-4 w-4" />
                                                <Clock v-else class="h-4 w-4" />
                                                {{ ctaLabel }}
                                            </button>
                                            <p v-if="selectedCtaMode === 'waiting'" class="mt-2 text-center text-xs font-semibold text-gray-500">
                                                A chamada é iniciada automaticamente pelo sistema.
                                            </p>
                                        </div>

                                        <div class="rounded-lg border border-[#dde5ea] bg-white p-5 shadow-sm">
                                            <h2 class="flex items-center gap-2 text-base font-black text-gray-950">
                                                <MonitorUp class="h-4 w-4 text-[#0f6e78]" />
                                                Dicas para a teleconsulta
                                            </h2>
                                            <ul class="mt-3 space-y-3 text-sm font-semibold text-gray-600">
                                                <li class="flex gap-2">
                                                    <span class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-[#0f6e78]" />
                                                    A chamada é iniciada automaticamente na janela de 10 minutos.
                                                </li>
                                                <li class="flex gap-2">
                                                    <span class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-[#0f6e78]" />
                                                    Permita câmera e microfone no navegador antes de entrar.
                                                </li>
                                                <li class="flex gap-2">
                                                    <span class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-[#0f6e78]" />
                                                    Use um local reservado e bem iluminado para a consulta.
                                                </li>
                                                <li class="flex gap-2">
                                                    <span class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-[#0f6e78]" />
                                                    Tenha o prontuário do paciente aberto em outra aba se necessário.
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
