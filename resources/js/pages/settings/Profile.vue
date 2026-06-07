<script setup lang="ts">
import { update as updateRoute } from '@/actions/App/Http/Controllers/Settings/ProfileController';
import * as avatarRoutes from '@/routes/avatar';
import { edit } from '@/routes/profile';
import { send } from '@/routes/verification';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';

import DeleteUser from '@/components/DeleteUser.vue';
import InputError from '@/components/InputError.vue';
import Timeline from '@/components/Timeline.vue';
import TimelineModal from '@/components/modals/TimelineModal.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { useInitials } from '@/composables/useInitials';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { type BreadcrumbItem } from '@/types';
import axios from 'axios';
import {
    AlertCircle,
    Banknote,
    Bell,
    Building2,
    CheckCircle2,
    ChevronDown,
    CreditCard,
    Languages,
    Loader2,
    Mail,
    MapPin,
    Plus,
    ShieldCheck,
    Smartphone,
    Stethoscope,
    Upload,
    Video,
    WalletCards,
    X,
} from 'lucide-vue-next';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';

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
    cns_registered?: boolean;
    cpf_registered?: boolean;
    consent_telemedicine?: boolean;
}

interface Specialization {
    id: string;
    name: string;
}

interface Doctor {
    id: string;
    crm?: string | null;
    cns_registered?: boolean;
    cbo?: string | null;
    biography?: string | null;
    license_number?: string | null;
    license_expiry_date?: string | null;
    consultation_fee?: number | null;
    status?: string | null;
    availability_schedule?: Record<string, unknown> | null;
    specializations?: string[];
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
    doctor?: Doctor | null;
    bloodTypes?: string[];
    specializations?: Specialization[];
    avatarUrl?: string | null;
    avatarThumbnailUrl?: string | null;
    avatarMaxKb?: number;
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
const isDoctor = computed(() => auth?.isDoctor === true || auth?.role === 'doctor');
const isPatient = computed(() => auth?.isPatient === true || auth?.role === 'patient');
const { getInitials } = useInitials();

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
    cns: '',
    cpf: '',
    consent_telemedicine: props.patient?.consent_telemedicine ?? false,
    crm: props.doctor?.crm || '',
    cbo: props.doctor?.cbo || '',
    biography: props.doctor?.biography || '',
    license_number: props.doctor?.license_number || '',
    license_expiry_date: props.doctor?.license_expiry_date || '',
    consultation_fee: props.doctor?.consultation_fee || '',
    status: props.doctor?.status || 'active',
    specializations: [...(props.doctor?.specializations || [])] as string[],
});

// Função para atualizar consentimento de telemedicina
const updateConsentTelemedicine = (checked: boolean) => {
    form.consent_telemedicine = checked;
};

const isSpecializationsDropdownOpen = ref(false);
const specializationSearch = ref('');

const filteredSpecializationsList = computed(() => {
    const term = specializationSearch.value.trim().toLowerCase();
    const list = props.specializations ?? [];

    if (!term) {
        return list;
    }

    return list.filter((specialization) => specialization.name.toLowerCase().includes(term));
});

const selectedSpecializationsList = computed(() => {
    const selectedIds = Array.isArray(form.specializations) ? form.specializations : [];

    return (props.specializations ?? []).filter((specialization) => selectedIds.includes(specialization.id));
});

const primarySpecialtyLabel = computed(() => {
    const first = selectedSpecializationsList.value[0];
    return first?.name ?? null;
});

const doctorStatusLabel = computed(() => {
    if (form.status === 'inactive') {
        return 'Inativo';
    }

    if (form.status === 'suspended') {
        return 'Suspenso';
    }

    return 'Ativo';
});

const isSpecializationSelected = (specializationId: string) => form.specializations.includes(specializationId);

const toggleSpecialization = (specializationId: string) => {
    const current = Array.isArray(form.specializations) ? [...form.specializations] : [];
    const index = current.indexOf(specializationId);

    form.specializations = index > -1 ? current.filter((id) => id !== specializationId) : Array.from(new Set([...current, specializationId]));
};

const removeSpecialization = (specializationId: string) => {
    form.specializations = form.specializations.filter((id) => id !== specializationId);
};

const closeSpecializationsDropdown = () => {
    isSpecializationsDropdownOpen.value = false;
    specializationSearch.value = '';
};

const handleSpecializationsClickOutside = (event: MouseEvent) => {
    const dropdown = document.querySelector('.profile-specializations-dropdown');

    if (dropdown && !dropdown.contains(event.target as Node)) {
        closeSpecializationsDropdown();
    }
};

onMounted(() => {
    document.addEventListener('click', handleSpecializationsClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('click', handleSpecializationsClickOutside);
});

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
    if (isPatient.value) {
        if (!props.patient) return false;
        return !!(props.patient.emergency_contact && props.patient.emergency_phone);
    }
    if (isDoctor.value) {
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
    const event = timelineEvents.value.find((e) => e.id === eventId);
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
watch(
    () => props.timelineEvents,
    (newEvents) => {
        if (newEvents) {
            timelineEvents.value = newEvents;
        }
    },
    { immediate: true },
);

// Estados para upload de avatar
const avatarUrl = ref<string | null>(props.avatarUrl ?? null);
const avatarThumbnailUrl = ref<string | null>(props.avatarThumbnailUrl ?? null);
const previewUrl = ref<string | null>(null);
const isUploading = ref(false);
const uploadError = ref<string | null>(null);
const uploadSuccess = ref(false);
const fileInputRef = ref<HTMLInputElement | null>(null);
const avatarMaxKb = computed(() => props.avatarMaxKb ?? 5120);
const avatarMaxBytes = computed(() => avatarMaxKb.value * 1024);
const avatarMaxLabel = computed(() => {
    if (avatarMaxKb.value >= 1024) {
        const mb = avatarMaxKb.value / 1024;
        return `${Number.isInteger(mb) ? mb : mb.toFixed(1)} MB`;
    }

    return `${avatarMaxKb.value} KB`;
});

const staticLanguages = [
    { code: 'pt', label: 'Português', flag: 'BR', level: 'Nativo' },
    { code: 'en', label: 'Inglês', flag: 'EN', level: 'Fluente' },
    { code: 'es', label: 'Espanhol', flag: 'ES', level: 'Intermediário' },
];

const staticModalities = [
    {
        title: 'Consulta por vídeo (online)',
        description: 'Atendimento via plataforma de vídeo da Telemedicina Para Todos.',
        enabled: true,
        icon: Video,
    },
    {
        title: 'Consulta presencial',
        description: 'Atendimento em um dos seus locais cadastrados.',
        enabled: true,
        icon: Stethoscope,
    },
];

const staticLocations = [
    {
        name: 'Hospital São Luiz',
        kind: 'Hospital',
        address: 'R. Engenheiro Oscar Americano, 840 - Jardim Guedala, São Paulo',
        icon: Building2,
    },
    {
        name: 'Centro de Dor Vila Olímpia',
        kind: 'Clínica',
        address: 'R. Olimpíadas, 205 - Vila Olímpia, São Paulo',
        icon: Stethoscope,
    },
];

const staticNotifications = [
    {
        title: 'Novos agendamentos por e-mail',
        description: 'Sempre que um paciente agendar com você.',
        enabled: true,
        icon: Mail,
    },
    {
        title: 'Lembretes de consulta por e-mail',
        description: '1 hora antes de cada consulta.',
        enabled: true,
        icon: Mail,
    },
    {
        title: 'Lembretes por SMS',
        description: '15 minutos antes da consulta.',
        enabled: true,
        icon: Smartphone,
    },
    {
        title: 'Notificações push',
        description: 'Quando alguém marcar consulta ou enviar mensagem.',
        enabled: true,
        icon: Bell,
    },
    {
        title: 'Conteúdos e novidades',
        description: 'Atualizações de produto, melhores práticas e estudos.',
        enabled: false,
        icon: Mail,
    },
];

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

    // Validar tamanho
    if (file.size > avatarMaxBytes.value) {
        uploadError.value = `A imagem não pode ser maior que ${avatarMaxLabel.value}.`;
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
        const response = await axios.delete(avatarRoutes.deleteMethod.url());

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
            <div :class="['flex flex-col', isDoctor ? 'space-y-5' : 'space-y-6']">
                <div
                    :class="[
                        isDoctor
                            ? 'rounded-[14px] border border-slate-200 bg-white px-5 py-6 shadow-xs sm:px-7'
                            : 'rounded-lg border border-slate-200 bg-white p-5 shadow-sm sm:p-6',
                    ]"
                >
                    <div
                        :class="[
                            'flex flex-col gap-5 sm:flex-row sm:items-start',
                            isDoctor ? 'rounded-[12px] border border-slate-200 bg-slate-50/70 p-5' : '',
                        ]"
                    >
                        <div class="relative">
                            <Avatar
                                :class="[
                                    'size-20 ring-4 ring-teal-50 sm:size-24',
                                    isDoctor ? 'shadow-[0_0_0_4px_white,0_0_0_5px_#e2e8f0]' : '',
                                    { 'ring-2 ring-amber-300': !!previewUrl },
                                ]"
                            >
                                <AvatarImage
                                    v-if="previewUrl || avatarThumbnailUrl || avatarUrl"
                                    :src="previewUrl || avatarThumbnailUrl || avatarUrl || undefined"
                                    alt="Avatar"
                                />
                                <AvatarFallback class="bg-teal-700 text-2xl font-semibold text-white">
                                    {{ getInitials(user.name) || '?' }}
                                </AvatarFallback>
                            </Avatar>
                            <div v-if="isUploading" class="absolute inset-0 flex items-center justify-center rounded-full bg-black/50">
                                <Loader2 class="h-6 w-6 animate-spin text-white" />
                            </div>
                        </div>

                        <div class="min-w-0 flex-1 space-y-4">
                            <div class="min-w-0 space-y-2">
                                <div v-if="isDoctor" class="flex flex-wrap items-center gap-2">
                                    <span
                                        v-if="timelineCompleted"
                                        class="inline-flex h-7 items-center gap-1.5 rounded-full bg-teal-50 px-3 text-xs font-semibold text-teal-800 ring-1 ring-teal-100"
                                    >
                                        <CheckCircle2 class="size-3.5" />
                                        Perfil completo
                                    </span>
                                    <span
                                        v-if="form.crm || doctor?.crm"
                                        class="inline-flex h-7 items-center gap-1.5 rounded-full bg-slate-50 px-3 text-xs font-semibold text-slate-700 ring-1 ring-slate-200"
                                    >
                                        <ShieldCheck class="size-3.5" />
                                        CRM verificado
                                    </span>
                                    <span
                                        class="inline-flex h-7 items-center rounded-full bg-slate-50 px-3 text-xs font-semibold text-slate-700 ring-1 ring-slate-200"
                                    >
                                        {{ doctorStatusLabel }}
                                    </span>
                                </div>
                                <h1 class="text-2xl font-bold text-slate-950">{{ user.name }}</h1>
                                <p v-if="isDoctor && primarySpecialtyLabel" class="text-sm text-slate-600">
                                    {{ primarySpecialtyLabel }}
                                </p>
                            </div>

                            <div class="flex flex-wrap gap-3">
                                <input
                                    ref="fileInputRef"
                                    type="file"
                                    accept="image/jpeg,image/png,image/webp"
                                    class="hidden"
                                    @change="handleFileSelect"
                                />

                                <Button
                                    type="button"
                                    :disabled="isUploading"
                                    :class="
                                        isDoctor
                                            ? 'h-9 rounded-[9px] bg-teal-700 px-4 text-[13.5px] font-medium text-white hover:bg-teal-800'
                                            : 'bg-teal-500 text-slate-950 hover:bg-teal-400'
                                    "
                                    @click="previewUrl ? uploadAvatar() : selectFile()"
                                >
                                    <Upload class="mr-2 h-4 w-4" />
                                    {{ previewUrl ? 'Confirmar Upload' : 'Selecionar Imagem' }}
                                </Button>

                                <Button v-if="previewUrl" type="button" variant="outline" :disabled="isUploading" @click="cancelPreview">
                                    <X class="mr-2 h-4 w-4" />
                                    Cancelar
                                </Button>

                                <Button
                                    v-if="avatarUrl && !previewUrl"
                                    type="button"
                                    :disabled="isUploading"
                                    :class="
                                        isDoctor
                                            ? 'h-9 rounded-[9px] border border-rose-200 bg-white px-4 text-[13.5px] font-medium text-rose-700 hover:bg-rose-50'
                                            : 'border-0 bg-red-50 text-red-700 hover:bg-red-100'
                                    "
                                    @click="deleteAvatar"
                                >
                                    <X class="mr-2 h-4 w-4" />
                                    Remover
                                </Button>
                            </div>

                            <div v-if="uploadError" class="rounded-md bg-red-50 p-3 text-sm text-red-800 ring-1 ring-red-100">
                                {{ uploadError }}
                            </div>

                            <div v-if="uploadSuccess" class="rounded-md bg-teal-50 p-3 text-sm text-teal-800 ring-1 ring-teal-100">
                                Avatar atualizado com sucesso!
                            </div>

                            <p class="text-xs text-slate-500">Formatos aceitos: JPEG, PNG, WebP. Tamanho máximo: {{ avatarMaxLabel }}.</p>
                        </div>
                    </div>
                </div>

                <form @submit.prevent="submit" :class="isDoctor ? 'space-y-5' : 'space-y-6'">
                    <div
                        id="basic"
                        :class="[
                            'scroll-mt-20',
                            isDoctor
                                ? 'rounded-[14px] border border-slate-200 bg-white px-5 py-6 shadow-xs sm:px-7'
                                : 'rounded-lg border border-slate-200 bg-white p-5 shadow-sm sm:p-6',
                        ]"
                    >
                        <div class="mb-5 flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <p
                                    :class="
                                        isDoctor
                                            ? 'text-[11px] font-semibold tracking-[0.08em] text-slate-500 uppercase'
                                            : 'text-xs font-bold tracking-[0.08em] text-slate-500 uppercase'
                                    "
                                >
                                    Conta
                                </p>
                                <h2
                                    :class="
                                        isDoctor
                                            ? 'mt-1 text-lg font-semibold tracking-normal text-slate-950'
                                            : 'mt-1 text-xl font-bold text-slate-950'
                                    "
                                >
                                    Informações básicas
                                </h2>
                                <p :class="isDoctor ? 'mt-1 text-[13.5px] text-slate-500' : 'mt-1 text-sm text-slate-500'">
                                    Como você é identificado dentro da plataforma.
                                </p>
                            </div>
                        </div>

                        <div :class="isDoctor ? 'grid gap-4 md:grid-cols-2' : 'space-y-6'">
                            <div class="grid gap-2">
                                <Label for="name" class="text-slate-700">Nome</Label>
                                <Input
                                    id="name"
                                    name="name"
                                    v-model="form.name"
                                    required
                                    autocomplete="name"
                                    placeholder="Nome completo"
                                    :class="
                                        isDoctor
                                            ? 'h-10 rounded-[9px] border-slate-300 text-sm focus-visible:border-teal-700 focus-visible:ring-teal-700/20'
                                            : ''
                                    "
                                />
                                <InputError class="mt-2" :message="form.errors.name" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="email" class="text-slate-700">Endereço de e-mail</Label>
                                <Input
                                    id="email"
                                    type="email"
                                    name="email"
                                    v-model="form.email"
                                    required
                                    autocomplete="username"
                                    placeholder="Endereço de e-mail"
                                    :class="
                                        isDoctor
                                            ? 'h-10 rounded-[9px] border-slate-300 text-sm focus-visible:border-teal-700 focus-visible:ring-teal-700/20'
                                            : ''
                                    "
                                />
                                <InputError class="mt-2" :message="form.errors.email" />
                            </div>

                            <div v-if="isDoctor" class="grid gap-2">
                                <Label for="doctor_status_basic" class="text-slate-700">Status do perfil</Label>
                                <Select
                                    id="doctor_status_basic"
                                    name="status"
                                    v-model="form.status"
                                    class="h-10 rounded-[9px] border-slate-300 text-sm focus-visible:border-teal-700 focus-visible:ring-teal-700/20"
                                >
                                    <option value="active">Perfil ativo</option>
                                    <option value="inactive">Pausado</option>
                                    <option value="suspended">Suspenso</option>
                                </Select>
                                <p class="text-xs text-slate-500">
                                    {{ form.status === 'active' ? 'Recebendo novos agendamentos.' : 'Oculto dos pacientes para novos agendamentos.' }}
                                </p>
                                <InputError class="mt-2" :message="form.errors.status" />
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

                                <div v-if="status === 'verification-link-sent'" class="mt-2 text-sm font-medium text-teal-700">
                                    Um novo link de verificação foi enviado para o seu endereço de e-mail.
                                </div>
                            </div>
                        </div>
                    </div>

                    <div
                        v-if="isDoctor"
                        id="professional"
                        class="scroll-mt-20 rounded-[14px] border border-slate-200 bg-white px-5 py-6 shadow-xs sm:px-7"
                    >
                        <div class="mb-5 flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <p class="text-[11px] font-semibold tracking-[0.08em] text-slate-500 uppercase">Registro profissional</p>
                                <h2 class="mt-1 text-lg font-semibold tracking-normal text-slate-950">Dados profissionais</h2>
                                <p class="mt-1 text-[13.5px] text-slate-500">Atualize os dados exibidos para pacientes e integrações.</p>
                            </div>
                            <span
                                v-if="form.crm || doctor?.crm"
                                class="inline-flex h-6 items-center gap-1.5 rounded-full border border-teal-200 bg-teal-50 px-2.5 text-xs font-medium text-teal-900"
                            >
                                <ShieldCheck class="size-3" />
                                CRM verificado
                            </span>
                        </div>

                        <div class="space-y-6">
                            <div class="grid gap-4 md:grid-cols-3">
                                <div class="grid gap-2">
                                    <Label for="crm" class="text-slate-700">CRM</Label>
                                    <Input
                                        id="crm"
                                        name="crm"
                                        v-model="form.crm"
                                        placeholder="CRM sem pontos ou traços"
                                        maxlength="20"
                                        class="h-10 rounded-[9px] border-slate-300 text-sm focus-visible:border-teal-700 focus-visible:ring-teal-700/20"
                                    />
                                    <InputError class="mt-2" :message="form.errors.crm" />
                                </div>

                                <div class="grid gap-2">
                                    <Label for="doctor_cns" class="text-slate-700">CNS</Label>
                                    <Input
                                        id="doctor_cns"
                                        name="cns"
                                        inputmode="numeric"
                                        v-model="form.cns"
                                        :placeholder="props.doctor?.cns_registered ? '••• cadastrado — preencha para alterar' : '000000000000000'"
                                        maxlength="15"
                                        class="h-10 rounded-[9px] border-slate-300 text-sm focus-visible:border-teal-700 focus-visible:ring-teal-700/20"
                                    />
                                    <InputError class="mt-2" :message="form.errors.cns" />
                                </div>

                                <div class="grid gap-2">
                                    <Label for="cbo" class="text-slate-700">CBO</Label>
                                    <Input
                                        id="cbo"
                                        name="cbo"
                                        inputmode="numeric"
                                        v-model="form.cbo"
                                        placeholder="000000"
                                        maxlength="6"
                                        class="h-10 rounded-[9px] border-slate-300 text-sm focus-visible:border-teal-700 focus-visible:ring-teal-700/20"
                                    />
                                    <InputError class="mt-2" :message="form.errors.cbo" />
                                </div>
                            </div>

                            <div class="grid gap-4 md:grid-cols-3">
                                <div class="grid gap-2 md:col-span-2">
                                    <Label for="license_number" class="text-slate-700">Número de Licença</Label>
                                    <Input
                                        id="license_number"
                                        name="license_number"
                                        v-model="form.license_number"
                                        placeholder="Número de registro profissional"
                                        class="h-10 rounded-[9px] border-slate-300 text-sm focus-visible:border-teal-700 focus-visible:ring-teal-700/20"
                                    />
                                    <InputError class="mt-2" :message="form.errors.license_number" />
                                </div>

                                <div class="grid gap-2">
                                    <Label for="license_expiry_date" class="text-slate-700">Validade da Licença</Label>
                                    <Input
                                        id="license_expiry_date"
                                        name="license_expiry_date"
                                        type="date"
                                        v-model="form.license_expiry_date"
                                        class="h-10 rounded-[9px] border-slate-300 text-sm focus-visible:border-teal-700 focus-visible:ring-teal-700/20"
                                    />
                                    <InputError class="mt-2" :message="form.errors.license_expiry_date" />
                                </div>
                            </div>

                            <div class="grid gap-4 md:grid-cols-2">
                                <div class="grid gap-2">
                                    <Label for="consultation_fee" class="text-slate-700">Valor da Consulta</Label>
                                    <Input
                                        id="consultation_fee"
                                        name="consultation_fee"
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        v-model="form.consultation_fee"
                                        placeholder="Ex: 180.00"
                                        class="h-10 rounded-[9px] border-slate-300 text-sm focus-visible:border-teal-700 focus-visible:ring-teal-700/20"
                                    />
                                    <InputError class="mt-2" :message="form.errors.consultation_fee" />
                                </div>
                            </div>

                            <div id="specialties" class="grid scroll-mt-20 gap-2">
                                <Label for="specializations" class="text-slate-700">Especializações</Label>
                                <div class="profile-specializations-dropdown relative">
                                    <div
                                        id="specializations"
                                        role="combobox"
                                        tabindex="0"
                                        class="flex min-h-11 w-full cursor-pointer items-center justify-between gap-2 rounded-[9px] border border-slate-300 bg-white px-3 py-2 text-left text-sm shadow-xs transition hover:border-slate-300 focus-visible:border-teal-700 focus-visible:ring-[3px] focus-visible:ring-teal-700/20 focus-visible:outline-none"
                                        :aria-expanded="isSpecializationsDropdownOpen"
                                        aria-haspopup="listbox"
                                        @click.stop="isSpecializationsDropdownOpen = !isSpecializationsDropdownOpen"
                                        @keydown.enter.prevent="isSpecializationsDropdownOpen = !isSpecializationsDropdownOpen"
                                        @keydown.space.prevent="isSpecializationsDropdownOpen = !isSpecializationsDropdownOpen"
                                    >
                                        <div class="flex min-w-0 flex-1 flex-wrap items-center gap-1.5">
                                            <span
                                                v-for="specialization in selectedSpecializationsList"
                                                :key="specialization.id"
                                                class="inline-flex items-center gap-1 rounded-full bg-teal-50 px-2.5 py-1 text-xs font-medium text-teal-800 ring-1 ring-teal-100"
                                            >
                                                {{ specialization.name }}
                                                <button
                                                    type="button"
                                                    class="text-teal-700 transition hover:text-teal-900"
                                                    :aria-label="`Remover ${specialization.name}`"
                                                    @click.stop="removeSpecialization(specialization.id)"
                                                >
                                                    <X class="h-3 w-3" />
                                                </button>
                                            </span>
                                            <span v-if="selectedSpecializationsList.length === 0" class="text-sm text-slate-400">
                                                Selecione suas especializações
                                            </span>
                                        </div>
                                        <ChevronDown
                                            class="h-4 w-4 shrink-0 text-slate-500 transition-transform"
                                            :class="isSpecializationsDropdownOpen ? 'rotate-180' : ''"
                                        />
                                    </div>

                                    <div
                                        v-if="isSpecializationsDropdownOpen"
                                        class="absolute z-50 mt-2 w-full overflow-hidden rounded-lg border border-slate-200 bg-white shadow-lg"
                                        role="listbox"
                                    >
                                        <div class="border-b border-slate-100 p-3">
                                            <Input
                                                v-model="specializationSearch"
                                                placeholder="Buscar especialização..."
                                                class="h-9 text-sm"
                                                @click.stop
                                            />
                                        </div>
                                        <div class="max-h-48 overflow-y-auto">
                                            <button
                                                v-for="specialization in filteredSpecializationsList"
                                                :key="specialization.id"
                                                type="button"
                                                role="option"
                                                :aria-selected="isSpecializationSelected(specialization.id)"
                                                class="flex w-full cursor-pointer items-center justify-between px-3 py-2 text-left text-sm transition-colors hover:bg-slate-50"
                                                :class="isSpecializationSelected(specialization.id) ? 'bg-teal-50 text-teal-800' : 'text-slate-700'"
                                                @click.stop="toggleSpecialization(specialization.id)"
                                            >
                                                <span>{{ specialization.name }}</span>
                                                <Checkbox :checked="isSpecializationSelected(specialization.id)" class="pointer-events-none" />
                                            </button>
                                            <p v-if="filteredSpecializationsList.length === 0" class="px-3 py-4 text-center text-sm text-slate-500">
                                                Nenhuma especialização encontrada
                                            </p>
                                        </div>
                                        <p class="border-t border-slate-100 bg-slate-50 px-3 py-2 text-xs text-slate-500">
                                            {{ selectedSpecializationsList.length }} especialização(ões) selecionada(s)
                                        </p>
                                    </div>
                                </div>
                                <InputError class="mt-2" :message="form.errors.specializations" />
                            </div>

                            <div id="about" class="grid scroll-mt-20 gap-2">
                                <Label for="biography" class="text-slate-700">Biografia</Label>
                                <Textarea
                                    id="biography"
                                    name="biography"
                                    v-model="form.biography"
                                    placeholder="Resumo profissional exibido aos pacientes"
                                    :rows="4"
                                    class="rounded-[9px] border-slate-300 text-sm focus-visible:border-teal-700 focus-visible:ring-teal-700/20"
                                />
                                <InputError class="mt-2" :message="form.errors.biography" />
                            </div>
                        </div>
                    </div>

                    <template v-if="isPatient">
                        <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                            <div class="mb-5 flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <p class="text-xs font-bold tracking-[0.08em] text-slate-500 uppercase">Emergência</p>
                                    <h2 class="mt-1 text-xl font-bold text-slate-950">Contato de emergência</h2>
                                    <p class="mt-1 text-sm text-slate-500">Campos obrigatórios para habilitar o agendamento de consultas.</p>
                                </div>
                                <span
                                    v-if="isSecondStageComplete"
                                    class="inline-flex h-7 items-center gap-1.5 rounded-full bg-teal-50 px-3 text-xs font-semibold text-teal-800 ring-1 ring-teal-100"
                                >
                                    <CheckCircle2 class="size-3.5" />
                                    Completo
                                </span>
                                <span
                                    v-else
                                    class="inline-flex h-7 items-center gap-1.5 rounded-full bg-amber-50 px-3 text-xs font-semibold text-amber-700 ring-1 ring-amber-200"
                                >
                                    <AlertCircle class="size-3.5" />
                                    Incompleto
                                </span>
                            </div>

                            <div class="grid gap-4 md:grid-cols-2">
                                <div class="grid gap-2">
                                    <Label for="emergency_contact" class="text-slate-700">Nome do contato de emergência</Label>
                                    <Input
                                        id="emergency_contact"
                                        name="emergency_contact"
                                        v-model="form.emergency_contact"
                                        placeholder="Nome completo do contato"
                                    />
                                    <InputError class="mt-2" :message="form.errors.emergency_contact" />
                                </div>

                                <div class="grid gap-2">
                                    <Label for="emergency_phone" class="text-slate-700">Telefone de emergência</Label>
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

                        <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                            <div class="mb-5">
                                <p class="text-xs font-bold tracking-[0.08em] text-slate-500 uppercase">Saúde</p>
                                <h2 class="mt-1 text-xl font-bold text-slate-950">Informações médicas</h2>
                            </div>
                            <div class="space-y-4">
                                <div class="grid gap-4">
                                    <div class="grid gap-2">
                                        <Label for="medical_history" class="text-slate-700">Histórico médico</Label>
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
                                        <Label for="allergies" class="text-slate-700">Alergias</Label>
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
                                        <Label for="current_medications" class="text-slate-700">Medicamentos em uso</Label>
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
                        </div>

                        <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                            <div class="mb-5">
                                <p class="text-xs font-bold tracking-[0.08em] text-slate-500 uppercase">Dados físicos</p>
                                <h2 class="mt-1 text-xl font-bold text-slate-950">Medidas e tipo sanguíneo</h2>
                            </div>
                            <div class="grid gap-4 md:grid-cols-3">
                                <div class="grid gap-2">
                                    <Label for="blood_type" class="text-slate-700">Tipo sanguíneo</Label>
                                    <Select id="blood_type" name="blood_type" v-model="form.blood_type">
                                        <option value="">Selecione...</option>
                                        <option v-for="bloodType in props.bloodTypes" :key="bloodType" :value="bloodType">
                                            {{ bloodType }}
                                        </option>
                                    </Select>
                                    <InputError class="mt-2" :message="form.errors.blood_type" />
                                </div>

                                <div class="grid gap-2">
                                    <Label for="height" class="text-slate-700">Altura (cm)</Label>
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
                                    <Label for="weight" class="text-slate-700">Peso (kg)</Label>
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

                        <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                            <div class="mb-5">
                                <p class="text-xs font-bold tracking-[0.08em] text-slate-500 uppercase">Interoperabilidade</p>
                                <h2 class="mt-1 text-xl font-bold text-slate-950">Identificadores de saúde</h2>
                                <p class="mt-1 text-sm text-slate-500">Necessários para integração com laboratórios e com a RNDS.</p>
                            </div>

                            <div class="grid gap-4 md:grid-cols-2">
                                <div class="grid gap-2">
                                    <Label for="cns" class="text-slate-700">Cartão Nacional de Saúde (CNS)</Label>
                                    <Input
                                        id="cns"
                                        name="cns"
                                        inputmode="numeric"
                                        v-model="form.cns"
                                        :placeholder="props.patient?.cns_registered ? '••• cadastrado — preencha para alterar' : '000000000000000'"
                                        maxlength="15"
                                    />
                                    <p class="text-[10px] text-slate-500">15 dígitos numéricos</p>
                                    <InputError class="mt-2" :message="form.errors.cns" />
                                </div>

                                <div class="grid gap-2">
                                    <Label for="cpf" class="text-slate-700">CPF</Label>
                                    <Input
                                        id="cpf"
                                        name="cpf"
                                        inputmode="numeric"
                                        v-model="form.cpf"
                                        :placeholder="props.patient?.cpf_registered ? '••• cadastrado — preencha para alterar' : '00000000000'"
                                        maxlength="11"
                                    />
                                    <p class="text-[10px] text-slate-500">11 dígitos, sem pontos ou traços</p>
                                    <InputError class="mt-2" :message="form.errors.cpf" />
                                </div>
                            </div>
                        </div>

                        <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                            <div class="mb-5">
                                <p class="text-xs font-bold tracking-[0.08em] text-slate-500 uppercase">Cobertura</p>
                                <h2 class="mt-1 text-xl font-bold text-slate-950">Plano de saúde</h2>
                            </div>
                            <div class="grid gap-4 md:grid-cols-2">
                                <div class="grid gap-2">
                                    <Label for="insurance_provider" class="text-slate-700">Operadora do plano</Label>
                                    <Input
                                        id="insurance_provider"
                                        name="insurance_provider"
                                        v-model="form.insurance_provider"
                                        placeholder="Nome da operadora"
                                    />
                                    <InputError class="mt-2" :message="form.errors.insurance_provider" />
                                </div>

                                <div class="grid gap-2">
                                    <Label for="insurance_number" class="text-slate-700">Número do plano</Label>
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

                        <div class="rounded-lg border border-slate-200 bg-slate-50 p-5 shadow-sm sm:p-6">
                            <div class="mb-5">
                                <p class="text-xs font-bold tracking-[0.08em] text-slate-500 uppercase">Consentimento</p>
                                <h2 class="mt-1 text-xl font-bold text-slate-950">Telemedicina</h2>
                            </div>
                            <div class="flex items-start gap-3 rounded-lg border border-slate-200 bg-white p-4">
                                <div class="flex items-center">
                                    <Checkbox
                                        id="consent_telemedicine"
                                        :checked="form.consent_telemedicine"
                                        @update:checked="updateConsentTelemedicine"
                                    />
                                </div>
                                <div class="grid gap-1">
                                    <Label for="consent_telemedicine" class="cursor-pointer font-medium text-slate-700">
                                        Consentimento para telemedicina
                                    </Label>
                                    <p class="text-xs text-slate-600">
                                        Autorizo a realização de consultas médicas por meio de telemedicina, conforme a legislação vigente.
                                    </p>
                                    <InputError class="mt-2" :message="form.errors.consent_telemedicine" />
                                </div>
                            </div>
                        </div>
                    </template>

                    <div :class="['flex items-center gap-4 border-t border-slate-200', isDoctor ? 'pt-5' : 'pt-1']">
                        <Button
                            type="submit"
                            :disabled="form.processing"
                            :class="
                                isDoctor
                                    ? 'h-9 rounded-[9px] bg-teal-700 px-4 text-[13.5px] font-medium text-white hover:bg-teal-800'
                                    : 'bg-teal-500 text-slate-950 hover:bg-teal-400'
                            "
                        >
                            Salvar Alterações
                        </Button>

                        <Transition
                            enter-active-class="transition ease-in-out"
                            enter-from-class="opacity-0"
                            leave-active-class="transition ease-in-out"
                            leave-to-class="opacity-0"
                        >
                            <p v-show="recentlySuccessful" class="text-sm text-slate-600">Salvo.</p>
                        </Transition>
                    </div>

                    <Transition
                        enter-active-class="transition-all duration-300 ease-out"
                        enter-from-class="opacity-0 transform scale-95"
                        enter-to-class="opacity-100 transform scale-100"
                        leave-active-class="transition-all duration-200 ease-in"
                        leave-from-class="opacity-100 transform scale-100"
                        leave-to-class="opacity-0 transform scale-95"
                    >
                        <div v-if="recentlySuccessful && isPatient" class="rounded-lg border border-teal-200 bg-teal-50 p-4">
                            <div class="flex items-start gap-3">
                                <CheckCircle2 class="mt-0.5 h-5 w-5 flex-shrink-0 text-teal-700" />
                                <div class="flex-1">
                                    <h3 class="text-sm font-semibold text-teal-800">Perfil atualizado com sucesso!</h3>
                                    <p class="mt-1 text-sm text-teal-700">
                                        Suas informações foram salvas.
                                        <span v-if="!isSecondStageComplete" class="font-medium">
                                            Complete o contato de emergência para poder agendar consultas.
                                        </span>
                                        <span v-else class="font-medium"> Agora você pode agendar consultas normalmente. </span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </Transition>
                </form>

                <section
                    v-if="isDoctor"
                    id="languages"
                    class="scroll-mt-20 rounded-[14px] border border-slate-200 bg-white px-5 py-6 shadow-xs sm:px-7"
                >
                    <div class="mb-5 flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="text-[11px] font-semibold tracking-[0.08em] text-slate-500 uppercase">Comunicação</p>
                            <h2 class="mt-1 text-lg font-semibold tracking-normal text-slate-950">Idiomas que você atende</h2>
                            <p class="mt-1 text-[13.5px] text-slate-500">Pacientes podem filtrar médicos por idioma.</p>
                        </div>
                        <span class="grid size-9 place-items-center rounded-[10px] bg-teal-50 text-teal-800">
                            <Languages class="size-[18px]" />
                        </span>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <button
                            v-for="language in staticLanguages"
                            :key="language.code"
                            type="button"
                            class="inline-flex h-8 items-center gap-2 rounded-[8px] border border-teal-700 bg-teal-50 px-3 text-[12.5px] font-medium text-teal-900"
                        >
                            <span class="font-mono text-[11px] text-teal-700">{{ language.flag }}</span>
                            {{ language.label }}
                            <CheckCircle2 class="size-3" />
                        </button>
                    </div>

                    <div class="mt-4 space-y-2">
                        <p class="text-[11px] font-semibold tracking-[0.08em] text-slate-500 uppercase">Nível de proficiência</p>
                        <div
                            v-for="language in staticLanguages"
                            :key="`${language.code}-level`"
                            class="flex flex-col gap-3 rounded-[10px] border border-slate-200 px-4 py-3 sm:flex-row sm:items-center"
                        >
                            <span class="font-mono text-xs text-slate-500">{{ language.flag }}</span>
                            <span class="flex-1 text-sm font-medium text-slate-950">{{ language.label }}</span>
                            <select
                                class="h-8 rounded-[8px] border border-slate-300 bg-slate-50 px-3 text-[12.5px] text-slate-700"
                                :value="language.level"
                                disabled
                            >
                                <option>{{ language.level }}</option>
                            </select>
                        </div>
                    </div>
                </section>

                <section
                    v-if="isDoctor"
                    id="modality"
                    class="scroll-mt-20 rounded-[14px] border border-slate-200 bg-white px-5 py-6 shadow-xs sm:px-7"
                >
                    <div class="mb-5">
                        <p class="text-[11px] font-semibold tracking-[0.08em] text-slate-500 uppercase">Atendimento</p>
                        <h2 class="mt-1 text-lg font-semibold tracking-normal text-slate-950">Modalidades e valores</h2>
                        <p class="mt-1 text-[13.5px] text-slate-500">Defina como atende e quanto cobra por consulta.</p>
                    </div>

                    <div class="divide-y divide-slate-200">
                        <div v-for="modality in staticModalities" :key="modality.title" class="flex items-center gap-4 py-3.5">
                            <span class="grid size-9 shrink-0 place-items-center rounded-[10px] bg-teal-50 text-teal-800">
                                <component :is="modality.icon" class="size-[18px]" />
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="text-[13.5px] font-medium text-slate-950">{{ modality.title }}</p>
                                <p class="mt-0.5 text-[12.5px] text-slate-500">{{ modality.description }}</p>
                            </div>
                            <button
                                type="button"
                                class="inline-flex h-5 w-9 shrink-0 items-center rounded-full p-0.5"
                                :class="modality.enabled ? 'bg-teal-700' : 'bg-slate-300'"
                                :aria-checked="modality.enabled"
                                role="switch"
                                disabled
                            >
                                <span class="size-4 rounded-full bg-white shadow-sm" :class="modality.enabled ? 'translate-x-4' : ''" />
                            </button>
                        </div>
                    </div>

                    <div class="mt-5 border-t border-slate-200 pt-5">
                        <div class="grid gap-4 md:grid-cols-3">
                            <div class="grid gap-2">
                                <Label class="text-slate-700">Duração padrão</Label>
                                <select class="h-10 rounded-[9px] border border-slate-300 bg-slate-50 px-3 text-sm text-slate-700" disabled>
                                    <option>45 minutos</option>
                                </select>
                            </div>
                            <div class="grid gap-2">
                                <Label class="text-slate-700">Valor - online</Label>
                                <div class="relative">
                                    <span class="absolute top-1/2 left-3 -translate-y-1/2 text-sm text-slate-500">R$</span>
                                    <Input class="h-10 rounded-[9px] border-slate-300 bg-slate-50 pl-10 text-sm" model-value="380" disabled />
                                </div>
                            </div>
                            <div class="grid gap-2">
                                <Label class="text-slate-700">Valor - presencial</Label>
                                <div class="relative">
                                    <span class="absolute top-1/2 left-3 -translate-y-1/2 text-sm text-slate-500">R$</span>
                                    <Input class="h-10 rounded-[9px] border-slate-300 bg-slate-50 pl-10 text-sm" model-value="450" disabled />
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section
                    v-if="isDoctor"
                    id="locations"
                    class="scroll-mt-20 rounded-[14px] border border-slate-200 bg-white px-5 py-6 shadow-xs sm:px-7"
                >
                    <div class="mb-5 flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="text-[11px] font-semibold tracking-[0.08em] text-slate-500 uppercase">Onde atende</p>
                            <h2 class="mt-1 text-lg font-semibold tracking-normal text-slate-950">Locais de atendimento presencial</h2>
                            <p class="mt-1 text-[13.5px] text-slate-500">Endereços usados quando o paciente escolhe consulta presencial.</p>
                        </div>
                        <Button type="button" variant="outline" class="h-8 rounded-[7px] border-slate-300 px-3 text-xs" disabled>
                            <MapPin class="size-3.5" />
                            Adicionar local
                        </Button>
                    </div>

                    <div class="space-y-2">
                        <article
                            v-for="location in staticLocations"
                            :key="location.name"
                            class="flex flex-col gap-3 rounded-[12px] border border-slate-200 p-4 sm:flex-row sm:items-center"
                        >
                            <span class="grid size-10 shrink-0 place-items-center rounded-[10px] bg-teal-50 text-teal-800">
                                <component :is="location.icon" class="size-[18px]" />
                            </span>
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h3 class="text-sm font-semibold text-slate-950">{{ location.name }}</h3>
                                    <span class="rounded-full border border-slate-200 bg-white px-2 py-0.5 text-[11.5px] font-medium text-slate-600">
                                        {{ location.kind }}
                                    </span>
                                </div>
                                <p class="mt-1 text-[12.5px] text-slate-500">{{ location.address }}</p>
                            </div>
                            <div class="flex gap-2">
                                <Button type="button" variant="outline" class="h-8 rounded-[7px] border-slate-300 px-3 text-xs" disabled>
                                    Editar
                                </Button>
                                <Button type="button" variant="outline" class="h-8 rounded-[7px] border-rose-200 px-3 text-xs text-rose-700" disabled>
                                    Remover
                                </Button>
                            </div>
                        </article>
                    </div>
                </section>

                <section
                    v-if="isDoctor"
                    id="payouts"
                    class="scroll-mt-20 rounded-[14px] border border-slate-200 bg-white px-5 py-6 shadow-xs sm:px-7"
                >
                    <div class="mb-5">
                        <p class="text-[11px] font-semibold tracking-[0.08em] text-slate-500 uppercase">Recebimentos</p>
                        <h2 class="mt-1 text-lg font-semibold tracking-normal text-slate-950">Como você recebe</h2>
                        <p class="mt-1 text-[13.5px] text-slate-500">Repassamos a cada consulta concluída em até 2 dias úteis.</p>
                    </div>

                    <div class="grid gap-3 md:grid-cols-2">
                        <button type="button" class="flex items-center gap-4 rounded-[12px] border border-teal-700 bg-teal-50 p-4 text-left" disabled>
                            <span class="grid size-10 shrink-0 place-items-center rounded-[10px] bg-teal-700 text-white">
                                <Banknote class="size-[18px]" />
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block text-sm font-semibold text-slate-950">PIX</span>
                                <span class="mt-1 block text-[12.5px] text-slate-500">Recebimento mais rápido, sem taxas.</span>
                            </span>
                            <span class="grid size-[18px] place-items-center rounded-full border border-teal-700 bg-teal-700">
                                <span class="size-1.5 rounded-full bg-white" />
                            </span>
                        </button>

                        <button type="button" class="flex items-center gap-4 rounded-[12px] border border-slate-200 p-4 text-left" disabled>
                            <span class="grid size-10 shrink-0 place-items-center rounded-[10px] bg-slate-100 text-slate-500">
                                <CreditCard class="size-[18px]" />
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block text-sm font-semibold text-slate-950">Conta bancária</span>
                                <span class="mt-1 block text-[12.5px] text-slate-500">Transferência via TED.</span>
                            </span>
                            <span class="size-[18px] rounded-full border border-slate-300 bg-white" />
                        </button>
                    </div>

                    <div class="mt-5 grid gap-4 md:grid-cols-2">
                        <div class="grid gap-2">
                            <Label class="text-slate-700">Tipo de chave</Label>
                            <select class="h-10 rounded-[9px] border border-slate-300 bg-slate-50 px-3 text-sm text-slate-700" disabled>
                                <option>E-mail</option>
                            </select>
                        </div>
                        <div class="grid gap-2">
                            <Label class="text-slate-700">Chave PIX</Label>
                            <Input class="h-10 rounded-[9px] border-slate-300 bg-slate-50 text-sm" :model-value="user.email" disabled />
                        </div>
                    </div>
                </section>

                <section
                    v-if="isDoctor"
                    id="notifications"
                    class="scroll-mt-20 rounded-[14px] border border-slate-200 bg-white px-5 py-6 shadow-xs sm:px-7"
                >
                    <div class="mb-5 flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="text-[11px] font-semibold tracking-[0.08em] text-slate-500 uppercase">Notificações</p>
                            <h2 class="mt-1 text-lg font-semibold tracking-normal text-slate-950">Como te avisamos</h2>
                            <p class="mt-1 text-[13.5px] text-slate-500">Você pode mudar a qualquer momento quando a integração estiver pronta.</p>
                        </div>
                        <span class="grid size-9 place-items-center rounded-[10px] bg-teal-50 text-teal-800">
                            <WalletCards class="size-[18px]" />
                        </span>
                    </div>

                    <div class="divide-y divide-slate-200">
                        <div v-for="notification in staticNotifications" :key="notification.title" class="flex items-center gap-4 py-3.5">
                            <span class="grid size-9 shrink-0 place-items-center rounded-[10px] bg-slate-100 text-slate-500">
                                <component :is="notification.icon" class="size-[18px]" />
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="text-[13.5px] font-medium text-slate-950">{{ notification.title }}</p>
                                <p class="mt-0.5 text-[12.5px] text-slate-500">{{ notification.description }}</p>
                            </div>
                            <button
                                type="button"
                                class="inline-flex h-5 w-9 shrink-0 items-center rounded-full p-0.5"
                                :class="notification.enabled ? 'bg-teal-700' : 'bg-slate-300'"
                                :aria-checked="notification.enabled"
                                role="switch"
                                disabled
                            >
                                <span class="size-4 rounded-full bg-white shadow-sm" :class="notification.enabled ? 'translate-x-4' : ''" />
                            </button>
                        </div>
                    </div>
                </section>

                <div v-if="isDoctor" id="timeline" class="scroll-mt-20 rounded-[14px] border border-slate-200 bg-white px-5 py-6 shadow-xs sm:px-7">
                    <div class="mb-5 flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="text-[11px] font-semibold tracking-[0.08em] text-slate-500 uppercase">Trajetória</p>
                            <h2 class="mt-1 text-lg font-semibold tracking-normal text-slate-950">Formação e certificações</h2>
                            <p class="mt-1 text-[13.5px] text-slate-500">
                                Registre sua educação, cursos, certificados e projetos para completar seu perfil.
                            </p>
                        </div>
                        <span
                            v-if="isSecondStageComplete"
                            class="inline-flex h-7 items-center gap-1.5 rounded-full bg-teal-50 px-3 text-xs font-semibold text-teal-800 ring-1 ring-teal-100"
                        >
                            <CheckCircle2 class="size-3.5" />
                            Completo
                        </span>
                        <span
                            v-else
                            class="inline-flex h-7 items-center gap-1.5 rounded-full bg-amber-50 px-3 text-xs font-semibold text-amber-700 ring-1 ring-amber-200"
                        >
                            <AlertCircle class="size-3.5" />
                            Incompleto
                        </span>
                    </div>

                    <div v-if="!isSecondStageComplete" class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                        <p class="text-sm text-slate-700">
                            <strong>Nota:</strong> A segunda etapa de autenticação não é obrigatória, mas recomendamos que você complete seu perfil
                            adicionando sua formação acadêmica e experiência profissional. Isso ajuda os pacientes a conhecerem melhor sua trajetória.
                        </p>
                    </div>

                    <div class="mt-5 flex justify-end">
                        <Button
                            type="button"
                            @click="openCreateTimelineModal"
                            class="h-9 rounded-[9px] bg-teal-700 px-4 text-[13.5px] font-medium text-white hover:bg-teal-800"
                        >
                            <Plus class="mr-2 h-4 w-4" />
                            Adicionar Evento
                        </Button>
                    </div>

                    <div class="mt-5">
                        <Timeline :events="timelineEvents" :show-actions="true" @edit="openEditTimelineModal" @delete="deleteTimelineEvent" />
                    </div>
                </div>
            </div>

            <div id="danger" :class="['scroll-mt-20', isDoctor ? 'rounded-[14px] border border-rose-100 bg-white px-5 py-6 shadow-xs sm:px-7' : '']">
                <DeleteUser />
            </div>

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
