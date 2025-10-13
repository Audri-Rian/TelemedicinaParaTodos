<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { Calendar, FileText, Activity } from 'lucide-vue-next';
import * as patientRoutes from '@/routes/patient';
import { onMounted } from 'vue';
import { useRouteGuard } from '@/composables/auth';

interface UpcomingAppointment {
    id: string;
    doctor_name: string;
    scheduled_at: string;
    status: string;
    status_class: string;
}

interface RecentAppointment {
    id: string;
    doctor_name: string;
    scheduled_at: string;
    status: string;
}

interface Stats {
    total: number;
    completed: number;
}

interface Props {
    upcomingAppointments?: UpcomingAppointment[];
    recentAppointments?: RecentAppointment[];
    stats?: Stats;
}

const props = withDefaults(defineProps<Props>(), {
    upcomingAppointments: () => [],
    recentAppointments: () => [],
    stats: () => ({ total: 0, completed: 0 }),
});

const { canAccessPatientRoute } = useRouteGuard();

// Verificar acesso ao montar componente
onMounted(() => {
    canAccessPatientRoute();
});

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: patientRoutes.dashboard().url,
    },
];
</script>

<template>
    <Head title="Meu Painel" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-6 bg-gray-50">
            <!-- Header -->
            <div class="flex flex-col gap-1">
                <h1 class="text-3xl font-bold text-gray-900">Meu Painel</h1>
                <p class="text-gray-600">Bem-vindo ao seu painel de saúde</p>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="text-lg font-semibold text-gray-900">
                            Total de Consultas
                        </h3>
                        <Calendar class="w-8 h-8 text-blue-600" />
                    </div>
                    <div class="text-4xl font-bold text-blue-600">
                        {{ stats.total }}
                    </div>
                    <p class="text-sm text-gray-500 mt-1">Consultas agendadas</p>
                </div>
                
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="text-lg font-semibold text-gray-900">
                            Consultas Realizadas
                        </h3>
                        <Activity class="w-8 h-8 text-green-600" />
                    </div>
                    <div class="text-4xl font-bold text-green-600">
                        {{ stats.completed }}
                    </div>
                    <p class="text-sm text-gray-500 mt-1">Histórico completo</p>
                </div>
            </div>

            <!-- Main Content Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Upcoming Appointments -->
                <div class="lg:col-span-2 bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">Próximas Consultas</h2>
                    
                    <div v-if="upcomingAppointments.length > 0" class="space-y-3">
                        <div v-for="appointment in upcomingAppointments" 
                             :key="appointment.id"
                             class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50 transition">
                            <div class="flex justify-between items-start">
                                <div class="flex-1">
                                    <p class="font-semibold text-gray-900 mb-1">Dr. {{ appointment.doctor_name }}</p>
                                    <p class="text-sm text-gray-600">{{ appointment.scheduled_at }}</p>
                                </div>
                                <span :class="appointment.status_class"
                                      class="px-3 py-1 text-xs font-semibold rounded-full whitespace-nowrap">
                                    {{ appointment.status }}
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <div v-else class="text-center py-12">
                        <Calendar class="w-16 h-16 text-gray-300 mx-auto mb-4" />
                        <p class="text-gray-500">Nenhuma consulta agendada</p>
                        <p class="text-sm text-gray-400 mt-1">Agende sua próxima consulta</p>
                    </div>
                    
                    <Link :href="patientRoutes.appointments()"
                          class="mt-6 w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-4 rounded-lg transition flex items-center justify-center space-x-2">
                        <Calendar class="w-5 h-5" />
                        <span>Agendar Consulta</span>
                    </Link>
                </div>

                <!-- Quick Actions -->
                <div class="space-y-4">
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <h2 class="text-xl font-semibold text-gray-900 mb-4">Ações Rápidas</h2>
                        
                        <div class="space-y-3">
                            <Link :href="patientRoutes.healthRecords()"
                                  class="w-full bg-green-50 hover:bg-green-100 text-green-900 font-semibold py-3 px-4 rounded-lg transition flex items-center space-x-3">
                                <FileText class="w-5 h-5" />
                                <span>Meu Prontuário</span>
                            </Link>
                            
                            <Link :href="patientRoutes.appointments()"
                                  class="w-full bg-purple-50 hover:bg-purple-100 text-purple-900 font-semibold py-3 px-4 rounded-lg transition flex items-center space-x-3">
                                <Calendar class="w-5 h-5" />
                                <span>Ver Agenda</span>
                            </Link>
                        </div>
                    </div>

                    <!-- Recent Consultations -->
                    <div v-if="recentAppointments.length > 0" class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Consultas Recentes</h3>
                        
                        <div class="space-y-2">
                            <div v-for="appointment in recentAppointments" 
                                 :key="appointment.id"
                                 class="border-l-4 border-green-500 pl-3 py-2">
                                <p class="text-sm font-medium text-gray-900">Dr. {{ appointment.doctor_name }}</p>
                                <p class="text-xs text-gray-500">{{ appointment.scheduled_at }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

