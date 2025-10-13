<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import * as doctorRoutes from '@/routes/doctor';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Calendar, Clock, Phone, Mail, MapPin, User, FileText, MessageSquare } from 'lucide-vue-next';
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
    {
        title: 'Detalhes do Paciente',
        href: '#',
    },
];

// Dados mock do paciente
const patient = ref({
    id: 1,
    name: 'Sofia Almeida',
    avatar: 'https://images.unsplash.com/photo-1494790108755-2616b612b786?w=150&h=150&fit=crop&crop=face',
    email: 'sofia.almeida@email.com',
    phone: '(11) 99999-9999',
    birthDate: '15/03/1990',
    age: 34,
    address: 'Rua das Flores, 123 - São Paulo, SP',
    cpf: '123.456.789-00',
    emergencyContact: 'Carlos Almeida - (11) 98888-8888',
    medicalHistory: [
        'Hipertensão arterial',
        'Diabetes tipo 2',
        'Alergia a penicilina'
    ],
    lastConsultation: '20/07/2024',
    totalConsultations: 12
});

const consultations = ref([
    {
        id: 1,
        date: '20/07/2024',
        time: '10:00',
        status: 'Confirmada',
        statusClass: 'bg-primary/20 text-primary',
        notes: 'Consulta de rotina para controle da hipertensão'
    },
    {
        id: 2,
        date: '15/06/2024',
        time: '14:30',
        status: 'Concluída',
        statusClass: 'bg-green-100 text-green-800',
        notes: 'Ajuste na medicação para diabetes'
    },
    {
        id: 3,
        date: '10/05/2024',
        time: '09:15',
        status: 'Concluída',
        statusClass: 'bg-green-100 text-green-800',
        notes: 'Exame de rotina e atualização do prontuário'
    }
]);

const { getInitials } = useInitials();
</script>

<template>
    <Head title="Detalhes do Paciente" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-6 bg-gray-50">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Link 
                        href="/doctor/history"
                        class="flex items-center gap-2 text-gray-600 hover:text-gray-900 transition-colors"
                    >
                        <ArrowLeft class="w-4 h-4" />
                        <span>Voltar</span>
                    </Link>
                    <div class="h-6 w-px bg-gray-300"></div>
                    <h1 class="text-3xl font-bold text-gray-900">Detalhes do Paciente</h1>
                </div>
                
                <div class="flex items-center gap-3">
                    <button class="flex items-center gap-2 bg-primary hover:bg-primary/90 text-gray-900 font-semibold px-4 py-2 rounded-lg transition-colors">
                        <MessageSquare class="w-4 h-4" />
                        <span>Nova Consulta</span>
                    </button>
                </div>
            </div>

            <!-- Patient Info Card -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex items-start gap-6">
                    <!-- Avatar -->
                    <Avatar class="h-20 w-20">
                        <AvatarImage :src="patient.avatar" :alt="patient.name" />
                        <AvatarFallback class="bg-gray-200 text-gray-600 text-lg">
                            {{ getInitials(patient.name) }}
                        </AvatarFallback>
                    </Avatar>

                    <!-- Patient Info -->
                    <div class="flex-1">
                        <div class="flex items-start justify-between">
                            <div>
                                <h2 class="text-2xl font-bold text-gray-900">{{ patient.name }}</h2>
                                <p class="text-gray-600 mt-1">{{ patient.age }} anos • {{ patient.birthDate }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm text-gray-500">Última consulta</p>
                                <p class="font-semibold text-gray-900">{{ patient.lastConsultation }}</p>
                            </div>
                        </div>

                        <!-- Contact Info -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-6">
                            <div class="flex items-center gap-3">
                                <Mail class="w-4 h-4 text-gray-400" />
                                <span class="text-sm text-gray-600">{{ patient.email }}</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <Phone class="w-4 h-4 text-gray-400" />
                                <span class="text-sm text-gray-600">{{ patient.phone }}</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <MapPin class="w-4 h-4 text-gray-400" />
                                <span class="text-sm text-gray-600">{{ patient.address }}</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <User class="w-4 h-4 text-gray-400" />
                                <span class="text-sm text-gray-600">CPF: {{ patient.cpf }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Medical History and Consultations -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Medical History -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                            <FileText class="w-5 h-5" />
                            Histórico Médico
                        </h3>
                        <div class="space-y-3">
                            <div v-for="condition in patient.medicalHistory" :key="condition" class="flex items-center gap-2">
                                <div class="w-2 h-2 bg-red-500 rounded-full"></div>
                                <span class="text-sm text-gray-700">{{ condition }}</span>
                            </div>
                        </div>

                        <div class="mt-6 pt-4 border-t border-gray-200">
                            <h4 class="font-medium text-gray-900 mb-2">Contato de Emergência</h4>
                            <p class="text-sm text-gray-600">{{ patient.emergencyContact }}</p>
                        </div>
                    </div>
                </div>

                <!-- Recent Consultations -->
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                            <Calendar class="w-5 h-5" />
                            Consultas Recentes
                        </h3>
                        
                        <div class="space-y-4">
                            <div 
                                v-for="consultation in consultations" 
                                :key="consultation.id"
                                class="flex items-center justify-between p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors"
                            >
                                <div class="flex items-center gap-4">
                                    <div class="text-center">
                                        <div class="text-sm font-medium text-gray-900">{{ consultation.date }}</div>
                                        <div class="text-xs text-gray-500 flex items-center gap-1">
                                            <Clock class="w-3 h-3" />
                                            {{ consultation.time }}
                                        </div>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-600">{{ consultation.notes }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3">
                                    <span :class="consultation.statusClass" 
                                          class="inline-flex px-2 py-1 text-xs font-semibold rounded-full">
                                        {{ consultation.status }}
                                    </span>
                                    <button class="p-1 hover:bg-gray-100 rounded-full transition-colors">
                                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 text-center">
                            <button class="text-sm text-primary hover:text-primary/80 font-medium">
                                Ver todas as consultas ({{ patient.totalConsultations }})
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
