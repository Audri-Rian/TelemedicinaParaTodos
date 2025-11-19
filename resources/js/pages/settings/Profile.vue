<script setup lang="ts">
import { update as updateRoute } from '@/actions/App/Http/Controllers/Settings/ProfileController';
import { edit } from '@/routes/profile';
import { send } from '@/routes/verification';
import { useForm, Head, Link, usePage, router } from '@inertiajs/vue3';
import * as avatarRoutes from '@/routes/avatar';

import DeleteUser from '@/components/DeleteUser.vue';
import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import Timeline from '@/components/Timeline.vue';
import TimelineModal from '@/components/modals/TimelineModal.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Checkbox } from '@/components/ui/checkbox';
import { Select } from '@/components/ui/select';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { type BreadcrumbItem } from '@/types';
import { ref, computed, watch } from 'vue';
import { AlertCircle, Upload, X, User as UserIcon, Plus, CheckCircle } from 'lucide-vue-next';
import axios from 'axios';

interface Patient {
    id: string;
    emergency_contact?: string | null;
    emergency_phone?: string | null;
    medical_history?: string | null;
    allergies?: string | null;
    current_medications?: string | null;
    blood_type?: string | null;
    height?: number | null;
    weight?: number | null;
    insurance_provider?: string | null;
    insurance_number?: string | null;
    consent_telemedicine?: boolean;
}

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
    mustVerifyEmail: boolean;
    status?: string;
    patient?: Patient | null;
    bloodTypes?: string[];
    avatarUrl?: string | null;
    avatarThumbnailUrl?: string | null;
    timelineEvents?: TimelineEvent[];
    timelineCompleted?: boolean;
}

const props = defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Configurações de Perfil',
        href: edit().url,
    },
];

const page = usePage();
const user = (page.props.auth as any).user;
const auth = page.props.auth as { isPatient: boolean; isDoctor: boolean; role: string | null };

// Criar o formulário usando useForm do Inertia
const form = useForm({
    name: user.name || '',
    email: user.email || '',
    emergency_contact: props.patient?.emergency_contact || '',
    emergency_phone: props.patient?.emergency_phone || '',
    medical_history: props.patient?.medical_history || '',
    allergies: props.patient?.allergies || '',
    current_medications: props.patient?.current_medications || '',
    blood_type: props.patient?.blood_type || '',
    height: props.patient?.height || '',
    weight: props.patient?.weight || '',
    insurance_provider: props.patient?.insurance_provider || '',
    insurance_number: props.patient?.insurance_number || '',
    consent_telemedicine: props.patient?.consent_telemedicine ?? false,
});

// Função para atualizar consentimento de telemedicina
const updateConsentTelemedicine = (checked: boolean) => {
    form.consent_telemedicine = checked;
};

// Estado para mensagem de sucesso
const recentlySuccessful = ref(false);

// Função para enviar o formulário
const submit = () => {
    form.patch(updateRoute.url(), {
        preserveScroll: true,
        onSuccess: () => {
            recentlySuccessful.value = true;
            setTimeout(() => {
                recentlySuccessful.value = false;
            }, 2000);
        },
    });
};

// Verificar se segunda etapa está completa
const isSecondStageComplete = computed(() => {
    if (auth?.isPatient || auth?.role === 'patient') {
        if (!props.patient) return false;
        return !!(props.patient.emergency_contact && props.patient.emergency_phone);
    }
    if (auth?.isDoctor || auth?.role === 'doctor') {
        return props.timelineCompleted ?? false;
    }
    return false;
});

// Estados para timeline (doutor)
const timelineEvents = ref<TimelineEvent[]>(props.timelineEvents || []);
const isTimelineModalOpen = ref(false);
const editingEvent = ref<TimelineEvent | null>(null);

// Abrir modal para criar novo evento
const openCreateTimelineModal = () => {
    editingEvent.value = null;
    isTimelineModalOpen.value = true;
};

// Abrir modal para editar evento
const openEditTimelineModal = (eventId: string) => {
    const event = timelineEvents.value.find(e => e.id === eventId);
    if (event) {
        editingEvent.value = event;
        isTimelineModalOpen.value = true;
    }
};

// Fechar modal
const closeTimelineModal = () => {
    isTimelineModalOpen.value = false;
    editingEvent.value = null;
};

// Handler quando evento for salvo
const handleTimelineEventSaved = () => {
    // O componente modal já faz o reload, então apenas fechamos
    closeTimelineModal();
};

// Deletar evento
const deleteTimelineEvent = async (eventId: string) => {
    if (!confirm('Tem certeza que deseja deletar este evento?')) {
        return;
    }

    try {
        const response = await axios.delete(`/api/timeline-events/${eventId}`);

        if (response.data.success) {
            // Recarregar a página para atualizar os eventos
            router.reload({
                only: ['timelineEvents', 'timelineCompleted'],
            });
        }
    } catch (error: any) {
        alert(error.response?.data?.message || 'Erro ao deletar evento. Tente novamente.');
    }
};

// Atualizar timelineEvents quando props mudarem
watch(() => props.timelineEvents, (newEvents) => {
    if (newEvents) {
        timelineEvents.value = newEvents;
    }
}, { immediate: true });

// Estados para upload de avatar
const avatarUrl = ref<string | null>(props.avatarUrl ?? null);
const avatarThumbnailUrl = ref<string | null>(props.avatarThumbnailUrl ?? null);
const previewUrl = ref<string | null>(null);
const isUploading = ref(false);
const uploadError = ref<string | null>(null);
const uploadSuccess = ref(false);
const fileInputRef = ref<HTMLInputElement | null>(null);

// Função para selecionar arquivo
const selectFile = () => {
    fileInputRef.value?.click();
};

// Função para preview da imagem antes de upload
const handleFileSelect = (event: Event) => {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];
    
    if (!file) return;
    
    // Validar tipo de arquivo
    if (!file.type.startsWith('image/')) {
        uploadError.value = 'Por favor, selecione uma imagem válida.';
        return;
    }
    
    // Validar tamanho (5MB)
    if (file.size > 5 * 1024 * 1024) {
        uploadError.value = 'A imagem não pode ser maior que 5MB.';
        return;
    }
    
    uploadError.value = null;
    
    // Criar preview
    const reader = new FileReader();
    reader.onload = (e) => {
        previewUrl.value = e.target?.result as string;
    };
    reader.readAsDataURL(file);
};

// Função para fazer upload
const uploadAvatar = async () => {
    const file = fileInputRef.value?.files?.[0];
    if (!file) return;
    
    isUploading.value = true;
    uploadError.value = null;
    uploadSuccess.value = false;
    
    try {
        const formData = new FormData();
        formData.append('avatar', file);
        
        const response = await axios.post(avatarRoutes.upload.url(), formData, {
            headers: {
                'Content-Type': 'multipart/form-data',
            },
        });
        
        if (response.data.success) {
            avatarUrl.value = response.data.avatar_url;
            avatarThumbnailUrl.value = response.data.avatar_thumbnail_url;
            previewUrl.value = null;
            uploadSuccess.value = true;
            
            // Recarregar a página para atualizar os dados
            router.reload({ only: ['avatarUrl', 'avatarThumbnailUrl'] });
            
            // Limpar input
            if (fileInputRef.value) {
                fileInputRef.value.value = '';
            }
            
            // Limpar mensagem de sucesso após 3 segundos
            setTimeout(() => {
                uploadSuccess.value = false;
            }, 3000);
        } else {
            uploadError.value = response.data.message || 'Erro ao fazer upload do avatar.';
        }
    } catch (error: any) {
        uploadError.value = error.response?.data?.message || 'Erro ao fazer upload do avatar. Tente novamente.';
    } finally {
        isUploading.value = false;
    }
};

// Função para remover avatar
const deleteAvatar = async () => {
    if (!confirm('Tem certeza que deseja remover seu avatar?')) {
        return;
    }
    
    isUploading.value = true;
    uploadError.value = null;
    
    try {
        const response = await axios.delete((avatarRoutes as any).delete.url());
        
        if (response.data.success) {
            avatarUrl.value = null;
            avatarThumbnailUrl.value = null;
            previewUrl.value = null;
            uploadSuccess.value = true;
            
            // Recarregar a página
            router.reload({ only: ['avatarUrl', 'avatarThumbnailUrl'] });
            
            // Limpar mensagem de sucesso após 3 segundos
            setTimeout(() => {
                uploadSuccess.value = false;
            }, 3000);
        } else {
            uploadError.value = response.data.message || 'Erro ao remover avatar.';
        }
    } catch (error: any) {
        uploadError.value = error.response?.data?.message || 'Erro ao remover avatar. Tente novamente.';
    } finally {
        isUploading.value = false;
    }
};

// Função para cancelar preview
const cancelPreview = () => {
    previewUrl.value = null;
    uploadError.value = null;
    if (fileInputRef.value) {
        fileInputRef.value.value = '';
    }
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Configurações de Perfil" />

        <SettingsLayout>
            <div class="flex flex-col space-y-6">
                <!-- Seção de Avatar -->
                <div class="space-y-4 rounded-lg border border-gray-200 bg-white p-6">
                    <HeadingSmall title="Foto de Perfil" description="Adicione uma foto para personalizar seu perfil" />
                    
                    <div class="flex items-start gap-6">
                        <!-- Preview do Avatar -->
                        <div class="relative">
                            <div class="relative h-32 w-32 overflow-hidden rounded-full border-2 border-gray-200 bg-gray-100">
                                <img
                                    v-if="previewUrl || avatarThumbnailUrl || avatarUrl"
                                    :src="previewUrl || avatarThumbnailUrl || avatarUrl || ''"
                                    alt="Avatar"
                                    class="h-full w-full object-cover"
                                />
                                <div
                                    v-else
                                    class="flex h-full w-full items-center justify-center bg-linear-to-br from-blue-400 to-blue-600 text-white"
                                >
                                    <UserIcon class="h-12 w-12" />
                                </div>
                                
                                <!-- Overlay de loading -->
                                <div
                                    v-if="isUploading"
                                    class="absolute inset-0 flex items-center justify-center bg-black/50"
                                >
                                    <Loader2 class="h-6 w-6 animate-spin text-white" />
                                </div>
                            </div>
                        </div>
                        
                        <!-- Ações -->
                        <div class="flex flex-1 flex-col gap-3">
                            <div class="flex flex-wrap gap-3">
                                <!-- Input de arquivo oculto -->
                                <input
                                    ref="fileInputRef"
                                    type="file"
                                    accept="image/jpeg,image/png,image/webp"
                                    class="hidden"
                                    @change="handleFileSelect"
                                />
                                
                                <!-- Botão de selecionar/upload -->
                                <Button
                                    type="button"
                                    variant="default"
                                    :disabled="isUploading"
                                    @click="previewUrl ? uploadAvatar() : selectFile()"
                                >
                                    <Upload class="mr-2 h-4 w-4" />
                                    {{ previewUrl ? 'Confirmar Upload' : 'Selecionar Imagem' }}
                                </Button>
                                
                                <!-- Botão de cancelar preview -->
                                <Button
                                    v-if="previewUrl"
                                    type="button"
                                    variant="outline"
                                    :disabled="isUploading"
                                    @click="cancelPreview"
                                >
                                    <X class="mr-2 h-4 w-4" />
                                    Cancelar
                                </Button>
                                
                                <!-- Botão de remover -->
                                <Button
                                    v-if="avatarUrl && !previewUrl"
                                    type="button"
                                    variant="destructive"
                                    :disabled="isUploading"
                                    @click="deleteAvatar"
                                >
                                    <X class="mr-2 h-4 w-4" />
                                    Remover
                                </Button>
                            </div>
                            
                            <!-- Mensagens de feedback -->
                            <div v-if="uploadError" class="rounded-md bg-red-50 p-3 text-sm text-red-800">
                                {{ uploadError }}
                            </div>
                            
                            <div v-if="uploadSuccess" class="rounded-md bg-green-50 p-3 text-sm text-green-800">
                                Avatar atualizado com sucesso!
                            </div>
                            
                            <p class="text-xs text-gray-500">
                                Formatos aceitos: JPEG, PNG, WebP. Tamanho máximo: 5MB.
                            </p>
                        </div>
                    </div>
                </div>
                
                <HeadingSmall title="Informações do Perfil" description="Atualize seu nome e endereço de e-mail" />

                <form @submit.prevent="submit" class="space-y-6">
                    <!-- Primeira Etapa: Informações Básicas -->
                    <div class="space-y-6">
                        <div class="grid gap-2">
                            <Label for="name">Nome</Label>
                                <Input
                                    id="name"
                                    class="mt-1 block w-full"
                                    name="name"
                                    v-model="form.name"
                                    required
                                    autocomplete="name"
                                    placeholder="Nome completo"
                                />
                            <InputError class="mt-2" :message="form.errors.name" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="email">Endereço de e-mail</Label>
                                <Input
                                    id="email"
                                    type="email"
                                    class="mt-1 block w-full"
                                    name="email"
                                    v-model="form.email"
                                    required
                                    autocomplete="username"
                                    placeholder="Endereço de e-mail"
                                />
                            <InputError class="mt-2" :message="form.errors.email" />
                        </div>

                        <div v-if="mustVerifyEmail && !user.email_verified_at">
                            <p class="-mt-4 text-sm text-muted-foreground">
                                Seu endereço de e-mail não foi verificado.
                                <Link
                                    :href="send()"
                                    as="button"
                                    class="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current!"
                                >
                                    Clique aqui para reenviar o e-mail de verificação.
                                </Link>
                            </p>

                            <div v-if="status === 'verification-link-sent'" class="mt-2 text-sm font-medium text-green-600">
                                Um novo link de verificação foi enviado para o seu endereço de e-mail.
                            </div>
                        </div>
                    </div>

                    <!-- Segunda Etapa: Informações de Saúde e Emergência -->
                    <div v-if="auth?.isPatient || auth?.role === 'patient'" class="space-y-6 border-t pt-6">
                        <div class="flex items-center gap-2">
                            <HeadingSmall 
                                title="Segunda Etapa de Autenticação" 
                                description="Complete seu cadastro para agendar consultas"
                            />
                            <div v-if="!isSecondStageComplete" class="flex items-center gap-2 text-yellow-600">
                                <AlertCircle class="h-5 w-5" />
                                <span class="text-sm font-medium">Incompleto</span>
                            </div>
                            <div v-else class="flex items-center gap-2 text-green-600">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span class="text-sm font-medium">Completo</span>
                            </div>
                        </div>

                        <!-- Contato de Emergência (Obrigatório) -->
                        <div class="space-y-4 rounded-lg border border-yellow-200 bg-yellow-50 p-4">
                            <h3 class="text-sm font-semibold text-yellow-800">Contato de Emergência *</h3>
                            <p class="text-xs text-yellow-700">Estes campos são obrigatórios para agendar consultas.</p>
                            
                            <div class="grid gap-4 md:grid-cols-2">
                                <div class="grid gap-2">
                                    <Label for="emergency_contact">Nome do Contato de Emergência</Label>
                                    <Input
                                        id="emergency_contact"
                                        name="emergency_contact"
                                        v-model="form.emergency_contact"
                                        placeholder="Nome completo do contato"
                                    />
                                    <InputError class="mt-2" :message="form.errors.emergency_contact" />
                                </div>

                                <div class="grid gap-2">
                                    <Label for="emergency_phone">Telefone de Emergência</Label>
                                    <Input
                                        id="emergency_phone"
                                        name="emergency_phone"
                                        type="tel"
                                        v-model="form.emergency_phone"
                                        placeholder="(00) 00000-0000"
                                    />
                                    <InputError class="mt-2" :message="form.errors.emergency_phone" />
                                </div>
                            </div>
                        </div>

                        <!-- Informações Médicas -->
                        <div class="space-y-4">
                            <h3 class="text-sm font-semibold text-gray-800">Informações Médicas</h3>
                            
                            <div class="grid gap-4">
                                <div class="grid gap-2">
                                    <Label for="medical_history">Histórico Médico</Label>
                                    <Textarea
                                        id="medical_history"
                                        name="medical_history"
                                        v-model="form.medical_history"
                                        placeholder="Descreva seu histórico médico, cirurgias, condições crônicas, etc."
                                        :rows="4"
                                    />
                                    <InputError class="mt-2" :message="form.errors.medical_history" />
                                </div>

                                <div class="grid gap-2">
                                    <Label for="allergies">Alergias</Label>
                                    <Textarea
                                        id="allergies"
                                        name="allergies"
                                        v-model="form.allergies"
                                        placeholder="Liste suas alergias (medicamentos, alimentos, etc.)"
                                        :rows="3"
                                    />
                                    <InputError class="mt-2" :message="form.errors.allergies" />
                                </div>

                                <div class="grid gap-2">
                                    <Label for="current_medications">Medicamentos em Uso</Label>
                                    <Textarea
                                        id="current_medications"
                                        name="current_medications"
                                        v-model="form.current_medications"
                                        placeholder="Liste os medicamentos que você está tomando atualmente"
                                        :rows="3"
                                    />
                                    <InputError class="mt-2" :message="form.errors.current_medications" />
                                </div>
                            </div>
                        </div>

                        <!-- Informações Físicas e Tipo Sanguíneo -->
                        <div class="space-y-4">
                            <h3 class="text-sm font-semibold text-gray-800">Informações Físicas</h3>
                            
                            <div class="grid gap-4 md:grid-cols-3">
                                <div class="grid gap-2">
                                    <Label for="blood_type">Tipo Sanguíneo</Label>
                                    <Select
                                        id="blood_type"
                                        name="blood_type"
                                        v-model="form.blood_type"
                                    >
                                        <option value="">Selecione...</option>
                                        <option v-for="bloodType in props.bloodTypes" :key="bloodType" :value="bloodType">
                                            {{ bloodType }}
                                        </option>
                                    </Select>
                                    <InputError class="mt-2" :message="form.errors.blood_type" />
                                </div>

                                <div class="grid gap-2">
                                    <Label for="height">Altura (cm)</Label>
                                    <Input
                                        id="height"
                                        name="height"
                                        type="number"
                                        step="0.01"
                                        min="50"
                                        max="250"
                                        v-model="form.height"
                                        placeholder="Ex: 175"
                                    />
                                    <InputError class="mt-2" :message="form.errors.height" />
                                </div>

                                <div class="grid gap-2">
                                    <Label for="weight">Peso (kg)</Label>
                                    <Input
                                        id="weight"
                                        name="weight"
                                        type="number"
                                        step="0.01"
                                        min="1"
                                        max="500"
                                        v-model="form.weight"
                                        placeholder="Ex: 70.5"
                                    />
                                    <InputError class="mt-2" :message="form.errors.weight" />
                                </div>
                            </div>
                        </div>

                        <!-- Informações de Plano de Saúde -->
                        <div class="space-y-4">
                            <h3 class="text-sm font-semibold text-gray-800">Plano de Saúde</h3>
                            
                            <div class="grid gap-4 md:grid-cols-2">
                                <div class="grid gap-2">
                                    <Label for="insurance_provider">Operadora do Plano</Label>
                                    <Input
                                        id="insurance_provider"
                                        name="insurance_provider"
                                        v-model="form.insurance_provider"
                                        placeholder="Nome da operadora"
                                    />
                                    <InputError class="mt-2" :message="form.errors.insurance_provider" />
                                </div>

                                <div class="grid gap-2">
                                    <Label for="insurance_number">Número do Plano</Label>
                                    <Input
                                        id="insurance_number"
                                        name="insurance_number"
                                        v-model="form.insurance_number"
                                        placeholder="Número da carteirinha"
                                    />
                                    <InputError class="mt-2" :message="form.errors.insurance_number" />
                                </div>
                            </div>
                        </div>

                        <!-- Consentimento para Telemedicina -->
                        <div class="flex items-start gap-3 rounded-lg border border-gray-200 bg-gray-50 p-4">
                            <div class="flex items-center">
                                <Checkbox
                                    id="consent_telemedicine"
                                    :checked="form.consent_telemedicine"
                                    @update:checked="updateConsentTelemedicine"
                                />
                            </div>
                            <div class="grid gap-1">
                                <Label for="consent_telemedicine" class="cursor-pointer font-medium">
                                    Consentimento para Telemedicina
                                </Label>
                                <p class="text-xs text-gray-600">
                                    Autorizo a realização de consultas médicas por meio de telemedicina, conforme a legislação vigente.
                                </p>
                                <InputError class="mt-2" :message="form.errors.consent_telemedicine" />
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <Button type="submit" :disabled="form.processing">Salvar</Button>

                        <Transition
                            enter-active-class="transition ease-in-out"
                            enter-from-class="opacity-0"
                            leave-active-class="transition ease-in-out"
                            leave-to-class="opacity-0"
                        >
                            <p v-show="recentlySuccessful" class="text-sm text-neutral-600">Salvo.</p>
                        </Transition>
                    </div>
                </form>

                <!-- Segunda Etapa: Timeline (Apenas para Doutores) -->
                <div v-if="auth?.isDoctor || auth?.role === 'doctor'" class="space-y-6 border-t pt-6">
                    <div class="flex items-center gap-2">
                        <HeadingSmall 
                            title="Segunda Etapa de Autenticação - Timeline Profissional" 
                            description="Registre sua educação, cursos, certificados e projetos para completar seu perfil"
                        />
                        <div v-if="!isSecondStageComplete" class="flex items-center gap-2 text-yellow-600">
                            <AlertCircle class="h-5 w-5" />
                            <span class="text-sm font-medium">Incompleto</span>
                        </div>
                        <div v-else class="flex items-center gap-2 text-green-600">
                            <CheckCircle class="h-5 w-5" />
                            <span class="text-sm font-medium">Completo</span>
                        </div>
                    </div>

                    <!-- Aviso sobre segunda etapa não obrigatória -->
                    <div v-if="!isSecondStageComplete" class="rounded-lg border border-blue-200 bg-blue-50 p-4">
                        <p class="text-sm text-blue-800">
                            <strong>Nota:</strong> A segunda etapa de autenticação não é obrigatória, mas recomendamos que você complete seu perfil adicionando sua formação acadêmica e experiência profissional. Isso ajuda os pacientes a conhecerem melhor sua trajetória.
                        </p>
                    </div>

                    <!-- Botão para adicionar evento -->
                    <div class="flex justify-end">
                        <Button type="button" @click="openCreateTimelineModal" variant="default">
                            <Plus class="h-4 w-4 mr-2" />
                            Adicionar Evento
                        </Button>
                    </div>

                    <!-- Lista de eventos da timeline -->
                    <Timeline 
                        :events="timelineEvents" 
                        :show-actions="true"
                        @edit="openEditTimelineModal"
                        @delete="deleteTimelineEvent"
                    />
                </div>
            </div>

            <DeleteUser />

            <!-- Modal para criar/editar evento de timeline -->
            <TimelineModal
                :is-open="isTimelineModalOpen"
                :editing-event="editingEvent"
                @close="closeTimelineModal"
                @saved="handleTimelineEventSaved"
            />
        </SettingsLayout>
    </AppLayout>
</template>
