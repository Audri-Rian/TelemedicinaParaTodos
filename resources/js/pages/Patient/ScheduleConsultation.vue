<script setup lang="ts">
import ScheduleSelector from '@/components/ScheduleSelector.vue';
import IncompleteProfileModal from '@/components/modals/IncompleteProfileModal.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { useRouteGuard } from '@/composables/auth';
import { useInitials } from '@/composables/useInitials';
import AppLayout from '@/layouts/AppLayout.vue';
import * as appointmentsRoutes from '@/routes/appointments';
import * as patientRoutes from '@/routes/patient';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ArrowRight,
    Bell,
    Building2,
    Calendar,
    Check,
    CheckCircle2,
    ChevronLeft,
    CircleHelp,
    Clock,
    ExternalLink,
    MapPin,
    ShieldCheck,
    Sparkles,
    Stethoscope,
    Video,
    Wifi,
} from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';

interface Props {
    doctor: {
        id: string;
        user: {
            name: string;
            email: string;
            avatar?: string;
        };
        specializations: Array<{
            id: string;
            name: string;
        }>;
        consultation_fee: number | null;
        crm: string;
        biography: string;
    };
    availableDates: Array<{
        date: string;
        available_slots: string[];
    }>;
    patient: {
        id: string;
        user: {
            name: string;
        };
    };
    initialSelection?: {
        date?: string | null;
        time?: string | null;
        type?: string | null;
    };
}

const props = defineProps<Props>();

const { canAccessPatientRoute } = useRouteGuard();
const { getInitials } = useInitials();

// Estado do formulário
const consultationType = ref<'online' | 'presential'>(props.initialSelection?.type === 'presencial' ? 'presential' : 'online');
const selectedDate = ref<string | null>(props.initialSelection?.date ?? null);
const selectedTime = ref<string | null>(props.initialSelection?.time ?? null);
const isSubmitting = ref(false);
const errors = ref<Record<string, string>>({});
const showIncompleteProfileModal = ref(false);

// Dados do médico
const formattedSelectedDate = computed(() => formatDate(selectedDate.value));
const primarySpecialization = computed(() => props.doctor.specializations?.[0]?.name ?? 'Especialista');
const consultationPrice = computed(() => (props.doctor.consultation_fee == null ? null : Number(props.doctor.consultation_fee)));
const formattedConsultationFee = computed(() => (consultationPrice.value == null ? 'Valor a consultar' : formatCurrency(consultationPrice.value)));
const consultationModeLabel = computed(() => (consultationType.value === 'online' ? 'Consulta por vídeo' : 'Consulta presencial'));
const consultationModeShortLabel = computed(() => (consultationType.value === 'online' ? 'Online' : 'Presencial'));
const canSubmit = computed(() => Boolean(selectedDate.value && selectedTime.value && !isSubmitting.value));
const doctorProfileHref = computed(() => patientRoutes.doctorPerfil({ query: { doctor_id: props.doctor.id } }).url);

const quickSlots = computed(() => {
    return props.availableDates.flatMap((item) => item.available_slots.slice(0, 2).map((slot) => ({ date: item.date, slot }))).slice(0, 4);
});

const selectedDateLongLabel = computed(() => {
    if (!selectedDate.value) {
        return null;
    }

    return formatDateLong(selectedDate.value);
});

const visibleFieldErrors = computed(() => {
    return Object.entries(errors.value).filter(([key, value]) => Boolean(value) && key !== 'general' && key !== 'datetime');
});

const stepItems = computed(() => [
    {
        label: 'Modalidade',
        sub: 'Online ou presencial',
        state: 'done',
    },
    {
        label: 'Data e hora',
        sub: 'Calendário',
        state: selectedDate.value && selectedTime.value ? 'done' : 'active',
    },
    {
        label: 'Revisão',
        sub: 'Dados da consulta',
        state: selectedDate.value && selectedTime.value ? 'active' : 'pending',
    },
    {
        label: 'Pagamento',
        sub: 'Em breve',
        state: 'pending',
    },
]);

// Verificar acesso ao montar componente
onMounted(() => {
    canAccessPatientRoute();
});

// Função para confirmar agendamento
const confirmAppointment = async () => {
    if (!selectedDate.value || !selectedTime.value) {
        errors.value.datetime = 'Selecione data e horário';
        return;
    }

    isSubmitting.value = true;
    errors.value = {};

    // Combinar data e horário
    const scheduledAt = `${selectedDate.value}T${selectedTime.value}:00`;

    // Montar payload
    const payload = {
        doctor_id: props.doctor.id,
        scheduled_at: scheduledAt,
        notes: consultationType.value === 'online' ? 'Consulta online' : 'Consulta presencial',
        metadata: {
            type: consultationType.value,
        },
    };

    router.post(appointmentsRoutes.store.url(), payload, {
        onSuccess: () => {
            // Redirecionamento automático para /appointments/{id} via redirect no controller
        },
        onError: (pageErrors) => {
            const normalizedErrors: Record<string, string> = {};

            Object.entries(pageErrors as Record<string, string | string[]>).forEach(([key, value]) => {
                normalizedErrors[key] = Array.isArray(value) ? value.join(' ') : value;
            });

            if (normalizedErrors.error && !normalizedErrors.general) {
                normalizedErrors.general = normalizedErrors.error;
            }

            // Verificar se é erro de cadastro incompleto
            const errorMessage = normalizedErrors.general || normalizedErrors.error || '';
            const isIncompleteProfileError =
                errorMessage.includes('cadastro completo') ||
                errorMessage.includes('segunda etapa') ||
                errorMessage.includes('contato de emergência');

            if (isIncompleteProfileError) {
                showIncompleteProfileModal.value = true;
                errors.value = {}; // Limpar erros para não mostrar em vermelho
            } else {
                errors.value = normalizedErrors;
            }
        },
        onFinish: () => {
            isSubmitting.value = false;
        },
    });
};

const handleDateChange = (value: string | null) => {
    selectedDate.value = value;
    errors.value.datetime = '';
};

const handleTimeChange = (value: string | null) => {
    selectedTime.value = value;
    errors.value.datetime = '';
};

const handleQuickSlotSelect = (date: string, slot: string) => {
    selectedDate.value = date;
    selectedTime.value = slot;
    errors.value.datetime = '';
};

// Formatar data para exibição
const formatDate = (dateString: string | null): string => {
    if (!dateString) {
        return '';
    }

    const date = new Date(`${dateString}T00:00:00`);
    return date.toLocaleDateString('pt-BR', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
    });
};

const formatDateLong = (dateString: string): string => {
    const date = new Date(`${dateString}T00:00:00`);

    return date.toLocaleDateString('pt-BR', {
        weekday: 'long',
        day: '2-digit',
        month: 'long',
        year: 'numeric',
    });
};

const formatDateChip = (dateString: string): string => {
    const date = new Date(`${dateString}T00:00:00`);

    return date.toLocaleDateString('pt-BR', {
        day: '2-digit',
        month: 'short',
    });
};

const formatCurrency = (value: number): string => {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL',
    }).format(value);
};

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
        <div class="min-h-full bg-[#f7f8fb] pb-28 text-slate-950">
            <div class="flex w-full flex-col gap-6 px-3 py-6 sm:px-4 lg:px-5 xl:px-6">
                <header class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <p class="text-xs font-semibold tracking-[0.14em] text-teal-700 uppercase">Agendamento de consulta</p>
                        <h1 class="mt-2 text-2xl font-semibold tracking-tight text-slate-950 sm:text-3xl">Agendar consulta</h1>
                        <p class="mt-2 text-sm text-slate-600">Com {{ props.doctor.user.name }} · {{ primarySpecialization }}</p>
                    </div>

                    <Button as-child variant="outline" class="h-10 border-slate-200 bg-white text-sm font-semibold text-slate-700 hover:bg-slate-50">
                        <Link :href="patientRoutes.searchConsultations()">
                            <ChevronLeft class="mr-2 h-4 w-4" />
                            Trocar médico
                        </Link>
                    </Button>
                </header>

                <div
                    v-if="errors.general && !showIncompleteProfileModal"
                    class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700"
                >
                    {{ errors.general }}
                </div>

                <nav class="grid gap-2 rounded-xl border border-slate-200 bg-white p-3 shadow-sm md:grid-cols-4" aria-label="Etapas do agendamento">
                    <div
                        v-for="(step, index) in stepItems"
                        :key="step.label"
                        :class="[
                            'flex items-center gap-3 rounded-lg px-3 py-2 transition',
                            step.state === 'active' ? 'bg-teal-50 text-teal-950' : 'text-slate-500',
                        ]"
                    >
                        <span
                            :class="[
                                'grid h-8 w-8 shrink-0 place-items-center rounded-full border text-xs font-bold',
                                step.state === 'done'
                                    ? 'border-teal-600 bg-teal-600 text-white'
                                    : step.state === 'active'
                                      ? 'border-teal-600 bg-white text-teal-700'
                                      : 'border-slate-200 bg-slate-50 text-slate-400',
                            ]"
                        >
                            <Check v-if="step.state === 'done'" class="h-4 w-4" />
                            <span v-else>{{ index + 1 }}</span>
                        </span>
                        <span class="min-w-0">
                            <span class="block text-sm font-semibold">{{ step.label }}</span>
                            <span class="block truncate text-xs text-slate-500">{{ step.sub }}</span>
                        </span>
                    </div>
                </nav>

                <div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_360px]">
                    <main class="space-y-5">
                        <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                            <div class="mb-5">
                                <p class="text-xs font-semibold tracking-[0.14em] text-slate-500 uppercase">Passo 1 de 3</p>
                                <h2 class="mt-1 text-xl font-semibold tracking-tight text-slate-950">Como você prefere ser atendido?</h2>
                                <p class="mt-1 text-sm text-slate-600">A modalidade fica salva junto com o agendamento.</p>
                            </div>

                            <div class="grid gap-3 md:grid-cols-2">
                                <button
                                    type="button"
                                    @click="consultationType = 'online'"
                                    :class="[
                                        'relative rounded-xl border p-4 text-left transition hover:border-teal-300 hover:bg-teal-50/50',
                                        consultationType === 'online' ? 'border-teal-500 bg-teal-50 shadow-sm' : 'border-slate-200 bg-white',
                                    ]"
                                >
                                    <div class="flex items-start justify-between gap-3">
                                        <span class="grid h-10 w-10 place-items-center rounded-lg border border-teal-100 bg-white text-teal-700">
                                            <Video class="h-5 w-5" />
                                        </span>
                                        <span class="rounded-full bg-teal-600 px-2.5 py-1 text-[11px] font-bold text-white">Recomendado</span>
                                    </div>
                                    <div class="mt-4">
                                        <p class="text-sm font-semibold text-slate-950">Consulta por vídeo</p>
                                        <p class="mt-1 text-sm leading-5 text-slate-600">
                                            Link liberado antes da consulta, com lembretes automáticos.
                                        </p>
                                    </div>
                                    <div class="mt-4 flex items-center gap-2 text-sm">
                                        <span class="font-semibold text-slate-950">{{ formattedConsultationFee }}</span>
                                        <span class="text-slate-500">por consulta</span>
                                    </div>
                                    <span
                                        v-if="consultationType === 'online'"
                                        class="absolute right-4 bottom-4 grid h-5 w-5 place-items-center rounded-full bg-teal-600 text-white"
                                    >
                                        <Check class="h-3 w-3" />
                                    </span>
                                </button>

                                <button
                                    type="button"
                                    @click="consultationType = 'presential'"
                                    :class="[
                                        'relative rounded-xl border p-4 text-left transition hover:border-teal-300 hover:bg-teal-50/50',
                                        consultationType === 'presential' ? 'border-teal-500 bg-teal-50 shadow-sm' : 'border-slate-200 bg-white',
                                    ]"
                                >
                                    <div class="flex items-start justify-between gap-3">
                                        <span class="grid h-10 w-10 place-items-center rounded-lg border border-slate-200 bg-white text-slate-700">
                                            <Building2 class="h-5 w-5" />
                                        </span>
                                    </div>
                                    <div class="mt-4">
                                        <p class="text-sm font-semibold text-slate-950">Consulta presencial</p>
                                        <p class="mt-1 text-sm leading-5 text-slate-600">Atendimento no consultório indicado pela equipe médica.</p>
                                    </div>
                                    <div class="mt-4 flex items-center gap-2 text-sm">
                                        <span class="font-semibold text-slate-950">{{ formattedConsultationFee }}</span>
                                        <span class="text-slate-500">por consulta</span>
                                    </div>
                                    <span
                                        v-if="consultationType === 'presential'"
                                        class="absolute right-4 bottom-4 grid h-5 w-5 place-items-center rounded-full bg-teal-600 text-white"
                                    >
                                        <Check class="h-3 w-3" />
                                    </span>
                                </button>
                            </div>

                            <div v-if="consultationType === 'online'" class="mt-5 rounded-xl border border-slate-200 bg-slate-50 p-4">
                                <div class="mb-3 flex items-center gap-2 text-sm font-semibold text-slate-950">
                                    <Video class="h-4 w-4 text-teal-700" />
                                    Como funciona a consulta por vídeo
                                </div>
                                <div class="grid gap-3 sm:grid-cols-2">
                                    <div class="flex gap-3">
                                        <span
                                            class="grid h-8 w-8 shrink-0 place-items-center rounded-lg border border-slate-200 bg-white text-teal-700"
                                        >
                                            <Wifi class="h-4 w-4" />
                                        </span>
                                        <div>
                                            <p class="text-sm font-medium text-slate-900">Internet estável</p>
                                            <p class="text-xs text-slate-500">Recomendado usar câmera e microfone.</p>
                                        </div>
                                    </div>
                                    <div class="flex gap-3">
                                        <span
                                            class="grid h-8 w-8 shrink-0 place-items-center rounded-lg border border-slate-200 bg-white text-teal-700"
                                        >
                                            <Bell class="h-4 w-4" />
                                        </span>
                                        <div>
                                            <p class="text-sm font-medium text-slate-900">Lembretes</p>
                                            <p class="text-xs text-slate-500">Você será avisado antes do atendimento.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div v-else class="mt-5 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                                <div class="flex gap-3">
                                    <MapPin class="mt-0.5 h-4 w-4 shrink-0" />
                                    <p>
                                        O local de atendimento será confirmado nos detalhes da consulta. Se necessário, a equipe entrará em contato.
                                    </p>
                                </div>
                            </div>
                        </section>

                        <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                            <div class="mb-5 flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                                <div>
                                    <p class="text-xs font-semibold tracking-[0.14em] text-slate-500 uppercase">Passo 2 de 3</p>
                                    <h2 class="mt-1 text-xl font-semibold tracking-tight text-slate-950">Escolha a data e o horário</h2>
                                    <p class="mt-1 text-sm text-slate-600">Todos os horários consideram seu fuso local.</p>
                                </div>

                                <div v-if="quickSlots.length" class="flex flex-wrap gap-2">
                                    <button
                                        v-for="slot in quickSlots"
                                        :key="`${slot.date}-${slot.slot}`"
                                        type="button"
                                        @click="handleQuickSlotSelect(slot.date, slot.slot)"
                                        :class="[
                                            'h-9 rounded-full border px-3 text-xs font-semibold transition',
                                            selectedDate === slot.date && selectedTime === slot.slot
                                                ? 'border-teal-600 bg-teal-600 text-white'
                                                : 'border-slate-200 bg-white text-slate-700 hover:border-teal-300 hover:bg-teal-50',
                                        ]"
                                    >
                                        {{ formatDateChip(slot.date) }} · {{ slot.slot }}
                                    </button>
                                </div>
                            </div>

                            <ScheduleSelector
                                :available-dates="props.availableDates"
                                :selected-date="selectedDate"
                                :selected-time="selectedTime"
                                :disabled="isSubmitting"
                                timezone-notice=""
                                @update:selected-date="handleDateChange"
                                @update:selected-time="handleTimeChange"
                            />

                            <p class="mt-4 text-sm text-slate-500">
                                <template v-if="selectedDate && selectedTime">
                                    Consulta selecionada para {{ formattedSelectedDate }} às {{ selectedTime }}.
                                </template>
                                <template v-else-if="selectedDate"> Consulta agendada para {{ formattedSelectedDate }}. </template>
                                <template v-else> Selecione uma data para visualizar os horários disponíveis. </template>
                            </p>

                            <div
                                v-if="errors.datetime"
                                class="mt-3 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm font-medium text-red-700"
                            >
                                {{ errors.datetime }}
                            </div>

                            <div
                                v-if="visibleFieldErrors.length"
                                class="mt-3 space-y-1 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm font-medium text-red-700"
                            >
                                <p v-for="[key, error] in visibleFieldErrors" :key="key">{{ error }}</p>
                            </div>
                        </section>

                        <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                            <div class="mb-4">
                                <p class="text-xs font-semibold tracking-[0.14em] text-slate-500 uppercase">Passo 3 de 3</p>
                                <h2 class="mt-1 text-xl font-semibold tracking-tight text-slate-950">Revise antes de confirmar</h2>
                            </div>

                            <div class="grid gap-3 rounded-xl border border-slate-200 bg-slate-50 p-4 sm:grid-cols-2">
                                <div class="flex gap-3">
                                    <Stethoscope class="mt-0.5 h-4 w-4 shrink-0 text-slate-400" />
                                    <div>
                                        <p class="text-xs font-semibold tracking-wide text-slate-500 uppercase">Médico</p>
                                        <p class="mt-1 text-sm font-semibold text-slate-950">{{ props.doctor.user.name }}</p>
                                        <p class="text-xs text-slate-500">{{ primarySpecialization }} · CRM {{ props.doctor.crm }}</p>
                                    </div>
                                </div>
                                <div class="flex gap-3">
                                    <Calendar class="mt-0.5 h-4 w-4 shrink-0 text-slate-400" />
                                    <div>
                                        <p class="text-xs font-semibold tracking-wide text-slate-500 uppercase">Data e hora</p>
                                        <p class="mt-1 text-sm font-semibold text-slate-950">
                                            <template v-if="selectedDateLongLabel && selectedTime"
                                                >{{ selectedDateLongLabel }}, {{ selectedTime }}</template
                                            >
                                            <template v-else>A definir</template>
                                        </p>
                                    </div>
                                </div>
                                <div class="flex gap-3">
                                    <component
                                        :is="consultationType === 'online' ? Video : Building2"
                                        class="mt-0.5 h-4 w-4 shrink-0 text-slate-400"
                                    />
                                    <div>
                                        <p class="text-xs font-semibold tracking-wide text-slate-500 uppercase">Modalidade</p>
                                        <p class="mt-1 text-sm font-semibold text-slate-950">{{ consultationModeLabel }}</p>
                                    </div>
                                </div>
                                <div class="flex gap-3">
                                    <Sparkles class="mt-0.5 h-4 w-4 shrink-0 text-slate-400" />
                                    <div>
                                        <p class="text-xs font-semibold tracking-wide text-slate-500 uppercase">Valor</p>
                                        <p class="mt-1 text-sm font-semibold text-slate-950">{{ formattedConsultationFee }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-4 rounded-lg border border-teal-100 bg-teal-50 px-4 py-3">
                                <div class="flex gap-3 text-sm text-teal-950">
                                    <ShieldCheck class="mt-0.5 h-4 w-4 shrink-0 text-teal-700" />
                                    <p>
                                        Seus dados de agendamento seguem a política de privacidade da plataforma. Revise os detalhes antes de
                                        confirmar.
                                    </p>
                                </div>
                            </div>
                        </section>
                    </main>

                    <aside class="space-y-4 lg:sticky lg:top-6 lg:self-start">
                        <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                            <div class="flex gap-4">
                                <Avatar class="h-14 w-14 ring-4 ring-teal-50">
                                    <AvatarImage v-if="props.doctor.user.avatar" :src="props.doctor.user.avatar" />
                                    <AvatarFallback class="bg-teal-50 text-base font-semibold text-teal-700">
                                        {{ getInitials(props.doctor.user.name) }}
                                    </AvatarFallback>
                                </Avatar>
                                <div class="min-w-0 flex-1">
                                    <h3 class="truncate text-sm font-semibold text-slate-950">{{ props.doctor.user.name }}</h3>
                                    <p class="mt-0.5 text-sm text-slate-600">{{ primarySpecialization }}</p>
                                    <p class="mt-0.5 text-xs text-slate-500">CRM {{ props.doctor.crm }}</p>
                                </div>
                            </div>

                            <div class="mt-4 flex flex-wrap gap-2">
                                <span
                                    class="inline-flex items-center gap-1 rounded-full border border-teal-100 bg-teal-50 px-2.5 py-1 text-xs font-semibold text-teal-700"
                                >
                                    <CheckCircle2 class="h-3 w-3" />
                                    Perfil completo
                                </span>
                                <span
                                    class="inline-flex items-center gap-1 rounded-full border border-slate-200 bg-white px-2.5 py-1 text-xs font-semibold text-slate-600"
                                >
                                    <Video class="h-3 w-3" />
                                    Telemedicina
                                </span>
                            </div>

                            <Button
                                as-child
                                variant="outline"
                                class="mt-4 h-9 border-slate-200 bg-white text-xs font-semibold text-slate-700 hover:bg-slate-50"
                            >
                                <Link :href="doctorProfileHref">
                                    <ExternalLink class="mr-2 h-3.5 w-3.5" />
                                    Ver perfil completo
                                </Link>
                            </Button>

                            <div class="my-5 h-px bg-slate-200"></div>

                            <p class="text-xs font-semibold tracking-[0.14em] text-slate-500 uppercase">Resumo da consulta</p>
                            <div class="mt-4 space-y-4">
                                <div class="flex gap-3">
                                    <component
                                        :is="consultationType === 'online' ? Video : Building2"
                                        class="mt-0.5 h-4 w-4 shrink-0 text-slate-400"
                                    />
                                    <div>
                                        <p class="text-xs font-semibold tracking-wide text-slate-500 uppercase">Modalidade</p>
                                        <p class="mt-1 text-sm font-semibold text-slate-950">{{ consultationModeLabel }}</p>
                                    </div>
                                </div>
                                <div class="flex gap-3">
                                    <Calendar class="mt-0.5 h-4 w-4 shrink-0 text-slate-400" />
                                    <div>
                                        <p class="text-xs font-semibold tracking-wide text-slate-500 uppercase">Data</p>
                                        <p class="mt-1 text-sm font-semibold text-slate-950">{{ selectedDateLongLabel ?? 'A definir' }}</p>
                                    </div>
                                </div>
                                <div class="flex gap-3">
                                    <Clock class="mt-0.5 h-4 w-4 shrink-0 text-slate-400" />
                                    <div>
                                        <p class="text-xs font-semibold tracking-wide text-slate-500 uppercase">Horário</p>
                                        <p class="mt-1 text-sm font-semibold text-slate-950">{{ selectedTime ?? 'A definir' }}</p>
                                    </div>
                                </div>
                                <div class="flex gap-3">
                                    <Clock class="mt-0.5 h-4 w-4 shrink-0 text-slate-400" />
                                    <div>
                                        <p class="text-xs font-semibold tracking-wide text-slate-500 uppercase">Duração</p>
                                        <p class="mt-1 text-sm font-semibold text-slate-950">45 minutos</p>
                                    </div>
                                </div>
                            </div>

                            <div class="my-5 h-px bg-slate-200"></div>

                            <div class="flex items-end justify-between gap-4">
                                <div>
                                    <p class="text-xs text-slate-500">Valor</p>
                                    <p class="mt-1 text-2xl font-semibold tracking-tight text-slate-950">{{ formattedConsultationFee }}</p>
                                </div>
                                <p class="max-w-28 text-right text-xs leading-5 text-slate-500">{{ consultationModeShortLabel }} · 45 min</p>
                            </div>
                        </section>

                        <section class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <div class="flex gap-3">
                                <span class="grid h-8 w-8 shrink-0 place-items-center rounded-lg bg-white text-teal-700">
                                    <CircleHelp class="h-4 w-4" />
                                </span>
                                <div>
                                    <h3 class="text-sm font-semibold text-slate-950">Precisa de ajuda?</h3>
                                    <p class="mt-1 text-sm leading-5 text-slate-600">Suporte por chat de seg. a sáb., 8h-22h.</p>
                                    <Link
                                        :href="patientRoutes.messages()"
                                        class="mt-2 inline-flex text-sm font-semibold text-teal-700 hover:text-teal-800"
                                    >
                                        Falar com suporte
                                    </Link>
                                </div>
                            </div>
                        </section>
                    </aside>
                </div>
            </div>

            <div
                class="fixed inset-x-0 bottom-0 z-20 border-t border-slate-200 bg-white/95 px-4 py-3 shadow-[0_-12px_30px_rgba(15,23,42,0.08)] backdrop-blur"
            >
                <div class="flex w-full flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <Button as-child variant="outline" class="h-10 border-slate-200 bg-white font-semibold text-slate-700 hover:bg-slate-50">
                        <Link :href="patientRoutes.searchConsultations()">
                            <ChevronLeft class="mr-2 h-4 w-4" />
                            Voltar
                        </Link>
                    </Button>

                    <div class="hidden min-w-0 flex-1 items-center justify-center gap-2 text-sm text-slate-500 md:flex">
                        <Calendar class="h-4 w-4" />
                        <span v-if="selectedDate && selectedTime" class="truncate font-semibold text-slate-900">
                            {{ formattedSelectedDate }} · {{ selectedTime }} · {{ consultationModeShortLabel }} · {{ formattedConsultationFee }}
                        </span>
                        <span v-else>Escolha uma data e horário para liberar a confirmação.</span>
                    </div>

                    <Button
                        @click="confirmAppointment"
                        :disabled="!canSubmit"
                        class="h-11 bg-teal-600 px-6 font-semibold text-white hover:bg-teal-700 disabled:bg-slate-300 disabled:text-slate-500"
                    >
                        <span v-if="isSubmitting">Agendando...</span>
                        <span v-else class="inline-flex items-center">
                            Confirmar agendamento
                            <ArrowRight class="ml-2 h-4 w-4" />
                        </span>
                    </Button>
                </div>
            </div>
        </div>

        <!-- Modal de Cadastro Incompleto -->
        <IncompleteProfileModal :is-open="showIncompleteProfileModal" @close="showIncompleteProfileModal = false" />
    </AppLayout>
</template>
