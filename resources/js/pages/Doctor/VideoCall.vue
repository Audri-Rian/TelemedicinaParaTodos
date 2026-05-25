<script setup lang="ts">
import VideoControls from '@/components/VideoControls.vue';
import VideoGrid from '@/components/VideoGrid.vue';
import { useRouteGuard } from '@/composables/auth';
import { useVideoCall } from '@/composables/useVideoCall';
import AppLayout from '@/layouts/AppLayout.vue';
import * as doctorRoutes from '@/routes/doctor';
import { useVideoCallStore } from '@/stores/videoCall';
import { type BreadcrumbItem } from '@/types';
import { Head, usePage } from '@inertiajs/vue3';
import { Calendar, Loader2, Phone, RefreshCw, ShieldCheck, User, Video, VideoOff } from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';

interface ActiveCall {
    id: string;
    status: string;
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

const { canAccessDoctorRoute } = useRouteGuard();
const page = usePage();
const store = useVideoCallStore();

const { callState, currentCall, isLoading, sfu, acceptCall, endCall } = useVideoCall();

const appointments = (page.props.appointments as AppointmentProp[]) ?? [];
const selectedAppointment = ref<AppointmentProp | null>(appointments[0] ?? null);
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

const handleJoinCall = async (appointment: AppointmentProp) => {
    if (isLoading.value) return;
    selectedAppointment.value = appointment;

    // Prioriza call ativa da store (auto-start pelo sistema) ou active_call do SSR
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

const getStatusLabel = (status: string) => {
    const labels: Record<string, string> = {
        scheduled: 'Agendado',
        rescheduled: 'Reagendado',
        in_progress: 'Em andamento',
    };
    return labels[status] || status;
};

const statusBadgeClass = (status: string) => {
    if (status === 'in_progress') return 'bg-rose-50 text-rose-700';
    return 'bg-emerald-50 text-emerald-700';
};
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
                                Consultas disponíveis na janela de tempo. Inicie a chamada para o paciente.
                            </p>
                        </div>
                        <div class="grid grid-cols-2 gap-2 sm:flex">
                            <div class="rounded-lg border border-[#dde5ea] bg-[#f4f6f8] px-4 py-2">
                                <p class="text-[11px] font-black text-gray-500 uppercase">Disponíveis</p>
                                <p class="text-xl font-black text-gray-950">{{ appointments.length }}</p>
                            </div>
                        </div>
                    </div>
                </header>

                <div class="min-h-0 flex-1 overflow-y-auto p-4 lg:p-5">
                    <div v-if="appointments.length === 0" class="flex h-full flex-col items-center justify-center px-6 text-center">
                        <VideoOff class="h-12 w-12 text-gray-300" />
                        <h3 class="mt-4 text-lg font-black text-gray-950">Nenhuma consulta na janela de tempo</h3>
                        <p class="mt-2 text-sm font-medium text-gray-500">Consultas aparecem aqui 10 minutos antes e depois do horário agendado.</p>
                    </div>

                    <div v-else class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                        <div
                            v-for="appointment in appointments"
                            :key="appointment.id"
                            class="flex flex-col rounded-lg border bg-white p-5 shadow-sm transition"
                            :class="selectedAppointment?.id === appointment.id && callState !== 'idle' ? 'border-[#0f6e78]' : 'border-[#dde5ea]'"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex items-center gap-3">
                                    <div class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-[#e5f1f2]">
                                        <User class="h-5 w-5 text-[#0f6e78]" />
                                    </div>
                                    <div>
                                        <p class="font-black text-gray-950">{{ appointment.patient.name }}</p>
                                        <span
                                            class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-black"
                                            :class="statusBadgeClass(appointment.status)"
                                        >
                                            {{ getStatusLabel(appointment.status) }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-4 flex items-center gap-2 text-sm font-semibold text-gray-600">
                                <Calendar class="h-4 w-4 text-[#0f6e78]" />
                                {{ appointment.formatted_date }} às {{ appointment.formatted_time }}
                            </div>

                            <p class="mt-1 text-xs font-semibold text-gray-500">{{ appointment.time_window_message }}</p>

                            <div class="mt-4 flex-1" />

                            <div
                                v-if="selectedAppointment?.id === appointment.id && callState === 'accepted'"
                                class="flex items-center gap-2 rounded-lg bg-teal-50 px-4 py-3"
                            >
                                <Video class="h-4 w-4 text-teal-700" />
                                <span class="text-sm font-black text-teal-700">Em chamada</span>
                            </div>

                            <template v-else>
                                <div
                                    v-if="appointment.active_call || (store.isActive && store.appointmentId === appointment.id)"
                                    class="mt-4 flex flex-col gap-2"
                                >
                                    <button
                                        type="button"
                                        class="flex h-10 w-full items-center justify-center gap-2 rounded-lg bg-teal-500 text-sm font-black text-gray-950 transition hover:bg-teal-400 focus:outline-none focus-visible:ring-2 focus-visible:ring-teal-400 disabled:cursor-not-allowed disabled:opacity-60"
                                        :disabled="isLoading"
                                        @click="handleJoinCall(appointment)"
                                    >
                                        <Loader2 v-if="isLoading && selectedAppointment?.id === appointment.id" class="h-4 w-4 animate-spin" />
                                        <Phone v-else class="h-4 w-4" />
                                        Entrar na consulta
                                    </button>
                                    <p class="text-center text-xs font-semibold text-teal-700">Paciente aguardando</p>
                                </div>

                                <button
                                    v-else
                                    type="button"
                                    class="mt-4 flex h-10 w-full cursor-not-allowed items-center justify-center gap-2 rounded-lg bg-gray-100 text-sm font-black text-gray-400"
                                    disabled
                                >
                                    <Video class="h-4 w-4" />
                                    {{ appointment.can_start_call ? 'Aguardando início' : appointment.time_window_message }}
                                </button>
                            </template>
                        </div>
                    </div>

                    <div v-if="callState === 'rejected'" class="mt-6 rounded-lg border border-orange-100 bg-orange-50 p-4">
                        <div class="flex items-center gap-3">
                            <RefreshCw class="h-5 w-5 text-orange-700" />
                            <p class="font-black text-orange-900">Paciente recusou a chamada. Tente novamente.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
