<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import * as patientRoutes from '@/routes/patient';
import { onMounted, computed, onUnmounted, ref, watch } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { useRouteGuard } from '@/composables/auth';
import { Button } from '@/components/ui/button';
import axios from 'axios';
import * as appointmentsRoutes from '@/routes/appointments';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
import AppointmentStatusBadge from '@/components/AppointmentStatusBadge.vue';
import AppointmentActions from '@/components/AppointmentActions.vue';
import CancelAppointmentModal from '@/components/modals/CancelAppointmentModal.vue';

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

const permissions = computed(() => appointment.value.can ?? {
    start: false,
    cancel: false,
    is_active: false,
    is_upcoming: false,
});

// Mapear status para badge
const statusBadge = computed(() => {
    const statusMap: Record<string, { label: string; class: string }> = {
        scheduled: { label: 'Agendada', class: 'bg-yellow-100 text-yellow-700' },
        in_progress: { label: 'Em Andamento', class: 'bg-blue-100 text-blue-700' },
        completed: { label: 'Concluída', class: 'bg-green-100 text-green-700' },
        cancelled: { label: 'Cancelada', class: 'bg-red-100 text-red-700' },
        rescheduled: { label: 'Reagendada', class: 'bg-purple-100 text-purple-700' },
        no_show: { label: 'Não Compareceu', class: 'bg-gray-100 text-gray-700' },
    };
    return statusMap[appointment.value.status] || statusMap.scheduled;
});

// Formatar data e hora
const formattedDate = computed(() => {
    const date = new Date(appointment.value.scheduled_at);
    return date.toLocaleDateString('pt-BR', {
        day: 'numeric',
        month: 'long',
        year: 'numeric'
    });
});

const formattedTime = computed(() => {
    const date = new Date(appointment.value.scheduled_at);
    return date.toLocaleTimeString('pt-BR', {
        hour: '2-digit',
        minute: '2-digit'
    });
});

// Construir timeline a partir dos logs
const timeline = computed(() => {
    return appointment.value.logs
        .sort((a, b) => new Date(a.created_at).getTime() - new Date(b.created_at).getTime())
        .map(log => {
            const eventNames: Record<string, string> = {
                'created': 'Consulta Agendada',
                'started': 'Consulta Iniciada',
                'ended': 'Consulta Finalizada',
                'cancelled': 'Consulta Cancelada',
                'rescheduled': 'Consulta Reagendada',
                'no_show': 'Não Compareceu',
            };
            
            const eventName = eventNames[log.event] || log.event;
            const time = new Date(log.created_at).toLocaleTimeString('pt-BR', {
                hour: '2-digit',
                minute: '2-digit'
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
                const newDate = log.payload?.new_scheduled_at 
                    ? new Date(log.payload.new_scheduled_at).toLocaleString('pt-BR')
                    : 'N/A';
                description = `Reagendada para: ${newDate}`;
            }
            
            return {
                time,
                event: eventName,
                description,
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
    return 'Não disponível';
});

// Estado da modal de cancelamento
const showCancelModal = ref(false);
const isCancelling = ref(false);
const showSuccessMessage = ref(false);
const successMessage = ref('');

const page = usePage();

// Verificar mensagens de sucesso do backend
watch(() => (page.props as any).flash, (flash: any) => {
    if (flash?.success) {
        showSuccessMessage.value = true;
        successMessage.value = flash.success;
        // Auto-ocultar após 5 segundos
        setTimeout(() => {
            showSuccessMessage.value = false;
        }, 5000);
    }
}, { immediate: true });

// Funções de ação
const startAppointment = async () => {
    try {
        await axios.post(appointmentsRoutes.start.url({ appointment: appointment.value.id }));
        router.reload();
    } catch (error: any) {
        console.error('Erro ao iniciar consulta:', error);
        alert(error.response?.data?.message || 'Erro ao iniciar consulta.');
    }
};

const openCancelModal = () => {
    showCancelModal.value = true;
};

const closeCancelModal = () => {
    showCancelModal.value = false;
};

const cancelAppointment = async (reason: string | null) => {
    isCancelling.value = true;
    try {
        const response = await axios.post(appointmentsRoutes.cancel.url({ appointment: appointment.value.id }), {
            reason: reason || null
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

const connectEcho = () => {
    if (typeof window === 'undefined' || echoInstance) {
        return;
    }

    // Verificar se as variáveis de ambiente estão definidas
    const appKey = import.meta.env.VITE_REVERB_APP_KEY;
    const wsHost = import.meta.env.VITE_REVERB_HOST;
    const wsPort = import.meta.env.VITE_REVERB_PORT;
    const scheme = import.meta.env.VITE_REVERB_SCHEME ?? 'https';

    if (!appKey || !wsHost) {
        console.warn('Reverb não configurado. Variáveis de ambiente VITE_REVERB_APP_KEY ou VITE_REVERB_HOST não encontradas.');
        return;
    }

    // Configurar Pusher globalmente se necessário
    if (typeof window !== 'undefined' && !(window as any).Pusher) {
        (window as any).Pusher = Pusher;
    }

    const echo = new Echo({
        broadcaster: 'reverb',
        key: appKey,
        wsHost: wsHost,
        wsPort: wsPort ? parseInt(wsPort) : 8080,
        wssPort: wsPort ? parseInt(wsPort) : 8080,
        forceTLS: scheme === 'https',
        enabledTransports: ['ws', 'wss'],
        client: Pusher,
    });

    echo.private(`appointment.${appointment.value.patient.id}`)
        .listen('.status.changed', (event: any) => {
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
});

onUnmounted(() => {
    if (echoInstance) {
        echoInstance.disconnect();
        echoInstance = null;
    }
});
</script>

<template>
    <Head title="Detalhes da Consulta" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col overflow-x-auto bg-white px-4 py-6">
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
                    class="mb-4 rounded-lg border border-green-200 bg-green-50 p-4 shadow-sm"
                    role="alert"
                >
                    <div class="flex items-start gap-3">
                        <div class="shrink-0">
                            <svg class="h-5 w-5 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="flex-1">
                            <p class="text-sm font-medium text-green-800">
                                {{ successMessage }}
                            </p>
                        </div>
                        <button
                            @click="showSuccessMessage = false"
                            class="shrink-0 text-green-600 hover:text-green-800"
                        >
                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </div>
                </div>
            </Transition>

            <!-- Page Header -->
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Detalhes da Consulta</h1>
                    <p class="mt-1 text-sm text-gray-600">
                        Paciente: {{ appointment.patient.user.name }} | {{ formattedDate }} às {{ formattedTime }}
                    </p>
                </div>
                <AppointmentStatusBadge :status="appointment.status" />
            </div>
            
            <!-- Ações baseadas no status -->
            <AppointmentActions
                :appointment="appointment"
                :show-reschedule="false"
                :loading-cancel="isCancelling"
                class="mb-6 gap-2"
                @start="startAppointment"
                @cancel="openCancelModal"
            />

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <!-- Left Column (Main Content) -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Informações Gerais Card -->
                    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                        <h3 class="mb-4 text-lg font-bold text-gray-900">Informações Gerais</h3>
                        <div class="grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-2">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Médico</p>
                                <p class="mt-1 text-base text-gray-900">
                                    {{ appointment.doctor.user.name }} ({{ appointment.doctor.specializations[0]?.name || 'Especialista' }})
                                </p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500">CRM</p>
                                <p class="mt-1 text-base text-gray-900">{{ appointment.doctor.crm }}</p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500">Duração</p>
                                <p class="mt-1 text-base text-gray-900">{{ duration }}</p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500">Data e Hora</p>
                                <p class="mt-1 text-base text-gray-900">
                                    {{ formattedDate }}, {{ formattedTime }}
                                </p>
                            </div>
                            <div class="sm:col-span-2">
                                <p class="text-sm font-medium text-gray-500">Código de Acesso</p>
                                <p class="mt-1 text-base text-gray-900">{{ appointment.access_code }}</p>
                            </div>
                            <div v-if="appointment.video_recording_url" class="sm:col-span-2">
                                <p class="text-sm font-medium text-gray-500">Gravação</p>
                                <a :href="appointment.video_recording_url" target="_blank" class="mt-1 text-base text-blue-600 hover:underline">
                                    Ver gravação
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Resumo Clínico / Laudo Médico Card -->
                    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                        <h3 class="mb-4 text-lg font-bold text-gray-900">Observações / Notas</h3>
                        <p v-if="appointment.notes" class="text-base text-gray-700">
                            {{ appointment.notes }}
                        </p>
                        <p v-else class="text-base text-gray-500 italic">
                            Nenhuma observação registrada.
                        </p>
                    </div>
                </div>

                <!-- Right Column (Sidebar) -->
                <div class="lg:col-span-1 space-y-6">
                    <!-- Linha do Tempo Card -->
                    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                        <h3 class="mb-4 text-lg font-bold text-gray-900">Linha do Tempo</h3>
                        <ol v-if="timeline.length > 0" class="relative border-s border-gray-200">
                            <li v-for="(item, index) in timeline" :key="index" class="mb-6 ms-4 last:mb-0">
                                <div class="absolute -start-1.5 mt-1.5 h-3 w-3 rounded-full border border-white bg-primary"></div>
                                <time class="mb-1 text-sm font-normal leading-none text-gray-500">
                                    {{ item.time }}
                                </time>
                                <h4 class="text-base font-semibold text-gray-900">{{ item.event }}</h4>
                                <p class="text-sm font-normal text-gray-700">{{ item.description }}</p>
                            </li>
                        </ol>
                        <p v-else class="text-sm text-gray-500 italic">
                            Nenhum evento registrado ainda.
                        </p>
                    </div>

                    <!-- Ações Finais Card -->
                    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                        <h3 class="mb-4 text-lg font-bold text-gray-900">Ações</h3>
                        <div class="space-y-3">
                            <Link :href="patientRoutes.historyConsultations()" class="block text-base text-blue-600 hover:underline">
                                Voltar ao Histórico de Consultas
                            </Link>
                            <Link :href="patientRoutes.searchConsultations()" class="block text-base text-blue-600 hover:underline">
                                Agendar Nova Consulta
                            </Link>
                        </div>
                    </div>
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

