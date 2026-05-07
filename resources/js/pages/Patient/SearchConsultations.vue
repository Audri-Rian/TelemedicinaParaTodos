<script setup lang="ts">
import DoctorCard from '@/components/DoctorCard.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { useRouteGuard } from '@/composables/auth';
import { useInitials } from '@/composables/useInitials';
import AppLayout from '@/layouts/AppLayout.vue';
import * as patientRoutes from '@/routes/patient';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import {
    Activity,
    Apple,
    Baby,
    Bone,
    Brain,
    Calendar,
    CheckCircle2,
    ChevronLeft,
    ChevronRight,
    Circle,
    Clock,
    Eye,
    Filter,
    Heart,
    Microscope,
    Pill,
    Search,
    SlidersHorizontal,
    Sparkles,
    Stethoscope,
    Syringe,
    Video,
    X,
} from 'lucide-vue-next';
import { computed, onMounted, ref, watch } from 'vue';

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
    from?: number;
    to?: number;
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

type SortOption = 'relevance' | 'priceAsc' | 'soonest' | 'name';

const props = defineProps<Props>();

const { canAccessPatientRoute } = useRouteGuard();
const { getInitials } = useInitials();

const searchQuery = ref(props.filters?.search ?? '');
const selectedSpecialty = ref<string | null>(props.filters?.specialization_id ?? null);
const selectedDate = ref<string | null>(props.filters?.date ?? null);
const availableOnDate = ref(false);
const sort = ref<SortOption>('relevance');

onMounted(() => {
    canAccessPatientRoute();
});

const specializationIcons: Record<string, any> = {
    Cardiologia: Heart,
    Dermatologia: Eye,
    Psicologia: Brain,
    Ortopedia: Bone,
    Nutrição: Apple,
    Pediatria: Baby,
    'Clínica Geral': Stethoscope,
    Farmacologia: Pill,
    Fisioterapia: Activity,
    Anestesiologia: Syringe,
    Biomedicina: Microscope,
};

const today = computed(() => new Date().toISOString().slice(0, 10));
const doctors = computed<Doctor[]>(() => props.availableDoctors?.data ?? []);
const paginationLinks = computed<PaginationLink[]>(() => props.availableDoctors?.links ?? []);
const totalResults = computed(() => props.availableDoctors?.total ?? doctors.value.length);
const hasActiveFilters = computed(() => Boolean(searchQuery.value.trim() || selectedSpecialty.value || selectedDate.value || availableOnDate.value));

const selectedSpecializationName = computed(() => {
    return props.specializations.find((specialization) => specialization.id === selectedSpecialty.value)?.name ?? null;
});

const activeChips = computed(() => {
    const chips: Array<{ kind: 'search' | 'specialty' | 'date' | 'available'; label: string }> = [];

    if (searchQuery.value.trim()) {
        chips.push({ kind: 'search', label: searchQuery.value.trim() });
    }

    if (selectedSpecializationName.value) {
        chips.push({ kind: 'specialty', label: selectedSpecializationName.value });
    }

    if (selectedDate.value) {
        chips.push({ kind: 'date', label: formatDate(selectedDate.value) });
    }

    if (availableOnDate.value) {
        chips.push({ kind: 'available', label: 'Com horários na data' });
    }

    return chips;
});

const displayedDoctors = computed(() => {
    let list = doctors.value.slice();

    if (availableOnDate.value && selectedDate.value) {
        list = list.filter((doctor) => (doctor.available_slots_for_day ?? []).length > 0);
    }

    if (sort.value === 'priceAsc') {
        list.sort((a, b) => Number(a.consultation_fee ?? Number.MAX_SAFE_INTEGER) - Number(b.consultation_fee ?? Number.MAX_SAFE_INTEGER));
    }

    if (sort.value === 'soonest') {
        list.sort((a, b) => (a.available_slots_for_day?.[0] ?? '99:99').localeCompare(b.available_slots_for_day?.[0] ?? '99:99'));
    }

    if (sort.value === 'name') {
        list.sort((a, b) => a.user.name.localeCompare(b.user.name));
    }

    return list;
});

const popularSpecializations = computed(() => props.specializations.slice(0, 6));

const buildQueryParams = () => {
    const queryParams: Record<string, string> = {};

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

const applyFilters = (replace = false) => {
    router.get(patientRoutes.searchConsultations.url(), buildQueryParams(), {
        preserveState: true,
        preserveScroll: true,
        replace,
    });
};

let searchTimeout: ReturnType<typeof setTimeout> | null = null;
watch(searchQuery, () => {
    if (searchTimeout) {
        clearTimeout(searchTimeout);
    }

    searchTimeout = setTimeout(() => {
        applyFilters(true);
    }, 500);
});

watch(selectedSpecialty, () => applyFilters());
watch(selectedDate, () => {
    if (!selectedDate.value) {
        availableOnDate.value = false;
    }

    applyFilters();
});

const changePage = (link: PaginationLink) => {
    if (!link.url || link.active) {
        return;
    }

    router.get(
        link.url,
        {},
        {
            preserveState: true,
            preserveScroll: true,
        },
    );
};

const resetFilters = () => {
    searchQuery.value = '';
    selectedSpecialty.value = null;
    selectedDate.value = null;
    availableOnDate.value = false;
    applyFilters();
};

const removeChip = (kind: 'search' | 'specialty' | 'date' | 'available') => {
    if (kind === 'search') {
        searchQuery.value = '';
    }

    if (kind === 'specialty') {
        selectedSpecialty.value = null;
    }

    if (kind === 'date') {
        selectedDate.value = null;
    }

    if (kind === 'available') {
        availableOnDate.value = false;
    }
};

const selectSpecialization = (specializationId: string) => {
    selectedSpecialty.value = selectedSpecialty.value === specializationId ? null : specializationId;
};

const getSpecializationIcon = (name: string) => specializationIcons[name] || Stethoscope;

const primarySpecialization = (doctor: Doctor) => doctor.specializations?.[0]?.name ?? 'Especialista';

const formatCurrency = (value?: number | null) => {
    if (value == null) {
        return 'Valor a consultar';
    }

    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL',
    }).format(Number(value));
};

function formatDate(date: string) {
    try {
        return new Intl.DateTimeFormat('pt-BR', {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
        }).format(new Date(`${date}T00:00:00`));
    } catch {
        return date;
    }
}

const paginationKind = (label: string) => {
    if (label.includes('Previous') || label.includes('Anterior') || label.includes('&laquo;')) {
        return 'previous';
    }

    if (label.includes('Next') || label.includes('Próximo') || label.includes('&raquo;')) {
        return 'next';
    }

    return 'page';
};

const cleanPaginationLabel = (label: string) => label.replace('&laquo;', '').replace('&raquo;', '').trim();

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
        <div class="min-h-full bg-[#f5f5f0] px-2 py-6 text-gray-950 sm:px-3 lg:px-4">
            <div class="flex w-full flex-col gap-6">
                <section class="overflow-hidden rounded-lg border border-[#dedbd2] bg-white shadow-sm">
                    <div class="grid gap-6 p-5 sm:p-6 lg:grid-cols-[minmax(0,1fr)_280px] lg:items-end lg:p-8">
                        <div class="space-y-5">
                            <div class="space-y-2">
                                <div
                                    class="inline-flex items-center gap-2 rounded-full border border-teal-100 bg-teal-50 px-3 py-1 text-xs font-bold text-teal-800"
                                >
                                    <Sparkles class="h-3.5 w-3.5" />
                                    Consulta online com profissionais ativos
                                </div>
                                <div class="max-w-3xl space-y-2">
                                    <h1 class="text-3xl font-black tracking-normal text-gray-950 sm:text-4xl">
                                        Encontre o especialista ideal para você
                                    </h1>
                                    <p class="text-base font-medium text-gray-600">
                                        Busque por médico, especialidade ou sintoma e compare horários disponíveis antes de agendar.
                                    </p>
                                </div>
                            </div>

                            <div class="relative max-w-4xl">
                                <Search class="pointer-events-none absolute top-1/2 left-4 h-5 w-5 -translate-y-1/2 text-gray-400" />
                                <Input
                                    v-model="searchQuery"
                                    placeholder="Buscar por especialidade, médico ou sintoma..."
                                    class="h-14 rounded-lg border-[#d7d2c8] bg-[#fbfbf7] pl-12 text-base font-semibold shadow-inner focus:border-teal-600 focus:ring-teal-600/20"
                                />
                            </div>
                        </div>

                        <div class="rounded-lg border border-teal-100 bg-teal-50 p-4">
                            <div class="flex items-center gap-2 text-sm font-extrabold text-teal-900">
                                <CheckCircle2 class="h-4 w-4" />
                                Busca atual
                            </div>
                            <p class="mt-2 text-3xl font-black text-teal-950">{{ totalResults }}</p>
                            <p class="text-sm font-semibold text-teal-800">médicos encontrados</p>
                        </div>
                    </div>
                </section>

                <section class="grid gap-5 lg:grid-cols-[300px_minmax(0,1fr)]">
                    <aside class="space-y-4 lg:sticky lg:top-6 lg:self-start">
                        <div class="rounded-lg border border-[#dedbd2] bg-white p-5 shadow-sm">
                            <div class="flex items-center justify-between gap-3">
                                <div class="flex items-center gap-2">
                                    <SlidersHorizontal class="h-5 w-5 text-teal-700" />
                                    <h2 class="text-lg font-black text-gray-950">Filtros</h2>
                                </div>
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    class="h-8 px-2 text-xs font-bold text-gray-600 hover:text-teal-700"
                                    @click="resetFilters"
                                >
                                    Limpar
                                </Button>
                            </div>

                            <div class="mt-5 space-y-5">
                                <label class="block space-y-2">
                                    <span class="text-sm font-extrabold text-gray-800">Especialidade</span>
                                    <select
                                        v-model="selectedSpecialty"
                                        class="h-11 w-full rounded-lg border border-[#d7d2c8] bg-white px-3 text-sm font-semibold text-gray-800 focus:border-teal-600 focus:ring-2 focus:ring-teal-600/20 focus:outline-none"
                                    >
                                        <option :value="null">Todas as especialidades</option>
                                        <option v-for="specialization in specializations" :key="specialization.id" :value="specialization.id">
                                            {{ specialization.name }}
                                        </option>
                                    </select>
                                </label>

                                <label class="block space-y-2">
                                    <span class="text-sm font-extrabold text-gray-800">Data da consulta</span>
                                    <Input
                                        v-model="selectedDate"
                                        type="date"
                                        :min="today"
                                        class="h-11 rounded-lg border-[#d7d2c8] bg-white text-sm font-semibold focus:border-teal-600 focus:ring-teal-600/20"
                                    />
                                </label>

                                <label
                                    class="flex items-start gap-3 rounded-lg border border-[#ebe7df] bg-[#fbfbf7] p-3"
                                    :class="!selectedDate ? 'opacity-60' : ''"
                                >
                                    <Checkbox v-model:checked="availableOnDate" :disabled="!selectedDate" class="mt-0.5" />
                                    <span class="space-y-1">
                                        <span class="block text-sm font-extrabold text-gray-800">Disponível na data</span>
                                        <span class="block text-xs font-semibold text-gray-500">
                                            Escolha uma data para mostrar apenas profissionais com horários livres.
                                        </span>
                                    </span>
                                </label>
                            </div>
                        </div>

                        <div class="rounded-lg border border-[#dedbd2] bg-white p-5 shadow-sm">
                            <h2 class="text-sm font-black text-gray-500 uppercase">Especialidades populares</h2>
                            <div class="mt-4 grid grid-cols-2 gap-2 lg:grid-cols-1">
                                <button
                                    v-for="specialization in popularSpecializations"
                                    :key="specialization.id"
                                    type="button"
                                    class="flex items-center gap-3 rounded-lg border px-3 py-3 text-left transition"
                                    :class="
                                        selectedSpecialty === specialization.id
                                            ? 'border-teal-500 bg-teal-50 text-teal-950'
                                            : 'border-[#ebe7df] bg-white text-gray-700 hover:border-teal-200 hover:bg-teal-50/60'
                                    "
                                    @click="selectSpecialization(specialization.id)"
                                >
                                    <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-[#f0f7f5] text-teal-700">
                                        <component :is="getSpecializationIcon(specialization.name)" class="h-4.5 w-4.5" />
                                    </span>
                                    <span class="min-w-0 text-sm font-extrabold">{{ specialization.name }}</span>
                                </button>
                            </div>
                        </div>
                    </aside>

                    <main class="min-w-0 space-y-4">
                        <div class="rounded-lg border border-[#dedbd2] bg-white p-4 shadow-sm">
                            <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                                <div>
                                    <div class="flex items-center gap-2 text-sm font-extrabold text-gray-500">
                                        <Filter class="h-4 w-4" />
                                        Resultados
                                    </div>
                                    <p class="mt-1 text-lg font-black text-gray-950">
                                        {{ totalResults }} médicos encontrados
                                        <span v-if="selectedSpecializationName" class="font-extrabold text-teal-700">
                                            em {{ selectedSpecializationName }}
                                        </span>
                                    </p>
                                </div>

                                <label class="flex items-center gap-2 text-sm font-bold text-gray-600">
                                    Ordenar
                                    <select
                                        v-model="sort"
                                        class="h-10 rounded-lg border border-[#d7d2c8] bg-white px-3 text-sm font-semibold text-gray-800 focus:border-teal-600 focus:ring-2 focus:ring-teal-600/20 focus:outline-none"
                                    >
                                        <option value="relevance">Mais relevantes</option>
                                        <option value="soonest">Horário mais próximo</option>
                                        <option value="priceAsc">Menor preço</option>
                                        <option value="name">Nome</option>
                                    </select>
                                </label>
                            </div>

                            <div v-if="activeChips.length" class="mt-4 flex flex-wrap gap-2">
                                <button
                                    v-for="chip in activeChips"
                                    :key="`${chip.kind}-${chip.label}`"
                                    type="button"
                                    class="inline-flex items-center gap-2 rounded-full border border-teal-100 bg-teal-50 px-3 py-1.5 text-xs font-extrabold text-teal-900"
                                    @click="removeChip(chip.kind)"
                                >
                                    {{ chip.label }}
                                    <X class="h-3.5 w-3.5" />
                                </button>
                            </div>
                        </div>

                        <div v-if="displayedDoctors.length > 0" class="space-y-3">
                            <article
                                v-for="doctor in displayedDoctors"
                                :key="doctor.id"
                                class="hidden rounded-lg border border-[#dedbd2] bg-white p-4 shadow-sm transition hover:border-teal-200 hover:shadow-md md:block"
                            >
                                <div class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_220px] xl:items-center">
                                    <div class="flex min-w-0 gap-4">
                                        <Avatar class="h-16 w-16 shrink-0 border border-teal-100">
                                            <AvatarImage v-if="doctor.user.avatar" :src="doctor.user.avatar" />
                                            <AvatarFallback class="bg-teal-50 text-lg font-black text-teal-800">
                                                {{ getInitials(doctor.user.name) }}
                                            </AvatarFallback>
                                        </Avatar>

                                        <div class="min-w-0 flex-1">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <h3 class="text-xl font-black text-gray-950">{{ doctor.user.name }}</h3>
                                                <span
                                                    class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-extrabold text-emerald-700"
                                                >
                                                    <Circle class="h-2.5 w-2.5 fill-emerald-500 text-emerald-500" />
                                                    Ativo
                                                </span>
                                                <span
                                                    class="inline-flex items-center gap-1 rounded-full bg-sky-50 px-2.5 py-1 text-xs font-extrabold text-sky-700"
                                                >
                                                    <Video class="h-3.5 w-3.5" />
                                                    Online
                                                </span>
                                            </div>

                                            <p class="mt-1 text-sm font-bold text-gray-600">
                                                {{ primarySpecialization(doctor) }}
                                                <span v-if="doctor.crm" class="text-gray-400"> · CRM {{ doctor.crm }}</span>
                                            </p>

                                            <p class="mt-3 line-clamp-2 text-sm leading-6 font-medium text-gray-600">
                                                {{
                                                    doctor.biography ||
                                                    'Atendimento online com acolhimento, escuta clínica e orientação personalizada para sua necessidade.'
                                                }}
                                            </p>

                                            <div v-if="selectedDate" class="mt-4">
                                                <div class="mb-2 flex items-center gap-2 text-xs font-black text-gray-500 uppercase">
                                                    <Calendar class="h-3.5 w-3.5" />
                                                    Horários em {{ formatDate(selectedDate) }}
                                                </div>
                                                <div v-if="doctor.available_slots_for_day?.length" class="flex flex-wrap gap-2">
                                                    <span
                                                        v-for="slot in doctor.available_slots_for_day.slice(0, 5)"
                                                        :key="slot"
                                                        class="inline-flex items-center gap-1 rounded-full bg-teal-50 px-3 py-1 text-xs font-extrabold text-teal-800"
                                                    >
                                                        <Clock class="h-3.5 w-3.5" />
                                                        {{ slot }}
                                                    </span>
                                                </div>
                                                <p v-else class="text-sm font-semibold text-gray-500">Nenhum horário livre nesta data.</p>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="flex flex-col gap-3 rounded-lg border border-[#ebe7df] bg-[#fbfbf7] p-4">
                                        <div>
                                            <p class="text-xs font-black text-gray-500 uppercase">Consulta</p>
                                            <p class="text-xl font-black text-gray-950">{{ formatCurrency(doctor.consultation_fee) }}</p>
                                        </div>
                                        <Button as-child class="bg-teal-500 font-black text-gray-950 hover:bg-teal-400">
                                            <Link
                                                :href="
                                                    patientRoutes.scheduleConsultation({
                                                        query: { doctor_id: doctor.id, date: selectedDate ?? undefined },
                                                    })
                                                "
                                            >
                                                Agendar consulta
                                            </Link>
                                        </Button>
                                        <Button
                                            as-child
                                            variant="outline"
                                            class="border-[#d7d2c8] bg-white font-extrabold text-gray-700 hover:bg-gray-50"
                                        >
                                            <Link :href="patientRoutes.doctorPerfil({ query: { doctor_id: doctor.id } })"> Ver perfil </Link>
                                        </Button>
                                    </div>
                                </div>
                            </article>

                            <DoctorCard
                                v-for="doctor in displayedDoctors"
                                :key="`mobile-${doctor.id}`"
                                :doctor="doctor"
                                :selected-date="selectedDate"
                                class="md:hidden"
                            >
                                <template #actions="{ doctor: doctorFromSlot }">
                                    <Button as-child class="flex-1 bg-teal-500 font-black text-gray-950 hover:bg-teal-400">
                                        <Link
                                            :href="
                                                patientRoutes.scheduleConsultation({
                                                    query: { doctor_id: doctorFromSlot.id, date: selectedDate ?? undefined },
                                                })
                                            "
                                        >
                                            Agendar
                                        </Link>
                                    </Button>
                                    <Button as-child variant="outline" class="flex-1 border-[#d7d2c8] font-extrabold text-gray-700">
                                        <Link :href="patientRoutes.doctorPerfil({ query: { doctor_id: doctorFromSlot.id } })"> Perfil </Link>
                                    </Button>
                                </template>
                            </DoctorCard>
                        </div>

                        <div v-else class="rounded-lg border border-dashed border-[#d7d2c8] bg-white px-6 py-14 text-center shadow-sm">
                            <Search class="mx-auto h-12 w-12 text-gray-300" />
                            <h2 class="mt-4 text-xl font-black text-gray-950">Nenhum médico encontrado</h2>
                            <p class="mx-auto mt-2 max-w-md text-sm font-medium text-gray-500">
                                Ajuste a busca ou remova filtros para ampliar os resultados disponíveis.
                            </p>
                            <Button v-if="hasActiveFilters" class="mt-6 bg-teal-500 font-black text-gray-950 hover:bg-teal-400" @click="resetFilters">
                                Limpar filtros
                            </Button>
                        </div>

                        <div v-if="paginationLinks.length > 0" class="flex flex-wrap items-center justify-center gap-2 pt-2">
                            <button
                                v-for="link in paginationLinks"
                                :key="link.label"
                                type="button"
                                class="grid h-10 min-w-10 place-items-center rounded-lg border px-3 text-sm font-black transition"
                                :class="[
                                    link.active
                                        ? 'border-teal-500 bg-teal-500 text-gray-950'
                                        : 'border-[#d7d2c8] bg-white text-gray-700 hover:border-teal-200 hover:bg-teal-50',
                                    !link.url ? 'cursor-not-allowed opacity-45' : 'cursor-pointer',
                                ]"
                                :disabled="!link.url"
                                @click="changePage(link)"
                            >
                                <ChevronLeft v-if="paginationKind(link.label) === 'previous'" class="h-4 w-4" />
                                <ChevronRight v-else-if="paginationKind(link.label) === 'next'" class="h-4 w-4" />
                                <span v-else>{{ cleanPaginationLabel(link.label) }}</span>
                            </button>
                        </div>
                    </main>
                </section>
            </div>
        </div>
    </AppLayout>
</template>
