<script setup lang="ts">
import { useRouteGuard } from '@/composables/auth';
import AppLayout from '@/layouts/AppLayout.vue';
import * as doctorRoutes from '@/routes/doctor';
import { type BreadcrumbItem } from '@/types';
import { Head, router, usePage } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';
// @ts-expect-error - route helper from Ziggy (used in template)
/* eslint-disable-next-line @typescript-eslint/no-unused-vars */
declare const route: (name: string, params?: any) => string;

const { canAccessDoctorRoute } = useRouteGuard();

const getConsultationDetailUrl = (appointmentId: string): string => {
    return `/doctor/consultations/${appointmentId}`;
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

const page = usePage();
const users = (page.props.users as User[]) || [];

const selectedUser = ref<User | null>(null);

// Função para atualizar o agendamento selecionado
const updateSelectedAppointment = () => {
    if (!selectedUser.value?.allAppointments) return;

    const selectedId = selectedUser.value.appointment.id;
    const newAppointment = selectedUser.value.allAppointments.find((apt) => apt.id === selectedId);

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
        scheduled: 'Agendado',
        rescheduled: 'Reagendado',
        in_progress: 'Em andamento',
        completed: 'Finalizado',
        cancelled: 'Cancelado',
        no_show: 'Não compareceu',
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
        <div class="flex h-full flex-1 flex-col overflow-hidden" style="height: 90vh">
            <!-- Tela Inicial - Seleção de Paciente -->
            <div class="flex flex-1 bg-gray-50">
                <!-- Sidebar com Pacientes -->
                <div class="flex w-80 flex-col border-r border-gray-200 bg-white">
                    <div class="border-b border-gray-200 p-6">
                        <h2 class="text-xl font-bold text-gray-900">Pacientes Disponíveis</h2>
                        <p class="mt-1 text-sm text-gray-600">Selecione um paciente para iniciar a consulta</p>
                    </div>
                    <div class="flex-1 space-y-2 overflow-y-auto p-4">
                        <div
                            v-for="user in users"
                            :key="user.id"
                            @click="selectedUser = user"
                            :class="[
                                'flex cursor-pointer items-center rounded-lg p-3 transition-all',
                                user.id === selectedUser?.id ? 'bg-primary text-gray-900 shadow-md' : 'hover:bg-gray-100',
                            ]"
                        >
                            <div
                                :class="[
                                    'flex h-12 w-12 items-center justify-center rounded-full font-semibold',
                                    user.id === selectedUser?.id ? 'bg-primary text-white' : 'bg-primary/20 text-primary',
                                ]"
                            >
                                {{ user.name?.charAt(0)?.toUpperCase() || 'P' }}
                            </div>
                            <div class="ml-3 flex-1">
                                <div :class="['font-semibold', user.id === selectedUser?.id ? 'text-gray-900' : 'text-gray-900']">
                                    {{ user.name }}
                                </div>
                                <div class="text-sm text-gray-500">{{ user.email }}</div>
                                <!-- Badge de Agendamento -->
                                <div v-if="user.hasAppointment" class="mt-1 flex items-center gap-1">
                                    <span
                                        :class="[
                                            'rounded-full px-2 py-0.5 text-xs',
                                            user.canStartCall ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700',
                                        ]"
                                    >
                                        {{ user.timeWindowMessage || 'Agendado' }}
                                    </span>
                                </div>
                                <div v-else class="mt-1 text-xs text-gray-400">Sem agendamento</div>
                                <!-- Botão rápido para abrir consulta -->
                                <div v-if="user.appointment" class="mt-2">
                                    <button
                                        @click.stop="router.get(getConsultationDetailUrl(user.appointment.id))"
                                        class="rounded bg-blue-100 px-2 py-1 text-xs text-blue-700 transition-colors hover:bg-blue-200"
                                    >
                                        {{
                                            user.appointment.status === 'completed'
                                                ? 'Complementar'
                                                : user.appointment.status === 'in_progress'
                                                  ? 'Abrir'
                                                  : 'Ver'
                                        }}
                                    </button>
                                </div>
                            </div>
                            <svg v-if="user.id === selectedUser?.id" class="h-5 w-5 text-gray-900" fill="currentColor" viewBox="0 0 20 20">
                                <path
                                    fill-rule="evenodd"
                                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                    clip-rule="evenodd"
                                />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Área Central de Seleção -->
                <div class="flex flex-1 flex-col items-center justify-center bg-gray-50">
                    <div v-if="!selectedUser" class="text-center">
                        <div class="mx-auto mb-6 flex h-24 w-24 items-center justify-center rounded-full bg-primary/20">
                            <svg class="h-12 w-12 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"
                                />
                            </svg>
                        </div>
                        <h3 class="mb-2 text-2xl font-bold text-gray-900">Videoconferência Médica</h3>
                        <p class="mb-4 text-gray-600">Selecione um paciente da lista ao lado para iniciar a consulta</p>
                        <div class="mt-4 max-w-md rounded-lg bg-gray-100 p-3">
                            <p class="text-center text-xs text-gray-600">
                                <strong>Importante:</strong> É necessário ter um agendamento para iniciar a chamada.<br />
                                A chamada só pode ser iniciada 10 minutos antes ou depois do horário agendado.
                            </p>
                        </div>
                    </div>
                    <div v-else class="max-w-md text-center">
                        <div class="mx-auto mb-6 flex h-24 w-24 items-center justify-center rounded-full bg-primary/20">
                            <div class="flex h-20 w-20 items-center justify-center rounded-full bg-primary">
                                <span class="text-2xl font-bold text-gray-900">
                                    {{ selectedUser.name?.charAt(0)?.toUpperCase() || 'P' }}
                                </span>
                            </div>
                        </div>
                        <h3 class="mb-2 text-2xl font-bold text-gray-900">{{ selectedUser.name }}</h3>
                        <p class="mb-4 text-gray-600">{{ selectedUser.email }}</p>

                        <!-- Informações de Agendamento -->
                        <div
                            v-if="selectedUser.hasAppointment && selectedUser.appointment"
                            class="mb-6 rounded-lg border border-gray-200 bg-white p-4 shadow-sm"
                        >
                            <div class="mb-2 flex items-center justify-center gap-2">
                                <svg class="h-5 w-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                                    />
                                </svg>
                                <span class="font-semibold text-gray-900">Agendamento</span>
                            </div>

                            <!-- Seletor de consulta se houver múltiplas -->
                            <div v-if="selectedUser.allAppointments && selectedUser.allAppointments.length > 1" class="mb-3">
                                <label class="mb-1 block text-sm font-medium text-gray-700">
                                    Selecionar consulta ({{ selectedUser.allAppointments.length }} consultas):
                                </label>
                                <select
                                    v-model="selectedUser.appointment.id"
                                    @change="updateSelectedAppointment"
                                    class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-transparent focus:ring-2 focus:ring-primary focus:outline-none"
                                >
                                    <option v-for="apt in selectedUser.allAppointments" :key="apt.id" :value="apt.id">
                                        {{ apt.formatted_date }} às {{ apt.formatted_time }} - {{ getStatusLabel(apt.status) }}
                                    </option>
                                </select>
                            </div>

                            <p class="mb-1 text-sm text-gray-700"><strong>Data:</strong> {{ selectedUser.appointment.formatted_date }}</p>
                            <p class="mb-2 text-sm text-gray-700"><strong>Horário:</strong> {{ selectedUser.appointment.formatted_time }}</p>
                            <div
                                :class="[
                                    'inline-flex items-center gap-2 rounded-full px-3 py-1.5 text-sm font-medium',
                                    selectedUser.canStartCall ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700',
                                ]"
                            >
                                <svg v-if="selectedUser.canStartCall" class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path
                                        fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                        clip-rule="evenodd"
                                    />
                                </svg>
                                <svg v-else class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path
                                        fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                        clip-rule="evenodd"
                                    />
                                </svg>
                                {{ selectedUser.timeWindowMessage || 'Status do agendamento' }}
                            </div>
                        </div>

                        <!-- Mensagem quando não tem agendamento -->
                        <div v-else class="mb-6 rounded-lg border border-yellow-200 bg-yellow-50 p-4">
                            <div class="mb-2 flex items-center justify-center gap-2">
                                <svg class="h-5 w-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                                    />
                                </svg>
                                <span class="font-semibold text-yellow-800">Atenção</span>
                            </div>
                            <p class="text-sm text-yellow-700">Este paciente não possui um agendamento para iniciar a chamada.</p>
                        </div>

                        <div class="flex flex-col items-center gap-3">
                            <button
                                disabled
                                class="flex cursor-not-allowed items-center gap-2 rounded-lg bg-gray-300 px-8 py-3 font-semibold text-gray-500 shadow-lg"
                                title="Videoconferência em migração para nova tecnologia (SFU). Em breve."
                            >
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"
                                    />
                                </svg>
                                Videoconferência em atualização
                            </button>

                            <!-- Botão para abrir página de detalhes da consulta -->
                            <button
                                v-if="selectedUser.appointment"
                                @click="router.get(getConsultationDetailUrl(selectedUser.appointment.id))"
                                :class="[
                                    'flex items-center gap-2 rounded-lg px-6 py-2 text-sm font-medium shadow-md transition-all',
                                    selectedUser.appointment.status === 'completed'
                                        ? 'bg-blue-500 text-white hover:bg-blue-600'
                                        : selectedUser.appointment.status === 'in_progress'
                                          ? 'bg-green-500 text-white hover:bg-green-600'
                                          : 'bg-gray-500 text-white hover:bg-gray-600',
                                ]"
                            >
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                    />
                                </svg>
                                {{
                                    selectedUser.appointment.status === 'completed'
                                        ? 'Complementar Consulta'
                                        : selectedUser.appointment.status === 'in_progress'
                                          ? 'Abrir Consulta'
                                          : 'Ver Detalhes'
                                }}
                            </button>
                        </div>

                        <!-- Informações sobre a janela de tempo -->
                        <div class="mt-6 rounded-lg bg-gray-100 p-3">
                            <p class="text-center text-xs text-gray-600">
                                <strong>Lembrete:</strong> A chamada só pode ser iniciada na janela de tempo permitida<br />
                                (10 minutos antes ou depois do horário agendado)
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
