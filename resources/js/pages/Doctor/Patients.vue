<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import * as doctorRoutes from '@/routes/doctor';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { Search } from 'lucide-vue-next';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { useInitials } from '@/composables/useInitials';
import { ref } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: doctorRoutes.dashboard().url,
    },
    {
        title: 'Pacientes',
        href: '/doctor/patients',
    },
];

// Dados mock dos pacientes
const patients = ref([
    {
        id: 1,
        name: 'Sofia Almeida',
        avatar: 'https://images.unsplash.com/photo-1494790108755-2616b612b786?w=150&h=150&fit=crop&crop=face',
        age: 35,
        lastConsultation: '15/03/2024',
        status: 'Ativo',
        statusClass: 'bg-green-100 text-green-800'
    },
    {
        id: 2,
        name: 'Carlos Mendes',
        avatar: 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=150&h=150&fit=crop&crop=face',
        age: 50,
        lastConsultation: '20/02/2024',
        status: 'Ativo',
        statusClass: 'bg-green-100 text-green-800'
    },
    {
        id: 3,
        name: 'Isabela Pereira',
        avatar: 'https://images.unsplash.com/photo-1438761681033-6461ffad8d80?w=150&h=150&fit=crop&crop=face',
        age: 28,
        lastConsultation: '10/01/2024',
        status: 'Inativo',
        statusClass: 'bg-red-100 text-red-800'
    },
    {
        id: 4,
        name: 'Ricardo Santos',
        avatar: 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=150&h=150&fit=crop&crop=face',
        age: 42,
        lastConsultation: '05/04/2024',
        status: 'Ativo',
        statusClass: 'bg-green-100 text-green-800'
    },
    {
        id: 5,
        name: 'Mariana Costa',
        avatar: 'https://images.unsplash.com/photo-1544005313-94ddf0286df2?w=150&h=150&fit=crop&crop=face',
        age: 60,
        lastConsultation: '22/03/2024',
        status: 'Ativo',
        statusClass: 'bg-green-100 text-green-800'
    },
    {
        id: 6,
        name: 'Pedro Oliveira',
        avatar: 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=150&h=150&fit=crop&crop=face',
        age: 30,
        lastConsultation: '18/02/2024',
        status: 'Inativo',
        statusClass: 'bg-red-100 text-red-800'
    }
]);

const searchQuery = ref('');
const selectedFilter = ref('todos');

const { getInitials } = useInitials();

// Filtrar pacientes baseado na busca e filtro selecionado
const filteredPatients = ref(patients.value);

const filterPatients = () => {
    let filtered = patients.value;
    
    // Filtrar por texto de busca
    if (searchQuery.value) {
        filtered = filtered.filter(patient => 
            patient.name.toLowerCase().includes(searchQuery.value.toLowerCase())
        );
    }
    
    // Filtrar por status
    if (selectedFilter.value !== 'todos') {
        filtered = filtered.filter(patient => 
            patient.status.toLowerCase() === selectedFilter.value
        );
    }
    
    filteredPatients.value = filtered;
};

// Observar mudanças na busca
const handleSearch = () => {
    filterPatients();
};

// Observar mudanças no filtro
const handleFilterChange = (filter: string) => {
    selectedFilter.value = filter;
    filterPatients();
};
</script>

<template>
    <Head title="Resumo de Pacientes" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-6 bg-gray-50">
            <!-- Header -->
            <div class="flex flex-col gap-1">
                <h1 class="text-3xl font-bold text-gray-900">Resumo de Pacientes</h1>
                <p class="text-gray-600">Gerencie e visualize as informações dos seus pacientes.</p>
            </div>

            <!-- Search and Filters -->
            <div class="flex items-center justify-between gap-4">
                <!-- Search Bar -->
                <div class="relative flex-1 max-w-md">
                    <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-4 h-4" />
                    <input
                        v-model="searchQuery"
                        @input="handleSearch"
                        type="text"
                        placeholder="Pesquisar pacientes por nome..."
                        class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                    />
                </div>

                <!-- Filter Buttons -->
                <div class="flex items-center gap-2">
                    <button 
                        @click="handleFilterChange('todos')"
                        :class="[
                            'px-4 py-2 rounded-lg font-medium transition-colors',
                            selectedFilter === 'todos' 
                                ? 'bg-primary text-gray-900' 
                                : 'bg-gray-200 text-gray-700 hover:bg-gray-300'
                        ]"
                    >
                        Todos
                    </button>
                    <button 
                        @click="handleFilterChange('ativo')"
                        :class="[
                            'px-4 py-2 rounded-lg font-medium transition-colors',
                            selectedFilter === 'ativo' 
                                ? 'bg-primary text-gray-900' 
                                : 'bg-gray-200 text-gray-700 hover:bg-gray-300'
                        ]"
                    >
                        Ativos
                    </button>
                    <button 
                        @click="handleFilterChange('inativo')"
                        :class="[
                            'px-4 py-2 rounded-lg font-medium transition-colors',
                            selectedFilter === 'inativo' 
                                ? 'bg-primary text-gray-900' 
                                : 'bg-gray-200 text-gray-700 hover:bg-gray-300'
                        ]"
                    >
                        Inativos
                    </button>
                </div>
            </div>

            <!-- Patients Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div 
                    v-for="patient in filteredPatients" 
                    :key="patient.id"
                    class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow"
                >
                    <!-- Patient Info -->
                    <div class="flex items-start gap-4 mb-4">
                        <Avatar class="h-12 w-12">
                            <AvatarImage :src="patient.avatar" :alt="patient.name" />
                            <AvatarFallback class="bg-gray-200 text-gray-600">
                                {{ getInitials(patient.name) }}
                            </AvatarFallback>
                        </Avatar>
                        
                        <div class="flex-1">
                            <h3 class="font-semibold text-gray-900 text-lg">{{ patient.name }}</h3>
                            <p class="text-gray-600 text-sm">{{ patient.age }} anos</p>
                        </div>
                    </div>

                    <!-- Last Consultation -->
                    <div class="mb-4">
                        <p class="text-gray-600 text-sm">
                            Última Consulta: <span class="font-medium">{{ patient.lastConsultation }}</span>
                        </p>
                    </div>

                    <!-- Status -->
                    <div class="mb-6">
                        <span :class="patient.statusClass" 
                              class="inline-flex px-2 py-1 text-xs font-semibold rounded-full">
                            {{ patient.status }}
                        </span>
                    </div>

                    <!-- View Details Button -->
                    <Link 
                        :href="`/doctor/patient/${patient.id}`"
                        class="w-full bg-primary hover:bg-primary/90 text-gray-900 font-semibold py-2 px-4 rounded-lg transition-colors text-center block"
                    >
                        Ver Detalhes
                    </Link>
                </div>
            </div>

            <!-- Empty State -->
            <div v-if="filteredPatients.length === 0" class="text-center py-12">
                <div class="text-gray-400 mb-4">
                    <svg class="w-16 h-16 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-medium text-gray-900 mb-2">Nenhum paciente encontrado</h3>
                <p class="text-gray-600">Tente ajustar os filtros ou termo de busca.</p>
            </div>
        </div>
    </AppLayout>
</template>
