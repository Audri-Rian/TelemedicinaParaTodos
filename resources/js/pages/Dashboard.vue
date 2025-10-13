<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import * as doctorRoutes from '@/routes/doctor';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { Calendar, History } from 'lucide-vue-next';
import { onMounted } from 'vue';
import { useRouteGuard } from '@/composables/auth';

interface UpcomingAppointment {
    id: string;
    patient_name: string;
    scheduled_at: string;
    status: string;
    status_class: string;
}

interface WeeklyStats {
    total: number;
    period: string;
}

interface MonthlyStats {
    total: number;
    period: string;
}

interface AppointmentData {
    day?: string;
    week?: string;
    count: number;
    max: number;
}

interface Props {
    upcomingAppointments?: UpcomingAppointment[];
    weeklyStats?: WeeklyStats;
    monthlyStats?: MonthlyStats;
    weeklyAppointments?: AppointmentData[];
    monthlyAppointments?: AppointmentData[];
}

const props = withDefaults(defineProps<Props>(), {
    upcomingAppointments: () => [],
    weeklyStats: () => ({ total: 0, period: 'Esta Semana' }),
    monthlyStats: () => ({ total: 0, period: 'Este Mês' }),
    weeklyAppointments: () => [],
    monthlyAppointments: () => []
});

const { canAccessDoctorRoute } = useRouteGuard();

// Verificar acesso ao montar componente
onMounted(() => {
    canAccessDoctorRoute();
});

// Dados fictícios para demonstração
const mockUpcomingAppointments: UpcomingAppointment[] = [
    {
        id: '1',
        patient_name: 'Sofia Almeida',
        scheduled_at: '10:00',
        status: 'Confirmada',
        status_class: 'bg-green-100 text-green-800'
    },
    {
        id: '2',
        patient_name: 'Carlos Mendes',
        scheduled_at: '11:30',
        status: 'Pendente',
        status_class: 'bg-orange-100 text-orange-800'
    },
    {
        id: '3',
        patient_name: 'Ana Pereira',
        scheduled_at: '14:00',
        status: 'Confirmada',
        status_class: 'bg-green-100 text-green-800'
    }
];

const mockWeeklyStats: WeeklyStats = {
    total: 25,
    period: 'Esta Semana'
};

const mockMonthlyStats: MonthlyStats = {
    total: 100,
    period: 'Este Mês'
};

const mockWeeklyAppointments: AppointmentData[] = [
    { day: 'Seg', count: 6, max: 10 },
    { day: 'Ter', count: 8, max: 10 },
    { day: 'Qua', count: 3, max: 10 },
    { day: 'Qui', count: 4, max: 10 },
    { day: 'Sex', count: 4, max: 10 }
];

const mockMonthlyAppointments: AppointmentData[] = [
    { week: 'S1', count: 28, max: 30 },
    { week: 'S2', count: 18, max: 30 },
    { week: 'S3', count: 22, max: 30 },
    { week: 'S4', count: 32, max: 30 }
];

// Usar dados fictícios se não houver dados reais
const upcomingAppointments = props.upcomingAppointments.length > 0 ? props.upcomingAppointments : mockUpcomingAppointments;
const weeklyStats = props.weeklyStats.total > 0 ? props.weeklyStats : mockWeeklyStats;
const monthlyStats = props.monthlyStats.total > 0 ? props.monthlyStats : mockMonthlyStats;
const weeklyAppointments = props.weeklyAppointments.length > 0 ? props.weeklyAppointments : mockWeeklyAppointments;
const monthlyAppointments = props.monthlyAppointments.length > 0 ? props.monthlyAppointments : mockMonthlyAppointments;

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: doctorRoutes.dashboard().url,
    },
];
</script>

<template>
    <Head title="Painel de Controle" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-6 bg-gray-50">
            <!-- Header -->
            <div class="flex flex-col gap-1">
                <h1 class="text-3xl font-bold text-gray-900">Painel de Controle</h1>
                <p class="text-gray-600">Visão geral do seu consultório</p>
            </div>

            <!-- Main Content Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 flex-1">
                <!-- Left Column - Consultas Próximas -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Consultas Próximas -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <h2 class="text-xl font-semibold text-gray-900 mb-4">Consultas Próximas</h2>
                        
                        <div class="overflow-hidden rounded-lg border border-gray-200">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-yellow-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Paciente
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Horário
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Status
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="appointment in upcomingAppointments" :key="appointment.id" class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            {{ appointment.patient_name }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ appointment.scheduled_at }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span :class="appointment.status_class" 
                                                  class="inline-flex px-2 py-1 text-xs font-semibold rounded-full">
                                                {{ appointment.status }}
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Métricas de Desempenho -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Consultas da Semana -->
                        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">
                                Número de Consultas na Semana
                            </h3>
                            <div class="text-4xl font-bold text-gray-900 mb-2">
                                {{ weeklyStats.total }}
                            </div>
                            <p class="text-sm text-gray-500 mb-4">{{ weeklyStats.period }}</p>
                            
                            <!-- Gráfico de barras simples -->
                            <div class="space-y-2">
                                <div v-for="day in weeklyAppointments" :key="day.day" class="flex items-center space-x-2">
                                    <span class="text-xs text-gray-600 w-8">{{ day.day }}</span>
                                    <div class="flex-1 bg-gray-200 rounded-full h-2">
                                        <div 
                                            class="bg-yellow-400 h-2 rounded-full transition-all duration-300"
                                            :style="{ width: `${Math.max(10, (day.count / day.max) * 100)}%` }"
                                        ></div>
                                    </div>
                                    <span class="text-xs text-gray-600 w-4">{{ day.count }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Consultas do Mês -->
                        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">
                                Total de Consultas no Mês
                            </h3>
                            <div class="text-4xl font-bold text-gray-900 mb-2">
                                {{ monthlyStats.total }}
                            </div>
                            <p class="text-sm text-gray-500 mb-4">{{ monthlyStats.period }}</p>
                            
                            <!-- Gráfico de barras simples -->
                            <div class="space-y-2">
                                <div v-for="week in monthlyAppointments" :key="week.week" class="flex items-center space-x-2">
                                    <span class="text-xs text-gray-600 w-8">{{ week.week }}</span>
                                    <div class="flex-1 bg-gray-200 rounded-full h-2">
                                        <div 
                                            class="bg-yellow-400 h-2 rounded-full transition-all duration-300"
                                            :style="{ width: `${Math.max(10, (week.count / week.max) * 100)}%` }"
                                        ></div>
                                    </div>
                                    <span class="text-xs text-gray-600 w-4">{{ week.count }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column - Acessos Rápidos -->
                <div class="space-y-6">
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <h2 class="text-xl font-semibold text-gray-900 mb-4">Acessos Rápidos</h2>
                        
                        <div class="space-y-3">
                            <Link 
                                :href="doctorRoutes.appointments()"
                                class="w-full bg-yellow-400 hover:bg-yellow-500 text-gray-900 font-semibold py-3 px-4 rounded-lg transition-colors duration-200 flex items-center justify-center space-x-2"
                            >
                                <Calendar class="w-5 h-5" />
                                <span>Gerenciar Agenda</span>
                            </Link>
                            
                            <button 
                                class="w-full bg-yellow-100 hover:bg-yellow-200 text-gray-900 font-semibold py-3 px-4 rounded-lg transition-colors duration-200 flex items-center justify-center space-x-2"
                            >
                                <History class="w-5 h-5" />
                                <span>Ver Histórico Completo</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
