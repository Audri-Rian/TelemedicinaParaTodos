<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import * as patientRoutes from '@/routes/patient';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { 
    Download, 
    ChevronDown,
    ChevronUp,
    FileText,
    Calendar,
    Stethoscope,
    Pill,
    TestTube,
    FileCheck,
    TrendingUp,
    Clock
} from 'lucide-vue-next';
import { onMounted, ref, computed, watch } from 'vue';
import { useRouteGuard } from '@/composables/auth';
import { Card, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Avatar, AvatarImage, AvatarFallback } from '@/components/ui/avatar';
import { useInitials } from '@/composables/useInitials';

const { canAccessPatientRoute } = useRouteGuard();
const { getInitials } = useInitials();

// Props do Inertia
interface Props {
    patient: {
        id: string;
        user: {
            name: string;
            avatar?: string;
        };
        date_of_birth: string;
        gender: string;
        age?: number;
    };
    appointments?: Array<{
        id: string;
        scheduled_at: string;
        status: string;
        doctor: {
            user: {
                name: string;
            };
            specializations: Array<{
                name: string;
            }>;
        };
        diagnosis?: string;
        cid10?: string;
        symptoms?: string;
        requested_exams?: string;
        instructions?: string;
        attachments?: Array<{
            name: string;
            url: string;
        }>;
        prescriptions?: Array<{
            name: string;
            url: string;
        }>;
    }>;
}

const props = withDefaults(defineProps<Props>(), {
    appointments: () => []
});

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: patientRoutes.dashboard().url,
    },
    {
        title: 'Prontuário Médico',
        href: patientRoutes.medicalRecords().url,
    },
];

// Estado das tabs
const activeTab = ref('historico');

const tabs = [
    { id: 'historico', label: 'Histórico' },
    { id: 'consultas', label: 'Consultas' },
    { id: 'prescricoes', label: 'Prescrições' },
    { id: 'exames', label: 'Exames' },
    { id: 'documentos', label: 'Documentos' },
    { id: 'evolucao', label: 'Evolução' },
    { id: 'consultas-futuras', label: 'Consultas Futuras' },
];

// Estado da timeline (quais itens estão expandidos)
const expandedItems = ref<Set<string>>(new Set());

const toggleExpand = (itemId: string) => {
    const newSet = new Set(expandedItems.value);
    if (newSet.has(itemId)) {
        newSet.delete(itemId);
    } else {
        newSet.add(itemId);
    }
    expandedItems.value = newSet;
};

const isExpanded = (itemId: string) => {
    return expandedItems.value.has(itemId);
};

// Formatação de data
const formatDate = (dateString: string) => {
    const date = new Date(dateString);
    const months = [
        'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
        'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'
    ];
    const day = date.getDate();
    const month = months[date.getMonth()];
    const year = date.getFullYear();
    return `${day} de ${month}, ${year}`;
};

// Formatação de gênero
const formatGender = (gender: string) => {
    const genderMap: Record<string, string> = {
        'male': 'Masculino',
        'female': 'Feminino',
        'other': 'Outro'
    };
    return genderMap[gender] || gender;
};

// Função para exportar PDF
const exportPDF = () => {
    // TODO: Implementar exportação PDF
    console.log('Exportar PDF');
};

// Dados das consultas (reais ou mockados)
const consultations = computed(() => {
    if (props.appointments && props.appointments.length > 0) {
        return props.appointments.map(apt => {
            const specialty = apt.doctor.specializations[0]?.name || 'Especialista';
            return {
                id: apt.id,
                date: formatDate(apt.scheduled_at),
                type: specialty === 'Cardiologia' ? 'Consulta Cardiológica' : 
                      specialty === 'Clínico Geral' ? 'Check-up de Rotina' : 
                      `Consulta de ${specialty}`,
                doctor: `${apt.doctor.user.name} | ${specialty}`,
                status: apt.status === 'completed' ? 'Finalizada' : apt.status,
                diagnosis: apt.diagnosis || null,
                cid10: apt.cid10 || null,
                symptoms: apt.symptoms || null,
                requestedExams: apt.requested_exams || null,
                instructions: apt.instructions || null,
                attachments: apt.attachments || [],
                prescriptions: apt.prescriptions || []
            };
        });
    }
    
    // Dados mockados padrão (para desenvolvimento)
    return [
        {
            id: '1',
            date: '15 de Julho, 2024',
            type: 'Consulta Cardiológica',
            doctor: 'Dr. Ana Sousa | Cardiologia',
            status: 'Finalizada',
            diagnosis: 'Hipertensão Arterial Essencial',
            cid10: 'I10',
            symptoms: 'Cefaleia ocasional, sem outros sintomas relatados.',
            requestedExams: 'Eletrocardiograma (ECG), Exames de sangue (perfil lipídico).',
            instructions: 'Aferir pressão arterial diariamente, dieta hipossódica, iniciar atividade física regular.',
            attachments: [
                { name: 'ecg_resultado.pdf', url: '#' }
            ],
            prescriptions: [
                { name: 'Receita_Losartana_20240715.pdf', url: '#' }
            ]
        },
        {
            id: '2',
            date: '02 de Março, 2024',
            type: 'Check-up de Rotina',
            doctor: 'Dr. João Silva | Clínico Geral',
            status: 'Finalizada',
        },
        {
            id: '3',
            date: '18 de Janeiro, 2023',
            type: 'Consulta Dermatológica',
            doctor: 'Dr. Maria Costa | Dermatologia',
            status: 'Finalizada',
        }
    ];
});

// Calcular idade
const age = computed(() => {
    if (props.patient.age) return props.patient.age;
    if (!props.patient.date_of_birth) return null;
    const birthDate = new Date(props.patient.date_of_birth);
    const today = new Date();
    let age = today.getFullYear() - birthDate.getFullYear();
    const monthDiff = today.getMonth() - birthDate.getMonth();
    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
        age--;
    }
    return age;
});

// Formatar data de nascimento
const formattedDateOfBirth = computed(() => {
    if (!props.patient.date_of_birth) return '';
    const date = new Date(props.patient.date_of_birth);
    return date.toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit', year: 'numeric' });
});

// ID do paciente (usando os últimos 9 dígitos do UUID)
const patientId = computed(() => {
    return props.patient.id.replace(/-/g, '').slice(-9).padStart(9, '0');
});

// Verificar acesso ao montar componente
onMounted(() => {
    canAccessPatientRoute();
});

// Expandir primeiro item por padrão quando as consultas forem carregadas
watch(consultations, (newConsultations) => {
    if (newConsultations.length > 0 && expandedItems.value.size === 0) {
        const newSet = new Set(expandedItems.value);
        newSet.add(newConsultations[0].id);
        expandedItems.value = newSet;
    }
}, { immediate: true });
</script>

<template>
    <Head title="Prontuário Médico" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col bg-gray-50">
            <!-- Header do Paciente -->
            <Card class="mx-6 mt-6 rounded-lg border-none shadow-sm">
                <CardContent class="p-6">
                    <div class="flex items-start justify-between">
                        <div class="flex items-start gap-4">
                            <Avatar class="h-20 w-20">
                                <AvatarImage 
                                    v-if="patient.user.avatar" 
                                    :src="patient.user.avatar" 
                                    :alt="patient.user.name" 
                                />
                                <AvatarFallback class="text-lg">
                                    {{ getInitials(patient.user.name) }}
                                </AvatarFallback>
                            </Avatar>
                            <div class="flex flex-col gap-1">
                                <h1 class="text-2xl font-bold text-gray-900">
                                    {{ patient.user.name }}
                                </h1>
                                <div class="flex flex-wrap gap-4 text-sm text-gray-600">
                                    <span v-if="age">Idade: {{ age }}</span>
                                    <span v-if="formattedDateOfBirth">DN: {{ formattedDateOfBirth }}</span>
                                    <span>Sexo: {{ formatGender(patient.gender) }}</span>
                                </div>
                                <div class="mt-1 text-sm text-gray-600">
                                    ID do Paciente: {{ patientId }}
                                </div>
                            </div>
                        </div>
                        <Button 
                            @click="exportPDF" 
                            class="bg-blue-600 hover:bg-blue-700 text-white"
                        >
                            <Download class="h-4 w-4 mr-2" />
                            Exportar Prontuário (PDF)
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <!-- Barra de Navegação (Tabs) -->
            <div class="mx-6 mt-4 border-b border-gray-200">
                <nav class="flex gap-1 overflow-x-auto">
                    <button
                        v-for="tab in tabs"
                        :key="tab.id"
                        @click="activeTab = tab.id"
                        :class="[
                            'px-4 py-3 text-sm font-medium transition-colors whitespace-nowrap border-b-2',
                            activeTab === tab.id
                                ? 'border-blue-600 text-blue-600'
                                : 'border-transparent text-gray-600 hover:text-gray-900 hover:border-gray-300'
                        ]"
                    >
                        {{ tab.label }}
                    </button>
                </nav>
            </div>

            <!-- Conteúdo Principal -->
            <div class="mx-6 my-6 flex-1 overflow-y-auto">
                <div v-if="activeTab === 'historico'">
                    <h2 class="mb-6 text-xl font-semibold text-gray-900">
                        Timeline de Histórico Médico
                    </h2>
                    
                    <div class="space-y-4">
                        <Card 
                            v-for="consultation in consultations" 
                            :key="consultation.id"
                            class="rounded-lg border border-gray-200 shadow-sm transition-all hover:shadow-md"
                        >
                            <CardContent class="p-0">
                                <!-- Header da Consulta -->
                                <div class="flex items-start justify-between p-4">
                                    <div class="flex items-start gap-4 flex-1">
                                        <div class="mt-1 flex h-10 w-10 items-center justify-center rounded-full bg-blue-100">
                                            <Stethoscope class="h-5 w-5 text-blue-600" />
                                        </div>
                                        <div class="flex-1">
                                            <div class="flex items-center gap-3">
                                                <p class="font-semibold text-gray-900">{{ consultation.date }}</p>
                                                <span 
                                                    class="rounded-full bg-green-100 px-3 py-1 text-xs font-medium text-green-700"
                                                    v-if="consultation.status === 'Finalizada'"
                                                >
                                                    {{ consultation.status }}
                                                </span>
                                            </div>
                                            <p class="mt-1 text-sm font-medium text-gray-700" v-if="consultation.type">
                                                {{ consultation.type }}
                                            </p>
                                            <p class="mt-1 text-sm text-gray-600">
                                                {{ consultation.doctor }}
                                            </p>
                                        </div>
                                    </div>
                                    <Button
                                        @click="toggleExpand(consultation.id)"
                                        variant="ghost"
                                        size="sm"
                                        class="text-blue-600 hover:text-blue-700"
                                    >
                                        {{ isExpanded(consultation.id) ? 'Ver Detalhes ^' : 'Ver Detalhes v' }}
                                        <ChevronUp 
                                            v-if="isExpanded(consultation.id)"
                                            class="ml-1 h-4 w-4"
                                        />
                                        <ChevronDown 
                                            v-else
                                            class="ml-1 h-4 w-4"
                                        />
                                    </Button>
                                </div>

                                <!-- Detalhes Expandidos -->
                                <div 
                                    v-if="isExpanded(consultation.id)"
                                    class="border-t border-gray-200 bg-gray-50 px-4 py-4"
                                >
                                    <div class="space-y-4">
                                        <div v-if="consultation.diagnosis">
                                            <p class="text-sm font-semibold text-gray-900">Diagnóstico:</p>
                                            <p class="mt-1 text-sm text-gray-700">{{ consultation.diagnosis }}</p>
                                        </div>
                                        
                                        <div v-if="consultation.cid10">
                                            <p class="text-sm font-semibold text-gray-900">CID-10:</p>
                                            <p class="mt-1 text-sm text-gray-700">{{ consultation.cid10 }}</p>
                                        </div>
                                        
                                        <div v-if="consultation.symptoms">
                                            <p class="text-sm font-semibold text-gray-900">Sintomas:</p>
                                            <p class="mt-1 text-sm text-gray-700">{{ consultation.symptoms }}</p>
                                        </div>
                                        
                                        <div v-if="consultation.requestedExams">
                                            <p class="text-sm font-semibold text-gray-900">Exames Solicitados:</p>
                                            <p class="mt-1 text-sm text-gray-700">{{ consultation.requestedExams }}</p>
                                        </div>
                                        
                                        <div v-if="consultation.instructions">
                                            <p class="text-sm font-semibold text-gray-900">Orientações:</p>
                                            <p class="mt-1 text-sm text-gray-700">{{ consultation.instructions }}</p>
                                        </div>
                                        
                                        <div v-if="consultation.attachments && consultation.attachments.length > 0">
                                            <p class="text-sm font-semibold text-gray-900">Anexos:</p>
                                            <div class="mt-1 space-y-1">
                                                <a
                                                    v-for="(attachment, idx) in consultation.attachments"
                                                    :key="idx"
                                                    :href="attachment.url"
                                                    class="block text-sm text-blue-600 hover:underline"
                                                >
                                                    {{ attachment.name }}
                                                </a>
                                            </div>
                                        </div>
                                        
                                        <div v-if="consultation.prescriptions && consultation.prescriptions.length > 0">
                                            <p class="text-sm font-semibold text-gray-900">Receitas Vinculadas:</p>
                                            <div class="mt-1 space-y-1">
                                                <a
                                                    v-for="(prescription, idx) in consultation.prescriptions"
                                                    :key="idx"
                                                    :href="prescription.url"
                                                    class="block text-sm text-blue-600 hover:underline"
                                                >
                                                    {{ prescription.name }}
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>

                <!-- Outras Tabs (placeholder) -->
                <div v-else class="flex h-64 items-center justify-center">
                    <p class="text-gray-500">Conteúdo da aba "{{ tabs.find(t => t.id === activeTab)?.label }}" em desenvolvimento.</p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
