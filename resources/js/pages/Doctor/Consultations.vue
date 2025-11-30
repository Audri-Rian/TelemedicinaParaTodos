<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, usePage, router } from '@inertiajs/vue3';
import axios from 'axios';
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { useVideoCall } from '@/composables/useVideoCall';
import * as appointmentsRoutes from '@/routes/appointments';
import * as doctorRoutes from '@/routes/doctor';
import { type BreadcrumbItem } from '@/types';
import { useRouteGuard } from '@/composables/auth';
import ConsultationSidebar from '@/components/Doctor/ConsultationSidebar.vue';
// @ts-ignore - route helper from Ziggy
declare const route: (name: string, params?: any) => string;

const { canAccessDoctorRoute } = useRouteGuard();

// Helper function para construir URL da rota de detalhes da consulta
const getConsultationDetailUrl = (appointmentId: string): string => {
    return `/doctor/consultations/${appointmentId}`;
};

// Abrir sidebar com prontuário
const openMedicalRecordSidebar = async () => {
    if (!selectedUser.value?.appointment?.id) return;

    isLoadingConsultation.value = true;
    showMedicalRecordSidebar.value = true;

    try {
        const response = await axios.get(`/doctor/consultations/${selectedUser.value.appointment.id}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });
        consultationData.value = response.data;
    } catch (error) {
        console.error('Erro ao carregar dados da consulta:', error);
        // Se der erro, ainda abre a sidebar mas sem dados
        consultationData.value = {
            appointment: {
                id: selectedUser.value.appointment.id,
                chief_complaint: '',
                anamnesis: '',
                physical_exam: '',
                diagnosis: '',
                cid10: '',
                instructions: '',
                notes: '',
            },
            patient: {
                id: selectedUser.value.id,
                name: selectedUser.value.name,
                age: 0,
                gender: '',
                allergies: [],
            },
            isCompleted: selectedUser.value.appointment.status === 'completed',
        };
    } finally {
        isLoadingConsultation.value = false;
    }
};

// Fechar sidebar
const closeMedicalRecordSidebar = () => {
    showMedicalRecordSidebar.value = false;
};

// Quando dados são salvos na sidebar, atualizar
const onSidebarSaved = () => {
    // Pode atualizar estado se necessário
    console.log('Dados salvos na sidebar');
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: doctorRoutes.dashboard().url,
    },
    {
        title: 'Consultas',
        href: doctorRoutes.consultations().url,
    },
];

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
    timeWindowMessage?: string | null;
}

interface AuthUser {
    user: User;
}

const page = usePage();
const auth = page.props.auth as AuthUser;
const users = (page.props.users as User[]) || [];

const selectedUser = ref<User | null>(null);
const isMuted = ref(false);
const isVideoEnabled = ref(true);
const showMedicalRecordSidebar = ref(false);
const consultationData = ref<any>(null);
const isLoadingConsultation = ref(false);

const {
    isCalling,
    hasRemoteStream,
    remoteVideoRef,
    localVideoRef,
    localStreamRef,
    callUser: initiateCall,
    endCall: endVideoCall,
    acceptCall,
    rejectCall,
    reconnectCall,
    initialize,
    cleanup,
    // Estados de conexão avançados
    isConnected,
    isReceivingCall,
    connectionLost,
    isReconnecting,
    showIncomingCallModal,
    incomingCallPeerId,
    connectionState,
    mediaConfig,
    // Novos estados para rejeições acidentais
    canCallBack,
    showRejectionConfirmModal,
    lastRejectedPeerId,
    showRejectConfirmation,
    confirmRejectCall,
    cancelRejectCall,
    callBack,
    // Novos estados detalhados
    callState,
    callDuration,
    networkQuality,
    formatCallDuration,
    resendCallRequest,
} = useVideoCall({
    routePrefix: '/doctor',
    onCallReceived: (user) => {
        const matchedUser = users.find((item) => item.id === user.id);
        selectedUser.value = matchedUser ? { ...matchedUser } : (user as User);
    },
    onCallEnd: async () => {
        await finalizeBackendAppointment();
        isMuted.value = false;
        isVideoEnabled.value = true;
    },
    onConnectionLost: () => {
        // Callback quando a conexão é perdida
        console.log('Conexão perdida - tentando reconectar automaticamente...');
    },
    onConnectionRestored: () => {
        // Callback quando a conexão é restaurada
        console.log('Conexão restaurada com sucesso!');
    },
});

const startBackendAppointment = async (): Promise<boolean> => {
    if (!selectedUser.value?.appointment?.id) {
        return false;
    }

    try {
        await axios.post(
            appointmentsRoutes.start.url({ appointment: selectedUser.value.appointment.id }),
        );

        selectedUser.value = {
            ...selectedUser.value,
            appointment: selectedUser.value.appointment
                ? {
                      ...selectedUser.value.appointment,
                      status: 'in_progress',
                  }
                : null,
            canStartCall: true,
            timeWindowMessage: 'Consulta em andamento',
        };

        return true;
    } catch (error: any) {
        const message = error?.response?.data?.message;
        if (message) {
            alert(message);
        }
        return false;
    }
};

const finalizeBackendAppointment = async () => {
    if (!selectedUser.value?.appointment?.id) {
        return;
    }

    try {
        await axios.post(
            appointmentsRoutes.end.url({ appointment: selectedUser.value.appointment.id }),
        );

        selectedUser.value = {
            ...selectedUser.value,
            appointment: selectedUser.value.appointment
                ? {
                      ...selectedUser.value.appointment,
                      status: 'completed',
                  }
                : null,
            canStartCall: false,
            timeWindowMessage: 'Consulta finalizada',
        };
    } catch (error) {
        // silencioso
    }
};

const callUser = async () => {
    if (!selectedUser.value) {
        return;
    }

    if (!selectedUser.value.hasAppointment || !selectedUser.value.appointment) {
        alert('É necessário um agendamento ativo para iniciar a chamada.');
        return;
    }

    // Se a consulta já está em andamento, reconectar diretamente
    if (selectedUser.value.appointment.status === 'in_progress') {
        console.log('Reconectando à consulta em andamento...');
        await initiateCall(selectedUser.value);
        return;
    }

    if (!selectedUser.value.canStartCall) {
        alert(
            selectedUser.value.timeWindowMessage ||
                'A chamada só pode ser iniciada dentro da janela permitida (10 minutos antes ou depois do agendamento).',
        );
        return;
    }

    const started = await startBackendAppointment();

    if (!started) {
        alert('Não foi possível iniciar a consulta. Verifique o agendamento.');
        return;
    }

    await initiateCall(selectedUser.value);
};

const endCall = async () => {
    await endVideoCall();
};

const toggleMute = () => {
    if (localStreamRef.value) {
        const audioTracks = localStreamRef.value.getAudioTracks();
        audioTracks.forEach(track => {
            track.enabled = !track.enabled;
        });
        isMuted.value = !isMuted.value;
    }
};

const toggleVideo = () => {
    if (localStreamRef.value) {
        const videoTracks = localStreamRef.value.getVideoTracks();
        videoTracks.forEach(track => {
            track.enabled = !track.enabled;
        });
        isVideoEnabled.value = !isVideoEnabled.value;
    }
};

// Função para atualizar o agendamento selecionado
const updateSelectedAppointment = () => {
    if (!selectedUser.value?.allAppointments) return;
    
    const selectedId = selectedUser.value.appointment.id;
    const newAppointment = selectedUser.value.allAppointments.find(apt => apt.id === selectedId);
    
    if (newAppointment) {
        selectedUser.value.appointment = { ...newAppointment };
        // Atualizar status baseado na nova consulta selecionada
        updateAppointmentStatus(newAppointment);
    }
};

// Função para atualizar status da consulta
const updateAppointmentStatus = (appointment: any) => {
    if (!selectedUser.value) return;
    
    const now = new Date();
    const scheduledAt = new Date(appointment.scheduled_at);
    const diffMinutes = Math.round((scheduledAt.getTime() - now.getTime()) / (1000 * 60));
    
    if (appointment.status === 'in_progress') {
        selectedUser.value.canStartCall = true;
        selectedUser.value.timeWindowMessage = 'Consulta em andamento';
    } else if (appointment.status === 'completed') {
        selectedUser.value.canStartCall = false;
        selectedUser.value.timeWindowMessage = 'Consulta finalizada';
    } else if (appointment.status === 'no_show') {
        selectedUser.value.canStartCall = false;
        selectedUser.value.timeWindowMessage = 'Consulta não comparecida';
    } else if (['scheduled', 'rescheduled'].includes(appointment.status)) {
        if (diffMinutes >= -10 && diffMinutes <= 10) {
            selectedUser.value.canStartCall = true;
                    if (diffMinutes < 0) {
                        selectedUser.value.timeWindowMessage = `${Math.abs(diffMinutes)} min`;
                    } else if (diffMinutes === 0) {
                        selectedUser.value.timeWindowMessage = 'Agora';
                    } else {
                        selectedUser.value.timeWindowMessage = `Em ${diffMinutes} min`;
                    }
                } else {
                    selectedUser.value.canStartCall = false;
                    if (diffMinutes < -10) {
                        selectedUser.value.timeWindowMessage = 'Expirado';
                    } else {
                        const daysUntil = Math.floor(diffMinutes / (24 * 60));
                        if (daysUntil > 0) {
                            selectedUser.value.timeWindowMessage = `${daysUntil} ${daysUntil === 1 ? 'dia' : 'dias'}`;
                        } else {
                            const hoursUntil = Math.floor(diffMinutes / 60);
                            selectedUser.value.timeWindowMessage = `Em ${hoursUntil}${hoursUntil === 1 ? 'h' : 'h'}`;
                        }
                    }
                }
    }
};

// Função para obter label do status
const getStatusLabel = (status: string): string => {
    const statusLabels: Record<string, string> = {
        'scheduled': 'Agendado',
        'rescheduled': 'Reagendado',
        'in_progress': 'Em andamento',
        'completed': 'Finalizado',
        'cancelled': 'Cancelado',
        'no_show': 'Não compareceu'
    };
    return statusLabels[status] || status;
};

// Função para verificar e reconectar automaticamente a consultas em andamento
const checkAndAutoReconnect = async () => {
    // Procurar por usuários com consultas em andamento
    const usersWithActiveConsultations = users.filter(user => 
        user.hasAppointment && 
        user.appointment?.status === 'in_progress'
    );
    
    if (usersWithActiveConsultations.length > 0) {
        const activeUser = usersWithActiveConsultations[0];
        console.log('Consulta em andamento detectada, tentando reconectar automaticamente...', activeUser);
        
        // Selecionar o usuário e tentar reconectar
        selectedUser.value = activeUser;
        
        // Aguardar um pouco para garantir que o peer está inicializado
        setTimeout(async () => {
            try {
                await initiateCall(activeUser);
            } catch (error) {
                console.log('Reconexão automática falhou, usuário pode reconectar manualmente:', error);
            }
        }, 2000);
    }
};

onMounted(() => {
    canAccessDoctorRoute();
    initialize();
    
    // Verificar se há consultas em andamento para reconexão automática
    checkAndAutoReconnect();
});

onUnmounted(() => {
    cleanup();
});
</script>

<template>
    <Head title="Consultas - Videoconferência Médica" />
    
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col overflow-hidden" style="height: 90vh;">
            <!-- Área Principal de Vídeo -->
            <div v-if="isCalling && selectedUser" class="flex-1 flex flex-col bg-white">
                <!-- Barra Superior com Informações -->
                <div class="bg-white/95 backdrop-blur-sm px-6 py-3 flex items-center justify-between border-b border-gray-200 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-primary rounded-full flex items-center justify-center">
                            <span class="text-gray-900 font-bold text-sm">
                                {{ selectedUser.name?.charAt(0)?.toUpperCase() || 'P' }}
                            </span>
                        </div>
                        <div>
                            <div class="text-gray-900 font-semibold">{{ selectedUser.name }}</div>
                            <div class="text-gray-600 text-sm">Consultando</div>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <!-- Indicador de Qualidade da Conexão -->
                        <div class="flex items-center gap-2">
                            <div :class="[
                                'w-2 h-2 rounded-full',
                                connectionLost ? 'bg-red-500' : hasRemoteStream ? 'bg-green-500' : 'bg-yellow-500'
                            ]"></div>
                            <span class="text-xs text-gray-400">
                                {{ connectionLost ? 'Reconectando...' : hasRemoteStream ? 'Conectado' : 'Conectando...' }}
                            </span>
                        </div>
                        <div class="text-primary text-sm font-medium">00:00</div>
                        <!-- Botão para abrir prontuário -->
                        <button
                            v-if="selectedUser.appointment"
                            @click="openMedicalRecordSidebar"
                            class="px-3 py-1.5 bg-blue-500 text-white rounded-lg text-sm font-medium hover:bg-blue-600 transition-colors flex items-center gap-2"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            Prontuário
                        </button>
                    </div>
                </div>

                <!-- Container Principal de Vídeo -->
                <div class="flex-1 flex gap-4 p-4 relative max-h-[calc(100vh-200px)]">
                    <!-- Sidebar do Prontuário -->
                    <Transition
                        enter-active-class="transition-all duration-300 ease-out"
                        enter-from-class="translate-x-full opacity-0"
                        enter-to-class="translate-x-0 opacity-100"
                        leave-active-class="transition-all duration-300 ease-in"
                        leave-from-class="translate-x-0 opacity-100"
                        leave-to-class="translate-x-full opacity-0"
                    >
                        <div
                            v-if="showMedicalRecordSidebar"
                            class="w-96 border-l border-gray-200 bg-white shadow-xl z-10 h-full"
                        >
                            <ConsultationSidebar
                                v-if="consultationData && !isLoadingConsultation && selectedUser?.appointment"
                                :appointment-id="selectedUser.appointment.id"
                                :patient="consultationData.patient"
                                :consultation-data="consultationData.appointment"
                                :is-completed="consultationData.appointment?.status === 'completed'"
                                @close="closeMedicalRecordSidebar"
                                @saved="onSidebarSaved"
                            />
                            <div v-else class="flex items-center justify-center h-full">
                                <div class="text-center">
                                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary mx-auto mb-2"></div>
                                    <p class="text-sm text-gray-600">Carregando prontuário...</p>
                                </div>
                            </div>
                        </div>
                    </Transition>
                    <!-- Vídeo Remoto (Paciente) -->
                    <div :class="[
                        'relative bg-white rounded-lg overflow-hidden border border-gray-200 max-h-full transition-all duration-300',
                        showMedicalRecordSidebar ? 'flex-1' : 'flex-1'
                    ]">
                        <video 
                            ref="remoteVideoRef" 
                            autoplay 
                            playsinline 
                            class="w-full h-full object-cover max-h-full"
                        ></video>
                        <div v-if="!hasRemoteStream" class="absolute inset-0 w-full h-full flex items-center justify-center bg-white z-10">
                            <div class="text-center">
                                <div class="w-20 h-20 bg-primary/20 rounded-full flex items-center justify-center mx-auto mb-4">
                                    <svg v-if="!connectionLost" class="w-10 h-10 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                    <svg v-else class="w-10 h-10 text-red-400 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>
                                </div>
                                <p class="text-gray-600">
                                    {{ connectionLost ? 'Reconectando...' : 'Aguardando conexão...' }}
                                </p>
                                <div v-if="connectionLost" class="mt-3">
                                    <button
                                        @click="reconnectCall"
                                        :disabled="isReconnecting"
                                        class="px-4 py-2 bg-primary text-gray-900 rounded-lg text-sm font-medium hover:bg-primary/90 disabled:opacity-50"
                                    >
                                        {{ isReconnecting ? 'Reconectando...' : 'Tentar Novamente' }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Vídeo Local (Médico) -->
                    <div class="w-56 h-64 relative bg-white rounded-lg overflow-hidden border-2 border-primary/30">
                        <video 
                            ref="localVideoRef" 
                            autoplay 
                            playsinline 
                            muted
                            class="w-full h-full object-cover"
                            v-if="isVideoEnabled"
                        ></video>
                        <div v-else class="w-full h-full flex items-center justify-center bg-white">
                            <div class="text-center">
                                <div class="w-16 h-16 bg-primary/20 rounded-full flex items-center justify-center mx-auto mb-3">
                                    <svg class="w-8 h-8 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                </div>
                                <p class="text-gray-600 text-sm">{{ auth.user.name }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Controles Inferiores -->
                <div class="bg-white/95 backdrop-blur-sm px-6 py-4 border-t border-gray-200 shadow-sm">
                    <div class="flex items-center justify-center gap-4">
                        <!-- Mute/Desmute -->
                        <button
                            @click="toggleMute"
                            :class="[
                                'w-12 h-12 rounded-full flex items-center justify-center transition-all',
                                isMuted 
                                    ? 'bg-red-500 hover:bg-red-600' 
                                    : 'bg-gray-200 hover:bg-gray-300'
                            ]"
                            title="Microfone"
                        >
                            <svg v-if="isMuted" class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2" />
                            </svg>
                            <svg v-else class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
                            </svg>
                        </button>

                        <!-- Liga/Desliga Vídeo -->
                        <button
                            @click="toggleVideo"
                            :class="[
                                'w-12 h-12 rounded-full flex items-center justify-center transition-all',
                                isVideoEnabled 
                                    ? 'bg-gray-200 hover:bg-gray-300' 
                                    : 'bg-red-500 hover:bg-red-600'
                            ]"
                            title="Câmera"
                        >
                            <svg v-if="isVideoEnabled" class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                            </svg>
                            <svg v-else class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 715.636 5.636m12.728 12.728L5.636 5.636" />
                            </svg>
                        </button>

                        <!-- Desligar -->
                        <button
                            v-if="isCalling"
                            @click="endCall"
                            class="w-14 h-14 rounded-full bg-red-500 hover:bg-red-600 flex items-center justify-center transition-all shadow-lg"
                            title="Encerrar Chamada"
                        >
                            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Tela Inicial - Seleção de Paciente -->
            <div v-else class="flex-1 flex bg-gray-50">
                <!-- Sidebar com Pacientes -->
                <div class="w-80 bg-white border-r border-gray-200 flex flex-col">
                    <div class="p-6 border-b border-gray-200">
                        <h2 class="text-xl font-bold text-gray-900">Pacientes Disponíveis</h2>
                        <p class="text-sm text-gray-600 mt-1">Selecione um paciente para iniciar a consulta</p>
                    </div>
                    <div class="flex-1 overflow-y-auto p-4 space-y-2">
                        <div
                            v-for="user in users"
                            :key="user.id"
                            @click="selectedUser = user"
                            :class="[
                                'flex items-center p-3 rounded-lg cursor-pointer transition-all',
                                user.id === selectedUser?.id 
                                    ? 'bg-primary text-gray-900 shadow-md' 
                                    : 'hover:bg-gray-100'
                            ]"
                        >
                            <div :class="[
                                'w-12 h-12 rounded-full flex items-center justify-center font-semibold',
                                user.id === selectedUser?.id 
                                    ? 'bg-primary text-white' 
                                    : 'bg-primary/20 text-primary'
                            ]">
                                {{ user.name?.charAt(0)?.toUpperCase() || 'P' }}
                            </div>
                            <div class="ml-3 flex-1">
                                <div :class="[
                                    'font-semibold',
                                    user.id === selectedUser?.id ? 'text-gray-900' : 'text-gray-900'
                                ]">{{ user.name }}</div>
                                <div class="text-sm text-gray-500">{{ user.email }}</div>
                                <!-- Badge de Agendamento -->
                                <div v-if="user.hasAppointment" class="flex items-center gap-1 mt-1">
                                    <span :class="[
                                        'text-xs px-2 py-0.5 rounded-full',
                                        user.canStartCall 
                                            ? 'bg-green-100 text-green-700' 
                                            : 'bg-yellow-100 text-yellow-700'
                                    ]">
                                        {{ user.timeWindowMessage || 'Agendado' }}
                                    </span>
                                </div>
                                <div v-else class="text-xs text-gray-400 mt-1">
                                    Sem agendamento
                                </div>
                                <!-- Botão rápido para abrir consulta -->
                                <div v-if="user.appointment" class="mt-2">
                                    <button
                                        @click.stop="router.get(getConsultationDetailUrl(user.appointment.id))"
                                        class="text-xs px-2 py-1 bg-blue-100 text-blue-700 rounded hover:bg-blue-200 transition-colors"
                                    >
                                        {{ user.appointment.status === 'completed' ? 'Complementar' : user.appointment.status === 'in_progress' ? 'Abrir' : 'Ver' }}
                                    </button>
                                </div>
                            </div>
                            <svg v-if="user.id === selectedUser?.id" class="w-5 h-5 text-gray-900" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Área Central de Seleção -->
                <div class="flex-1 flex flex-col items-center justify-center bg-gray-50">
                    <div v-if="!selectedUser" class="text-center">
                        <div class="w-24 h-24 bg-primary/20 rounded-full flex items-center justify-center mx-auto mb-6">
                            <svg class="w-12 h-12 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-900 mb-2">Videoconferência Médica</h3>
                        <p class="text-gray-600 mb-4">Selecione um paciente da lista ao lado para iniciar a consulta</p>
                        <div class="mt-4 p-3 bg-gray-100 rounded-lg max-w-md">
                            <p class="text-xs text-gray-600 text-center">
                                <strong>Importante:</strong> É necessário ter um agendamento para iniciar a chamada.<br/>
                                A chamada só pode ser iniciada 10 minutos antes ou depois do horário agendado.
                            </p>
                        </div>
                    </div>
                    <div v-else class="text-center max-w-md">
                        <div class="w-24 h-24 bg-primary/20 rounded-full flex items-center justify-center mx-auto mb-6">
                            <div class="w-20 h-20 bg-primary rounded-full flex items-center justify-center">
                                <span class="text-gray-900 font-bold text-2xl">
                                    {{ selectedUser.name?.charAt(0)?.toUpperCase() || 'P' }}
                                </span>
                            </div>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-900 mb-2">{{ selectedUser.name }}</h3>
                        <p class="text-gray-600 mb-4">{{ selectedUser.email }}</p>
                        
                        <!-- Informações de Agendamento -->
                        <div v-if="selectedUser.hasAppointment && selectedUser.appointment" class="mb-6 p-4 bg-white rounded-lg border border-gray-200 shadow-sm">
                            <div class="flex items-center justify-center gap-2 mb-2">
                                <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                <span class="font-semibold text-gray-900">Agendamento</span>
                            </div>

                            <!-- Seletor de consulta se houver múltiplas -->
                            <div v-if="selectedUser.allAppointments && selectedUser.allAppointments.length > 1" class="mb-3">
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Selecionar consulta ({{ selectedUser.allAppointments.length }} consultas):
                                </label>
                                <select 
                                    v-model="selectedUser.appointment.id"
                                    @change="updateSelectedAppointment"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
                                >
                                    <option 
                                        v-for="apt in selectedUser.allAppointments" 
                                        :key="apt.id" 
                                        :value="apt.id"
                                    >
                                        {{ apt.formatted_date }} às {{ apt.formatted_time }} - {{ getStatusLabel(apt.status) }}
                                    </option>
                                </select>
                            </div>

                            <p class="text-sm text-gray-700 mb-1">
                                <strong>Data:</strong> {{ selectedUser.appointment.formatted_date }}
                            </p>
                            <p class="text-sm text-gray-700 mb-2">
                                <strong>Horário:</strong> {{ selectedUser.appointment.formatted_time }}
                            </p>
                            <div :class="[
                                'inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-sm font-medium',
                                selectedUser.canStartCall 
                                    ? 'bg-green-100 text-green-700' 
                                    : 'bg-yellow-100 text-yellow-700'
                            ]">
                                <svg v-if="selectedUser.canStartCall" class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                </svg>
                                <svg v-else class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                </svg>
                                {{ selectedUser.timeWindowMessage || 'Status do agendamento' }}
                            </div>
                        </div>
                        
                        <!-- Mensagem quando não tem agendamento -->
                        <div v-else class="mb-6 p-4 bg-yellow-50 rounded-lg border border-yellow-200">
                            <div class="flex items-center justify-center gap-2 mb-2">
                                <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                                <span class="font-semibold text-yellow-800">Atenção</span>
                            </div>
                            <p class="text-sm text-yellow-700">
                                Este paciente não possui um agendamento para iniciar a chamada.
                            </p>
                        </div>
                        
                        <div class="flex flex-col gap-3 items-center">
                            <button
                                @click="callUser"
                                :disabled="!selectedUser.canStartCall"
                                :class="[
                                    'px-8 py-3 rounded-lg transition-all font-semibold shadow-lg flex items-center gap-2',
                                    selectedUser.canStartCall
                                        ? 'bg-primary text-gray-900 hover:bg-primary/90'
                                        : 'bg-gray-300 text-gray-500 cursor-not-allowed'
                                ]"
                            >
                                <svg v-if="selectedUser.appointment?.status === 'in_progress'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                                <svg v-else class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z" />
                                </svg>
                                {{ selectedUser.appointment?.status === 'in_progress' ? 'Reconectar' : 'Iniciar Consulta' }}
                            </button>
                            
                            <!-- Botão para abrir página de detalhes da consulta -->
                            <button
                                v-if="selectedUser.appointment"
                                @click="router.get(getConsultationDetailUrl(selectedUser.appointment.id))"
                                :class="[
                                    'px-6 py-2 rounded-lg transition-all font-medium shadow-md flex items-center gap-2 text-sm',
                                    selectedUser.appointment.status === 'completed'
                                        ? 'bg-blue-500 text-white hover:bg-blue-600'
                                        : selectedUser.appointment.status === 'in_progress'
                                        ? 'bg-green-500 text-white hover:bg-green-600'
                                        : 'bg-gray-500 text-white hover:bg-gray-600'
                                ]"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                {{ selectedUser.appointment.status === 'completed' ? 'Complementar Consulta' : selectedUser.appointment.status === 'in_progress' ? 'Abrir Consulta' : 'Ver Detalhes' }}
                            </button>
                            
                            <!-- Botão "Chamar Novamente" para rejeições acidentais -->
                            <button
                                v-if="canCallBack"
                                @click="callBack"
                                class="px-6 py-2 rounded-lg bg-orange-500 text-white hover:bg-orange-600 transition-all font-medium shadow-md flex items-center gap-2 text-sm"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                </svg>
                                Chamar Novamente
                            </button>
                        </div>
                        
                        <!-- Informações sobre a janela de tempo -->
                        <div class="mt-6 p-3 bg-gray-100 rounded-lg">
                            <p class="text-xs text-gray-600 text-center">
                                <strong>Lembrete:</strong> A chamada só pode ser iniciada na janela de tempo permitida<br/>
                                (10 minutos antes ou depois do horário agendado)
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal de Chamada Recebida -->
        <div v-if="showIncomingCallModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50">
            <div class="bg-white rounded-xl shadow-2xl max-w-md w-full mx-4 overflow-hidden">
                <!-- Header -->
                <div class="bg-primary px-6 py-4">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 bg-white/20 rounded-full flex items-center justify-center">
                            <svg class="w-6 h-6 text-gray-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-900">Chamada Recebida</h3>
                            <p class="text-sm text-gray-800">Paciente solicitando consulta</p>
                        </div>
                    </div>
                </div>

                <!-- Body -->
                <div class="p-6">
                    <div class="text-center mb-6">
                        <div class="w-20 h-20 bg-primary/10 rounded-full flex items-center justify-center mx-auto mb-4">
                            <span class="text-2xl font-bold text-primary">
                                {{ selectedUser?.name?.charAt(0)?.toUpperCase() || 'P' }}
                            </span>
                        </div>
                        <h4 class="text-xl font-semibold text-gray-900 mb-1">
                            {{ selectedUser?.name || 'Paciente' }}
                        </h4>
                        <p class="text-gray-600">está solicitando uma consulta</p>
                    </div>

                    <!-- Informações do agendamento se disponível -->
                    <div v-if="selectedUser?.appointment" class="bg-gray-50 rounded-lg p-4 mb-6">
                        <div class="flex items-center gap-2 mb-2">
                            <svg class="w-4 h-4 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <span class="text-sm font-medium text-gray-900">Agendamento</span>
                        </div>
                        <p class="text-sm text-gray-700">
                            {{ selectedUser.appointment.formatted_date }} às {{ selectedUser.appointment.formatted_time }}
                        </p>
                    </div>

                    <!-- Botões de ação -->
                    <div class="flex gap-3">
                        <button
                            @click="showRejectConfirmation"
                            class="flex-1 px-4 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg font-medium transition-colors"
                        >
                            Recusar
                        </button>
                        <button
                            @click="acceptCall"
                            class="flex-1 px-4 py-3 bg-primary hover:bg-primary/90 text-gray-900 rounded-lg font-medium transition-colors"
                        >
                            Aceitar
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal de Confirmação de Rejeição -->
        <div v-if="showRejectionConfirmModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50" @click.self="cancelRejectCall">
            <div class="bg-white rounded-xl shadow-2xl max-w-md w-full mx-4 overflow-hidden animate-in fade-in zoom-in duration-200">
                <!-- Header -->
                <div class="p-6 border-b border-gray-100 bg-gradient-to-r from-orange-50 to-amber-50">
                    <div class="flex items-center gap-3">
                        <div class="flex items-center justify-center w-14 h-14 rounded-full bg-orange-100 ring-4 ring-orange-50">
                            <svg class="w-7 h-7 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
                            </svg>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-xl font-bold text-gray-900">Confirmar Rejeição</h3>
                            <p class="text-sm text-gray-600 mt-1">Tem certeza que deseja recusar esta chamada?</p>
                        </div>
                    </div>
                </div>

                <!-- Content -->
                <div class="p-6">
                    <!-- Informações do chamador -->
                    <div v-if="selectedUser" class="mb-4 p-4 bg-gray-50 rounded-lg border border-gray-200">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-primary/20 rounded-full flex items-center justify-center">
                                <span class="text-primary font-bold text-sm">
                                    {{ selectedUser.name?.charAt(0)?.toUpperCase() || 'P' }}
                                </span>
                            </div>
                            <div>
                                <p class="font-semibold text-gray-900">{{ selectedUser.name }}</p>
                                <p class="text-xs text-gray-500">está tentando iniciar a consulta</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Aviso sobre rejeição acidental -->
                    <div class="bg-amber-50 border-l-4 border-amber-400 rounded-lg p-4 mb-4">
                        <div class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-amber-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <div class="text-sm flex-1">
                                <p class="font-semibold text-amber-900 mb-1">Rejeição acidental?</p>
                                <p class="text-amber-800 leading-relaxed">
                                    Se você recusar por engano, poderá usar o botão <strong>"Chamar Novamente"</strong> que aparecerá por <strong>2 minutos</strong> após a rejeição.
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Informações adicionais -->
                    <div class="text-xs text-gray-500 space-y-1">
                        <p>• A chamada será encerrada imediatamente</p>
                        <p>• Você poderá iniciar uma nova chamada a qualquer momento</p>
                        <p>• O botão "Chamar Novamente" estará disponível por 2 minutos</p>
                    </div>
                </div>

                <!-- Footer -->
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex gap-3">
                    <button
                        @click="cancelRejectCall"
                        class="flex-1 px-4 py-3 text-gray-700 bg-white border-2 border-gray-300 rounded-lg hover:bg-gray-50 hover:border-gray-400 font-semibold transition-all duration-200"
                    >
                        Voltar
                    </button>
                    <button
                        @click="confirmRejectCall"
                        class="flex-1 px-4 py-3 bg-red-600 text-white rounded-lg hover:bg-red-700 font-semibold transition-all duration-200 shadow-md hover:shadow-lg"
                    >
                        Sim, Recusar
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

