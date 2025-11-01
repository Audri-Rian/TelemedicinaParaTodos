<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { Search, ChevronDown, Video, Circle, Star, Heart, Brain, Eye, Bone, Apple, Calendar, Clock, Baby, Stethoscope, Pill, Activity, Microscope, Syringe } from 'lucide-vue-next';
import * as patientRoutes from '@/routes/patient';
import { onMounted, ref, computed } from 'vue';
import { useRouteGuard } from '@/composables/auth';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Avatar, AvatarImage, AvatarFallback } from '@/components/ui/avatar';
import { useInitials } from '@/composables/useInitials';
import { Link } from '@inertiajs/vue3';

interface Doctor {
    id: string;
    user: {
        name: string;
        email?: string;
        avatar?: string;
    };
    specializations?: Array<{
        id: string;
        name: string;
    }>;
    rating?: number;
    reviews_count?: number;
    description?: string;
    status?: string;
}

interface Specialization {
    id: string;
    name: string;
    doctors_count?: number;
}

interface Appointment {
    id: string;
    doctor: {
        user: {
            name: string;
        };
        specializations?: Array<{
            name: string;
        }>;
    };
    scheduled_at: string;
    status: string;
}

interface Props {
    appointments?: Appointment[];
    availableDoctors?: Doctor[];
    specializations?: Specialization[];
}

const props = withDefaults(defineProps<Props>(), {
    appointments: () => [],
    availableDoctors: () => [],
    specializations: () => [],
});

const { canAccessPatientRoute } = useRouteGuard();
const { getInitials } = useInitials();

// Estado da busca e filtros
const searchQuery = ref('');
const selectedSpecialty = ref<string | null>(null);
const selectedInsurance = ref<string | null>(null);
const selectedDate = ref<string | null>(null);
const telemedicineOnly = ref(false);
const availableNow = ref(false);

// Verificar acesso ao montar componente
onMounted(() => {
    canAccessPatientRoute();
});

// Ícones para especializações
const specializationIcons: Record<string, any> = {
    'Cardiologia': Heart,
    'Dermatologia': Eye,
    'Psicologia': Brain,
    'Ortopedia': Bone,
    'Nutrição': Apple,
    'Pediatria': Baby,
    'Clínica Geral': Stethoscope,
    'Farmacologia': Pill,
    'Fisioterapia': Activity,
    'Anestesiologia': Syringe,
    'Biomedicina': Microscope,
};

const getSpecializationIcon = (name: string) => {
    const icon = specializationIcons[name] || Heart;
    return icon;
};

// Médicos estáticos de exemplo
const exampleDoctors: Doctor[] = [
    {
        id: 'example-1',
        user: {
            name: 'Dr. Ana Costa',
            email: 'ana.costa@example.com',
        },
        specializations: [{ id: '1', name: 'Cardiologista' }],
        rating: 4.9,
        reviews_count: 124,
        description: 'Especialista em saúde do coração com mais de 10 anos de experiência.',
        status: 'active',
    },
    {
        id: 'example-2',
        user: {
            name: 'Dr. Bruno Alves',
            email: 'bruno.alves@example.com',
        },
        specializations: [{ id: '2', name: 'Dermatologista' }],
        rating: 4.8,
        reviews_count: 98,
        description: 'Tratamentos de pele, cabelo e unhas. Foco em dermatologia clínica.',
        status: 'active',
    },
    {
        id: 'example-3',
        user: {
            name: 'Dr. Carlos Lima',
            email: 'carlos.lima@example.com',
        },
        specializations: [{ id: '3', name: 'Clínico Geral' }],
        rating: 5.0,
        reviews_count: 210,
        description: 'Atendimento geral para todas as idades, com foco em medicina preventiva.',
        status: 'active',
    },
    {
        id: 'example-4',
        user: {
            name: 'Dra. Maria Silva',
            email: 'maria.silva@example.com',
        },
        specializations: [{ id: '4', name: 'Pediatra' }],
        rating: 4.9,
        reviews_count: 156,
        description: 'Especializada em cuidados com crianças e adolescentes há mais de 8 anos.',
        status: 'active',
    },
    {
        id: 'example-5',
        user: {
            name: 'Dr. João Santos',
            email: 'joao.santos@example.com',
        },
        specializations: [{ id: '5', name: 'Ortopedista' }],
        rating: 4.7,
        reviews_count: 89,
        description: 'Especialista em cirurgia ortopédica e tratamento de lesões esportivas.',
        status: 'active',
    },
    {
        id: 'example-6',
        user: {
            name: 'Dra. Fernanda Oliveira',
            email: 'fernanda.oliveira@example.com',
        },
        specializations: [{ id: '6', name: 'Psicóloga' }],
        rating: 4.9,
        reviews_count: 203,
        description: 'Terapia cognitivo-comportamental e apoio psicológico para todas as idades.',
        status: 'active',
    },
];

// Filtrar médicos baseado nos filtros
const filteredDoctors = computed(() => {
    // Combinar médicos do backend com médicos estáticos de exemplo
    let doctors = [...(props.availableDoctors || []), ...exampleDoctors];
    
    if (searchQuery.value.trim()) {
        const query = searchQuery.value.toLowerCase();
        doctors = doctors.filter(doctor => 
            doctor.user.name.toLowerCase().includes(query) ||
            doctor.specializations?.some(spec => spec.name.toLowerCase().includes(query))
        );
    }
    
    if (selectedSpecialty.value) {
        doctors = doctors.filter(doctor => 
            doctor.specializations?.some(spec => spec.id === selectedSpecialty.value)
        );
    }
    
    if (telemedicineOnly.value) {
        // Filtrar médicos que atendem online (assumindo que todos atendem online por enquanto)
    }
    
    if (availableNow.value) {
        // Filtrar médicos disponíveis agora (assumindo que todos estão disponíveis por enquanto)
    }
    
    return doctors;
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
];
</script>

<template>
    <Head title="Pesquisar Médicos" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-12 overflow-x-auto bg-white px-6 py-8">
            <!-- Header -->
            <div class="flex flex-col gap-2 items-center text-center">
                <h1 class="text-4xl font-bold text-gray-900">Encontre o especialista ideal para você</h1>
                <p class="text-lg text-gray-600">Busque por especialidade, médico ou sintoma e agende sua consulta online.</p>
            </div>

            <!-- Barra de Busca -->
            <div class="relative w-[90%] mx-auto">
                <Search class="absolute left-4 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400 pointer-events-none" />
                <Input
                    v-model="searchQuery"
                    placeholder="Buscar por especialidade, médico ou sintoma..."
                    class="pl-12 h-14 text-base bg-primary/10 border-primary/20 focus:border-primary focus:ring-primary rounded-xl w-full"
                />
            </div>

            <!-- Filtros -->
            <div class="flex flex-wrap items-center justify-between gap-4 w-[90%] mx-auto">
                <!-- Grupo Esquerdo: Dropdowns -->
                <div class="flex flex-wrap items-center gap-4">
                    <!-- Dropdown Especialidade -->
                    <div class="relative">
                        <Button
                            variant="outline"
                            class="h-10 px-4 bg-gray-50 hover:bg-gray-100 border-gray-200 text-gray-700 rounded-lg"
                        >
                            <span>Especialidade</span>
                            <ChevronDown class="ml-2 h-4 w-4" />
                        </Button>
                    </div>

                    <!-- Dropdown Convênio -->
                    <div class="relative">
                        <Button
                            variant="outline"
                            class="h-10 px-4 bg-gray-50 hover:bg-gray-100 border-gray-200 text-gray-700 rounded-lg"
                        >
                            <span>Convênio</span>
                            <ChevronDown class="ml-2 h-4 w-4" />
                        </Button>
                    </div>

                    <!-- Dropdown Data -->
                    <div class="relative">
                        <Button
                            variant="outline"
                            class="h-10 px-4 bg-gray-50 hover:bg-gray-100 border-gray-200 text-gray-700 rounded-lg"
                        >
                            <span>Data</span>
                            <ChevronDown class="ml-2 h-4 w-4" />
                        </Button>
                    </div>
                </div>

                <!-- Grupo Direito: Checkboxes -->
                <div class="flex flex-wrap items-center gap-4">
                    <!-- Checkbox Telemedicina -->
                    <div class="flex items-center space-x-2">
                        <Checkbox id="telemedicine" v-model:checked="telemedicineOnly" />
                        <label for="telemedicine" class="text-sm text-gray-700 cursor-pointer">Telemedicina</label>
                    </div>

                    <!-- Checkbox Atende Agora -->
                    <div class="flex items-center space-x-2">
                        <Checkbox id="available-now" v-model:checked="availableNow" />
                        <label for="available-now" class="text-sm text-gray-700 cursor-pointer">Atende Agora</label>
                    </div>
                </div>
            </div>

            <!-- Especializações Recomendadas -->
            <div class="space-y-8">
                <h2 class="text-2xl font-bold text-gray-900 text-center">Especializações Recomendadas para Você</h2>
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 max-w-6xl mx-auto">
                    <button
                        v-for="specialization in specializations.slice(0, 6)"
                        :key="specialization.id"
                        class="flex flex-col items-center justify-center gap-3 p-6 bg-primary/10 hover:bg-primary/20 rounded-2xl transition-colors cursor-pointer group"
                    >
                        <component
                            :is="getSpecializationIcon(specialization.name)"
                            class="h-8 w-8 text-primary group-hover:scale-110 transition-transform"
                        />
                        <span class="text-sm font-medium text-gray-900 text-center">{{ specialization.name }}</span>
                    </button>
                </div>
            </div>

            <!-- Médicos Disponíveis Agora -->
            <div class="space-y-12">
                <h2 class="text-2xl font-bold text-gray-900 text-center">Precisa de atendimento agora?</h2>
                
                <div v-if="filteredDoctors.length > 0" class="grid grid-cols-1 md:grid-cols-3 gap-6 max-w-7xl mx-auto">
                    <div
                        v-for="doctor in filteredDoctors.slice(0, 6)"
                        :key="doctor.id"
                        class="bg-white rounded-xl border border-gray-200 p-6 hover:shadow-lg transition-shadow"
                    >
                        <!-- Avatar e Informações Básicas -->
                        <div class="flex items-start gap-4 mb-4">
                            <Avatar class="h-16 w-16">
                                <AvatarImage :src="doctor.user.avatar || undefined" />
                                <AvatarFallback class="bg-primary/10 text-primary text-lg font-semibold">
                                    {{ getInitials(doctor.user?.name) }}
                                </AvatarFallback>
                            </Avatar>
                            
                            <div class="flex-1 min-w-0">
                                <h3 class="font-bold text-lg text-gray-900 mb-1">{{ doctor.user.name }}</h3>
                                <p class="text-sm text-gray-600 mb-2">
                                    {{ doctor.specializations?.[0]?.name || 'Especialista' }}
                                </p>
                                
                                <!-- Avaliação -->
                                <div class="flex items-center gap-1">
                                    <Star class="h-4 w-4 text-primary fill-primary" />
                                    <span class="text-sm font-medium text-gray-900">
                                        {{ doctor.rating || '4.9' }} ({{ doctor.reviews_count || '124' }})
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Descrição -->
                        <p class="text-sm text-gray-600 mb-4 line-clamp-2">
                            {{ doctor.description || 'Especialista com vasta experiência em atendimento médico.' }}
                        </p>

                        <!-- Badges de Disponibilidade -->
                        <div class="flex flex-wrap gap-2 mb-4">
                            <span class="inline-flex items-center gap-1 px-3 py-1 bg-blue-50 text-blue-700 rounded-full text-xs font-medium">
                                <Video class="h-3 w-3" />
                                Atende Online
                            </span>
                            <span class="inline-flex items-center gap-1 px-3 py-1 bg-green-50 text-green-700 rounded-full text-xs font-medium">
                                <Circle class="h-3 w-3 fill-green-500 text-green-500" />
                                Disponível Agora
                            </span>
                        </div>

                        <!-- Botões de Ação -->
                        <div class="flex gap-2">
                            <Button as-child class="flex-1 bg-primary hover:bg-primary/90 text-gray-900 font-semibold">
                                <Link :href="patientRoutes.scheduleConsultation()">
                                    Agendar Consulta
                                </Link>
                            </Button>
                            <Button as-child variant="outline" class="flex-1 border-gray-200 text-gray-700">
                                <Link :href="patientRoutes.doctorPerfil()">
                                    Ver Perfil
                                </Link>
                            </Button>
                        </div>
                    </div>
                </div>

                <div v-else class="text-center py-12">
                    <Search class="h-16 w-16 text-gray-300 mx-auto mb-4" />
                    <p class="text-gray-500 text-lg">Nenhum médico encontrado com os filtros selecionados</p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
