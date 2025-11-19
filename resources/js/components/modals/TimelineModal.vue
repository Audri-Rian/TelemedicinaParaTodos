<script setup lang="ts">
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select } from '@/components/ui/select';
import InputError from '@/components/InputError.vue';
import { Loader2 } from 'lucide-vue-next';
import { ref, watch, computed } from 'vue';
import axios from 'axios';
import { router } from '@inertiajs/vue3';

interface TimelineEvent {
    id: string;
    type: 'education' | 'course' | 'certificate' | 'project';
    type_label: string;
    title: string;
    subtitle?: string;
    start_date: string;
    end_date?: string;
    description?: string;
    media_url?: string;
    degree_type?: string;
    is_public: boolean;
    extra_data?: Record<string, any>;
    order_priority: number;
    formatted_start_date: string;
    formatted_end_date?: string;
    date_range: string;
    duration?: string;
    is_in_progress: boolean;
}

interface Props {
    isOpen: boolean;
    editingEvent?: TimelineEvent | null;
}

const props = withDefaults(defineProps<Props>(), {
    editingEvent: null,
});

const emit = defineEmits<{
    close: [];
    saved: [];
}>();

// Estados do formulário
const isSubmitting = ref(false);
const formErrors = ref<Record<string, string[]>>({});

const form = ref({
    type: 'education' as 'education' | 'course' | 'certificate' | 'project',
    title: '',
    subtitle: '',
    start_date: '',
    end_date: '',
    description: '',
    media_url: '',
    degree_type: '',
    is_public: true,
    order_priority: 0,
});

// Opções para tipos de evento
const eventTypeOptions = [
    { value: 'education', label: 'Educação' },
    { value: 'course', label: 'Curso' },
    { value: 'certificate', label: 'Certificado' },
    { value: 'project', label: 'Projeto' },
];

// Opções para degree_type
const degreeTypeOptions = [
    { value: 'fundamental', label: 'Ensino Fundamental' },
    { value: 'medio', label: 'Ensino Médio' },
    { value: 'graduacao', label: 'Graduação' },
    { value: 'pos', label: 'Pós-Graduação' },
    { value: 'curso_livre', label: 'Curso Livre' },
    { value: 'certificacao', label: 'Certificação' },
    { value: 'projeto', label: 'Projeto' },
];

// Computed para determinar se está editando
const isEditing = computed(() => !!props.editingEvent);

// Computed para título da modal
const modalTitle = computed(() => isEditing.value ? 'Editar Evento' : 'Adicionar Evento');

// Resetar formulário
const resetForm = () => {
    form.value = {
        type: 'education',
        title: '',
        subtitle: '',
        start_date: '',
        end_date: '',
        description: '',
        media_url: '',
        degree_type: '',
        is_public: true,
        order_priority: 0,
    };
    formErrors.value = {};
};

// Preencher formulário com dados do evento (quando editando)
const populateForm = (event: TimelineEvent) => {
    // Garantir que is_public seja sempre boolean
    const isPublic = typeof event.is_public === 'boolean' 
        ? event.is_public 
        : event.is_public === 'true' || event.is_public === 1 || event.is_public === '1' || Boolean(event.is_public);
    
    form.value = {
        type: event.type,
        title: event.title,
        subtitle: event.subtitle || '',
        start_date: event.start_date,
        end_date: event.end_date || '',
        description: event.description || '',
        media_url: event.media_url || '',
        degree_type: event.degree_type || '',
        is_public: isPublic,
        order_priority: event.order_priority,
    };
};

// Watch para preencher formulário quando modal abrir com evento para editar
watch(() => [props.editingEvent, props.isOpen], ([newEvent, isOpen]) => {
    if (isOpen) {
        if (newEvent) {
            populateForm(newEvent);
        } else {
            resetForm();
        }
    }
}, { immediate: true });

// Watch para resetar quando modal fechar
watch(() => props.isOpen, (isOpen) => {
    if (!isOpen) {
        resetForm();
        formErrors.value = {};
    } else if (props.editingEvent) {
        // Se modal abrir com evento, popular formulário
        populateForm(props.editingEvent);
    } else {
        // Se modal abrir sem evento, resetar formulário
        resetForm();
    }
});

// Fechar modal
const handleClose = () => {
    if (isSubmitting.value) return;
    emit('close');
};

// Salvar evento (criar ou atualizar)
const handleSubmit = async () => {
    isSubmitting.value = true;
    formErrors.value = {};

    try {
        const url = isEditing.value
            ? `/api/timeline-events/${props.editingEvent!.id}`
            : '/api/timeline-events';
        
        const method = isEditing.value ? 'put' : 'post';
        
        // Preparar dados para envio, garantindo que is_public seja sempre boolean
        const dataToSend = {
            ...form.value,
            is_public: Boolean(form.value.is_public),
        };
        
        const response = await axios[method](url, dataToSend);

        if (response.data.success) {
            // Recarregar a página para atualizar os eventos e timeline_completed
            router.reload({
                only: ['timelineEvents', 'timelineCompleted'],
                onFinish: () => {
                    emit('saved');
                    handleClose();
                },
            });
        }
    } catch (error: any) {
        if (error.response?.data?.errors) {
            formErrors.value = error.response.data.errors;
        } else {
            alert(error.response?.data?.message || 'Erro ao salvar evento. Tente novamente.');
        }
    } finally {
        isSubmitting.value = false;
    }
};

// Placeholder dinâmico para título
const titlePlaceholder = computed(() => {
    switch (form.value.type) {
        case 'education':
            return 'Ex: Medicina';
        case 'course':
            return 'Ex: Curso de Laravel Avançado';
        case 'certificate':
            return 'Ex: AWS Practitioner';
        case 'project':
            return 'Ex: Telemedicina Para Todos';
        default:
            return '';
    }
});

// Label dinâmico para subtítulo
const subtitleLabel = computed(() => {
    switch (form.value.type) {
        case 'education':
            return 'Instituição *';
        case 'certificate':
            return 'Organização';
        default:
            return 'Instituição/Empresa';
    }
});

// Placeholder dinâmico para subtítulo
const subtitlePlaceholder = computed(() => {
    return form.value.type === 'education' 
        ? 'Ex: Universidade Federal' 
        : 'Ex: Udemy, Amazon, etc.';
});

// Descrição obrigatória para projeto
const isDescriptionRequired = computed(() => form.value.type === 'project');

// Media URL obrigatória para certificado
const isMediaUrlRequired = computed(() => form.value.type === 'certificate');

// Placeholder dinâmico para descrição
const descriptionPlaceholder = computed(() => {
    switch (form.value.type) {
        case 'education':
            return 'Descreva sua formação, participações, projetos, etc.';
        case 'project':
            return 'Descreva o projeto, tecnologias utilizadas, objetivos alcançados, etc.';
        default:
            return 'Descreva o curso ou certificado';
    }
});
</script>

<template>
    <Dialog :open="isOpen" @update:open="(value) => { if (!value && !isSubmitting) handleClose() }">
        <DialogContent class="sm:max-w-2xl max-h-[90vh] overflow-y-auto">
            <DialogHeader>
                <DialogTitle>
                    {{ modalTitle }}
                </DialogTitle>
                <DialogDescription>
                    Preencha as informações do evento de timeline (educação, curso, certificado ou projeto).
                </DialogDescription>
            </DialogHeader>

            <form @submit.prevent="handleSubmit" class="space-y-6">
                <!-- Tipo de Evento -->
                <div class="grid gap-2">
                    <Label for="timeline_type">Tipo de Evento *</Label>
                    <Select
                        id="timeline_type"
                        name="type"
                        v-model="form.type"
                        required
                    >
                        <option v-for="option in eventTypeOptions" :key="option.value" :value="option.value">
                            {{ option.label }}
                        </option>
                    </Select>
                    <InputError :message="formErrors.type?.[0]" />
                </div>

                <!-- Título -->
                <div class="grid gap-2">
                    <Label for="timeline_title">Título *</Label>
                    <Input
                        id="timeline_title"
                        name="title"
                        v-model="form.title"
                        :placeholder="titlePlaceholder"
                        required
                    />
                    <InputError :message="formErrors.title?.[0]" />
                </div>

                <!-- Subtítulo (Instituição/Empresa) -->
                <div class="grid gap-2">
                    <Label for="timeline_subtitle">
                        {{ subtitleLabel }}
                    </Label>
                    <Input
                        id="timeline_subtitle"
                        name="subtitle"
                        v-model="form.subtitle"
                        :placeholder="subtitlePlaceholder"
                        :required="form.type === 'education'"
                    />
                    <InputError :message="formErrors.subtitle?.[0]" />
                </div>

                <!-- Data de Início -->
                <div class="grid gap-2">
                    <Label for="timeline_start_date">Data de Início *</Label>
                    <Input
                        id="timeline_start_date"
                        name="start_date"
                        type="date"
                        v-model="form.start_date"
                        required
                    />
                    <InputError :message="formErrors.start_date?.[0]" />
                </div>

                <!-- Data de Fim -->
                <div class="grid gap-2">
                    <Label for="timeline_end_date">Data de Fim (deixe em branco se ainda estiver em andamento)</Label>
                    <Input
                        id="timeline_end_date"
                        name="end_date"
                        type="date"
                        v-model="form.end_date"
                        :min="form.start_date"
                    />
                    <InputError :message="formErrors.end_date?.[0]" />
                </div>

                <!-- Descrição -->
                <div class="grid gap-2">
                    <Label for="timeline_description">
                        Descrição {{ isDescriptionRequired ? '*' : '' }}
                    </Label>
                    <Textarea
                        id="timeline_description"
                        name="description"
                        v-model="form.description"
                        :placeholder="descriptionPlaceholder"
                        :rows="4"
                        :required="isDescriptionRequired"
                    />
                    <InputError :message="formErrors.description?.[0]" />
                </div>

                <!-- Media URL -->
                <div class="grid gap-2">
                    <Label for="timeline_media_url">
                        URL do Certificado/Documento {{ isMediaUrlRequired ? '*' : '' }}
                    </Label>
                    <Input
                        id="timeline_media_url"
                        name="media_url"
                        type="url"
                        v-model="form.media_url"
                        placeholder="https://exemplo.com/certificado.pdf"
                        :required="isMediaUrlRequired"
                    />
                    <InputError :message="formErrors.media_url?.[0]" />
                    <p class="text-xs text-gray-500">
                        URL do certificado, diploma ou documento relacionado ao evento.
                    </p>
                </div>

                <!-- Degree Type -->
                <div class="grid gap-2">
                    <Label for="timeline_degree_type">Nível/Grau</Label>
                    <Select
                        id="timeline_degree_type"
                        name="degree_type"
                        v-model="form.degree_type"
                    >
                        <option value="">Selecione...</option>
                        <option v-for="option in degreeTypeOptions" :key="option.value" :value="option.value">
                            {{ option.label }}
                        </option>
                    </Select>
                    <InputError :message="formErrors.degree_type?.[0]" />
                </div>

                <!-- Is Public -->
                <div class="grid gap-2">
                    <Label for="timeline_is_public">Visibilidade do Evento *</Label>
                    <Select
                        id="timeline_is_public"
                        name="is_public"
                        :model-value="form.is_public ? 'true' : 'false'"
                        @update:model-value="(value: string) => form.is_public = value === 'true'"
                        required
                        :disabled="isSubmitting"
                    >
                        <option value="true">Público</option>
                        <option value="false">Privado</option>
                    </Select>
                    <InputError :message="formErrors.is_public?.[0]" />
                    <p class="text-xs text-gray-500">
                        <strong>Público:</strong> O evento será visível para pacientes na timeline do seu perfil.
                        <strong>Privado:</strong> O evento não será exibido publicamente (útil para projetos internos).
                    </p>
                </div>

                <!-- Order Priority -->
                <div class="grid gap-2">
                    <Label for="timeline_order_priority">Prioridade de Ordenação</Label>
                    <Input
                        id="timeline_order_priority"
                        name="order_priority"
                        type="number"
                        v-model.number="form.order_priority"
                        min="0"
                        placeholder="0"
                        :disabled="isSubmitting"
                    />
                    <InputError :message="formErrors.order_priority?.[0]" />
                    <p class="text-xs text-gray-500">
                        Eventos com maior prioridade aparecem primeiro na timeline. Padrão: 0.
                    </p>
                </div>

                <DialogFooter>
                    <Button 
                        type="button" 
                        variant="outline" 
                        @click="handleClose" 
                        :disabled="isSubmitting"
                    >
                        Cancelar
                    </Button>
                    <Button 
                        type="submit" 
                        :disabled="isSubmitting"
                    >
                        <Loader2 v-if="isSubmitting" class="h-4 w-4 animate-spin mr-2" />
                        {{ isEditing ? 'Salvar Alterações' : 'Adicionar Evento' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>

