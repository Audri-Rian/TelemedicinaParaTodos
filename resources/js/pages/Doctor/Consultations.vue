<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, usePage, router } from '@inertiajs/vue3';
import axios from 'axios';
import { ref, onMounted } from 'vue';
import * as appointmentsRoutes from '@/routes/appointments';
import * as doctorRoutes from '@/routes/doctor';
import { type BreadcrumbItem } from '@/types';
import { useRouteGuard } from '@/composables/auth';
import ConsultationSidebar from '@/components/Doctor/ConsultationSidebar.vue';
// @ts-expect-error - route helper from Ziggy (used in template)
/* eslint-disable-next-line @typescript-eslint/no-unused-vars */
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
const showMedicalRecordSidebar = ref(false);
const consultationData = ref<any>(null);
const isLoadingConsultation = ref(false);

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
    } catch {
        // silencioso
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

onMounted(() => {
    canAccessDoctorRoute();
});
</script>

<template>
    <Head title="Consultas - Videoconferência Médica" />
    
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col overflow-hidden" style="height: 90vh;">
            <!-- Tela Inicial - Seleção de Paciente -->
            <div class="flex-1 flex bg-gray-50">
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
                                disabled
                                class="px-8 py-3 rounded-lg font-semibold shadow-lg flex items-center gap-2 bg-gray-300 text-gray-500 cursor-not-allowed"
                                title="Videoconferência em migração para nova tecnologia (SFU). Em breve."
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                                Videoconferência em atualização
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

    </AppLayout>
</template>

