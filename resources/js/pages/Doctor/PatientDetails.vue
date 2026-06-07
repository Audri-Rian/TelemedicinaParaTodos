<script setup lang="ts">
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { useInitials } from '@/composables/useInitials';
import AppLayout from '@/layouts/AppLayout.vue';
import * as doctorRoutes from '@/routes/doctor';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Calendar, Clock, FileText, Mail, MapPin, MessageSquare, Phone, User } from 'lucide-vue-next';

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

interface PatientData {
    id: string | number;
    name: string;
    avatar?: string | null;
    email?: string;
    phone?: string | null;
    birthDate?: string | null;
    age?: number | null;
    address?: string | null;
    cpf?: string | null;
    emergencyContact?: string | null;
    medicalHistory?: string[];
    lastConsultation?: string | null;
    totalConsultations?: number;
}

interface Consultation {
    id: string | number;
    date?: string | null;
    time?: string | null;
    status: string;
    statusClass: string;
    notes?: string | null;
}

interface Props {
    patientId?: string | number;
    patient?: PatientData;
    consultations?: Consultation[];
}

const props = withDefaults(defineProps<Props>(), {
    patient: () => ({
        id: '',
        name: '',
        medicalHistory: [],
        totalConsultations: 0,
    }),
    consultations: () => [],
});

const patient = props.patient;
const consultations = props.consultations;

const { getInitials } = useInitials();
</script>

<template>
    <Head title="Detalhes do Paciente" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl bg-gray-50 p-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Link href="/doctor/history" class="flex items-center gap-2 text-gray-600 transition-colors hover:text-gray-900">
                        <ArrowLeft class="h-4 w-4" />
                        <span>Voltar</span>
                    </Link>
                    <div class="h-6 w-px bg-gray-300"></div>
                    <h1 class="text-3xl font-bold text-gray-900">Detalhes do Paciente</h1>
                </div>

                <div class="flex items-center gap-3">
                    <button
                        class="flex items-center gap-2 rounded-lg bg-primary px-4 py-2 font-semibold text-gray-900 transition-colors hover:bg-primary/90"
                    >
                        <MessageSquare class="h-4 w-4" />
                        <span>Nova Consulta</span>
                    </button>
                </div>
            </div>

            <!-- Patient Info Card -->
            <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                <div class="flex items-start gap-6">
                    <!-- Avatar -->
                    <Avatar class="h-20 w-20">
                        <AvatarImage :src="patient.avatar" :alt="patient.name" />
                        <AvatarFallback class="bg-gray-200 text-lg text-gray-600">
                            {{ getInitials(patient.name) }}
                        </AvatarFallback>
                    </Avatar>

                    <!-- Patient Info -->
                    <div class="flex-1">
                        <div class="flex items-start justify-between">
                            <div>
                                <h2 class="text-2xl font-bold text-gray-900">{{ patient.name }}</h2>
                                <p class="mt-1 text-gray-600">{{ patient.age }} anos • {{ patient.birthDate }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm text-gray-500">Última consulta</p>
                                <p class="font-semibold text-gray-900">{{ patient.lastConsultation }}</p>
                            </div>
                        </div>

                        <!-- Contact Info -->
                        <div class="mt-6 grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div class="flex items-center gap-3">
                                <Mail class="h-4 w-4 text-gray-400" />
                                <span class="text-sm text-gray-600">{{ patient.email }}</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <Phone class="h-4 w-4 text-gray-400" />
                                <span class="text-sm text-gray-600">{{ patient.phone }}</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <MapPin class="h-4 w-4 text-gray-400" />
                                <span class="text-sm text-gray-600">{{ patient.address }}</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <User class="h-4 w-4 text-gray-400" />
                                <span class="text-sm text-gray-600">CPF: {{ patient.cpf }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Medical History and Consultations -->
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <!-- Medical History -->
                <div class="lg:col-span-1">
                    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                        <h3 class="mb-4 flex items-center gap-2 text-lg font-semibold text-gray-900">
                            <FileText class="h-5 w-5" />
                            Histórico Médico
                        </h3>
                        <div class="space-y-3">
                            <div v-for="condition in patient.medicalHistory" :key="condition" class="flex items-center gap-2">
                                <div class="h-2 w-2 rounded-full bg-red-500"></div>
                                <span class="text-sm text-gray-700">{{ condition }}</span>
                            </div>
                        </div>

                        <div class="mt-6 border-t border-gray-200 pt-4">
                            <h4 class="mb-2 font-medium text-gray-900">Contato de Emergência</h4>
                            <p class="text-sm text-gray-600">{{ patient.emergencyContact }}</p>
                        </div>
                    </div>
                </div>

                <!-- Recent Consultations -->
                <div class="lg:col-span-2">
                    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                        <h3 class="mb-4 flex items-center gap-2 text-lg font-semibold text-gray-900">
                            <Calendar class="h-5 w-5" />
                            Consultas Recentes
                        </h3>

                        <div class="space-y-4">
                            <div
                                v-for="consultation in consultations"
                                :key="consultation.id"
                                class="flex items-center justify-between rounded-lg border border-gray-200 p-4 transition-colors hover:bg-gray-50"
                            >
                                <div class="flex items-center gap-4">
                                    <div class="text-center">
                                        <div class="text-sm font-medium text-gray-900">{{ consultation.date }}</div>
                                        <div class="flex items-center gap-1 text-xs text-gray-500">
                                            <Clock class="h-3 w-3" />
                                            {{ consultation.time }}
                                        </div>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-600">{{ consultation.notes }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3">
                                    <span :class="consultation.statusClass" class="inline-flex rounded-full px-2 py-1 text-xs font-semibold">
                                        {{ consultation.status }}
                                    </span>
                                    <button class="rounded-full p-1 transition-colors hover:bg-gray-100">
                                        <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"
                                            ></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 text-center">
                            <button class="text-sm font-medium text-primary hover:text-primary/80">
                                Ver todas as consultas ({{ patient.totalConsultations }})
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
