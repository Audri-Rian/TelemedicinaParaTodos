<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import * as doctorRoutes from '@/routes/doctor';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight, Plus, Lock } from 'lucide-vue-next';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: doctorRoutes.dashboard().url,
    },
    {
        title: 'Agenda',
        href: doctorRoutes.appointments().url,
    },
];

// Dados do calendário
const currentDate = new Date();
const currentMonth = currentDate.toLocaleDateString('pt-BR', { month: 'long', year: 'numeric' });
const selectedDay = 5;

// Dias da semana
const weekDays = ['DOM', 'SEG', 'TER', 'QUA', 'QUI', 'SEX', 'SÁB'];

// Dias do mês (exemplo para outubro 2024)
const calendarDays = [
    // Semana 1
    [null, null, null, 1, 2, 3, 4],
    // Semana 2
    [5, 6, 7, 8, 9, 10, 11],
    // Semana 3
    [12, 13, 14, 15, 16, 17, 18],
    // Semana 4
    [19, 20, 21, 22, 23, 24, 25],
    // Semana 5
    [26, 27, 28, 29, 30, 31, null]
];
</script>

<template>
    <Head title="Gestão de Agenda" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-6 bg-gray-50">
            <!-- Header -->
            <div class="flex flex-col gap-1">
                <h1 class="text-3xl font-bold text-gray-900">Gestão de Agenda</h1>
                <p class="text-gray-600">Gerencie sua agenda de consultas e horários de trabalho.</p>
            </div>

            <!-- Botões de Ação -->
            <div class="flex justify-end gap-3">
                <button class="bg-yellow-400 hover:bg-yellow-500 text-gray-900 font-semibold py-2 px-4 rounded-lg transition-colors duration-200 flex items-center space-x-2">
                    <Plus class="w-4 h-4" />
                    <span>Adicionar Horário</span>
                </button>
                <button class="bg-gray-200 hover:bg-gray-300 text-gray-900 font-semibold py-2 px-4 rounded-lg transition-colors duration-200 flex items-center space-x-2">
                    <Lock class="w-4 h-4" />
                    <span>Bloquear Horário</span>
                </button>
            </div>

            <!-- Card do Calendário -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 flex-1">
                <!-- Abas de Visualização -->
                <div class="flex gap-8 mb-6">
                    <button class="text-gray-600 hover:text-gray-900 transition-colors duration-200">
                        Semanal
                    </button>
                    <button class="text-gray-900 font-semibold border-b-2 border-yellow-400 pb-1">
                        Mensal
                    </button>
                </div>

                <!-- Navegação do Mês -->
                <div class="flex items-center justify-between mb-6">
                    <button class="p-2 hover:bg-gray-100 rounded-lg transition-colors duration-200">
                        <ChevronLeft class="w-5 h-5 text-gray-600" />
                    </button>
                    
                    <h2 class="text-xl font-bold text-gray-900 capitalize">{{ currentMonth }}</h2>
                    
                    <button class="p-2 hover:bg-gray-100 rounded-lg transition-colors duration-200">
                        <ChevronRight class="w-5 h-5 text-gray-600" />
                    </button>
                </div>

                <!-- Cabeçalho dos Dias da Semana -->
                <div class="grid grid-cols-7 gap-1 mb-4">
                    <div v-for="day in weekDays" :key="day" class="text-center py-2 text-sm font-medium text-gray-700">
                        {{ day }}
                    </div>
                </div>

                <!-- Grid do Calendário -->
                <div class="grid grid-cols-7 gap-1">
                    <div v-for="(week, weekIndex) in calendarDays" :key="weekIndex" class="contents">
                        <div v-for="(day, dayIndex) in week" :key="dayIndex" class="h-12 flex items-center justify-center">
                            <div v-if="day" 
                                 :class="[
                                     'w-8 h-8 flex items-center justify-center rounded-full text-sm font-medium transition-colors duration-200 cursor-pointer',
                                     day === selectedDay 
                                         ? 'bg-yellow-400 text-gray-900' 
                                         : 'text-gray-900 hover:bg-gray-100'
                                 ]">
                                {{ day }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Legenda -->
                <div class="flex items-center gap-6 mt-8 pt-6 border-t border-gray-200">
                    <div class="flex items-center gap-2">
                        <div class="w-3 h-3 rounded-full bg-green-500"></div>
                        <span class="text-sm text-gray-600">Disponíveis</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-3 h-3 rounded-full bg-red-500"></div>
                        <span class="text-sm text-gray-600">Ocupados</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-3 h-3 rounded-full bg-gray-400"></div>
                        <span class="text-sm text-gray-600">Bloqueados</span>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
