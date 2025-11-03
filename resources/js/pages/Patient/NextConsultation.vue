<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import * as patientRoutes from '@/routes/patient';
import { onMounted, ref } from 'vue';
import { useRouteGuard } from '@/composables/auth';
import { Avatar, AvatarImage, AvatarFallback } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Camera, Mic, Wifi, Paperclip, Video, MapPin, FolderOpen, FileText } from 'lucide-vue-next';
import { useInitials } from '@/composables/useInitials';
import CancelConsultationModal from '@/components/modals/CancelConsultationModal.vue';

const { canAccessPatientRoute } = useRouteGuard();
const { getInitials } = useInitials();

// Estado do pré-chat
const preChatMessage = ref('');
const myNotes = ref('');

// Estado da modal
const isCancelModalOpen = ref(false);

// Dados da consulta (estáticos por enquanto)
const consultation = {
    doctor: {
        name: 'Dr. Ricardo Almeida',
        specialty: 'Cardiologia',
        crm: '123456-SP',
        avatar: null
    },
    date: '28 de Outubro, 2024',
    time: '15:30',
    type: 'Telemedicina',
    status: 'Confirmada',
    isVideoCallEnabled: false
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
        title: 'Próxima Consulta',
        href: patientRoutes.nextConsultation().url,
    },
];

// Função para cancelar consulta
const handleCancelConsultation = () => {
    // Aqui será implementada a lógica de cancelamento
    console.log('Consulta cancelada');
};

// Verificar acesso ao montar componente
onMounted(() => {
    canAccessPatientRoute();
});
</script>

<template>
    <Head title="Próxima Consulta" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col overflow-x-auto bg-white px-4 py-6">
            <!-- Header -->
            <div class="mb-6">
                <h1 class="text-3xl font-bold text-gray-900 mb-1">
                    Consulta com {{ consultation.doctor.name }}
                </h1>
                <p class="text-base text-primary">
                    {{ consultation.doctor.specialty }}
                </p>
            </div>

            <!-- Main Content Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Left Column: Main Details -->
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
                        <!-- Doctor Info -->
                        <div class="flex items-start justify-between mb-6 pb-6 border-b border-gray-200">
                            <div>
                                <p class="text-sm text-primary mb-1">Médico</p>
                                <h2 class="text-2xl font-bold text-gray-900 mb-1">
                                    {{ consultation.doctor.name }}
                                </h2>
                                <p class="text-sm text-gray-600">
                                    {{ consultation.doctor.specialty }} - CRM {{ consultation.doctor.crm }}
                                </p>
                            </div>
                            <Avatar class="h-24 w-24 shrink-0">
                                <AvatarImage v-if="consultation.doctor.avatar" :src="consultation.doctor.avatar" />
                                <AvatarFallback class="bg-primary/10 text-primary text-2xl font-semibold">
                                    {{ getInitials(consultation.doctor.name) }}
                                </AvatarFallback>
                            </Avatar>
                        </div>

                        <!-- Consultation Details Grid -->
                        <div class="grid grid-cols-4 gap-4 mb-6 pb-6 border-b border-gray-200">
                            <div>
                                <h3 class="text-xs font-medium text-gray-500 mb-1">Data</h3>
                                <p class="text-sm font-semibold text-gray-900">{{ consultation.date }}</p>
                            </div>
                            <div>
                                <h3 class="text-xs font-medium text-gray-500 mb-1">Horário</h3>
                                <p class="text-sm font-semibold text-gray-900">{{ consultation.time }}</p>
                            </div>
                            <div>
                                <h3 class="text-xs font-medium text-gray-500 mb-1">Tipo</h3>
                                <p class="text-sm font-semibold text-gray-900">{{ consultation.type }}</p>
                            </div>
                            <div>
                                <h3 class="text-xs font-medium text-gray-500 mb-1">Status</h3>
                                <span class="inline-block px-3 py-1 rounded-lg bg-primary text-gray-900 text-xs font-medium">
                                    {{ consultation.status }}
                                </span>
                            </div>
                        </div>

                        <!-- Video Call Button -->
                        <div>
                            <Button
                                :disabled="!consultation.isVideoCallEnabled"
                                class="w-full bg-primary hover:bg-primary/90 text-gray-900 font-semibold py-4 text-base disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                <Video class="h-5 w-5 mr-2" />
                                Entrar na Videochamada
                            </Button>
                            <p class="text-xs text-gray-500 text-center mt-2">
                                O botão será habilitado 10 minutos antes da consulta.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Sidebar -->
                <div class="lg:col-span-1 space-y-6">
                    <!-- Technical Checklist Card -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                        <h3 class="text-base font-bold text-gray-900 mb-4">
                            Checklist Técnico
                        </h3>
                        <div class="space-y-3">
                            <div class="flex items-center gap-3">
                                <Camera class="h-5 w-5 text-primary" />
                                <span class="text-sm text-gray-700">Câmera funcionando</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <Mic class="h-5 w-5 text-primary" />
                                <span class="text-sm text-gray-700">Microfone habilitado</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <Wifi class="h-5 w-5 text-primary" />
                                <span class="text-sm text-gray-700">Conexão estável</span>
                            </div>
                        </div>
                    </div>

                    <!-- Pre-Chat Card -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                        <h3 class="text-base font-bold text-gray-900 mb-3">
                            Pré-Chat com o Médico
                        </h3>
                        <div class="mb-3">
                            <textarea
                                v-model="preChatMessage"
                                placeholder="Envie uma mensagem ou anexo para o Dr. Ricardo..."
                                class="w-full h-24 px-3 py-2 rounded-lg border border-gray-300 resize-none focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent text-sm text-gray-700 placeholder-gray-400"
                            ></textarea>
                        </div>
                        <div class="flex items-center justify-between">
                            <button class="p-2 rounded-lg hover:bg-gray-100 transition-colors">
                                <Paperclip class="h-5 w-5 text-gray-500" />
                            </button>
                            <Button class="bg-primary hover:bg-primary/90 text-gray-900 font-medium px-6">
                                Enviar
                            </Button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Instructions and Notes Section -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
                <!-- Instructions Card -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <h3 class="text-base font-bold text-gray-900 mb-4">
                        Instruções e Pré-Consulta
                    </h3>
                    <div class="space-y-4">
                        <div class="flex items-start gap-3">
                            <MapPin class="h-5 w-5 text-primary mt-0.5 shrink-0" />
                            <span class="text-sm text-gray-700 leading-relaxed">
                                Prepare um ambiente calmo e bem iluminado.
                            </span>
                        </div>
                        <div class="flex items-start gap-3">
                            <FolderOpen class="h-5 w-5 text-primary mt-0.5 shrink-0" />
                            <span class="text-sm text-gray-700 leading-relaxed">
                                Tenha seus exames recentes em mãos, se necessário.
                            </span>
                        </div>
                        <div class="flex items-start gap-3">
                            <FileText class="h-5 w-5 text-primary mt-0.5 shrink-0" />
                            <span class="text-sm text-gray-700 leading-relaxed">
                                Anote suas dúvidas para não esquecer de perguntar ao médico.
                            </span>
                        </div>
                    </div>
                </div>

                <!-- My Notes Card -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <h3 class="text-base font-bold text-gray-900 mb-4">
                        Minhas Anotações
                    </h3>
                    <textarea
                        v-model="myNotes"
                        placeholder="Anote aqui seus sintomas, dúvidas ou informações importantes para a consulta..."
                        class="w-full h-40 px-3 py-2 rounded-lg border border-gray-300 resize-none bg-secondary/50 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent text-sm text-gray-700 placeholder-gray-400"
                    ></textarea>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center justify-end gap-4 mt-6">
                <Button 
                    as-child
                    variant="outline" 
                    class="border border-gray-300 bg-secondary/30 text-gray-900 hover:bg-secondary/50 font-medium px-6"
                >
                    <Link :href="patientRoutes.scheduleConsultation()">
                        Reagendar
                    </Link>
                </Button>
                <button 
                    @click="isCancelModalOpen = true"
                    class="text-base text-red-600 hover:text-red-700 font-medium underline"
                >
                    Cancelar Consulta
                </button>
            </div>
        </div>

        <!-- Cancel Consultation Modal -->
        <CancelConsultationModal
            :is-open="isCancelModalOpen"
            :doctor-name="consultation.doctor.name"
            @close="isCancelModalOpen = false"
            @confirm="handleCancelConsultation"
        />
    </AppLayout>
</template>

