<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight, Video, Building2, Star, Clock, MapPin } from 'lucide-vue-next';
import * as patientRoutes from '@/routes/patient';
import { onMounted, ref } from 'vue';
import { useRouteGuard } from '@/composables/auth';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Avatar, AvatarImage, AvatarFallback } from '@/components/ui/avatar';
import { useInitials } from '@/composables/useInitials';
import { Link } from '@inertiajs/vue3';

const { canAccessPatientRoute } = useRouteGuard();
const { getInitials } = useInitials();

// Estado do formulário
const consultationType = ref<'online' | 'presential'>('online');
const selectedDate = ref(9);
const selectedTime = ref('09:30');
const currentMonth = ref('Outubro 2024');

// Horários disponíveis
const availableTimes = [
    '09:00', '09:30', '10:00', '10:30', '11:00', '11:30',
    '14:00', '14:30'
];

// Dados do médico (estático)
const selectedDoctor = {
    name: 'Dr. João Silva',
    specialty: 'Cardiologista',
    avatar: null,
    rating: 4.9,
    reviewsCount: 156,
    experience: '10 anos',
    crm: 'CRM 12345',
    description: 'Especialista em cardiologia com vasta experiência em procedimentos cardíacos e prevenção cardiovascular.',
    location: 'São Paulo, SP',
};

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
        title: 'Pesquisar Médicos',
        href: patientRoutes.searchConsultations().url,
    },
    {
        title: 'Agendar Consulta',
        href: patientRoutes.scheduleConsultation().url,
    },
];
</script>

<template>
    <Head title="Agendar Consulta" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-3 overflow-x-auto bg-white px-4 py-3">
            <!-- Header -->
            <div class="flex flex-col gap-1 items-center text-center">
                <h1 class="text-2xl font-bold text-gray-900">Agendar Consulta</h1>
                <p class="text-sm text-gray-600">Selecione o tipo de consulta e escolha o melhor horário para você.</p>
            </div>

            <!-- Barra de Progresso -->
            <div class="flex justify-center items-center gap-6 max-w-4xl mx-auto w-full">
                <div class="flex flex-col items-center gap-2">
                    <div class="w-full h-1 bg-primary rounded-full"></div>
                    <span class="text-sm font-medium text-gray-900">Informações</span>
                </div>
                <div class="flex flex-col items-center gap-2 flex-1">
                    <div class="w-full h-1 bg-primary rounded-full"></div>
                    <span class="text-sm font-medium text-primary">Horário</span>
                </div>
                <div class="flex flex-col items-center gap-2">
                    <div class="w-full h-1 bg-gray-200 rounded-full"></div>
                    <span class="text-sm font-medium text-gray-400">Pagamento</span>
                </div>
            </div>

            <!-- Conteúdo Principal -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-3 w-full">
                <!-- Painel Esquerdo: Médico Selecionado -->
                <Card class="bg-secondary/30">
                    <CardHeader class="pb-3">
                        <CardTitle class="text-base font-bold text-gray-900">Médico Selecionado</CardTitle>
                    </CardHeader>
                    <CardContent class="flex flex-col gap-3 pt-0">
                        <!-- Avatar e Nome -->
                        <div class="flex flex-col items-center gap-2">
                            <Avatar class="h-16 w-16">
                                <AvatarImage :src="selectedDoctor.avatar" />
                                <AvatarFallback class="bg-primary/10 text-primary text-lg font-semibold">
                                    {{ getInitials(selectedDoctor.name) }}
                                </AvatarFallback>
                            </Avatar>
                            <div class="text-center">
                                <h3 class="font-bold text-base text-gray-900 mb-0.5">{{ selectedDoctor.name }}</h3>
                                <p class="text-xs text-gray-600 mb-1">{{ selectedDoctor.specialty }}</p>
                                <p class="text-xs text-gray-500">{{ selectedDoctor.crm }}</p>
                            </div>
                        </div>

                        <!-- Avaliação -->
                        <div class="flex items-center justify-center gap-1 border-t border-gray-200 pt-2">
                            <Star class="h-4 w-4 text-primary fill-primary" />
                            <span class="text-sm font-semibold text-gray-900">{{ selectedDoctor.rating }}</span>
                            <span class="text-xs text-gray-500">({{ selectedDoctor.reviewsCount }} avaliações)</span>
                        </div>

                        <!-- Informações Adicionais -->
                        <div class="space-y-2 border-t border-gray-200 pt-2">
                            <div class="flex items-center gap-2 text-xs text-gray-600">
                                <Clock class="h-3 w-3 text-gray-500" />
                                <span>{{ selectedDoctor.experience }} de experiência</span>
                            </div>
                            <div class="flex items-center gap-2 text-xs text-gray-600">
                                <MapPin class="h-3 w-3 text-gray-500" />
                                <span>{{ selectedDoctor.location }}</span>
                            </div>
                        </div>

                        <!-- Descrição -->
                        <div class="border-t border-gray-200 pt-2">
                            <p class="text-xs text-gray-600 leading-relaxed">
                                {{ selectedDoctor.description }}
                            </p>
                        </div>

                        <!-- Botão Trocar Médico -->
                        <Button variant="outline" class="w-full bg-secondary hover:bg-secondary/80 text-gray-900 text-xs py-1.5 h-8 mt-1">
                            Trocar médico
                        </Button>
                    </CardContent>
                </Card>

                <!-- Painel Direito: Detalhes da Consulta -->
                <div class="lg:col-span-2 space-y-3">
                    <!-- Tipo de Consulta -->
                    <div class="space-y-2">
                        <h2 class="text-lg font-bold text-gray-900">Selecione o Tipo de Consulta</h2>
                        <div class="grid grid-cols-2 gap-3">
                            <!-- Consulta Online -->
                            <button
                                @click="consultationType = 'online'"
                                :class="[
                                    'flex flex-col items-center justify-center gap-2 p-4 rounded-lg border-2 transition-all',
                                    consultationType === 'online'
                                        ? 'bg-primary/10 border-primary'
                                        : 'bg-secondary/30 border-gray-200 hover:border-gray-300'
                                ]"
                            >
                                <Video 
                                    :class="[
                                        'h-6 w-6',
                                        consultationType === 'online' ? 'text-primary' : 'text-gray-600'
                                    ]"
                                />
                                <div class="text-center">
                                    <p class="font-semibold text-sm text-gray-900">Consulta Online</p>
                                    <p class="text-xs text-gray-600">via vídeo</p>
                                </div>
                            </button>

                            <!-- Consulta Presencial -->
                            <button
                                @click="consultationType = 'presential'"
                                :class="[
                                    'flex flex-col items-center justify-center gap-2 p-4 rounded-lg border-2 transition-all',
                                    consultationType === 'presential'
                                        ? 'bg-primary/10 border-primary'
                                        : 'bg-secondary/30 border-gray-200 hover:border-gray-300'
                                ]"
                            >
                                <Building2 
                                    :class="[
                                        'h-6 w-6',
                                        consultationType === 'presential' ? 'text-primary' : 'text-gray-600'
                                    ]"
                                />
                                <div class="text-center">
                                    <p class="font-semibold text-sm text-gray-900">Consulta Presencial</p>
                                    <p class="text-xs text-gray-600">no consultório</p>
                                </div>
                            </button>
                        </div>
                    </div>

                    <!-- Data e Horário -->
                    <div class="space-y-2">
                        <h2 class="text-lg font-bold text-gray-900">Selecione a Data e Horário</h2>
                        
                        <!-- Calendário -->
                        <Card class="bg-secondary/30">
                            <CardContent class="p-4">
                                <!-- Navegação do Mês -->
                                <div class="flex items-center justify-between mb-3">
                                    <Button variant="ghost" size="icon" class="h-8 w-8">
                                        <ChevronLeft class="h-4 w-4" />
                                    </Button>
                                    <span class="font-semibold text-sm text-gray-900">{{ currentMonth }}</span>
                                    <Button variant="ghost" size="icon" class="h-7 w-7">
                                        <ChevronRight class="h-3 w-3" />
                                    </Button>
                                </div>

                                <!-- Dias da Semana -->
                                <div class="grid grid-cols-7 gap-1 mb-2">
                                    <div class="text-center text-xs font-medium text-gray-600">D</div>
                                    <div class="text-center text-xs font-medium text-gray-600">S</div>
                                    <div class="text-center text-xs font-medium text-gray-600">T</div>
                                    <div class="text-center text-xs font-medium text-gray-600">Q</div>
                                    <div class="text-center text-xs font-medium text-gray-600">Q</div>
                                    <div class="text-center text-xs font-medium text-gray-600">S</div>
                                    <div class="text-center text-xs font-medium text-gray-600">S</div>
                                </div>

                                <!-- Dias do Mês -->
                                <div class="grid grid-cols-7 gap-1">
                                    <!-- Dias do mês anterior (desabilitados) -->
                                    <div class="text-center text-xs text-gray-300 py-1">29</div>
                                    <div class="text-center text-xs text-gray-300 py-1">30</div>
                                    
                                    <!-- Dias do mês atual (1-31) -->
                                    <div 
                                        v-for="day in 31" 
                                        :key="day"
                                        @click="selectedDate = day"
                                        :class="[
                                            'text-center text-xs py-1 rounded cursor-pointer transition-colors',
                                            day === selectedDate
                                                ? 'bg-primary text-white font-semibold'
                                                : 'text-gray-900 hover:bg-gray-100'
                                        ]"
                                    >
                                        {{ day }}
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Horários Disponíveis -->
                        <div class="space-y-2">
                            <h3 class="text-xs font-medium text-gray-900">
                                Horários disponíveis para Quarta, 9 de Outubro
                            </h3>
                            <div class="grid grid-cols-4 gap-2">
                                <button
                                    v-for="time in availableTimes"
                                    :key="time"
                                    @click="selectedTime = time"
                                    :class="[
                                        'py-1.5 px-3 rounded-lg text-xs font-medium transition-colors',
                                        selectedTime === time
                                            ? 'bg-primary text-white'
                                            : 'bg-secondary/30 text-gray-900 hover:bg-secondary/50'
                                    ]"
                                >
                                    {{ time }}
                                </button>
                            </div>
                            <p class="text-xs text-gray-500">
                                Todos os horários são mostrados em seu fuso horário local (GMT-3). Duração da consulta: 30 minutos.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Botões de Ação -->
            <div class="flex justify-end gap-3 w-full pt-2">
                <Button as-child variant="outline" class="bg-secondary hover:bg-secondary/80 text-gray-900 h-9 px-6">
                    <Link :href="patientRoutes.searchConsultations()">
                        Voltar
                    </Link>
                </Button>
                <Button class="bg-primary hover:bg-primary/90 text-gray-900 font-semibold px-6 h-9">
                    Confirmar Agendamento
                </Button>
            </div>
        </div>
    </AppLayout>
</template>
