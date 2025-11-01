<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { 
    Star, Bookmark, Heart, ChevronLeft, ChevronRight, 
    GraduationCap, Briefcase, CheckCircle2 
} from 'lucide-vue-next';
import * as patientRoutes from '@/routes/patient';
import { onMounted, ref } from 'vue';
import { useRouteGuard } from '@/composables/auth';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Avatar, AvatarImage, AvatarFallback } from '@/components/ui/avatar';
import { useInitials } from '@/composables/useInitials';

const { canAccessPatientRoute } = useRouteGuard();
const { getInitials } = useInitials();

// Estado do calendário
const selectedDate = ref(8);
const selectedTime = ref('14:00');
const currentMonth = ref('Maio 2024');

// Horários disponíveis
const availableTimes = [
    '14:00', '14:30', '15:00', '15:30', '16:00'
];

// Dados do médico (estático)
const doctor = {
    name: 'Dra. Ana Silva',
    specialty: 'Cardiologista',
    avatar: null,
    rating: 4.9,
    reviewsCount: 128,
    experience: '10 anos',
    crm: '123456-SP',
    languages: 'Português, Inglês',
    about: 'Com mais de uma década de experiência em cardiologia clínica, Dra. Ana é dedicada a fornecer cuidados compassivos e baseados em evidências para ajudar seus pacientes a alcançarem uma saúde cardíaca ótima. Especialista em prevenção de doenças cardiovasculares e tratamento de condições crônicas.',
    modalities: ['Online'],
    availableToday: true,
    consultationTime: '45 minutos',
    consultationPrice: 350.00,
    specialties: [
        'Hipertensão',
        'Aritmia',
        'Prevenção Cardiovascular',
        'Doença Coronariana',
        'Check-up Cardiológico'
    ],
    timeline: [
        {
            type: 'education',
            title: 'Residência em Cardiologia',
            location: 'Hospital das Clínicas da FMUSP',
            year: '2012'
        },
        {
            type: 'work',
            title: 'Cardiologista',
            location: 'Hospital Sírio-Libanês',
            period: '2012 - Presente'
        }
    ],
    reviews: [
        {
            rating: 5,
            comment: 'A Dra. Ana foi extremamente atenciosa e clara em suas explicações. Senti muita segurança no atendimento.',
            patient: 'Carlos P.',
            date: '12/04/2024'
        },
        {
            rating: 5,
            comment: 'Excelente profissional! Muito competente e humana. O tratamento proposto está fazendo toda a diferença.',
            patient: 'Mariana S.',
            date: '05/03/2024'
        }
    ],
    verified: true
};

// Verificar acesso ao montar componente
onMounted(() => {
    canAccessPatientRoute();
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
                            <AvatarImage :src="doctor.avatar" />
                            <AvatarFallback class="bg-primary/10 text-primary text-2xl font-semibold">
                                {{ getInitials(doctor.name) }}
                            </AvatarFallback>
                        </Avatar>

                        <!-- Doctor Info -->
                        <div class="flex-1">
                            <div class="flex items-start justify-between mb-2">
                                <div>
                                    <h1 class="text-2xl font-bold text-gray-900 mb-0.5">{{ doctor.name }}</h1>
                                    <p class="text-base text-gray-600 mb-1.5">{{ doctor.specialty }}</p>
                                    <div class="flex items-center gap-2 mb-2">
                                        <div class="flex items-center gap-0.5">
                                            <Star 
                                                v-for="i in 5" 
                                                :key="i" 
                                                class="h-4 w-4 text-primary fill-primary" 
                                            />
                                        </div>
                                        <span class="text-sm font-semibold text-gray-900">
                                            {{ doctor.rating }}
                                        </span>
                                        <span class="text-xs text-gray-500">
                                            ({{ doctor.reviewsCount }} avaliações)
                                        </span>
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

                            <!-- Tags -->
                            <div class="flex flex-wrap gap-2">
                                <span 
                                    v-if="doctor.modalities.includes('Online')"
                                    class="px-2.5 py-0.5 rounded-full bg-primary/10 text-primary text-xs font-medium"
                                >
                                    Atende online
                                </span>
                                <span 
                                    v-if="doctor.availableToday"
                                    class="px-2.5 py-0.5 rounded-full bg-primary/10 text-primary text-xs font-medium"
                                >
                                    Disponível hoje
                                </span>
                                <span class="px-2.5 py-0.5 rounded-full bg-primary/10 text-primary text-xs font-medium">
                                    +{{ doctor.experience }} de experiência
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
                            <p class="text-sm font-semibold text-gray-900">{{ doctor.modalities.join(', ') }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-600 mb-0.5">Tempo Consulta:</p>
                            <p class="text-sm font-semibold text-gray-900">{{ doctor.consultationTime }}</p>
                        </div>
                        <div class="border-t border-gray-200 pt-3">
                            <p class="text-xs text-gray-600 mb-1">Valor da Consulta</p>
                            <p class="text-xl font-bold text-gray-900">
                                R$ {{ doctor.consultationPrice.toFixed(2).replace('.', ',') }}
                            </p>
                        </div>
                        <Button as-child class="w-full bg-primary hover:bg-primary/90 text-gray-900 font-semibold py-4 text-sm mt-2">
                            <Link :href="patientRoutes.scheduleConsultation()">
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
                            <CardTitle class="text-xl font-bold text-gray-900">Sobre a {{ doctor.name.split(' ')[0] }}</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <p class="text-gray-700 leading-relaxed">{{ doctor.about }}</p>
                            <div class="space-y-2 pt-4 border-t border-gray-200">
                                <p class="text-sm text-gray-600">
                                    <span class="font-semibold">Idiomas:</span> {{ doctor.languages }}
                                </p>
                                <p class="text-sm text-gray-600">
                                    <span class="font-semibold">CRM:</span> {{ doctor.crm }}
                                </p>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Specialties Section -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="text-xl font-bold text-gray-900">
                                Especialidades & Condições Atendidas
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

                    <!-- Experience & Education Section -->
                    <Card>
                        <CardHeader>
                            <div class="flex items-center justify-between">
                                <CardTitle class="text-xl font-bold text-gray-900">
                                    Experiência & Formação
                                </CardTitle>
                                <span 
                                    v-if="doctor.verified"
                                    class="flex items-center gap-1 px-3 py-1 rounded-full bg-green-100 text-green-700 text-xs font-medium"
                                >
                                    <CheckCircle2 class="h-4 w-4" />
                                    Perfil Verificado
                                </span>
                            </div>
                        </CardHeader>
                        <CardContent>
                            <div class="relative space-y-6">
                                <div
                                    v-for="(item, index) in doctor.timeline"
                                    :key="index"
                                    class="relative pl-10"
                                >
                                    <!-- Timeline dot -->
                                    <div class="absolute left-0 top-1">
                                        <div class="h-3 w-3 rounded-full bg-primary border-2 border-white"></div>
                                    </div>
                                    <!-- Timeline line -->
                                    <div 
                                        v-if="index < doctor.timeline.length - 1"
                                        class="absolute left-1.5 top-4 h-full w-0.5 bg-gray-200"
                                        style="height: calc(100% + 1.5rem);"
                                    ></div>
                                    <!-- Content -->
                                    <div class="flex items-start gap-3">
                                        <component 
                                            :is="item.type === 'education' ? GraduationCap : Briefcase"
                                            class="h-5 w-5 text-primary mt-0.5 flex-shrink-0"
                                        />
                                        <div>
                                            <h4 class="font-semibold text-gray-900">{{ item.title }}</h4>
                                            <p class="text-sm text-gray-600">{{ item.location }}</p>
                                            <p class="text-xs text-gray-500 mt-1">
                                                {{ item.year || item.period }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Reviews Section -->
                    <Card>
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
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div
                                    v-for="(review, index) in doctor.reviews"
                                    :key="index"
                                    class="p-4 rounded-lg bg-secondary/30 space-y-3"
                                >
                                    <div class="flex items-center gap-1">
                                        <Star
                                            v-for="i in review.rating"
                                            :key="i"
                                            class="h-4 w-4 text-primary fill-primary"
                                        />
                                    </div>
                                    <p class="text-sm text-gray-700 leading-relaxed">
                                        "{{ review.comment }}"
                                    </p>
                                    <div class="text-xs text-gray-500">
                                        <span class="font-semibold">{{ review.patient }}</span> - {{ review.date }}
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
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
                                <Button variant="ghost" size="icon" class="h-7 w-7">
                                    <ChevronLeft class="h-3 w-3" />
                                </Button>
                                <span class="font-semibold text-xs text-gray-900">{{ currentMonth }}</span>
                                <Button variant="ghost" size="icon" class="h-7 w-7">
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
                                <!-- Previous month days -->
                                <div class="text-center text-xs text-gray-300 py-0.5">28</div>
                                <div class="text-center text-xs text-gray-300 py-0.5">29</div>
                                <div class="text-center text-xs text-gray-300 py-0.5">30</div>
                                
                                <!-- Current month days -->
                                <div
                                    v-for="day in 31"
                                    :key="day"
                                    @click="selectedDate = day"
                                    :class="[
                                        'text-center text-xs py-0.5 rounded cursor-pointer transition-colors',
                                        day === selectedDate
                                            ? 'bg-primary text-white font-semibold'
                                            : 'text-gray-900 hover:bg-gray-100'
                                    ]"
                                >
                                    {{ day }}
                                </div>
                            </div>

                            <!-- Available Times -->
                            <div class="space-y-1.5">
                                <h3 class="text-xs font-medium text-gray-900">
                                    Horários para Quarta, {{ selectedDate }} de Maio
                                </h3>
                                <div class="grid grid-cols-1 gap-1.5">
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
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
