<script setup lang="ts">
import { ref, computed } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Badge } from '@/components/ui/badge';
import {
    ChevronDown,
    ChevronUp,
    Save,
    CheckCircle2,
    AlertCircle,
    Stethoscope,
    FileText,
    Pill,
    TestTube,
    ClipboardList,
    X,
} from 'lucide-vue-next';

interface Props {
    appointmentId: string;
    patient: {
        id: string;
        name: string;
        age: number;
        gender: string;
        allergies: string[];
        current_medications?: string | null;
    };
    consultationData?: {
        chief_complaint?: string;
        anamnesis?: string;
        physical_exam?: string;
        diagnosis?: string;
        cid10?: string;
        instructions?: string;
        notes?: string;
    };
    isCompleted?: boolean;
}

const props = defineProps<Props>();
const emit = defineEmits<{
    close: [];
    saved: [];
}>();

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

// Formulário
const consultationForm = useForm({
    chief_complaint: props.consultationData?.chief_complaint || '',
    anamnesis: props.consultationData?.anamnesis || '',
    physical_exam: props.consultationData?.physical_exam || '',
    diagnosis: props.consultationData?.diagnosis || '',
    cid10: props.consultationData?.cid10 || '',
    instructions: props.consultationData?.instructions || '',
    notes: props.consultationData?.notes || '',
});

const isSaving = ref(false);
const lastSaved = ref<Date | null>(null);
const showSuccessNotification = ref(false);

// Salvar manualmente (apenas quando clicar no botão)
const saveDraft = async () => {
    if (isSaving.value) return;

    isSaving.value = true;
    try {
        await consultationForm.post(
            `/doctor/consultations/${props.appointmentId}/save-draft`,
            {
                preserveScroll: true,
                preserveState: true,
                only: [],
                onSuccess: () => {
                    lastSaved.value = new Date();
                    showSuccessNotification.value = true;
                    emit('saved');
                    // Esconder notificação após 3 segundos
                    setTimeout(() => {
                        showSuccessNotification.value = false;
                    }, 3000);
                },
                onError: () => {
                    showSuccessNotification.value = false;
                },
            }
        );
    } catch (error) {
        console.error('Erro ao salvar rascunho:', error);
    } finally {
        isSaving.value = false;
    }
};

// Toggle card
const toggleCard = (cardId: string) => {
    collapsedCards.value[cardId] = !collapsedCards.value[cardId];
};

// Helper para formatar hora
const formatTime = (date: Date): string => {
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');
    const seconds = String(date.getSeconds()).padStart(2, '0');
    return `${hours}:${minutes}:${seconds}`;
};

// Auto-save desabilitado - salva apenas manualmente
</script>

<template>
    <div class="h-full flex flex-col bg-white relative">
        <!-- Notificação de Sucesso -->
        <Transition
            enter-active-class="transition-all duration-300 ease-out"
            enter-from-class="translate-x-full opacity-0"
            enter-to-class="translate-x-0 opacity-100"
            leave-active-class="transition-all duration-300 ease-in"
            leave-from-class="translate-x-0 opacity-100"
            leave-to-class="translate-x-full opacity-0"
        >
            <div
                v-if="showSuccessNotification"
                class="absolute top-4 right-4 bg-green-500 text-white px-4 py-2 rounded-lg shadow-lg z-50 flex items-center gap-2"
            >
                <CheckCircle2 class="w-4 h-4" />
                <span class="text-sm font-medium">Rascunho salvo com sucesso!</span>
            </div>
        </Transition>

        <!-- Header da Sidebar -->
        <div class="flex items-center justify-between p-4 border-b bg-gray-50">
            <div>
                <h3 class="font-semibold text-gray-900">Prontuário</h3>
                <p class="text-xs text-gray-500">{{ patient.name }}</p>
            </div>
            <div class="flex items-center gap-2">
                <div v-if="isSaving" class="text-xs text-gray-500 flex items-center gap-1">
                    <div class="animate-spin rounded-full h-3 w-3 border-b-2 border-gray-900"></div>
                    Salvando...
                </div>
                <div v-else-if="lastSaved" class="text-xs text-gray-400">
                    Salvo {{ formatTime(lastSaved) }}
                </div>
                <Button
                    variant="ghost"
                    size="sm"
                    @click="emit('close')"
                    class="h-8 w-8 p-0"
                >
                    <X class="w-4 h-4" />
                </Button>
            </div>
        </div>

        <!-- Alergias (se houver) -->
        <div v-if="patient.allergies.length > 0" class="p-4 bg-red-50 border-b">
            <div class="flex items-center gap-2 mb-2">
                <AlertCircle class="w-4 h-4 text-red-600" />
                <span class="text-sm font-semibold text-red-900">Alergias</span>
            </div>
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

        <!-- Conteúdo Scrollável -->
        <div class="flex-1 overflow-y-auto p-4 space-y-3">
            <!-- Card: Queixa Principal -->
            <Card>
                <CardHeader class="flex flex-row items-center justify-between cursor-pointer p-3" @click="toggleCard('chief_complaint')">
                    <CardTitle class="text-sm flex items-center gap-2">
                        <Stethoscope class="w-4 h-4" />
                        Queixa Principal
                    </CardTitle>
                    <component :is="collapsedCards.chief_complaint ? ChevronDown : ChevronUp" class="w-4 h-4" />
                </CardHeader>
                <CardContent v-if="!collapsedCards.chief_complaint" class="p-3 pt-0">
                    <Textarea
                        v-model="consultationForm.chief_complaint"
                        placeholder="Descreva a queixa principal..."
                        class="min-h-[80px] text-sm"
                    />
                </CardContent>
            </Card>

            <!-- Card: Anamnese -->
            <Card>
                <CardHeader class="flex flex-row items-center justify-between cursor-pointer p-3" @click="toggleCard('anamnesis')">
                    <CardTitle class="text-sm flex items-center gap-2">
                        <FileText class="w-4 h-4" />
                        Anamnese
                    </CardTitle>
                    <component :is="collapsedCards.anamnesis ? ChevronDown : ChevronUp" class="w-4 h-4" />
                </CardHeader>
                <CardContent v-if="!collapsedCards.anamnesis" class="p-3 pt-0">
                    <Textarea
                        v-model="consultationForm.anamnesis"
                        placeholder="História da moléstia atual..."
                        class="min-h-[100px] text-sm"
                    />
                </CardContent>
            </Card>

            <!-- Card: Exame Físico -->
            <Card>
                <CardHeader class="flex flex-row items-center justify-between cursor-pointer p-3" @click="toggleCard('physical_exam')">
                    <CardTitle class="text-sm flex items-center gap-2">
                        <Stethoscope class="w-4 h-4" />
                        Exame Físico
                    </CardTitle>
                    <component :is="collapsedCards.physical_exam ? ChevronDown : ChevronUp" class="w-4 h-4" />
                </CardHeader>
                <CardContent v-if="!collapsedCards.physical_exam" class="p-3 pt-0">
                    <Textarea
                        v-model="consultationForm.physical_exam"
                        placeholder="Descreva o exame físico..."
                        class="min-h-[100px] text-sm"
                    />
                </CardContent>
            </Card>

            <!-- Card: Diagnóstico -->
            <Card>
                <CardHeader class="flex flex-row items-center justify-between cursor-pointer p-3" @click="toggleCard('diagnosis')">
                    <CardTitle class="text-sm flex items-center gap-2">
                        <FileText class="w-4 h-4" />
                        Diagnóstico
                        <Badge v-if="consultationForm.cid10" variant="outline" class="text-xs">
                            {{ consultationForm.cid10 }}
                        </Badge>
                    </CardTitle>
                    <component :is="collapsedCards.diagnosis ? ChevronDown : ChevronUp" class="w-4 h-4" />
                </CardHeader>
                <CardContent v-if="!collapsedCards.diagnosis" class="p-3 pt-0 space-y-2">
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="text-xs font-medium mb-1 block">CID-10</label>
                            <Input
                                v-model="consultationForm.cid10"
                                placeholder="Ex: J00"
                                class="h-8 text-sm"
                            />
                        </div>
                        <div>
                            <label class="text-xs font-medium mb-1 block">Descrição</label>
                            <Input
                                v-model="consultationForm.diagnosis"
                                placeholder="Descrição"
                                class="h-8 text-sm"
                            />
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Card: Anotações -->
            <Card>
                <CardHeader class="flex flex-row items-center justify-between cursor-pointer p-3" @click="toggleCard('notes')">
                    <CardTitle class="text-sm flex items-center gap-2">
                        <ClipboardList class="w-4 h-4" />
                        Anotações
                    </CardTitle>
                    <component :is="collapsedCards.notes ? ChevronDown : ChevronUp" class="w-4 h-4" />
                </CardHeader>
                <CardContent v-if="!collapsedCards.notes" class="p-3 pt-0">
                    <Textarea
                        v-model="consultationForm.notes"
                        placeholder="Anotações adicionais..."
                        class="min-h-[80px] text-sm"
                    />
                </CardContent>
            </Card>

            <!-- Card: Orientações -->
            <Card>
                <CardHeader class="flex flex-row items-center justify-between cursor-pointer p-3" @click="toggleCard('instructions')">
                    <CardTitle class="text-sm flex items-center gap-2">
                        <FileText class="w-4 h-4" />
                        Orientações
                    </CardTitle>
                    <component :is="collapsedCards.instructions ? ChevronDown : ChevronUp" class="w-4 h-4" />
                </CardHeader>
                <CardContent v-if="!collapsedCards.instructions" class="p-3 pt-0">
                    <Textarea
                        v-model="consultationForm.instructions"
                        placeholder="Orientações para o paciente..."
                        class="min-h-[80px] text-sm"
                    />
                </CardContent>
            </Card>
        </div>

        <!-- Footer com Botões -->
        <div class="p-4 border-t bg-gray-50 space-y-2">
            <Button
                @click="saveDraft"
                variant="outline"
                class="w-full"
                :disabled="isSaving"
                size="sm"
            >
                <Save class="w-4 h-4 mr-2" />
                Salvar Rascunho
            </Button>
            <Button
                @click="router.get(`/doctor/consultations/${appointmentId}`)"
                variant="default"
                class="w-full"
                size="sm"
            >
                Abrir Página Completa
            </Button>
        </div>
    </div>
</template>

