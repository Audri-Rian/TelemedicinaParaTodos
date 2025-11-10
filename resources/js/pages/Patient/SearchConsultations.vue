<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { Search, Video, Circle, Heart, Brain, Eye, Bone, Apple, Calendar, Clock, Baby, Stethoscope, Pill, Activity, Microscope, Syringe } from 'lucide-vue-next';
import * as patientRoutes from '@/routes/patient';
import { onMounted, ref, computed, watch } from 'vue';
import { useRouteGuard } from '@/composables/auth';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Avatar, AvatarImage, AvatarFallback } from '@/components/ui/avatar';
import { useInitials } from '@/composables/useInitials';
import { Link } from '@inertiajs/vue3';
import DoctorCard from '@/components/DoctorCard.vue';

interface DoctorSpecialization {
    id: string;
    name: string;
}

interface DoctorUser {
    name: string;
    email?: string;
    avatar?: string | null;
}

interface Doctor {
    id: string;
    crm?: string;
    status?: string;
    consultation_fee?: number | null;
    biography?: string | null;
    availability_schedule?: Record<string, { start?: string; end?: string; slots?: string[] }> | null;
    available_slots_for_day?: string[] | null;
    user: DoctorUser;
    specializations: DoctorSpecialization[];
}

interface Specialization {
    id: string;
    name: string;
}

interface AppointmentSummary {
    id: string;
    status: string;
    scheduled_at: string | null;
    doctor: {
        id: string;
        name: string;
        specializations: (string | null)[];
    };
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginatedDoctors {
    data: Doctor[];
    links?: PaginationLink[];
    current_page?: number;
    last_page?: number;
    total?: number;
}

interface Filters {
    search?: string;
    specialization_id?: string;
    date?: string;
}

interface Props {
    appointments?: AppointmentSummary[];
    availableDoctors: PaginatedDoctors;
    specializations: Specialization[];
    filters?: Filters;
}

const props = defineProps<Props>();

const { canAccessPatientRoute } = useRouteGuard();
const { getInitials } = useInitials();

const searchQuery = ref(props.filters?.search ?? '');
const selectedSpecialty = ref<string | null>(props.filters?.specialization_id ?? null);
const selectedDate = ref<string | null>(props.filters?.date ?? null);
const telemedicineOnly = ref(false);
const availableNow = ref(false);

onMounted(() => {
    canAccessPatientRoute();
});

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

const doctors = computed<Doctor[]>(() => props.availableDoctors?.data ?? []);
const paginationLinks = computed<PaginationLink[]>(() => props.availableDoctors?.links ?? []);

const filteredDoctors = computed(() => {
    let list = doctors.value.slice();

    if (telemedicineOnly.value) {
        // Placeholder para futuras implementações. Atualmente, todos atendem online.
        list = list;
    }

    if (availableNow.value && selectedDate.value) {
        list = list.filter((doctor) => (doctor.available_slots_for_day ?? []).length > 0);
    }

    return list;
});

const buildQueryParams = () => {
    const queryParams: Record<string, any> = {};

    if (searchQuery.value.trim()) {
        queryParams.search = searchQuery.value.trim();
    }

    if (selectedSpecialty.value) {
        queryParams.specialization_id = selectedSpecialty.value;
    }

    if (selectedDate.value) {
        queryParams.date = selectedDate.value;
    }

    return queryParams;
};

const applyFilters = () => {
    router.get(patientRoutes.searchConsultations.url(), buildQueryParams(), {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

let searchTimeout: ReturnType<typeof setTimeout> | null = null;
watch(searchQuery, () => {
    if (searchTimeout) {
        clearTimeout(searchTimeout);
    }
    searchTimeout = setTimeout(() => {
        applyFilters();
    }, 500);
});

watch(selectedSpecialty, () => {
    applyFilters();
});

watch(selectedDate, () => {
    applyFilters();
});

const changePage = (link: PaginationLink) => {
    if (!link.url || link.active) {
        return;
    }

    router.get(link.url, {}, {
        preserveState: true,
        preserveScroll: true,
    });
};

const resetFilters = () => {
    searchQuery.value = '';
    selectedSpecialty.value = null;
    selectedDate.value = null;
    telemedicineOnly.value = false;
    availableNow.value = false;
    applyFilters();
};

const selectSpecialization = (specializationId: string) => {
    selectedSpecialty.value = specializationId;
};

const formatDateLabel = (dateString: string | null) => {
    if (!dateString) {
        return '';
    }

    try {
        return new Intl.DateTimeFormat('pt-BR', {
            day: '2-digit',
            month: 'long',
            year: 'numeric',
        }).format(new Date(`${dateString}T00:00:00`));
    } catch (error) {
        return dateString;
    }
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
                <!-- Filtros principais -->
                <div class="flex flex-wrap items-end gap-4">
                    <div class="flex flex-col">
                        <label for="specialization" class="text-sm font-medium text-gray-700 mb-1">Especialidade</label>
                        <select
                            id="specialization"
                            v-model="selectedSpecialty"
                            class="h-10 min-w-[200px] rounded-lg border border-gray-200 bg-white px-3 text-sm text-gray-700 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/30"
                        >
                            <option :value="null">Todas</option>
                            <option v-for="specialization in specializations" :key="specialization.id" :value="specialization.id">
                                {{ specialization.name }}
                            </option>
                        </select>
                    </div>
                    <div class="flex flex-col">
                        <label for="filter-date" class="text-sm font-medium text-gray-700 mb-1">Data</label>
                        <Input
                            id="filter-date"
                            type="date"
                            v-model="selectedDate"
                            class="h-10 w-40 rounded-lg border border-gray-200 bg-white px-3 text-sm text-gray-700 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/30"
                        />
                    </div>
                    <div class="flex items-center">
                        <Button
                            variant="ghost"
                            class="text-sm text-gray-600 hover:text-primary"
                            @click="resetFilters"
                        >
                            Limpar filtros
                        </Button>
                    </div>
                </div>

                <!-- Filtros auxiliares -->
                <div class="flex flex-wrap items-center gap-4">
                    <div class="flex items-center space-x-2">
                        <Checkbox id="telemedicine" v-model:checked="telemedicineOnly" />
                        <label for="telemedicine" class="text-sm text-gray-700 cursor-pointer">Telemedicina</label>
                    </div>

                    <div class="flex items-center space-x-2">
                        <Checkbox id="available-now" v-model:checked="availableNow" />
                        <label for="available-now" class="text-sm text-gray-700 cursor-pointer">Disponível na data</label>
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
                        @click="selectSpecialization(specialization.id)"
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
                    <DoctorCard
                        v-for="doctor in filteredDoctors"
                        :key="doctor.id"
                        :doctor="doctor"
                        :selected-date="selectedDate"
                        class="hover:shadow-lg transition-shadow"
                    >
                        <template #actions="{ doctor: doctorFromSlot }">
                            <Button as-child class="flex-1 bg-primary hover:bg-primary/90 text-gray-900 font-semibold">
                                <Link :href="patientRoutes.scheduleConsultation({ query: { doctor_id: doctorFromSlot.id, date: selectedDate ?? undefined } })">
                                    Agendar Consulta
                                </Link>
                            </Button>
                            <Button as-child variant="outline" class="flex-1 border-gray-200 text-gray-700">
                                <Link :href="patientRoutes.doctorPerfil({ query: { doctor_id: doctorFromSlot.id } })">
                                    Ver Perfil
                                </Link>
                            </Button>
                        </template>
                    </DoctorCard>
                </div>

                <div v-else class="text-center py-12">
                    <Search class="h-16 w-16 text-gray-300 mx-auto mb-4" />
                    <p class="text-gray-500 text-lg">Nenhum médico encontrado com os filtros selecionados</p>
                </div>

                <div v-if="paginationLinks.length > 0" class="flex flex-wrap items-center justify-center gap-2">
                    <button
                        v-for="link in paginationLinks"
                        :key="link.label"
                        type="button"
                        @click="changePage(link)"
                        :disabled="!link.url"
                        class="px-3 py-1.5 rounded-lg text-sm"
                        :class="[
                            link.active ? 'bg-primary text-white' : 'bg-white text-gray-700 border border-gray-200 hover:bg-gray-100',
                            !link.url ? 'cursor-not-allowed opacity-60' : 'cursor-pointer'
                        ]"
                        v-html="link.label"
                    />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
