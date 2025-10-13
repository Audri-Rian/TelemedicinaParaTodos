<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { Calendar, Clock, MapPin } from 'lucide-vue-next';
import * as patientRoutes from '@/routes/patient';
import { onMounted } from 'vue';
import { useRouteGuard } from '@/composables/auth';

interface Doctor {
    id: string;
    user: {
        name: string;
    };
    specializations?: Array<{
        name: string;
    }>;
}

interface Appointment {
    id: string;
    doctor: {
        user: {
            name: string;
        };
        specializations?: Array<{
            name: string;
        }>;
    };
    scheduled_at: string;
    status: string;
}

interface Props {
    appointments?: Appointment[];
    availableDoctors?: Doctor[];
}

const props = withDefaults(defineProps<Props>(), {
    appointments: () => [],
    availableDoctors: () => [],
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
    {
        title: 'Agendamentos',
        href: patientRoutes.appointments().url,
    },
];
</script>

<template>
    <Head title="Meus Agendamentos" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-6 bg-gray-50">
            <!-- Header -->
            <div class="flex flex-col gap-1">
                <h1 class="text-3xl font-bold text-gray-900">Meus Agendamentos</h1>
                <p class="text-gray-600">Gerencie suas consultas médicas</p>
            </div>

            <!-- Main Content -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Lista de Agendamentos -->
                <div class="lg:col-span-2 space-y-6">
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <h2 class="text-xl font-semibold text-gray-900 mb-4">Consultas Agendadas</h2>
                        
                        <div v-if="appointments.length > 0" class="space-y-4">
                            <div v-for="appointment in appointments" 
                                 :key="appointment.id"
                                 class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition">
                                <div class="flex justify-between items-start mb-3">
                                    <div>
                                        <h3 class="font-semibold text-gray-900">Dr. {{ appointment.doctor.user.name }}</h3>
                                        <p v-if="appointment.doctor.specializations?.length" class="text-sm text-gray-600">
                                            {{ appointment.doctor.specializations[0].name }}
                                        </p>
                                    </div>
                                    <span class="px-3 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                        {{ appointment.status }}
                                    </span>
                                </div>
                                
                                <div class="flex items-center space-x-4 text-sm text-gray-600">
                                    <div class="flex items-center space-x-1">
                                        <Calendar class="w-4 h-4" />
                                        <span>{{ appointment.scheduled_at }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div v-else class="text-center py-12">
                            <Calendar class="w-16 h-16 text-gray-300 mx-auto mb-4" />
                            <p class="text-gray-500">Nenhuma consulta agendada</p>
                        </div>
                    </div>
                </div>

                <!-- Médicos Disponíveis -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">Médicos Disponíveis</h2>
                    
                    <div v-if="availableDoctors.length > 0" class="space-y-3">
                        <div v-for="doctor in availableDoctors" 
                             :key="doctor.id"
                             class="border border-gray-200 rounded-lg p-3 hover:bg-gray-50 transition cursor-pointer">
                            <p class="font-medium text-gray-900">Dr. {{ doctor.user.name }}</p>
                            <p v-if="doctor.specializations?.length" class="text-sm text-gray-600">
                                {{ doctor.specializations[0].name }}
                            </p>
                            <button class="mt-2 w-full text-sm bg-blue-600 hover:bg-blue-700 text-white py-2 rounded-md transition">
                                Agendar
                            </button>
                        </div>
                    </div>
                    
                    <div v-else class="text-center py-8">
                        <p class="text-gray-500 text-sm">Nenhum médico disponível no momento</p>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

