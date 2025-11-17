<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import Timeline from '@/components/Timeline.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { 
    Star, Bookmark, Heart, ChevronLeft, ChevronRight, 
    CheckCircle2, MessageCircle, Video
} from 'lucide-vue-next';
import * as patientRoutes from '@/routes/patient';
import { onMounted, ref, computed } from 'vue';
import { useRouteGuard } from '@/composables/auth';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Avatar, AvatarImage, AvatarFallback } from '@/components/ui/avatar';
import { useInitials } from '@/composables/useInitials';

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

interface AvailableDate {
    date: string;
    formatted_date: string;
    day_of_week: string;
    day_of_week_label: string;
    available_slots: string[];
}

interface Doctor {
    id: string;
    name: string;
    email: string;
    avatar?: string | null;
    avatar_thumbnail?: string | null;
    crm?: string | null;
    biography?: string | null;
    languages: string;
    consultation_fee?: number | null;
    consultation_fee_formatted: string;
    specialties: string[];
    primary_specialty: string;
    has_online_service: boolean;
    has_presencial_service: boolean;
    modalities: string[];
    status: string;
    timeline_events: TimelineEvent[];
    timeline_completed: boolean;
    available_dates: AvailableDate[];
}

interface Props {
    doctor: Doctor;
}

const props = defineProps<Props>();

const { canAccessPatientRoute } = useRouteGuard();
const { getInitials } = useInitials();

// Estado do calendário
const selectedDate = ref<string | null>(null);
const selectedTime = ref<string | null>(null);
const currentMonthStart = ref(new Date());

// Computed properties
const consultationPrice = computed(() => props.doctor.consultation_fee || 0);
const consultationTime = computed(() => '45 minutos'); // Padrão do sistema
const about = computed(() => props.doctor.biography || 'Informações não disponíveis.');
const modalities = computed(() => props.doctor.modalities || []);

// Calendário dinâmico
const currentMonth = computed(() => {
    const date = currentMonthStart.value;
    const months = [
        'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
        'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'
    ];
    return `${months[date.getMonth()]} ${date.getFullYear()}`;
});

// Datas disponíveis do mês atual
const availableDatesInMonth = computed(() => {
    const monthStart = new Date(currentMonthStart.value.getFullYear(), currentMonthStart.value.getMonth(), 1);
    const monthEnd = new Date(currentMonthStart.value.getFullYear(), currentMonthStart.value.getMonth() + 1, 0);
    
    return props.doctor.available_dates?.filter(date => {
        const dateObj = new Date(date.date);
        return dateObj >= monthStart && dateObj <= monthEnd;
    }) || [];
});

// Dias do calendário
const calendarDays = computed(() => {
    const year = currentMonthStart.value.getFullYear();
    const month = currentMonthStart.value.getMonth();
    const firstDay = new Date(year, month, 1);
    const lastDay = new Date(year, month + 1, 0);
    const daysInMonth = lastDay.getDate();
    const startingDayOfWeek = firstDay.getDay(); // 0 = Domingo, 1 = Segunda, etc.
    
    const days: Array<{ day: number; date: string | null; isAvailable: boolean }> = [];
    
    // Dias do mês anterior (para completar a semana)
    const prevMonth = new Date(year, month - 1, 0);
    const daysInPrevMonth = prevMonth.getDate();
    for (let i = startingDayOfWeek - 1; i >= 0; i--) {
        days.push({ 
            day: daysInPrevMonth - i, 
            date: null, 
            isAvailable: false 
        });
    }
    
    // Dias do mês atual
    for (let day = 1; day <= daysInMonth; day++) {
        const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
        const availableDate = availableDatesInMonth.value.find(d => d.date === dateStr);
        days.push({
            day,
            date: dateStr,
            isAvailable: !!availableDate,
        });
    }
    
    // Completar a última semana se necessário
    const remainingDays = 42 - days.length; // 6 semanas x 7 dias
    for (let day = 1; day <= remainingDays; day++) {
        days.push({ 
            day, 
            date: null, 
            isAvailable: false 
        });
    }
    
    return days;
});

// Horários disponíveis para a data selecionada
const availableTimes = computed(() => {
    if (!selectedDate.value) return [];
    
    const availableDate = props.doctor.available_dates?.find(d => d.date === selectedDate.value);
    return availableDate?.available_slots || [];
});

// Data formatada para exibição
const selectedDateLabel = computed(() => {
    if (!selectedDate.value) return '';
    
    const date = new Date(selectedDate.value);
    const dayLabels = ['Domingo', 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado'];
    const months = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
        'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
    
    const availableDate = props.doctor.available_dates?.find(d => d.date === selectedDate.value);
    const dayOfWeek = availableDate?.day_of_week_label || dayLabels[date.getDay()];
    
    return `${dayOfWeek}, ${date.getDate()} de ${months[date.getMonth()]}`;
});

// Funções do calendário
const goToPreviousMonth = () => {
    const newDate = new Date(currentMonthStart.value);
    newDate.setMonth(newDate.getMonth() - 1);
    currentMonthStart.value = newDate;
    selectedDate.value = null;
    selectedTime.value = null;
};

const goToNextMonth = () => {
    const newDate = new Date(currentMonthStart.value);
    newDate.setMonth(newDate.getMonth() + 1);
    currentMonthStart.value = newDate;
    selectedDate.value = null;
    selectedTime.value = null;
};

const selectCalendarDate = (date: string | null) => {
    if (date) {
        selectedDate.value = date;
        selectedTime.value = null;
    }
};

// Selecionar primeira data disponível ao montar
onMounted(() => {
    canAccessPatientRoute();
    if (props.doctor.available_dates && props.doctor.available_dates.length > 0) {
        selectedDate.value = props.doctor.available_dates[0].date;
    }
});

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
        title: 'Perfil do Médico',
        href: patientRoutes.doctorPerfil().url,
    },
];
</script>

<template>
    <Head title="Perfil do Médico" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col overflow-x-auto bg-white px-4 py-6">
            <!-- Top Section: Two Columns -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
                <!-- Left Column: Doctor Overview -->
                <div class="lg:col-span-2">
                    <div class="flex flex-col sm:flex-row gap-4">
                        <!-- Avatar -->
                        <Avatar class="h-24 w-24 flex-shrink-0">
                            <AvatarImage v-if="doctor.avatar || doctor.avatar_thumbnail" :src="doctor.avatar_thumbnail || doctor.avatar" />
                            <AvatarFallback class="bg-primary/10 text-primary text-2xl font-semibold">
                                {{ getInitials(doctor.name) }}
                            </AvatarFallback>
                        </Avatar>

                        <!-- Doctor Info -->
                        <div class="flex-1">
                            <div class="flex items-start justify-between mb-2">
                                <div>
                                    <h1 class="text-2xl font-bold text-gray-900 mb-0.5">{{ doctor.name }}</h1>
                                    <p class="text-base text-gray-600 mb-1.5">{{ doctor.primary_specialty }}</p>
                                    <div class="flex items-center gap-2 mb-2">
                                        <div v-if="doctor.timeline_completed" class="flex items-center gap-1 px-2 py-0.5 rounded-full bg-green-100 text-green-700 text-xs font-medium">
                                            <CheckCircle2 class="h-3 w-3" />
                                            Perfil Completo
                                        </div>
                                    </div>
                                </div>
                                <!-- Icons -->
                                <div class="flex gap-2">
                                    <button class="p-1.5 rounded-lg bg-secondary/30 hover:bg-secondary/50 transition-colors">
                                        <Bookmark class="h-4 w-4 text-gray-700" />
                                    </button>
                                    <button class="p-1.5 rounded-lg bg-secondary/30 hover:bg-secondary/50 transition-colors">
                                        <Heart class="h-4 w-4 text-gray-700" />
                                    </button>
                                </div>
                            </div>

                            <!-- Message Button -->
                            <div class="mt-3">
                                <Button as-child variant="outline" class="w-full bg-white hover:bg-gray-50 border-primary text-primary font-medium">
                                    <Link :href="patientRoutes.messages()">
                                        <MessageCircle class="h-4 w-4 mr-2" />
                                        Enviar Mensagem
                                    </Link>
                                </Button>
                            </div>

                            <!-- Tags -->
                            <div class="flex flex-wrap gap-2">
                                <span 
                                    v-if="doctor.has_online_service"
                                    class="px-2.5 py-0.5 rounded-full bg-primary/10 text-primary text-xs font-medium flex items-center gap-1"
                                >
                                    <Video class="h-3 w-3" />
                                    Atende online
                                </span>
                                <span 
                                    v-if="doctor.has_presencial_service"
                                    class="px-2.5 py-0.5 rounded-full bg-primary/10 text-primary text-xs font-medium"
                                >
                                    Atende presencial
                                </span>
                                <span 
                                    v-if="doctor.crm"
                                    class="px-2.5 py-0.5 rounded-full bg-primary/10 text-primary text-xs font-medium"
                                >
                                    CRM {{ doctor.crm }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Essential Information & Booking -->
                <Card class="bg-secondary/30">
                    <CardHeader class="pb-2">
                        <CardTitle class="text-base font-bold text-gray-900">Informações Essenciais</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-3 pt-0">
                        <div>
                            <p class="text-xs text-gray-600 mb-0.5">Modalidade:</p>
                            <p class="text-sm font-semibold text-gray-900">{{ modalities.length > 0 ? modalities.join(', ') : 'Não configurado' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-600 mb-0.5">Tempo Consulta:</p>
                            <p class="text-sm font-semibold text-gray-900">{{ consultationTime }}</p>
                        </div>
                        <div class="border-t border-gray-200 pt-3">
                            <p class="text-xs text-gray-600 mb-1">Valor da Consulta</p>
                            <p class="text-xl font-bold text-gray-900">
                                {{ doctor.consultation_fee_formatted || 'A consultar' }}
                            </p>
                        </div>
                        <Button as-child class="w-full bg-primary hover:bg-primary/90 text-gray-900 font-semibold py-4 text-sm mt-2">
                            <Link :href="patientRoutes.scheduleConsultation({ query: { doctor_id: doctor.id } })">
                                Agendar Consulta
                            </Link>
                        </Button>
                    </CardContent>
                </Card>
            </div>

            <!-- Main Content: Single Column -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Left Column: Main Content -->
                <div class="lg:col-span-2 space-y-8">
                    <!-- About Section -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="text-xl font-bold text-gray-900">Sobre {{ doctor.name.split(' ')[0] }}</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <p class="text-gray-700 leading-relaxed">{{ about }}</p>
                            <div class="space-y-2 pt-4 border-t border-gray-200">
                                <p v-if="doctor.languages" class="text-sm text-gray-600">
                                    <span class="font-semibold">Idiomas:</span> {{ doctor.languages }}
                                </p>
                                <p v-if="doctor.crm" class="text-sm text-gray-600">
                                    <span class="font-semibold">CRM:</span> {{ doctor.crm }}
                                </p>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Specialties Section -->
                    <Card v-if="doctor.specialties && doctor.specialties.length > 0">
                        <CardHeader>
                            <CardTitle class="text-xl font-bold text-gray-900">
                                Especialidades
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="flex flex-wrap gap-2">
                                <span
                                    v-for="specialty in doctor.specialties"
                                    :key="specialty"
                                    class="px-3 py-1.5 rounded-full bg-primary/10 text-primary text-sm font-medium"
                                >
                                    {{ specialty }}
                                </span>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Experience & Education Section (Timeline) -->
                    <Card v-if="doctor.timeline_events && doctor.timeline_events.length > 0">
                        <CardHeader>
                            <div class="flex items-center justify-between">
                                <CardTitle class="text-xl font-bold text-gray-900">
                                    Formação & Certificações
                                </CardTitle>
                                <span 
                                    v-if="doctor.timeline_completed"
                                    class="flex items-center gap-1 px-3 py-1 rounded-full bg-green-100 text-green-700 text-xs font-medium"
                                >
                                    <CheckCircle2 class="h-4 w-4" />
                                    Perfil Completo
                                </span>
                            </div>
                        </CardHeader>
                        <CardContent>
                            <Timeline 
                                :events="doctor.timeline_events" 
                                :show-actions="false"
                            />
                        </CardContent>
                    </Card>

                    <!-- Reviews Section - Placeholder for future implementation -->
                    <!-- <Card>
                        <CardHeader>
                            <div class="flex items-center justify-between">
                                <CardTitle class="text-xl font-bold text-gray-900">
                                    Avaliações de Pacientes
                                </CardTitle>
                                <button class="text-sm text-primary hover:text-primary/80 font-medium">
                                    Ver todas
                                </button>
                            </div>
                        </CardHeader>
                        <CardContent>
                            <p class="text-sm text-gray-500">Avaliações estarão disponíveis em breve.</p>
                        </CardContent>
                    </Card> -->
                </div>

                <!-- Right Column: Availability -->
                <div class="lg:col-span-1">
                    <Card class="bg-secondary/30 sticky top-6">
                        <CardHeader class="pb-2">
                            <div class="flex items-center justify-between">
                                <CardTitle class="text-base font-bold text-gray-900">
                                    Disponibilidade
                                </CardTitle>
                                <button class="text-xs text-primary hover:text-primary/80 font-medium">
                                    Ver agenda
                                </button>
                            </div>
                        </CardHeader>
                        <CardContent class="space-y-3 pt-0">
                            <!-- Calendar Navigation -->
                            <div class="flex items-center justify-between">
                                <Button variant="ghost" size="icon" class="h-7 w-7" @click="goToPreviousMonth">
                                    <ChevronLeft class="h-3 w-3" />
                                </Button>
                                <span class="font-semibold text-xs text-gray-900">{{ currentMonth }}</span>
                                <Button variant="ghost" size="icon" class="h-7 w-7" @click="goToNextMonth">
                                    <ChevronRight class="h-3 w-3" />
                                </Button>
                            </div>

                            <!-- Days of Week -->
                            <div class="grid grid-cols-7 gap-0.5 mb-1">
                                <div class="text-center text-xs font-medium text-gray-600">D</div>
                                <div class="text-center text-xs font-medium text-gray-600">S</div>
                                <div class="text-center text-xs font-medium text-gray-600">T</div>
                                <div class="text-center text-xs font-medium text-gray-600">Q</div>
                                <div class="text-center text-xs font-medium text-gray-600">Q</div>
                                <div class="text-center text-xs font-medium text-gray-600">S</div>
                                <div class="text-center text-xs font-medium text-gray-600">S</div>
                            </div>

                            <!-- Calendar Days -->
                            <div class="grid grid-cols-7 gap-0.5 mb-3">
                                <div
                                    v-for="(calendarDay, index) in calendarDays"
                                    :key="index"
                                    @click="calendarDay.isAvailable ? selectCalendarDate(calendarDay.date) : null"
                                    :class="[
                                        'text-center text-xs py-0.5 rounded transition-colors',
                                        calendarDay.isAvailable
                                            ? 'cursor-pointer text-gray-900 hover:bg-gray-100'
                                            : 'text-gray-300 cursor-not-allowed',
                                        calendarDay.date && selectedDate === calendarDay.date
                                            ? 'bg-primary text-white font-semibold'
                                            : ''
                                    ]"
                                >
                                    {{ calendarDay.day }}
                                </div>
                            </div>

                            <!-- Available Times -->
                            <div v-if="selectedDate" class="space-y-1.5">
                                <h3 class="text-xs font-medium text-gray-900">
                                    Horários para {{ selectedDateLabel }}
                                </h3>
                                <div v-if="availableTimes.length > 0" class="grid grid-cols-1 gap-1.5">
                                    <button
                                        v-for="time in availableTimes"
                                        :key="time"
                                        @click="selectedTime = time"
                                        :class="[
                                            'py-1.5 px-3 rounded-lg text-xs font-medium transition-colors text-center',
                                            selectedTime === time
                                                ? 'bg-primary text-white'
                                                : 'bg-primary/10 text-primary hover:bg-primary/20'
                                        ]"
                                    >
                                        {{ time }}
                                    </button>
                                </div>
                                <p v-else class="text-xs text-gray-500 text-center py-2">
                                    Nenhum horário disponível nesta data
                                </p>
                            </div>
                            <div v-else class="space-y-1.5">
                                <p class="text-xs text-gray-500 text-center py-2">
                                    Selecione uma data para ver os horários disponíveis
                                </p>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
