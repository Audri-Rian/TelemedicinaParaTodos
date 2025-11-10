<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { Video, Building2 } from 'lucide-vue-next';
import * as patientRoutes from '@/routes/patient';
import { onMounted, ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { useRouteGuard } from '@/composables/auth';
import { Button } from '@/components/ui/button';
import { Link } from '@inertiajs/vue3';
import * as appointmentsRoutes from '@/routes/appointments';
import DoctorCard from '@/components/DoctorCard.vue';
import ScheduleSelector from '@/components/ScheduleSelector.vue';
import IncompleteProfileModal from '@/components/modals/IncompleteProfileModal.vue';

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
}

const props = defineProps<Props>();

const { canAccessPatientRoute } = useRouteGuard();

// Estado do formulário
const consultationType = ref<'online' | 'presential'>('online');
const selectedDate = ref<string | null>(null);
const selectedTime = ref<string | null>(null);
const isSubmitting = ref(false);
const errors = ref<Record<string, string>>({});
const showIncompleteProfileModal = ref(false);

// Dados do médico
const formattedSelectedDate = computed(() => formatDate(selectedDate.value));

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
        patient_id: props.patient.id,
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
            const isIncompleteProfileError = errorMessage.includes('cadastro completo') || 
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
        }
    });
};

const handleDateChange = (value: string | null) => {
    selectedDate.value = value;
};

const handleTimeChange = (value: string | null) => {
    selectedTime.value = value;
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
        month: 'long' 
    });
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
        <div class="flex h-full flex-1 flex-col gap-3 overflow-x-auto bg-white px-4 py-3">
            <!-- Header -->
            <div class="flex flex-col gap-1 items-center text-center">
                <h1 class="text-2xl font-bold text-gray-900">Agendar Consulta</h1>
                <p class="text-sm text-gray-600">Selecione o tipo de consulta e escolha o melhor horário para você.</p>
            </div>

            <div v-if="errors.general && !showIncompleteProfileModal" class="max-w-2xl mx-auto w-full rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">
                {{ errors.general }}
            </div>

            <!-- Barra de Progresso -->
            <div class="flex justify-center items-center gap-6 max-w-4xl mx-auto w-full">
                <div class="flex flex-col items-center gap-2">
                    <div class="w-full h-1 bg-primary rounded-full"></div>
                    <span class="text-sm font-medium text-gray-900">Informações</span>
                </div>
                <div class="flex flex-col items-center gap-2 flex-1">
                    <div class="w-full h-1 bg-primary rounded-full"></div>
                    <span class="text-sm font-medium text-primary">Horário</span>
                </div>
                <div class="flex flex-col items-center gap-2">
                    <div class="w-full h-1 bg-gray-200 rounded-full"></div>
                    <span class="text-sm font-medium text-gray-400">Pagamento</span>
                </div>
            </div>

            <!-- Conteúdo Principal -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-3 w-full">
                <DoctorCard
                    :doctor="props.doctor"
                    :selected-date="selectedDate"
                    :show-availability-badge="false"
                    class="lg:h-full"
                >
                    <template #actions>
                        <Button as-child variant="outline" class="w-full bg-secondary hover:bg-secondary/80 text-gray-900 text-xs py-1.5 h-8 mt-1">
                            <Link :href="patientRoutes.searchConsultations()">
                                Trocar médico
                            </Link>
                        </Button>
                    </template>
                </DoctorCard>

                <!-- Painel Direito: Detalhes da Consulta -->
                <div class="lg:col-span-2 space-y-3">
                    <!-- Tipo de Consulta -->
                    <div class="space-y-2">
                        <h2 class="text-lg font-bold text-gray-900">Selecione o Tipo de Consulta</h2>
                        <div class="grid grid-cols-2 gap-3">
                            <!-- Consulta Online -->
                            <button
                                @click="consultationType = 'online'"
                                :class="[
                                    'flex flex-col items-center justify-center gap-2 p-4 rounded-lg border-2 transition-all',
                                    consultationType === 'online'
                                        ? 'bg-primary/10 border-primary'
                                        : 'bg-secondary/30 border-gray-200 hover:border-gray-300'
                                ]"
                            >
                                <Video 
                                    :class="[
                                        'h-6 w-6',
                                        consultationType === 'online' ? 'text-primary' : 'text-gray-600'
                                    ]"
                                />
                                <div class="text-center">
                                    <p class="font-semibold text-sm text-gray-900">Consulta Online</p>
                                    <p class="text-xs text-gray-600">via vídeo</p>
                                </div>
                            </button>

                            <!-- Consulta Presencial -->
                            <button
                                @click="consultationType = 'presential'"
                                :class="[
                                    'flex flex-col items-center justify-center gap-2 p-4 rounded-lg border-2 transition-all',
                                    consultationType === 'presential'
                                        ? 'bg-primary/10 border-primary'
                                        : 'bg-secondary/30 border-gray-200 hover:border-gray-300'
                                ]"
                            >
                                <Building2 
                                    :class="[
                                        'h-6 w-6',
                                        consultationType === 'presential' ? 'text-primary' : 'text-gray-600'
                                    ]"
                                />
                                <div class="text-center">
                                    <p class="font-semibold text-sm text-gray-900">Consulta Presencial</p>
                                    <p class="text-xs text-gray-600">no consultório</p>
                                </div>
                            </button>
                        </div>
                    </div>

                    <!-- Data e Horário -->
                    <div class="space-y-4">
                        <h2 class="text-lg font-bold text-gray-900">Selecione a Data e Horário</h2>
                        <ScheduleSelector
                            :available-dates="props.availableDates"
                            :selected-date="selectedDate"
                            :selected-time="selectedTime"
                            :disabled="isSubmitting"
                            @update:selected-date="handleDateChange"
                            @update:selected-time="handleTimeChange"
                        />
                        <p class="text-xs text-gray-500">
                            <template v-if="selectedDate && selectedTime">
                                Consulta selecionada para {{ formattedSelectedDate }} às {{ selectedTime }}.
                            </template>
                            <template v-else-if="selectedDate">
                                Consulta agendada para {{ formattedSelectedDate }}.
                            </template>
                            <template v-else>
                                Selecione uma data para visualizar os horários disponíveis.
                            </template>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Botões de Ação -->
            <div class="flex justify-end gap-3 w-full pt-2">
                <Button as-child variant="outline" class="bg-secondary hover:bg-secondary/80 text-gray-900 h-9 px-6">
                    <Link :href="patientRoutes.searchConsultations()">
                        Voltar
                    </Link>
                </Button>
                <Button 
                    @click="confirmAppointment"
                    :disabled="isSubmitting || !selectedDate || !selectedTime"
                    class="bg-primary hover:bg-primary/90 text-gray-900 font-semibold px-6 h-9"
                >
                    <span v-if="isSubmitting">Agendando...</span>
                    <span v-else>Confirmar Agendamento</span>
                </Button>
                
                <!-- Exibir erros -->
                <div v-if="Object.keys(errors).length > 0" class="w-full">
                    <div v-for="(error, key) in errors" :key="key" class="text-sm text-red-600 mt-2">
                        {{ error }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal de Cadastro Incompleto -->
        <IncompleteProfileModal 
            :is-open="showIncompleteProfileModal"
            @close="showIncompleteProfileModal = false"
        />
    </AppLayout>
</template>
