<script setup lang="ts">
import AddLocationModal from '@/components/modals/doctor/AddLocationModal.vue';
import * as availabilityRoutes from '@/routes/doctor/availability';
import * as locationRoutes from '@/routes/doctor/locations';
import * as scheduleRoutes from '@/routes/doctor/schedule';
import { usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { Calendar, ChevronDown, ChevronLeft, ChevronRight, ChevronUp, Clock, MapPin, Plus, Trash2, Video } from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';

// Interfaces
interface ServiceLocation {
    id: string;
    name: string;
    type: string;
    type_label: string;
    address?: string;
    phone?: string;
    description?: string;
}

interface AvailabilitySlot {
    id: string;
    day_of_week?: string;
    day_of_week_label?: string;
    specific_date?: string;
    start_time: string;
    end_time: string;
    location_id?: string;
    location?: {
        id: string;
        name: string;
        type: string;
    } | null;
}

interface SpecificSlotsByDate {
    date: string;
    formatted_date: string;
    slots: AvailabilitySlot[];
}

interface BlockedDate {
    id: string;
    blocked_date: string;
    formatted_date: string;
    reason?: string;
}

interface ScheduleConfig {
    locations: ServiceLocation[];
    recurring_slots: AvailabilitySlot[];
    specific_slots: SpecificSlotsByDate[];
    blocked_dates: BlockedDate[];
}

interface Props {
    scheduleConfig?: ScheduleConfig;
}

const props = withDefaults(defineProps<Props>(), {
    scheduleConfig: () => ({
        locations: [],
        recurring_slots: [],
        specific_slots: [],
        blocked_dates: [],
    }),
});

// Obter ID do médico do usuário autenticado
const page = usePage();
const auth = computed(() => (page.props as any).auth);
const doctorId = computed(() => auth.value?.profile?.id);

// Estados do calendário para datas específicas
const currentCalendarDate = ref(new Date());
const selectedDates = ref<Set<string>>(new Set());
const expandedSpecificDates = ref<Set<string>>(new Set());

// Configurações das datas específicas
const specificDatesConfig = ref<
    Record<
        string,
        {
            available: boolean;
            timeSlots: Array<{
                id: string;
                startTime: string;
                endTime: string;
                location_id?: string;
                location: string;
            }>;
        }
    >
>({});

// Estados dos dados do backend
const serviceLocations = ref<ServiceLocation[]>(props.scheduleConfig.locations || []);
const specificSlots = ref<SpecificSlotsByDate[]>(props.scheduleConfig.specific_slots || []);
const blockedDates = ref<BlockedDate[]>(props.scheduleConfig.blocked_dates || []);

// Inicializar dados dos props
const initializeFromProps = () => {
    if (props.scheduleConfig) {
        serviceLocations.value = props.scheduleConfig.locations || [];
        specificSlots.value = props.scheduleConfig.specific_slots || [];
        blockedDates.value = props.scheduleConfig.blocked_dates || [];

        // Mapear slots específicos para as datas selecionadas
        const datesSet = new Set<string>();
        props.scheduleConfig.specific_slots?.forEach((dateSlot) => {
            datesSet.add(dateSlot.date);

            // Inicializar configuração para esta data
            specificDatesConfig.value[dateSlot.date] = {
                available: dateSlot.slots.length > 0,
                timeSlots: dateSlot.slots.map((slot) => ({
                    id: slot.id,
                    startTime: slot.start_time,
                    endTime: slot.end_time,
                    location_id: slot.location_id,
                    location: slot.location?.name || 'Sem local',
                })),
            };

            // Expandir datas que têm slots
            if (dateSlot.slots.length > 0) {
                expandedSpecificDates.value.add(dateSlot.date);
            }
        });

        selectedDates.value = datesSet;
    }
};

// Inicializar dados dos props ao montar
onMounted(() => {
    initializeFromProps();
});

// Função para carregar configuração completa do backend
const loadScheduleConfig = async () => {
    if (!doctorId.value) return;

    try {
        const response = await axios.get(scheduleRoutes.show.url({ doctor: doctorId.value }));
        const config = response.data.data || (response.data as ScheduleConfig);

        serviceLocations.value = config.locations || [];
        specificSlots.value = config.specific_slots || [];
        blockedDates.value = config.blocked_dates || [];

        // Limpar configurações antigas
        specificDatesConfig.value = {};
        expandedSpecificDates.value.clear();

        // Mapear slots específicos para as datas selecionadas
        const datesSet = new Set<string>();
        config.specific_slots?.forEach((dateSlot) => {
            datesSet.add(dateSlot.date);

            // Inicializar configuração para esta data
            specificDatesConfig.value[dateSlot.date] = {
                available: dateSlot.slots.length > 0,
                timeSlots: dateSlot.slots.map((slot) => ({
                    id: slot.id,
                    startTime: slot.start_time,
                    endTime: slot.end_time,
                    location_id: slot.location_id,
                    location: slot.location?.name || 'Sem local',
                })),
            };

            // Expandir datas que têm slots
            if (dateSlot.slots.length > 0) {
                expandedSpecificDates.value.add(dateSlot.date);
            }
        });

        selectedDates.value = datesSet;
    } catch (error: any) {
        console.error('Erro ao carregar configuração:', error);
        const errorMessage = error.response?.data?.message || 'Erro ao carregar configuração da agenda';
        alert(errorMessage);
    }
};

// Funções do calendário
const currentMonth = computed(() => {
    return currentCalendarDate.value.toLocaleDateString('pt-BR', { month: 'long', year: 'numeric' });
});

const getDaysInMonth = (date: Date) => {
    const year = date.getFullYear();
    const month = date.getMonth();
    const firstDay = new Date(year, month, 1);
    const lastDay = new Date(year, month + 1, 0);
    const daysInMonth = lastDay.getDate();
    const startingDayOfWeek = firstDay.getDay();

    return { daysInMonth, startingDayOfWeek, year, month };
};

const calendarDays = computed(() => {
    const { daysInMonth, startingDayOfWeek } = getDaysInMonth(currentCalendarDate.value);
    const days = [];

    // Adicionar dias vazios no início
    for (let i = 0; i < startingDayOfWeek; i++) {
        days.push(null);
    }

    // Adicionar dias do mês
    for (let day = 1; day <= daysInMonth; day++) {
        days.push(day);
    }

    return days;
});

const previousMonth = () => {
    const newDate = new Date(currentCalendarDate.value);
    newDate.setMonth(newDate.getMonth() - 1);
    currentCalendarDate.value = newDate;
};

const nextMonth = () => {
    const newDate = new Date(currentCalendarDate.value);
    newDate.setMonth(newDate.getMonth() + 1);
    currentCalendarDate.value = newDate;
};

const formatDateKey = (day: number) => {
    const { year, month } = getDaysInMonth(currentCalendarDate.value);
    return `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
};

const toggleDateSelection = (day: number) => {
    if (!day) return;

    // Verificar se a data é passada
    if (isDatePast(day)) {
        alert('Não é possível selecionar datas passadas.');
        return;
    }

    const dateKey = formatDateKey(day);
    const newSet = new Set(selectedDates.value);
    if (newSet.has(dateKey)) {
        newSet.delete(dateKey);
        // Remove a configuração também
        delete specificDatesConfig.value[dateKey];
        expandedSpecificDates.value.delete(dateKey);
    } else {
        newSet.add(dateKey);
        // Inicializa a configuração quando seleciona
        if (!specificDatesConfig.value[dateKey]) {
            specificDatesConfig.value[dateKey] = {
                available: true,
                timeSlots: [],
            };
        }
    }
    selectedDates.value = newSet;
};

const isDateSelected = (day: number) => {
    if (!day) return false;
    const dateKey = formatDateKey(day);
    return selectedDates.value.has(dateKey);
};

// Verificar se a data é passada (anterior a hoje)
const isDatePast = (day: number) => {
    if (!day) return false;
    const dateKey = formatDateKey(day);
    const [year, month, dayNum] = dateKey.split('-').map(Number);
    const selectedDate = new Date(year, month - 1, dayNum);
    const today = new Date();
    today.setHours(0, 0, 0, 0); // Resetar horas para comparação apenas de data
    selectedDate.setHours(0, 0, 0, 0);
    return selectedDate < today;
};

const isDateBlocked = (day: number) => {
    if (!day) return false;
    const dateKey = formatDateKey(day);
    return blockedDates.value.some((bd) => bd.blocked_date === dateKey);
};

const removeDate = (dateKey: string) => {
    const newSet = new Set(selectedDates.value);
    newSet.delete(dateKey);
    selectedDates.value = newSet;
    // Remove a configuração também
    delete specificDatesConfig.value[dateKey];
    const newExpandedSet = new Set(expandedSpecificDates.value);
    newExpandedSet.delete(dateKey);
    expandedSpecificDates.value = newExpandedSet;
};

const toggleSpecificDate = (dateKey: string) => {
    const newSet = new Set(expandedSpecificDates.value);
    if (newSet.has(dateKey)) {
        newSet.delete(dateKey);
    } else {
        newSet.add(dateKey);
        // Inicializa a configuração se não existir
        if (!specificDatesConfig.value[dateKey]) {
            specificDatesConfig.value[dateKey] = {
                available: true,
                timeSlots: [],
            };
        }
    }
    expandedSpecificDates.value = newSet;
};

const isSpecificDateExpanded = (dateKey: string) => {
    return expandedSpecificDates.value.has(dateKey);
};

const formatDateDisplay = (dateKey: string) => {
    const [year, month, day] = dateKey.split('-').map(Number);
    const date = new Date(year, month - 1, day);
    return date.toLocaleDateString('pt-BR', {
        weekday: 'long',
        day: '2-digit',
        month: 'long',
        year: 'numeric',
    });
};

const formatDateShort = (dateKey: string) => {
    const [year, month, day] = dateKey.split('-').map(Number);
    const date = new Date(year, month - 1, day);
    return date.toLocaleDateString('pt-BR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
    });
};

const getSpecificDateConfig = (dateKey: string) => {
    if (!specificDatesConfig.value[dateKey]) {
        specificDatesConfig.value[dateKey] = {
            available: true,
            timeSlots: [],
        };
    }
    return specificDatesConfig.value[dateKey];
};

const toggleSpecificDateAvailability = (dateKey: string) => {
    const config = getSpecificDateConfig(dateKey);
    config.available = !config.available;
};

const weekDays = ['DOM', 'SEG', 'TER', 'QUA', 'QUI', 'SEX', 'SÁB'];

// Estado da modal de adicionar local
const isAddLocationModalOpen = ref(false);
const isLoading = ref(false);

const openAddLocationModal = () => {
    isAddLocationModalOpen.value = true;
};

const closeAddLocationModal = () => {
    isAddLocationModalOpen.value = false;
};

// Handler para adicionar local de atendimento
const handleAddLocation = async (data: { name: string; type: string; address?: string; phone?: string; description?: string }) => {
    if (!doctorId.value) {
        alert('ID do médico não encontrado');
        return;
    }

    isLoading.value = true;
    try {
        // Mapear tipo do frontend (português) para o backend (inglês)
        const typeMap: Record<string, string> = {
            teleconsulta: 'teleconsultation',
            consultorio: 'office',
            hospital: 'hospital',
            clinica: 'clinic',
        };

        const backendType = typeMap[data.type] || data.type;

        const response = await axios.post(locationRoutes.store.url({ doctor: doctorId.value }), {
            name: data.name,
            type: backendType,
            address: data.address || null,
            phone: data.phone || null,
            description: data.description || null,
        });

        if (response.data.success) {
            // Adicionar novo local à lista
            serviceLocations.value.push(response.data.data);
            closeAddLocationModal();
            // Recarregar configuração completa para garantir sincronização
            await loadScheduleConfig();
        }
    } catch (error: any) {
        console.error('Erro ao adicionar local:', error);
        const errorMessage = error.response?.data?.message || error.response?.data?.error || 'Erro ao adicionar local de atendimento';
        alert(errorMessage);
    } finally {
        isLoading.value = false;
    }
};

// Handler para adicionar slot de disponibilidade específico
const handleAddSlot = async (dateKey: string, startTime: string, endTime: string, locationId?: string) => {
    if (!doctorId.value) return;

    // Validar se a data não é passada
    const [year, month, day] = dateKey.split('-').map(Number);
    const selectedDate = new Date(year, month - 1, day);
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    selectedDate.setHours(0, 0, 0, 0);

    if (selectedDate < today) {
        alert('Não é possível adicionar horários para datas passadas.');
        return;
    }

    isLoading.value = true;
    try {
        const response = await axios.post(availabilityRoutes.store.url({ doctor: doctorId.value }), {
            type: 'specific',
            specific_date: dateKey,
            start_time: startTime,
            end_time: endTime,
            location_id: locationId || null,
        });

        if (response.data.success) {
            const slot = response.data.data;
            const config = getSpecificDateConfig(dateKey);

            // Adicionar slot à configuração local
            config.timeSlots.push({
                id: slot.id,
                startTime: slot.start_time,
                endTime: slot.end_time,
                location_id: slot.location_id,
                location: slot.location?.name || 'Sem local',
            });

            // Recarregar configuração completa
            await loadScheduleConfig();
        }
    } catch (error: any) {
        console.error('Erro ao adicionar slot:', error);
        alert(error.response?.data?.message || 'Erro ao adicionar horário');
    } finally {
        isLoading.value = false;
    }
};

// Handler para remover slot
const handleRemoveSlot = async (dateKey: string, slotId: string) => {
    if (!doctorId.value) return;

    isLoading.value = true;
    try {
        await axios.delete(availabilityRoutes.destroy.url({ doctor: doctorId.value, slot: slotId }));

        // Remover slot da configuração local
        const config = getSpecificDateConfig(dateKey);
        config.timeSlots = config.timeSlots.filter((slot) => slot.id !== slotId);

        // Recarregar configuração completa
        await loadScheduleConfig();
    } catch (error: any) {
        console.error('Erro ao remover slot:', error);
        alert(error.response?.data?.message || 'Erro ao remover horário');
    } finally {
        isLoading.value = false;
    }
};

// Estados temporários para adicionar novo slot
const newSlotData = ref<
    Record<
        string,
        {
            startTime: string;
            endTime: string;
            locationId: string;
        }
    >
>({});

const setNewSlotData = (dateKey: string, field: 'startTime' | 'endTime' | 'locationId', value: string) => {
    if (!newSlotData.value[dateKey]) {
        newSlotData.value[dateKey] = {
            startTime: '',
            endTime: '',
            locationId: '',
        };
    }
    newSlotData.value[dateKey][field] = value;
};

const getNewSlotData = (dateKey: string) => {
    if (!newSlotData.value[dateKey]) {
        newSlotData.value[dateKey] = {
            startTime: '',
            endTime: '',
            locationId: '',
        };
    }
    return newSlotData.value[dateKey];
};

// Função para calcular o horário mínimo de fim (1 hora após o início)
const getMinEndTime = (startTime: string): string => {
    if (!startTime) return '';

    const [hours, minutes] = startTime.split(':').map(Number);
    const startDate = new Date();
    startDate.setHours(hours, minutes, 0, 0);

    // Adicionar 1 hora
    startDate.setHours(startDate.getHours() + 1);

    const minHours = startDate.getHours().toString().padStart(2, '0');
    const minMinutes = startDate.getMinutes().toString().padStart(2, '0');

    return `${minHours}:${minMinutes}`;
};

const handleAddSlotSubmit = async (dateKey: string) => {
    const data = getNewSlotData(dateKey);

    if (!data.startTime || !data.endTime) {
        alert('Por favor, preencha os horários de início e fim');
        return;
    }

    // Validar duração mínima de 1 hora
    const start = new Date(`2000-01-01T${data.startTime}:00`);
    const end = new Date(`2000-01-01T${data.endTime}:00`);
    const diffInMinutes = (end.getTime() - start.getTime()) / (1000 * 60);

    if (diffInMinutes < 60) {
        alert('O horário de fim deve ser pelo menos 1 hora após o horário de início.');
        return;
    }

    await handleAddSlot(dateKey, data.startTime, data.endTime, data.locationId || undefined);

    // Limpar dados do formulário
    newSlotData.value[dateKey] = {
        startTime: '',
        endTime: '',
        locationId: '',
    };
};

// Handler para remover local
const handleRemoveLocation = async (locationId: string) => {
    if (!doctorId.value) return;
    if (!confirm('Tem certeza que deseja remover este local?')) return;

    isLoading.value = true;
    try {
        await axios.delete(locationRoutes.destroy.url({ doctor: doctorId.value, location: locationId }));

        // Remover local da lista
        serviceLocations.value = serviceLocations.value.filter((loc) => loc.id !== locationId);
    } catch (error: any) {
        console.error('Erro ao remover local:', error);
        alert(error.response?.data?.message || 'Erro ao remover local de atendimento');
    } finally {
        isLoading.value = false;
    }
};

// Função auxiliar para obter ícone do local
const getLocationIcon = (type: string) => {
    return type === 'teleconsultation' ? 'video' : 'pin';
};
</script>

<template>
    <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl bg-gray-50">
        <!-- Header -->
        <div class="flex flex-col gap-1">
            <h1 class="text-3xl font-bold text-gray-900">Configurar Disponibilidade</h1>
            <p class="text-gray-600">Defina seus horários de atendimento e locais para otimizar sua agenda.</p>
        </div>

        <!-- Main Content: Two Column Layout -->
        <div class="flex flex-1 gap-6">
            <!-- Left Section: Configure Availability -->
            <div class="flex flex-1 flex-col gap-4">
                <!-- Calendar Section for Specific Dates -->
                <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                    <h2 class="mb-4 text-xl font-semibold text-gray-900">Datas Específicas</h2>
                    <p class="mb-4 text-sm text-gray-600">Selecione datas específicas do calendário para configurar horários de atendimento.</p>

                    <!-- Calendar Navigation -->
                    <div class="mb-4 flex items-center justify-between">
                        <button @click="previousMonth" class="rounded-lg p-2 transition-colors duration-200 hover:bg-gray-100">
                            <ChevronLeft class="h-5 w-5 text-gray-600" />
                        </button>

                        <h3 class="text-lg font-semibold text-gray-900 capitalize">{{ currentMonth }}</h3>

                        <button @click="nextMonth" class="rounded-lg p-2 transition-colors duration-200 hover:bg-gray-100">
                            <ChevronRight class="h-5 w-5 text-gray-600" />
                        </button>
                    </div>

                    <!-- Calendar Grid -->
                    <div class="mb-2 grid grid-cols-7 gap-1">
                        <div v-for="day in weekDays" :key="day" class="py-2 text-center text-xs font-medium text-gray-600">
                            {{ day }}
                        </div>
                    </div>

                    <div class="grid grid-cols-7 gap-1">
                        <div v-for="(day, index) in calendarDays" :key="index" class="flex h-10 items-center justify-center">
                            <button
                                v-if="day !== null"
                                @click="toggleDateSelection(day)"
                                :class="[
                                    'flex h-8 w-8 items-center justify-center rounded-full text-sm font-medium transition-colors duration-200',
                                    isDatePast(day)
                                        ? 'cursor-not-allowed bg-gray-200 text-gray-400'
                                        : isDateBlocked(day)
                                          ? 'cursor-pointer bg-red-100 text-red-600 ring-2 ring-red-300'
                                          : isDateSelected(day)
                                            ? 'cursor-pointer bg-primary text-gray-900 ring-2 ring-primary ring-offset-2'
                                            : 'cursor-pointer text-gray-900 hover:bg-gray-100',
                                ]"
                                :disabled="isDatePast(day) || isDateBlocked(day)"
                                :title="isDatePast(day) ? 'Data passada' : isDateBlocked(day) ? 'Data bloqueada' : ''"
                            >
                                {{ day }}
                            </button>
                        </div>
                    </div>

                    <!-- Selected Dates List -->
                    <div v-if="selectedDates.size > 0" class="mt-4 border-t border-gray-200 pt-4">
                        <p class="mb-2 text-sm font-medium text-gray-700">Datas selecionadas ({{ selectedDates.size }}):</p>
                        <div class="flex flex-wrap gap-2">
                            <span
                                v-for="dateKey in Array.from(selectedDates).sort()"
                                :key="dateKey"
                                class="inline-flex items-center gap-1 rounded-full bg-primary/10 px-3 py-1 text-xs font-medium text-primary"
                            >
                                {{ formatDateShort(dateKey) }}
                                <button @click="removeDate(dateKey)" class="ml-1 transition-colors hover:text-red-500">
                                    <span class="text-xs">×</span>
                                </button>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Specific Dates Configuration Cards -->
                <div v-if="selectedDates.size > 0" class="space-y-4">
                    <h2 class="text-xl font-semibold text-gray-900">Configuração de Datas Específicas</h2>

                    <div
                        v-for="dateKey in Array.from(selectedDates).sort()"
                        :key="dateKey"
                        class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm"
                    >
                        <!-- Specific Date Header -->
                        <div
                            @click="toggleSpecificDate(dateKey)"
                            class="flex cursor-pointer items-center justify-between p-4 transition-colors hover:bg-gray-50"
                        >
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 capitalize">{{ formatDateDisplay(dateKey) }}</h3>
                                <p class="mt-0.5 text-sm text-gray-500">{{ formatDateShort(dateKey) }}</p>
                            </div>
                            <ChevronUp v-if="isSpecificDateExpanded(dateKey)" class="h-5 w-5 text-gray-600" />
                            <ChevronDown v-else class="h-5 w-5 text-gray-600" />
                        </div>

                        <!-- Specific Date Content (Expanded) -->
                        <div v-if="isSpecificDateExpanded(dateKey)" class="space-y-4 px-4 pb-4">
                            <!-- Availability Toggle -->
                            <div class="flex items-center justify-between rounded-lg bg-gray-50 p-3">
                                <div class="flex items-center gap-2">
                                    <Calendar class="h-5 w-5 text-primary" />
                                    <span class="text-sm font-medium text-gray-700">Disponível nesta data</span>
                                </div>
                                <button
                                    @click="toggleSpecificDateAvailability(dateKey)"
                                    :class="[
                                        'relative inline-flex h-6 w-11 items-center rounded-full transition-colors duration-200 ease-in-out focus:ring-2 focus:ring-primary focus:ring-offset-2 focus:outline-none',
                                        getSpecificDateConfig(dateKey).available ? 'bg-primary' : 'bg-gray-300',
                                    ]"
                                >
                                    <span
                                        :class="[
                                            'inline-block h-4 w-4 transform rounded-full bg-white transition-transform duration-200 ease-in-out',
                                            getSpecificDateConfig(dateKey).available ? 'translate-x-6' : 'translate-x-1',
                                        ]"
                                    />
                                </button>
                            </div>

                            <!-- Time Slots -->
                            <div v-if="getSpecificDateConfig(dateKey).available" class="space-y-3">
                                <div
                                    v-for="slot in getSpecificDateConfig(dateKey).timeSlots"
                                    :key="slot.id"
                                    class="flex items-center gap-4 rounded-lg bg-primary/10 p-4"
                                >
                                    <!-- Clock Icon -->
                                    <Clock class="h-5 w-5 flex-shrink-0 text-primary" />

                                    <!-- Time Inputs -->
                                    <div class="flex flex-1 items-center gap-2">
                                        <input
                                            type="text"
                                            :value="slot.startTime"
                                            readonly
                                            class="w-16 rounded border border-gray-300 bg-white px-2 py-1 text-sm text-gray-900"
                                        />
                                        <span class="text-gray-600">-</span>
                                        <input
                                            type="text"
                                            :value="slot.endTime"
                                            readonly
                                            class="w-16 rounded border border-gray-300 bg-white px-2 py-1 text-sm text-gray-900"
                                        />
                                    </div>

                                    <!-- Location Display -->
                                    <div class="max-w-xs flex-1">
                                        <span class="text-sm text-gray-700">{{ slot.location }}</span>
                                    </div>

                                    <!-- Delete Button -->
                                    <button
                                        @click="handleRemoveSlot(dateKey, slot.id)"
                                        :disabled="isLoading"
                                        class="p-2 text-gray-400 transition-colors hover:text-red-500 disabled:opacity-50"
                                    >
                                        <Trash2 class="h-5 w-5" />
                                    </button>
                                </div>

                                <!-- Add Schedule Card -->
                                <div
                                    class="rounded-lg border-2 border-dashed border-primary/50 bg-primary/5 p-4 transition-colors hover:bg-primary/10"
                                >
                                    <div class="mb-3 flex items-center gap-2">
                                        <Plus class="h-5 w-5 text-primary" />
                                        <h4 class="font-semibold text-gray-900">Adicionar Novo Horário</h4>
                                    </div>

                                    <div class="flex flex-wrap items-center gap-3">
                                        <!-- Start Time -->
                                        <div class="min-w-[140px] flex-1">
                                            <label class="mb-1 block text-xs font-medium text-gray-700">Horário Início</label>
                                            <input
                                                type="time"
                                                :value="getNewSlotData(dateKey).startTime"
                                                @input="setNewSlotData(dateKey, 'startTime', ($event.target as HTMLInputElement).value)"
                                                class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-transparent focus:ring-2 focus:ring-primary"
                                            />
                                        </div>

                                        <!-- End Time -->
                                        <div class="min-w-[140px] flex-1">
                                            <label class="mb-1 block text-xs font-medium text-gray-700">Horário Fim</label>
                                            <input
                                                type="time"
                                                :value="getNewSlotData(dateKey).endTime"
                                                :min="getMinEndTime(getNewSlotData(dateKey).startTime)"
                                                @input="setNewSlotData(dateKey, 'endTime', ($event.target as HTMLInputElement).value)"
                                                class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-transparent focus:ring-2 focus:ring-primary"
                                            />
                                            <p v-if="getNewSlotData(dateKey).startTime" class="mt-1 text-xs text-gray-500">Duração mínima: 1 hora</p>
                                        </div>

                                        <!-- Location -->
                                        <div class="min-w-[180px] flex-1">
                                            <label class="mb-1 block text-xs font-medium text-gray-700">Local de Atendimento</label>
                                            <div class="relative">
                                                <select
                                                    :value="getNewSlotData(dateKey).locationId"
                                                    @change="setNewSlotData(dateKey, 'locationId', ($event.target as HTMLSelectElement).value)"
                                                    class="w-full cursor-pointer appearance-none rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-transparent focus:ring-2 focus:ring-primary"
                                                >
                                                    <option value="">Selecione o local</option>
                                                    <option v-for="location in serviceLocations" :key="location.id" :value="location.id">
                                                        {{ location.name }}
                                                    </option>
                                                </select>
                                                <ChevronDown
                                                    class="pointer-events-none absolute top-1/2 right-3 h-4 w-4 -translate-y-1/2 transform text-gray-600"
                                                />
                                            </div>
                                        </div>

                                        <!-- Add Button -->
                                        <div class="flex items-end">
                                            <button
                                                @click="handleAddSlotSubmit(dateKey)"
                                                :disabled="isLoading"
                                                class="flex items-center gap-2 rounded-lg bg-primary px-6 py-2 font-semibold whitespace-nowrap text-gray-900 transition-colors duration-200 hover:bg-primary/90 disabled:cursor-not-allowed disabled:opacity-50"
                                            >
                                                <Plus class="h-4 w-4" />
                                                Adicionar
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Section: Service Locations -->
            <div class="flex w-80 flex-col gap-4">
                <h2 class="text-xl font-semibold text-gray-900">Locais de Atendimento</h2>

                <div class="space-y-3">
                    <div v-for="location in serviceLocations" :key="location.id" class="flex items-start gap-3 rounded-lg bg-primary/10 p-4">
                        <!-- Icon -->
                        <div class="mt-0.5 flex-shrink-0">
                            <Video v-if="getLocationIcon(location.type) === 'video'" class="h-5 w-5 text-primary" />
                            <MapPin v-else class="h-5 w-5 text-primary" />
                        </div>

                        <!-- Location Info -->
                        <div class="min-w-0 flex-1">
                            <h3 class="mb-1 font-medium text-gray-900">{{ location.name }}</h3>
                            <p v-if="location.address" class="text-sm text-gray-600">{{ location.address }}</p>
                            <p v-if="location.type_label" class="mt-1 text-xs text-gray-500">{{ location.type_label }}</p>
                        </div>

                        <!-- More Options -->
                        <button
                            @click="handleRemoveLocation(location.id)"
                            :disabled="isLoading"
                            class="p-1 text-gray-400 transition-colors hover:text-red-500 disabled:opacity-50"
                            title="Remover local"
                        >
                            <Trash2 class="h-5 w-5" />
                        </button>
                    </div>
                </div>

                <!-- Add Location Button -->
                <button
                    @click="openAddLocationModal"
                    :disabled="isLoading"
                    class="flex w-full items-center justify-center gap-2 rounded-lg bg-primary px-4 py-3 font-semibold text-gray-900 transition-colors duration-200 hover:bg-primary/90 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    <Plus class="h-5 w-5" />
                    <span>Adicionar Local</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Add Location Modal -->
    <AddLocationModal :is-open="isAddLocationModalOpen" @close="closeAddLocationModal" @confirm="handleAddLocation" />
</template>
