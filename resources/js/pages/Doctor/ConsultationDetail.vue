<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, ref, onMounted, onUnmounted, watch } from 'vue';
// @ts-ignore - route helper from Ziggy
declare const route: (name: string, params?: any) => string;
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Badge } from '@/components/ui/badge';
import { Avatar, AvatarImage, AvatarFallback } from '@/components/ui/avatar';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    ChevronDown,
    ChevronUp,
    Save,
    CheckCircle2,
    Lock,
    Clock,
    AlertCircle,
    Stethoscope,
    FileText,
    Pill,
    TestTube,
    ClipboardList,
    Heart,
    Thermometer,
    Activity,
    FilePlus2,
    Download,
    Video,
    X,
} from 'lucide-vue-next';

interface Props {
    appointment: {
        id: string;
        scheduled_at: string;
        started_at?: string | null;
        ended_at?: string | null;
        status: string;
        notes?: string | null;
        chief_complaint?: string;
        anamnesis?: string;
        physical_exam?: string;
        diagnosis?: string;
        cid10?: string;
        instructions?: string;
        prescriptions?: Array<any>;
        examinations?: Array<any>;
        diagnoses?: Array<any>;
        vital_signs?: Array<any>;
        clinical_notes?: Array<any>;
    };
    patient: {
        id: string;
        name: string;
        age: number;
        gender: string;
        blood_type?: string | null;
        allergies: string[];
        current_medications?: string | null;
        medical_history?: string | null;
        height?: number | null;
        weight?: number | null;
        bmi?: number | null;
    };
    recent_consultations: Array<{
        id: string;
        date: string;
        diagnosis?: string | null;
        cid10?: string | null;
    }>;
    mode: 'scheduled' | 'in_progress' | 'completed';
    elapsed_time?: number | null;
    can_edit: boolean;
    can_complement: boolean;
}

const props = defineProps<Props>();

// Estado da página
const sidebarCollapsed = ref(false);
const showFinalizeModal = ref(false);
const lastSaved = ref<Date | null>(null);
const isSaving = ref(false);

// Estado dos cards (colapsáveis)
const collapsedCards = ref<Record<string, boolean>>({
    chief_complaint: false,
    anamnesis: false,
    physical_exam: false,
    diagnosis: false,
    prescription: false,
    examinations: false,
    vital_signs: false,
    notes: false,
    instructions: false,
});

// Formulário principal
const consultationForm = useForm({
    chief_complaint: props.appointment.chief_complaint || '',
    anamnesis: props.appointment.anamnesis || '',
    physical_exam: props.appointment.physical_exam || '',
    diagnosis: props.appointment.diagnosis || '',
    cid10: props.appointment.cid10 || '',
    instructions: props.appointment.instructions || '',
    notes: props.appointment.notes || '',
});

// Formulário de complementação removido - todos os campos são editáveis

// Computed
const isInProgress = computed(() => props.mode === 'in_progress');
const isCompleted = computed(() => props.mode === 'completed');
const isScheduled = computed(() => props.mode === 'scheduled');

const statusBadge = computed(() => {
    if (isInProgress.value) return { label: 'Em andamento', color: 'bg-green-500', icon: CheckCircle2 };
    if (isCompleted.value) return { label: 'Finalizada', color: 'bg-gray-500', icon: Lock };
    return { label: 'Agendada', color: 'bg-blue-500', icon: Clock };
});

const elapsedTimeFormatted = computed(() => {
    if (!props.elapsed_time) return '00:00';
    const hours = Math.floor(props.elapsed_time / 60);
    const minutes = props.elapsed_time % 60;
    return `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}`;
});

// Helper para formatar data em português
const formatDatePortuguese = (dateString: string): string => {
    if (!dateString) return '';
    
    const date = new Date(dateString);
    const months = [
        'janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho',
        'julho', 'agosto', 'setembro', 'outubro', 'novembro', 'dezembro'
    ];
    
    const day = String(date.getDate()).padStart(2, '0');
    const month = months[date.getMonth()];
    const year = date.getFullYear();
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');
    
    return `${day} de ${month} de ${year} às ${hours}:${minutes}`;
};

// Helper para formatar hora
const formatTime = (date: Date): string => {
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');
    const seconds = String(date.getSeconds()).padStart(2, '0');
    return `${hours}:${minutes}:${seconds}`;
};

const scheduledDateFormatted = computed(() => {
    if (!props.appointment.scheduled_at) return '';
    return formatDatePortuguese(props.appointment.scheduled_at);
});

const showSuccessNotification = ref(false);

// Salvar manualmente
const saveDraft = async () => {
    if (isSaving.value) return;

    isSaving.value = true;
    try {
        await consultationForm.post(
            route('doctor.consultations.detail.save-draft', props.appointment.id),
            {
                preserveScroll: true,
                preserveState: true,
                only: [],
                onSuccess: () => {
                    lastSaved.value = new Date();
                    showSuccessNotification.value = true;
                    setTimeout(() => {
                        showSuccessNotification.value = false;
                    }, 3000);
                },
            }
        );
    } catch (error) {
        console.error('Erro ao salvar rascunho:', error);
    } finally {
        isSaving.value = false;
    }
};

// Hotkeys
const handleKeyDown = (e: KeyboardEvent) => {
    // Ctrl/Cmd + D = Diagnóstico
    if ((e.ctrlKey || e.metaKey) && e.key === 'd') {
        e.preventDefault();
        collapsedCards.value.diagnosis = false;
        document.getElementById('diagnosis-card')?.scrollIntoView({ behavior: 'smooth' });
    }
    // Ctrl/Cmd + P = Prescrição
    if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
        e.preventDefault();
        collapsedCards.value.prescription = false;
        document.getElementById('prescription-card')?.scrollIntoView({ behavior: 'smooth' });
    }
    // Ctrl/Cmd + X = Exames
    if ((e.ctrlKey || e.metaKey) && e.key === 'x') {
        e.preventDefault();
        collapsedCards.value.examinations = false;
        document.getElementById('examinations-card')?.scrollIntoView({ behavior: 'smooth' });
    }
    // Ctrl/Cmd + S = Salvar
    if ((e.ctrlKey || e.metaKey) && e.key === 's') {
        e.preventDefault();
        saveDraft();
    }
};

// Finalizar consulta
const finalizeConsultation = () => {
    showFinalizeModal.value = true;
};

const confirmFinalize = async () => {
    try {
        await router.post(route('doctor.consultations.detail.finalize', props.appointment.id), {}, {
            onSuccess: () => {
                showFinalizeModal.value = false;
            },
        });
    } catch (error) {
        console.error('Erro ao finalizar consulta:', error);
    }
};

// Função de complementação removida - todos os campos são editáveis via saveDraft

// Iniciar consulta
const startConsultation = async () => {
    try {
        await router.post(route('doctor.consultations.detail.start', props.appointment.id));
    } catch (error) {
        console.error('Erro ao iniciar consulta:', error);
    }
};

// Toggle card
const toggleCard = (cardId: string) => {
    collapsedCards.value[cardId] = !collapsedCards.value[cardId];
};

// Lifecycle
onMounted(() => {
    // Hotkeys
    window.addEventListener('keydown', handleKeyDown);
});

onUnmounted(() => {
    window.removeEventListener('keydown', handleKeyDown);
});

// Auto-save desabilitado - salva apenas manualmente via botão ou Ctrl+S
</script>

<template>
    <AppLayout>
        <Head :title="`Consulta - ${patient.name}`" />

        <!-- Header Fixo -->
        <div class="sticky top-0 z-50 bg-white border-b shadow-sm">
            <div class="container mx-auto px-4 py-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div>
                            <h1 class="text-2xl font-bold">{{ patient.name }}</h1>
                            <p class="text-sm text-gray-600">
                                {{ scheduledDateFormatted }}
                            </p>
                        </div>
                        <Badge :class="statusBadge.color" class="text-white">
                            <component :is="statusBadge.icon" class="w-4 h-4 mr-1" />
                            {{ statusBadge.label }}
                        </Badge>
                        <div v-if="isInProgress && elapsed_time" class="flex items-center gap-2 text-sm text-gray-600">
                            <Clock class="w-4 h-4" />
                            <span class="font-mono">{{ elapsedTimeFormatted }}</span>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <div v-if="isSaving" class="text-sm text-gray-500 flex items-center gap-2">
                            <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-gray-900"></div>
                            Salvando...
                        </div>
                        <div v-else-if="lastSaved" class="text-xs text-gray-400">
                            Salvo às {{ formatTime(lastSaved) }}
                        </div>

                        <Button
                            v-if="isScheduled"
                            @click="startConsultation"
                            variant="default"
                        >
                            Iniciar Consulta
                        </Button>

                        <Button
                            v-if="isInProgress || isCompleted"
                            @click="saveDraft"
                            variant="outline"
                            :disabled="isSaving"
                        >
                            <Save class="w-4 h-4 mr-2" />
                            Salvar
                        </Button>

                        <Button
                            v-if="isInProgress"
                            @click="finalizeConsultation"
                            variant="default"
                        >
                            <CheckCircle2 class="w-4 h-4 mr-2" />
                            Finalizar Consulta
                        </Button>

                        <Button
                            v-if="isCompleted"
                            variant="outline"
                            @click="router.get(route('doctor.consultations.detail.pdf', appointment.id))"
                        >
                            <Download class="w-4 h-4 mr-2" />
                            Gerar PDF
                        </Button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Notificação de Sucesso -->
        <Transition
            enter-active-class="transition-all duration-300 ease-out"
            enter-from-class="translate-y-[-100%] opacity-0"
            enter-to-class="translate-y-0 opacity-100"
            leave-active-class="transition-all duration-300 ease-in"
            leave-from-class="translate-y-0 opacity-100"
            leave-to-class="translate-y-[-100%] opacity-0"
        >
            <div
                v-if="showSuccessNotification"
                class="fixed top-4 right-4 bg-green-500 text-white px-4 py-3 rounded-lg shadow-lg z-50 flex items-center gap-2"
            >
                <CheckCircle2 class="w-5 h-5" />
                <span class="font-medium">Rascunho salvo com sucesso!</span>
            </div>
        </Transition>

        <!-- Conteúdo Principal -->
        <div class="container mx-auto px-4 py-6">
            <div class="flex gap-6">
                <!-- Sidebar - Prontuário Resumido -->
                <div
                    :class="[
                        'transition-all duration-300',
                        sidebarCollapsed ? 'w-0 overflow-hidden' : 'w-80 flex-shrink-0'
                    ]"
                >
                    <Card>
                        <CardHeader class="flex flex-row items-center justify-between">
                            <CardTitle class="text-lg">Prontuário Resumido</CardTitle>
                            <Button
                                variant="ghost"
                                size="sm"
                                @click="sidebarCollapsed = !sidebarCollapsed"
                            >
                                <ChevronUp v-if="!sidebarCollapsed" class="w-4 h-4" />
                                <ChevronDown v-else class="w-4 h-4" />
                            </Button>
                        </CardHeader>
                        <CardContent v-if="!sidebarCollapsed" class="space-y-4">
                            <!-- Alergias -->
                            <div v-if="patient.allergies.length > 0">
                                <h3 class="text-sm font-semibold mb-2 flex items-center gap-2">
                                    <AlertCircle class="w-4 h-4 text-red-500" />
                                    Alergias
                                </h3>
                                <div class="flex flex-wrap gap-2">
                                    <Badge
                                        v-for="allergy in patient.allergies"
                                        :key="allergy"
                                        variant="destructive"
                                    >
                                        {{ allergy }}
                                    </Badge>
                                </div>
                            </div>

                            <!-- Medicações -->
                            <div v-if="patient.current_medications">
                                <h3 class="text-sm font-semibold mb-2 flex items-center gap-2">
                                    <Pill class="w-4 h-4" />
                                    Medicações em Uso
                                </h3>
                                <p class="text-sm text-gray-600">{{ patient.current_medications }}</p>
                            </div>

                            <!-- Dados Básicos -->
                            <div>
                                <h3 class="text-sm font-semibold mb-2">Dados Básicos</h3>
                                <div class="text-sm space-y-1">
                                    <p><span class="font-medium">Idade:</span> {{ patient.age }} anos</p>
                                    <p><span class="font-medium">Gênero:</span> {{ patient.gender }}</p>
                                    <p v-if="patient.blood_type">
                                        <span class="font-medium">Tipo Sanguíneo:</span> {{ patient.blood_type }}
                                    </p>
                                    <p v-if="patient.height && patient.weight">
                                        <span class="font-medium">IMC:</span> {{ patient.bmi?.toFixed(1) }}
                                    </p>
                                </div>
                            </div>

                            <!-- Histórico Recente -->
                            <div v-if="recent_consultations.length > 0">
                                <h3 class="text-sm font-semibold mb-2">Últimas Consultas</h3>
                                <div class="space-y-2">
                                    <div
                                        v-for="consultation in recent_consultations"
                                        :key="consultation.id"
                                        class="text-sm p-2 bg-gray-50 rounded"
                                    >
                                        <p class="font-medium">{{ consultation.date }}</p>
                                        <p v-if="consultation.diagnosis" class="text-gray-600 text-xs">
                                            {{ consultation.diagnosis }}
                                        </p>
                                        <p v-if="consultation.cid10" class="text-xs text-gray-500">
                                            CID-10: {{ consultation.cid10 }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <Button
                                variant="outline"
                                class="w-full"
                                @click="router.get(route('doctor.patients.medical-record', patient.id))"
                            >
                                Ver Prontuário Completo
                            </Button>
                        </CardContent>
                    </Card>
                </div>

                <!-- Área Principal - Formulário -->
                <div class="flex-1 space-y-4">

                    <!-- Card: Queixa Principal -->
                    <Card id="chief-complaint-card">
                        <CardHeader class="flex flex-row items-center justify-between cursor-pointer" @click="toggleCard('chief_complaint')">
                            <CardTitle class="flex items-center gap-2">
                                <Stethoscope class="w-5 h-5" />
                                Queixa Principal
                            </CardTitle>
                            <component :is="collapsedCards.chief_complaint ? ChevronDown : ChevronUp" class="w-4 h-4" />
                        </CardHeader>
                        <CardContent v-if="!collapsedCards.chief_complaint">
                            <Textarea
                                v-model="consultationForm.chief_complaint"
                                placeholder="Descreva a queixa principal do paciente..."
                                class="min-h-[100px]"
                            />
                        </CardContent>
                    </Card>

                    <!-- Card: Anamnese -->
                    <Card id="anamnesis-card">
                        <CardHeader class="flex flex-row items-center justify-between cursor-pointer" @click="toggleCard('anamnesis')">
                            <CardTitle class="flex items-center gap-2">
                                <FileText class="w-5 h-5" />
                                Anamnese (HMA)
                            </CardTitle>
                            <component :is="collapsedCards.anamnesis ? ChevronDown : ChevronUp" class="w-4 h-4" />
                        </CardHeader>
                        <CardContent v-if="!collapsedCards.anamnesis">
                            <Textarea
                                v-model="consultationForm.anamnesis"
                                placeholder="História da moléstia atual..."
                                class="min-h-[150px]"
                            />
                        </CardContent>
                    </Card>

                    <!-- Card: Exame Físico -->
                    <Card id="physical-exam-card">
                        <CardHeader class="flex flex-row items-center justify-between cursor-pointer" @click="toggleCard('physical_exam')">
                            <CardTitle class="flex items-center gap-2">
                                <Stethoscope class="w-5 h-5" />
                                Exame Físico
                            </CardTitle>
                            <component :is="collapsedCards.physical_exam ? ChevronDown : ChevronUp" class="w-4 h-4" />
                        </CardHeader>
                        <CardContent v-if="!collapsedCards.physical_exam">
                            <Textarea
                                v-model="consultationForm.physical_exam"
                                placeholder="Descreva o exame físico realizado..."
                                class="min-h-[150px]"
                            />
                        </CardContent>
                    </Card>

                    <!-- Card: Diagnóstico -->
                    <Card id="diagnosis-card">
                        <CardHeader class="flex flex-row items-center justify-between cursor-pointer" @click="toggleCard('diagnosis')">
                            <CardTitle class="flex items-center gap-2">
                                <FileText class="w-5 h-5" />
                                Diagnóstico
                                <Badge v-if="consultationForm.cid10" variant="outline">
                                    {{ consultationForm.cid10 }}
                                </Badge>
                            </CardTitle>
                            <div class="flex items-center gap-2">
                                <span class="text-xs text-gray-500">Ctrl+D</span>
                                <component :is="collapsedCards.diagnosis ? ChevronDown : ChevronUp" class="w-4 h-4" />
                            </div>
                        </CardHeader>
                        <CardContent v-if="!collapsedCards.diagnosis">
                            <div class="space-y-4">
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="text-sm font-medium mb-2 block">CID-10</label>
                                        <Input
                                            v-model="consultationForm.cid10"
                                            placeholder="Ex: J00"
                                        />
                                    </div>
                                    <div>
                                        <label class="text-sm font-medium mb-2 block">Descrição</label>
                                        <Input
                                            v-model="consultationForm.diagnosis"
                                            placeholder="Descrição do diagnóstico"
                                        />
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Card: Prescrição -->
                    <Card id="prescription-card">
                        <CardHeader class="flex flex-row items-center justify-between cursor-pointer" @click="toggleCard('prescription')">
                            <CardTitle class="flex items-center gap-2">
                                <Pill class="w-5 h-5" />
                                Prescrição
                            </CardTitle>
                            <div class="flex items-center gap-2">
                                <span class="text-xs text-gray-500">Ctrl+P</span>
                                <component :is="collapsedCards.prescription ? ChevronDown : ChevronUp" class="w-4 h-4" />
                            </div>
                        </CardHeader>
                        <CardContent v-if="!collapsedCards.prescription">
                            <div v-if="appointment.prescriptions && appointment.prescriptions.length > 0" class="mb-4">
                                <h4 class="text-sm font-semibold mb-2">Prescrições Registradas</h4>
                                <div class="space-y-2">
                                    <div
                                        v-for="prescription in appointment.prescriptions"
                                        :key="prescription.id"
                                        class="p-3 bg-gray-50 rounded border"
                                    >
                                        <div class="text-sm">
                                            <p class="font-medium">{{ prescription.medications?.map((m: any) => m.name).join(', ') }}</p>
                                            <p v-if="prescription.instructions" class="text-gray-600 mt-1">
                                                {{ prescription.instructions }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="text-sm text-gray-500">
                                Use o botão "Registrar Prescrição" no prontuário completo para adicionar prescrições.
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Card: Exames Solicitados -->
                    <Card id="examinations-card">
                        <CardHeader class="flex flex-row items-center justify-between cursor-pointer" @click="toggleCard('examinations')">
                            <CardTitle class="flex items-center gap-2">
                                <TestTube class="w-5 h-5" />
                                Exames Solicitados
                            </CardTitle>
                            <div class="flex items-center gap-2">
                                <span class="text-xs text-gray-500">Ctrl+X</span>
                                <component :is="collapsedCards.examinations ? ChevronDown : ChevronUp" class="w-4 h-4" />
                            </div>
                        </CardHeader>
                        <CardContent v-if="!collapsedCards.examinations">
                            <div v-if="appointment.examinations && appointment.examinations.length > 0" class="mb-4">
                                <h4 class="text-sm font-semibold mb-2">Exames Registrados</h4>
                                <div class="space-y-2">
                                    <div
                                        v-for="exam in appointment.examinations"
                                        :key="exam.id"
                                        class="p-3 bg-gray-50 rounded border"
                                    >
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <p class="font-medium">{{ exam.name }}</p>
                                                <p class="text-sm text-gray-600">{{ exam.type }}</p>
                                            </div>
                                            <Badge>{{ exam.status }}</Badge>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="text-sm text-gray-500">
                                Use o botão "Solicitar Exame" no prontuário completo para adicionar exames.
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Card: Sinais Vitais -->
                    <Card id="vital-signs-card">
                        <CardHeader class="flex flex-row items-center justify-between cursor-pointer" @click="toggleCard('vital_signs')">
                            <CardTitle class="flex items-center gap-2">
                                <Activity class="w-5 h-5" />
                                Sinais Vitais
                            </CardTitle>
                            <component :is="collapsedCards.vital_signs ? ChevronDown : ChevronUp" class="w-4 h-4" />
                        </CardHeader>
                        <CardContent v-if="!collapsedCards.vital_signs">
                            <div v-if="appointment.vital_signs && appointment.vital_signs.length > 0" class="space-y-2">
                                <div
                                    v-for="vital in appointment.vital_signs"
                                    :key="vital.id"
                                    class="p-3 bg-gray-50 rounded border"
                                >
                                    <div class="grid grid-cols-3 gap-4 text-sm">
                                        <div>
                                            <span class="font-medium">PA:</span>
                                            {{ vital.blood_pressure_systolic }}/{{ vital.blood_pressure_diastolic }} mmHg
                                        </div>
                                        <div>
                                            <span class="font-medium">FC:</span>
                                            {{ vital.heart_rate }} bpm
                                        </div>
                                        <div>
                                            <span class="font-medium">Temp:</span>
                                            {{ vital.temperature }}°C
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="text-sm text-gray-500">
                                Use o botão "Registrar Sinais Vitais" no prontuário completo.
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Card: Anotações -->
                    <Card id="notes-card">
                        <CardHeader class="flex flex-row items-center justify-between cursor-pointer" @click="toggleCard('notes')">
                            <CardTitle class="flex items-center gap-2">
                                <ClipboardList class="w-5 h-5" />
                                Anotações
                            </CardTitle>
                            <component :is="collapsedCards.notes ? ChevronDown : ChevronUp" class="w-4 h-4" />
                        </CardHeader>
                        <CardContent v-if="!collapsedCards.notes">
                            <Textarea
                                v-model="consultationForm.notes"
                                placeholder="Anotações adicionais sobre a consulta..."
                                class="min-h-[100px]"
                            />
                        </CardContent>
                    </Card>

                    <!-- Card: Instruções -->
                    <Card id="instructions-card">
                        <CardHeader class="flex flex-row items-center justify-between cursor-pointer" @click="toggleCard('instructions')">
                            <CardTitle class="flex items-center gap-2">
                                <FileText class="w-5 h-5" />
                                Orientações
                            </CardTitle>
                            <component :is="collapsedCards.instructions ? ChevronDown : ChevronUp" class="w-4 h-4" />
                        </CardHeader>
                        <CardContent v-if="!collapsedCards.instructions">
                            <Textarea
                                v-model="consultationForm.instructions"
                                placeholder="Orientações para o paciente..."
                                class="min-h-[100px]"
                            />
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>

        <!-- Modal de Finalização -->
        <Dialog v-model:open="showFinalizeModal">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Finalizar Consulta</DialogTitle>
                    <DialogDescription>
                        Ao finalizar, a consulta será marcada como concluída. Você ainda poderá editar os dados posteriormente se necessário.
                    </DialogDescription>
                </DialogHeader>
                <div class="space-y-2 py-4">
                    <div class="flex items-center gap-2">
                        <CheckCircle2 v-if="consultationForm.chief_complaint" class="w-5 h-5 text-green-500" />
                        <X v-else class="w-5 h-5 text-red-500" />
                        <span>Queixa principal</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <CheckCircle2 v-if="consultationForm.diagnosis || consultationForm.cid10" class="w-5 h-5 text-green-500" />
                        <X v-else class="w-5 h-5 text-red-500" />
                        <span>Diagnóstico</span>
                    </div>
                </div>
                <DialogFooter>
                    <Button variant="outline" @click="showFinalizeModal = false">
                        Cancelar
                    </Button>
                    <Button @click="confirmFinalize">
                        Finalizar Consulta
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>

