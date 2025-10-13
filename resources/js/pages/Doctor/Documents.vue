<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import * as doctorRoutes from '@/routes/doctor';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { ChevronDown, Search, Plus, Trash2 } from 'lucide-vue-next';
import { ref } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: doctorRoutes.dashboard().url,
    },
    {
        title: 'Documentos',
        href: '/doctor/documents',
    },
];

// Dados reativos
const selectedTab = ref('prescricao');
const selectedPatient = ref('');
const medicationSearch = ref('');

// Lista de pacientes mock
const patients = ref([
    'Ana Beatriz Silva',
    'Carlos Mendes',
    'Sofia Almeida',
    'João Silva',
    'Mariana Costa',
    'Ricardo Santos'
]);

// Lista de medicamentos mock
const medications = ref([
    {
        id: 1,
        name: 'Ibuprofeno',
        dosage: '200mg',
        route: 'Oral',
        instructions: 'Tomar 1 comp. a cada 6 horas'
    },
    {
        id: 2,
        name: 'Paracetamol',
        dosage: '500mg',
        route: 'Oral',
        instructions: 'Tomar 1 comp. a cada 8 horas'
    },
    {
        id: 3,
        name: 'Amoxicilina',
        dosage: '500mg',
        route: 'Oral',
        instructions: 'Tomar 1 cápsula a cada 12 horas'
    }
]);

// Medicamentos selecionados para a prescrição
const selectedMedications = ref([
    {
        id: 1,
        name: 'Ibuprofeno',
        dosage: '200mg',
        route: 'Oral',
        instructions: 'Tomar 1 comp. a cada 6 horas'
    },
    {
        id: 2,
        name: 'Paracetamol',
        dosage: '500mg',
        route: 'Oral',
        instructions: 'Tomar 1 comp. a cada 8 horas'
    },
    {
        id: 3,
        name: 'Amoxicilina',
        dosage: '500mg',
        route: 'Oral',
        instructions: 'Tomar 1 cápsula a cada 12 horas'
    }
]);

// Funções
const addMedication = (medication: any) => {
    if (!selectedMedications.value.find(m => m.id === medication.id)) {
        selectedMedications.value.push(medication);
    }
};

const removeMedication = (id: number) => {
    selectedMedications.value = selectedMedications.value.filter(m => m.id !== id);
};

const getCurrentDate = () => {
    return new Date().toLocaleDateString('pt-BR', {
        day: 'numeric',
        month: 'long',
        year: 'numeric'
    });
};
</script>

<template>
    <Head title="Emissão de Documentos" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-6 bg-gray-50">
            <!-- Header -->
            <div class="flex flex-col gap-1">
                <h1 class="text-3xl font-bold text-gray-900">Emissão de Documentos</h1>
            </div>

            <!-- Navigation Tabs -->
            <div class="flex gap-8 border-b border-gray-200">
                <button 
                    @click="selectedTab = 'prescricao'"
                    :class="[
                        'pb-3 text-lg font-medium transition-colors',
                        selectedTab === 'prescricao' 
                            ? 'text-primary border-b-2 border-primary' 
                            : 'text-gray-600 hover:text-gray-900'
                    ]"
                >
                    Prescrição
                </button>
                <button 
                    @click="selectedTab = 'atestado'"
                    :class="[
                        'pb-3 text-lg font-medium transition-colors',
                        selectedTab === 'atestado' 
                            ? 'text-primary border-b-2 border-primary' 
                            : 'text-gray-600 hover:text-gray-900'
                    ]"
                >
                    Atestado
                </button>
                <button 
                    @click="selectedTab = 'exames'"
                    :class="[
                        'pb-3 text-lg font-medium transition-colors',
                        selectedTab === 'exames' 
                            ? 'text-primary border-b-2 border-primary' 
                            : 'text-gray-600 hover:text-gray-900'
                    ]"
                >
                    Pedido de Exames
                </button>
            </div>

            <!-- Form Section -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Patient Selection -->
                <div class="space-y-2">
                    <label class="text-sm font-medium text-gray-700">Paciente</label>
                    <div class="relative">
                        <select 
                            v-model="selectedPatient"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent appearance-none bg-white"
                        >
                            <option value="">Selecione o paciente</option>
                            <option v-for="patient in patients" :key="patient" :value="patient">
                                {{ patient }}
                            </option>
                        </select>
                        <ChevronDown class="absolute right-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none" />
                    </div>
                </div>

                <!-- Medication Search -->
                <div class="space-y-2">
                    <label class="text-sm font-medium text-gray-700">Buscar medicamento</label>
                    <div class="relative">
                        <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-4 h-4" />
                        <input
                            v-model="medicationSearch"
                            type="text"
                            placeholder="Ex: Ibuprofeno"
                            class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                        />
                    </div>
                </div>
            </div>

            <!-- Medication List Table -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-primary/10">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    MEDICAMENTO
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    DOSAGEM
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    VIA
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    INSTRUÇÕES
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    AÇÃO
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="medication in selectedMedications" :key="medication.id" class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    {{ medication.name }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ medication.dosage }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ medication.route }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500">
                                    {{ medication.instructions }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-red-600">
                                    <button 
                                        @click="removeMedication(medication.id)"
                                        class="hover:text-red-800 transition-colors"
                                    >
                                        Remover
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Available Medications (for adding) -->
            <div v-if="medicationSearch" class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                <h3 class="text-sm font-medium text-gray-700 mb-3">Medicamentos encontrados:</h3>
                <div class="space-y-2">
                    <div 
                        v-for="medication in medications.filter(m => 
                            m.name.toLowerCase().includes(medicationSearch.toLowerCase())
                        )" 
                        :key="medication.id"
                        class="flex items-center justify-between p-3 border border-gray-200 rounded-lg"
                    >
                        <div>
                            <span class="font-medium">{{ medication.name }}</span>
                            <span class="text-gray-500 ml-2">{{ medication.dosage }} - {{ medication.route }}</span>
                        </div>
                        <button 
                            @click="addMedication(medication)"
                            class="flex items-center gap-1 bg-primary hover:bg-primary/90 text-gray-900 px-3 py-1 rounded-lg text-sm font-medium transition-colors"
                        >
                            <Plus class="w-3 h-3" />
                            Adicionar
                        </button>
                    </div>
                </div>
            </div>

            <!-- Preview Section -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Pré-visualização</h3>
                
                <div class="bg-white border border-gray-300 rounded-lg p-6 max-w-2xl">
                    <h4 class="text-xl font-bold text-gray-900 mb-4">Prescrição Médica</h4>
                    
                    <div class="space-y-2 mb-6">
                        <p class="text-gray-700">
                            <span class="font-medium">Paciente:</span> 
                            <span class="bg-primary/20 px-2 py-1 rounded text-gray-900 font-medium">
                                {{ selectedPatient || 'Ana Beatriz Silva' }}
                            </span>
                        </p>
                        <p class="text-gray-700">
                            <span class="font-medium">Data:</span> 
                            <span class="bg-primary/20 px-2 py-1 rounded text-gray-900 font-medium">
                                {{ getCurrentDate() }}
                            </span>
                        </p>
                    </div>

                    <div class="space-y-2 mb-6">
                        <p class="font-medium text-gray-900">Medicamentos prescritos:</p>
                        <ul class="space-y-1">
                            <li 
                                v-for="medication in selectedMedications" 
                                :key="medication.id"
                                class="text-gray-700"
                            >
                                - {{ medication.name }} {{ medication.dosage }}: {{ medication.instructions }}
                            </li>
                        </ul>
                    </div>

                    <div class="border-t border-gray-200 pt-4">
                        <p class="text-gray-900 font-medium">Dr. Ricardo Almeida</p>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex justify-end gap-3">
                <button class="bg-primary/20 hover:bg-primary/30 text-gray-900 font-semibold px-6 py-2 rounded-lg transition-colors">
                    Assinar Digitalmente
                </button>
                <button class="bg-primary hover:bg-primary/90 text-gray-900 font-semibold px-6 py-2 rounded-lg transition-colors">
                    Gerar e Enviar para o Paciente
                </button>
            </div>
        </div>
    </AppLayout>
</template>
