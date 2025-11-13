<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { ref, onMounted, onUnmounted } from 'vue';
import { useVideoCall } from '@/composables/useVideoCall';
import * as appointmentsRoutes from '@/routes/appointments';

defineOptions({
    layout: AppLayout,
});

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

const {
    isCalling,
    hasRemoteStream,
    remoteVideoRef,
    localVideoRef,
    callUser: initiateCall,
    endCall: endVideoCall,
    initialize,
    cleanup,
} = useVideoCall({
    routePrefix: '/doctor',
    onCallReceived: (user) => {
        const matchedUser = users.find((item) => item.id === user.id);
        selectedUser.value = matchedUser ? { ...matchedUser } : (user as User);
    },
    onCallEnd: async () => {
        await finalizeBackendAppointment();
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

    if (!selectedUser.value.canStartCall && selectedUser.value.appointment.status !== 'in_progress') {
        alert(
            selectedUser.value.timeWindowMessage ||
                'A chamada só pode ser iniciada dentro da janela permitida (10 minutos antes ou depois do agendamento).',
        );
        return;
    }

    const started = await startBackendAppointment();

    if (!started && selectedUser.value.appointment?.status !== 'in_progress') {
        alert('Não foi possível iniciar a consulta. Verifique o agendamento.');
        return;
    }

    await initiateCall(selectedUser.value);
};

const endCall = async () => {
    endVideoCall();
    await finalizeBackendAppointment();
};

onMounted(() => {
    initialize();
});

onUnmounted(() => {
    cleanup();
});
</script>

<template>
    <Head title="Consultas - Videoconferência Médica" />
    
    <div class="h-screen flex bg-gray-100" style="height: 90vh;">
        <!-- Sidebar -->
        <div class="w-1/4 bg-white border-r border-gray-200">
            <div class="p-4 bg-gray-100 font-bold text-lg border-b border-gray-200">
                Médicos Disponíveis
            </div>
            <div class="p-4 space-y-4">
                <!-- Lista de Contatos -->
                <div
                    v-for="user in users"
                    :key="user.id"
                    @click="selectedUser = user"
                    :class="[
                        'flex items-center p-2 hover:bg-blue-500 hover:text-white rounded cursor-pointer transition-colors',
                        user.id === selectedUser?.id ? 'bg-primary text-white' : ''
                    ]"
                >
                    <div class="w-12 h-12 bg-blue-200 rounded-full flex items-center justify-center">
                        <span class="text-blue-600 font-semibold">
                            {{ user.name?.charAt(0)?.toUpperCase() || 'U' }}
                        </span>
                    </div>
                    <div class="ml-4">
                        <div class="font-semibold">{{ user.name }}</div>
                        <div class="text-sm text-gray-500">{{ user.email }}</div>
                        <div
                            class="text-xs mt-1"
                            :class="user.canStartCall ? 'text-green-600' : 'text-gray-500'"
                        >
                            {{ user.timeWindowMessage || 'Sem agendamento' }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Área de Chamadas -->
        <div class="flex flex-col w-3/4">
            <div v-if="!selectedUser" class="h-full flex justify-center items-center text-gray-800 font-bold">
                Selecione um Médico para Consulta
            </div>
            
            <div v-if="selectedUser">
                <!-- Cabeçalho do Contato -->
                <div class="p-4 border-b border-gray-200 flex items-center">
                    <div class="w-12 h-12 bg-blue-200 rounded-full flex items-center justify-center">
                        <span class="text-blue-600 font-semibold">
                            {{ selectedUser.name?.charAt(0)?.toUpperCase() || 'U' }}
                        </span>
                    </div>
                    <div class="ml-4 flex items-center justify-between w-full">
                        <div class="font-bold">{{ selectedUser.name }}</div>
                        <div>
                            <button
                                v-if="!isCalling"
                                @click="callUser"
                                :disabled="!selectedUser.canStartCall"
                                :class="[
                                    'px-4 py-2 rounded-lg transition-colors',
                                    selectedUser.canStartCall
                                        ? 'bg-primary text-white hover:bg-blue-600'
                                        : 'bg-gray-300 text-gray-500 cursor-not-allowed',
                                ]"
                            >
                                Iniciar Consulta
                            </button>
                            <button
                                v-if="isCalling"
                                @click="endCall"
                                class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors"
                            >
                                Encerrar Chamada
                            </button>
                            <p
                                v-if="!isCalling && selectedUser && !selectedUser.canStartCall"
                                class="text-xs text-gray-500 mt-2"
                            >
                                {{ selectedUser.timeWindowMessage || 'Sem agendamento disponível.' }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Área de Chamada -->
                <div class="flex-1 overflow-y-auto p-4 space-y-4 bg-gray-50 relative">
                    <div v-if="isCalling" class="relative">
                        <video 
                            id="remoteVideo" 
                            ref="remoteVideoRef" 
                            autoplay 
                            playsinline 
                            muted 
                            class="border-2 border-gray-800 w-full rounded-lg"
                        ></video>
                        <div
                            v-if="!hasRemoteStream"
                            class="absolute inset-0 flex items-center justify-center bg-gray-900/70 text-white font-semibold rounded-lg"
                        >
                            Aguardando conexão...
                        </div>
                        <video 
                            id="localVideo" 
                            ref="localVideoRef" 
                            autoplay 
                            playsinline 
                            muted 
                            class="border-2 border-gray-800 absolute top-6 right-6 w-4/12 rounded-lg"
                            style="margin: 0;"
                        ></video>
                    </div>
                    
                    <div v-if="!isCalling" class="h-full flex justify-center items-center text-gray-800 font-bold">
                        {{ selectedUser?.timeWindowMessage || 'Nenhuma Consulta em Andamento.' }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
