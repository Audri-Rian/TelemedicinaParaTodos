<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import * as doctorRoutes from '@/routes/doctor';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { Search, Calendar, Filter, Plus, MoreHorizontal } from 'lucide-vue-next';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { useInitials } from '@/composables/useInitials';
import { ref } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: doctorRoutes.dashboard().url,
    },
    {
        title: 'Histórico',
        href: '/doctor/history',
    },
];

// Dados mock das consultas
const consultations = ref([
    {
        id: 1,
        patientName: 'Sofia Almeida',
        patientAvatar: 'https://images.unsplash.com/photo-1494790108755-2616b612b786?w=150&h=150&fit=crop&crop=face',
        date: '20/07/2024',
        time: '10:00',
        status: 'Confirmada',
        statusClass: 'bg-primary/20 text-primary'
    },
    {
        id: 2,
        patientName: 'Carlos Pereira',
        patientAvatar: 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=150&h=150&fit=crop&crop=face',
        date: '20/07/2024',
        time: '11:30',
        status: 'Concluída',
        statusClass: 'bg-green-100 text-green-800'
    },
    {
        id: 3,
        patientName: 'Ana Costa',
        patientAvatar: 'https://images.unsplash.com/photo-1438761681033-6461ffad8d80?w=150&h=150&fit=crop&crop=face',
        date: '21/07/2024',
        time: '09:00',
        status: 'Cancelada',
        statusClass: 'bg-red-100 text-red-800'
    },
    {
        id: 4,
        patientName: 'João Silva',
        patientAvatar: 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=150&h=150&fit=crop&crop=face',
        date: '21/07/2024',
        time: '14:00',
        status: 'Confirmada',
        statusClass: 'bg-primary/20 text-primary'
    },
    {
        id: 5,
        patientName: 'Mariana Santos',
        patientAvatar: 'https://images.unsplash.com/photo-1544005313-94ddf0286df2?w=150&h=150&fit=crop&crop=face',
        date: '22/07/2024',
        time: '10:30',
        status: 'Concluída',
        statusClass: 'bg-green-100 text-green-800'
    },
    {
        id: 6,
        patientName: 'Ricardo Oliveira',
        patientAvatar: 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=150&h=150&fit=crop&crop=face',
        date: '22/07/2024',
        time: '15:00',
        status: 'Confirmada',
        statusClass: 'bg-primary/20 text-primary'
    }
]);

const { getInitials } = useInitials();

const searchQuery = ref('');
const totalConsultations = 25;
const currentPage = 1;
const itemsPerPage = 6;
</script>

<template>
    <Head title="Histórico de Consultas" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-6 bg-gray-50">
            <!-- Header -->
            <div class="flex flex-col gap-1">
                <h1 class="text-3xl font-bold text-gray-900">Histórico de Consultas</h1>
                <p class="text-gray-600">Visualize e gerencie o histórico completo de consultas</p>
            </div>

            <!-- Search and Filters -->
            <div class="flex items-center justify-between gap-4">
                <div class="flex items-center gap-4 flex-1">
                    <!-- Search Bar -->
                    <div class="relative flex-1 max-w-md">
                        <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-4 h-4" />
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Buscar paciente..."
                            class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                        />
                    </div>

                    <!-- Filter Buttons -->
                    <button class="flex items-center gap-2 px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                        <Calendar class="w-4 h-4 text-gray-600" />
                        <span class="text-gray-700">Período</span>
                    </button>

                    <button class="flex items-center gap-2 px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                        <Filter class="w-4 h-4 text-gray-600" />
                        <span class="text-gray-700">Status</span>
                    </button>
                </div>

                <!-- New Consultation Button -->
                <button class="flex items-center gap-2 bg-primary hover:bg-primary/90 text-gray-900 font-semibold px-4 py-2 rounded-lg transition-colors">
                    <Plus class="w-4 h-4" />
                    <span>Nova Consulta</span>
                </button>
            </div>

            <!-- Consultations Table -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-primary/10">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    NOME DO PACIENTE
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    DATA
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    HORA
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    STATUS
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    AÇÕES
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="consultation in consultations" :key="consultation.id" class="hover:bg-gray-50 cursor-pointer">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <Link :href="`/doctor/patient/${consultation.id}`" class="flex items-center">
                                        <Avatar class="h-10 w-10 mr-3">
                                            <AvatarImage :src="consultation.patientAvatar" :alt="consultation.patientName" />
                                            <AvatarFallback class="bg-gray-200 text-gray-600">
                                                {{ getInitials(consultation.patientName) }}
                                            </AvatarFallback>
                                        </Avatar>
                                        <div class="text-sm font-medium text-gray-900">
                                            {{ consultation.patientName }}
                                        </div>
                                    </Link>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ consultation.date }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ consultation.time }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span :class="consultation.statusClass" 
                                          class="inline-flex px-2 py-1 text-xs font-semibold rounded-full">
                                        {{ consultation.status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <button class="p-1 hover:bg-gray-100 rounded-full transition-colors" @click.stop>
                                        <MoreHorizontal class="w-4 h-4" />
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pagination -->
            <div class="flex items-center justify-between">
                <div class="text-sm text-gray-700">
                    Mostrando 1 a {{ itemsPerPage }} de {{ totalConsultations }} consultas
                </div>
                <div class="flex items-center gap-2">
                    <button class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50 transition-colors">
                        Anterior
                    </button>
                    <button class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50 transition-colors">
                        Próximo
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
