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
    MessageSquare,
    Loader2,
} from 'lucide-vue-next';
import CID10Autocomplete from '@/components/CID10Autocomplete.vue';

interface Props {
    appointment: {
        id: string;
        scheduled_at: string;
        started_at?: string | null;
        ended_at?: string | null;
        status: string;
        notes?: string | null;
        chief_complaint?: string;
        physical_exam?: string;
        diagnosis?: string;
        cid10?: string;
        instructions?: string;
        prescriptions?: Array<any>;
        examinations?: Array<any>;
        diagnoses?: Array<any>;
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
const autoSaveStatus = ref<'idle' | 'saving' | 'saved' | 'error'>('idle');
const hasUnsavedChanges = ref(false);
let autoSaveTimer: ReturnType<typeof setTimeout> | null = null;
let debounceTimer: ReturnType<typeof setTimeout> | null = null;

// Estado dos cards (colapsáveis)
const collapsedCards = ref<Record<string, boolean>>({
    chief_complaint: false,
    physical_exam: false,
    diagnosis: false,
    prescription: false,
    examinations: false,
    notes: false,
    instructions: false,
});

// Formulário principal (SOAP - sem Anamnese)
const consultationForm = useForm({
    chief_complaint: props.appointment.chief_complaint || '',
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

// Salvar manualmente ou automaticamente
const saveDraft = async (isAutoSave = false) => {
    if (isSaving.value) return;
    
    // Se for auto-save e não houver mudanças, não salvar
    if (isAutoSave && !hasUnsavedChanges.value) {
        return;
    }

    isSaving.value = true;
    autoSaveStatus.value = 'saving';
    
    try {
        await consultationForm.post(
            route('doctor.consultations.detail.save-draft', props.appointment.id),
            {
                preserveScroll: true,
                preserveState: true,
                only: [],
                onSuccess: () => {
                    lastSaved.value = new Date();
                    hasUnsavedChanges.value = false;
                    autoSaveStatus.value = 'saved';
                    
                    if (!isAutoSave) {
                        showSuccessNotification.value = true;
                        setTimeout(() => {
                            showSuccessNotification.value = false;
                        }, 3000);
                    }
                    
                    // Resetar status após 2 segundos
                    setTimeout(() => {
                        if (autoSaveStatus.value === 'saved') {
                            autoSaveStatus.value = 'idle';
                        }
                    }, 2000);
                },
                onError: () => {
                    autoSaveStatus.value = 'error';
                    setTimeout(() => {
                        autoSaveStatus.value = 'idle';
                    }, 3000);
                },
            }
        );
    } catch (error) {
        console.error('Erro ao salvar rascunho:', error);
        autoSaveStatus.value = 'error';
        setTimeout(() => {
            autoSaveStatus.value = 'idle';
        }, 3000);
    } finally {
        isSaving.value = false;
    }
};

// Auto-save com debounce
const triggerAutoSave = () => {
    hasUnsavedChanges.value = true;
    
    // Limpar timer anterior
    if (debounceTimer) {
        clearTimeout(debounceTimer);
    }
    
    // Salvar após 3 segundos de inatividade
    debounceTimer = setTimeout(() => {
        if (isInProgress.value && hasUnsavedChanges.value) {
            saveDraft(true);
        }
    }, 3000);
};

// Auto-save periódico (a cada 30 segundos se houver mudanças)
const startAutoSaveInterval = () => {
    if (autoSaveTimer) {
        clearInterval(autoSaveTimer);
    }
    
    autoSaveTimer = setInterval(() => {
        if (isInProgress.value && hasUnsavedChanges.value && !isSaving.value) {
            saveDraft(true);
        }
    }, 30000); // 30 segundos
};

// Parar auto-save
const stopAutoSaveInterval = () => {
    if (autoSaveTimer) {
        clearInterval(autoSaveTimer);
        autoSaveTimer = null;
    }
    if (debounceTimer) {
        clearTimeout(debounceTimer);
        debounceTimer = null;
    }
};

// Watch para detectar mudanças no formulário
watch(() => consultationForm.data(), () => {
    if (isInProgress.value) {
        triggerAutoSave();
    }
}, { deep: true });

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
    // Ctrl/Cmd + Q = Queixa Principal
    if ((e.ctrlKey || e.metaKey) && e.key === 'q') {
        e.preventDefault();
        collapsedCards.value.chief_complaint = false;
        document.getElementById('chief-complaint-card')?.scrollIntoView({ behavior: 'smooth' });
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
    
    // Iniciar auto-save se consulta estiver em andamento
    if (isInProgress.value) {
        startAutoSaveInterval();
    }
});

onUnmounted(() => {
    window.removeEventListener('keydown', handleKeyDown);
    stopAutoSaveInterval();
    
    // Salvar mudanças pendentes antes de sair
    if (hasUnsavedChanges.value && isInProgress.value) {
        saveDraft(true);
    }
});

// Watch para iniciar/parar auto-save quando status mudar
watch(() => props.mode, (newMode) => {
    if (newMode === 'in_progress') {
        startAutoSaveInterval();
    } else {
        stopAutoSaveInterval();
    }
});
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
                        <!-- Status de Auto-save -->
                        <div v-if="isInProgress" class="flex items-center gap-2 text-xs">
                            <div v-if="autoSaveStatus === 'saving'" class="flex items-center gap-1 text-blue-600">
                                <Loader2 class="w-3 h-3 animate-spin" />
                                <span>Salvando...</span>
                            </div>
                            <div v-else-if="autoSaveStatus === 'saved'" class="flex items-center gap-1 text-green-600">
                                <CheckCircle2 class="w-3 h-3" />
                                <span>Salvo</span>
                            </div>
                            <div v-else-if="autoSaveStatus === 'error'" class="flex items-center gap-1 text-red-600">
                                <AlertCircle class="w-3 h-3" />
                                <span>Erro ao salvar</span>
                            </div>
                            <div v-else-if="hasUnsavedChanges" class="flex items-center gap-1 text-amber-600">
                                <Clock class="w-3 h-3" />
                                <span>Alterações não salvas</span>
                            </div>
                            <div v-else-if="lastSaved" class="text-gray-400">
                                Salvo às {{ formatTime(lastSaved) }}
                            </div>
                        </div>
                        
                        <!-- Status manual -->
                        <div v-else-if="isSaving" class="text-sm text-gray-500 flex items-center gap-2">
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
                        
                        <!-- Botão de Mensagens -->
                        <Button
                            v-if="isInProgress || isCompleted"
                            variant="outline"
                            @click="router.get(route('doctor.messages'))"
                            title="Enviar mensagem ao paciente"
                        >
                            <MessageSquare class="w-4 h-4 mr-2" />
                            Mensagens
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
                        <CardContent v-if="!sidebarCollapsed" class="space-y-4 max-h-[calc(100vh-200px)] overflow-y-auto">
                            <!-- Alergias -->
                            <div v-if="patient.allergies.length > 0" class="border-b pb-3">
                                <h3 class="text-sm font-semibold mb-2 flex items-center gap-2 text-red-600">
                                    <AlertCircle class="w-4 h-4" />
                                    Alergias
                                </h3>
                                <div class="flex flex-wrap gap-2">
                                    <Badge
                                        v-for="allergy in patient.allergies"
                                        :key="allergy"
                                        variant="destructive"
                                        class="text-xs"
                                    >
                                        {{ allergy }}
                                    </Badge>
                                </div>
                            </div>

                            <!-- Medicações -->
                            <div v-if="patient.current_medications" class="border-b pb-3">
                                <h3 class="text-sm font-semibold mb-2 flex items-center gap-2">
                                    <Pill class="w-4 h-4 text-blue-600" />
                                    Medicações em Uso
                                </h3>
                                <p class="text-sm text-gray-700 leading-relaxed">{{ patient.current_medications }}</p>
                            </div>
                            
                            <!-- Histórico Médico -->
                            <div v-if="patient.medical_history" class="border-b pb-3">
                                <h3 class="text-sm font-semibold mb-2 flex items-center gap-2">
                                    <FileText class="w-4 h-4 text-purple-600" />
                                    Histórico Médico
                                </h3>
                                <p class="text-sm text-gray-700 leading-relaxed line-clamp-3">{{ patient.medical_history }}</p>
                            </div>

                            <!-- Dados Básicos -->
                            <div class="border-b pb-3">
                                <h3 class="text-sm font-semibold mb-2 flex items-center gap-2">
                                    <Heart class="w-4 h-4 text-primary" />
                                    Dados Básicos
                                </h3>
                                <div class="text-sm space-y-2">
                                    <div class="flex justify-between">
                                        <span class="font-medium text-gray-600">Idade:</span>
                                        <span class="text-gray-900">{{ patient.age }} anos</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="font-medium text-gray-600">Gênero:</span>
                                        <span class="text-gray-900 capitalize">{{ patient.gender === 'male' ? 'Masculino' : patient.gender === 'female' ? 'Feminino' : 'Outro' }}</span>
                                    </div>
                                    <div v-if="patient.blood_type" class="flex justify-between">
                                        <span class="font-medium text-gray-600">Tipo Sanguíneo:</span>
                                        <span class="text-gray-900">{{ patient.blood_type }}</span>
                                    </div>
                                    <div v-if="patient.height && patient.weight" class="flex justify-between">
                                        <span class="font-medium text-gray-600">IMC:</span>
                                        <span class="text-gray-900 font-semibold">{{ patient.bmi?.toFixed(1) }}</span>
                                    </div>
                                    <div v-if="patient.height" class="flex justify-between">
                                        <span class="font-medium text-gray-600">Altura:</span>
                                        <span class="text-gray-900">{{ patient.height }} cm</span>
                                    </div>
                                    <div v-if="patient.weight" class="flex justify-between">
                                        <span class="font-medium text-gray-600">Peso:</span>
                                        <span class="text-gray-900">{{ patient.weight }} kg</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Histórico Recente -->
                            <div v-if="recent_consultations.length > 0" class="border-b pb-3">
                                <h3 class="text-sm font-semibold mb-2 flex items-center gap-2">
                                    <Clock class="w-4 h-4 text-amber-600" />
                                    Últimas Consultas
                                </h3>
                                <div class="space-y-2">
                                    <div
                                        v-for="consultation in recent_consultations"
                                        :key="consultation.id"
                                        class="text-sm p-2 bg-gray-50 rounded border border-gray-200 hover:bg-gray-100 transition-colors cursor-pointer"
                                        @click="router.get(route('doctor.consultations.detail', consultation.id))"
                                    >
                                        <p class="font-medium text-gray-900">{{ consultation.date }}</p>
                                        <p v-if="consultation.diagnosis" class="text-gray-700 text-xs mt-1">
                                            {{ consultation.diagnosis }}
                                        </p>
                                        <p v-if="consultation.cid10" class="text-xs text-gray-500 mt-1">
                                            CID-10: <span class="font-mono">{{ consultation.cid10 }}</span>
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <Button
                                variant="outline"
                                class="w-full"
                                @click="router.get(`/doctor/patients/${patient.id}/medical-record`)"
                            >
                                <FileText class="w-4 h-4 mr-2" />
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
                                @input="triggerAutoSave"
                                placeholder="Descreva a queixa principal do paciente..."
                                class="min-h-[100px] resize-y"
                            />
                            <p class="text-xs text-gray-500 mt-2">
                                {{ consultationForm.chief_complaint.length }}/1000 caracteres
                            </p>
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
                                @input="triggerAutoSave"
                                placeholder="Descreva o exame físico realizado..."
                                class="min-h-[150px] resize-y"
                            />
                            <p class="text-xs text-gray-500 mt-2">
                                {{ consultationForm.physical_exam.length }}/5000 caracteres
                            </p>
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
                                        <label class="text-sm font-medium mb-2 block text-gray-700">
                                            CID-10 <span class="text-gray-400 text-xs">(opcional)</span>
                                        </label>
                                        <CID10Autocomplete
                                            v-model="consultationForm.cid10"
                                            @select="triggerAutoSave"
                                            placeholder="Digite o código CID-10 (ex: J00)"
                                        />
                                        <p class="text-xs text-gray-500 mt-1">
                                            Código da Classificação Internacional de Doenças
                                        </p>
                                    </div>
                                    <div>
                                        <label class="text-sm font-medium mb-2 block text-gray-700">
                                            Descrição do Diagnóstico
                                        </label>
                                        <Input
                                            v-model="consultationForm.diagnosis"
                                            @input="triggerAutoSave"
                                            placeholder="Descrição do diagnóstico"
                                            maxlength="500"
                                        />
                                        <p class="text-xs text-gray-500 mt-1">
                                            {{ consultationForm.diagnosis.length }}/500 caracteres
                                        </p>
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
                                @input="triggerAutoSave"
                                placeholder="Anotações adicionais sobre a consulta..."
                                class="min-h-[100px] resize-y"
                            />
                            <p class="text-xs text-gray-500 mt-2">
                                {{ consultationForm.notes.length }}/5000 caracteres
                            </p>
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
                                @input="triggerAutoSave"
                                placeholder="Orientações para o paciente..."
                                class="min-h-[100px] resize-y"
                            />
                            <p class="text-xs text-gray-500 mt-2">
                                {{ consultationForm.instructions.length }}/2000 caracteres
                            </p>
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

