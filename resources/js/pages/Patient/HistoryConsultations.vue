<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import * as patientRoutes from '@/routes/patient';
import { onMounted, ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { useRouteGuard } from '@/composables/auth';
import { Button } from '@/components/ui/button';
import { MoreVertical } from 'lucide-vue-next';
import { 
    PaginationEllipsis, 
    PaginationFirst, 
    PaginationLast, 
    PaginationList, 
    PaginationListItem, 
    PaginationNext, 
    PaginationPrev, 
    PaginationRoot 
} from 'reka-ui';
import AppointmentSummary from '@/components/AppointmentSummary.vue';

interface Appointment {
    id: string;
    status: 'scheduled' | 'in_progress' | 'completed' | 'cancelled' | 'rescheduled' | 'no_show';
    scheduled_at: string;
    doctor: {
        id: string;
        user: {
            id: string;
            name: string;
            avatar?: string | null;
        };
        specializations: Array<{ id: string; name: string }>;
    };
}

interface PaginatedAppointments {
    data: Appointment[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links?: any[];
}

interface Props {
    appointments: PaginatedAppointments;
    stats: {
        total: number;
        upcoming: number;
        completed: number;
        cancelled: number;
    };
    filters?: {
        status?: string;
    };
}

const props = defineProps<Props>();

const { canAccessPatientRoute } = useRouteGuard();

const stats = computed(() => props.stats ?? {
    total: 0,
    upcoming: 0,
    completed: 0,
    cancelled: 0,
});
const pagination = computed(() => props.appointments ?? {
    data: [],
    current_page: 1,
    last_page: 1,
    per_page: 10,
    total: 0,
});

// Estado para o filtro ativo (inicializar com filtro do back-end ou padrão)
const activeFilter = ref<'upcoming' | 'completed' | 'cancelled' | 'all'>(() => {
    if (props.filters?.status) {
        return props.filters.status as any;
    }
    return 'all';
});

// Aplicar filtro e fazer requisição
const applyFilter = (filter: 'upcoming' | 'completed' | 'cancelled' | 'all') => {
    activeFilter.value = filter;
    const queryParams: Record<string, any> = {};
    
    if (filter !== 'all') {
        queryParams.status = filter;
    }
    
    router.get(patientRoutes.historyConsultations.url(), queryParams, {
        preserveState: true,
        preserveScroll: true,
    });
};

// Formatar data para exibição
const formatDate = (dateString: string): string => {
    const date = new Date(dateString);
    return date.toLocaleDateString('pt-BR', {
        day: '2-digit',
        month: 'short',
        year: 'numeric'
    }).toUpperCase();
};

const formatTime = (dateString: string): string => {
    const date = new Date(dateString);
    return date.toLocaleTimeString('pt-BR', {
        hour: '2-digit',
        minute: '2-digit'
    });
};

// Mapear status para badge
const getStatusBadge = (status: string) => {
    const statusMap: Record<string, { label: string; class: string }> = {
        scheduled: { label: 'Agendada', class: 'bg-yellow-100 text-yellow-700' },
        in_progress: { label: 'Em Andamento', class: 'bg-blue-100 text-blue-700' },
        completed: { label: 'Concluída', class: 'bg-green-100 text-green-700' },
        cancelled: { label: 'Cancelada', class: 'bg-red-100 text-red-700' },
        rescheduled: { label: 'Reagendada', class: 'bg-purple-100 text-purple-700' },
        no_show: { label: 'Não Compareceu', class: 'bg-gray-100 text-gray-700' },
    };
    return statusMap[status] || statusMap.scheduled;
};

// Navegar para página
const goToPage = (page: number) => {
    const queryParams: Record<string, any> = { page };
    
    if (activeFilter.value !== 'all') {
        queryParams.status = activeFilter.value;
    }
    
    router.get(patientRoutes.historyConsultations.url(), queryParams, {
        preserveState: true,
        preserveScroll: true,
    });
};

// Dados das consultas do back-end
const consultations = computed(() => {
    if (!pagination.value || !pagination.value.data) {
        return [];
    }
    return pagination.value.data.filter((consultation: any) => consultation && consultation.id);
});

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: patientRoutes.dashboard().url,
    },
    {
        title: 'Histórico de Consultas',
        href: patientRoutes.historyConsultations().url,
    },
];


// Verificar acesso ao montar componente
onMounted(() => {
    canAccessPatientRoute();
});
</script>

<template>
    <Head title="Histórico de Consultas" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col overflow-x-auto bg-white px-4 py-6">
            <!-- Header Section -->
            <div class="mb-8">
                <h1 class="text-4xl font-bold text-gray-900 mb-2">
                    Meu Histórico de Consultas
                </h1>
                <p class="text-base text-primary">
                    Acesse detalhes, avalie atendimentos e gerencie seus acompanhamentos.
                </p>
            </div>

            <!-- Summary Cards Section -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <!-- Card 1: Total de Consultas -->
                <div class="bg-white rounded-lg border border-gray-200 p-6">
                    <h3 class="text-sm font-medium text-gray-600 mb-1">
                        Total de Consultas
                    </h3>
                    <p class="text-3xl font-bold text-gray-900">
                        {{ stats.total }}
                    </p>
                </div>

                <!-- Card 2: Concluídas -->
                <div class="bg-white rounded-lg border border-gray-200 p-6">
                    <h3 class="text-sm font-medium text-gray-600 mb-1">
                        Concluídas
                    </h3>
                    <p class="text-3xl font-bold text-gray-900">
                        {{ stats.completed }}
                    </p>
                </div>

                <!-- Card 3: Próximas -->
                <div class="bg-white rounded-lg border border-gray-200 p-6">
                    <h3 class="text-sm font-medium text-gray-600 mb-1">
                        Próximas
                    </h3>
                    <p class="text-3xl font-bold text-gray-900">
                        {{ stats.upcoming }}
                    </p>
                </div>
                
                <!-- Card 4: Canceladas -->
                <div class="bg-white rounded-lg border border-gray-200 p-6">
                    <h3 class="text-sm font-medium text-gray-600 mb-1">
                        Canceladas
                    </h3>
                    <p class="text-3xl font-bold text-gray-900">
                        {{ stats.cancelled }}
                    </p>
                </div>
            </div>

            <!-- Navigation/Filter Section -->
            <div class="bg-secondary/50 rounded-lg border border-gray-200 p-4">
                <div class="grid grid-cols-4 gap-2">
                    <button
                        @click="applyFilter('upcoming')"
                        :class="[
                            'py-3 rounded-lg font-medium transition-colors',
                            activeFilter === 'upcoming'
                                ? 'bg-primary text-gray-900'
                                : 'text-gray-500 hover:text-gray-700'
                        ]"
                    >
                        Próximas
                    </button>
                    <button
                        @click="applyFilter('completed')"
                        :class="[
                            'py-3 rounded-lg font-medium transition-colors',
                            activeFilter === 'completed'
                                ? 'bg-primary text-gray-900'
                                : 'text-gray-500 hover:text-gray-700'
                        ]"
                    >
                        Concluídas
                    </button>
                    <button
                        @click="applyFilter('cancelled')"
                        :class="[
                            'py-3 rounded-lg font-medium transition-colors',
                            activeFilter === 'cancelled'
                                ? 'bg-primary text-gray-900'
                                : 'text-gray-500 hover:text-gray-700'
                        ]"
                    >
                        Canceladas
                    </button>
                    <button
                        @click="applyFilter('all')"
                        :class="[
                            'py-3 rounded-lg font-medium transition-colors',
                            activeFilter === 'all'
                                ? 'bg-primary text-gray-900'
                                : 'text-gray-500 hover:text-gray-700'
                        ]"
                    >
                        Todas
                    </button>
                </div>
            </div>

            <!-- Content Area -->
            <div class="mt-6 space-y-4">
                <AppointmentSummary
                    v-for="consultation in consultations"
                    :key="consultation.id"
                    :appointment="{
                        id: consultation.id,
                        status: consultation.status,
                        scheduled_at: consultation.scheduled_at,
                        doctor: {
                            id: consultation.doctor?.id ?? '',
                            name: consultation.doctor?.user?.name ?? 'Médico não informado',
                            specializations: consultation.doctor?.specializations?.map((spec: any) => typeof spec === 'string' ? spec : spec.name) ?? [],
                        },
                    }"
                >
                    <template #actions>
                        <div class="flex items-center gap-2">
                            <Button 
                                as-child
                                :variant="['completed', 'scheduled', 'rescheduled'].includes(consultation.status) ? 'default' : 'outline'"
                                :class="[
                                    ['completed', 'scheduled', 'rescheduled'].includes(consultation.status)
                                        ? 'bg-primary hover:bg-primary/90 text-gray-900'
                                        : 'border border-gray-300 text-gray-900 hover:bg-gray-50'
                                ]"
                            >
                                <Link :href="patientRoutes.consultationDetails({ appointment: consultation.id })">
                                    Ver detalhes
                                </Link>
                            </Button>
                            <button class="p-1.5 rounded-full hover:bg-gray-100 transition-colors">
                                <MoreVertical class="h-5 w-5 text-gray-600" />
                            </button>
                        </div>
                    </template>
                </AppointmentSummary>

                <div v-if="consultations.length === 0" class="rounded-lg border border-dashed border-gray-300 bg-gray-50 py-12 text-center text-gray-500">
                    Nenhuma consulta encontrada para o filtro selecionado.
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="(pagination?.total ?? 0) > pagination?.per_page" class="mt-6 flex justify-end">
                <PaginationRoot 
                    :page="pagination?.current_page ?? 1" 
                    :default-page="1"
                    :items-per-page="pagination?.per_page ?? 10"
                    :total="pagination?.total ?? 0"
                    @update:page="goToPage"
                    class="flex items-center gap-1"
                >
                    <PaginationList v-slot="{ items }" class="flex items-center gap-0.5">
                        <PaginationFirst class="px-2 py-1 text-gray-600 hover:text-gray-800 transition-colors">
                            &lt;&lt;
                        </PaginationFirst>
                        <PaginationPrev class="px-2 py-1 text-gray-600 hover:text-gray-800 transition-colors">
                            &lt;
                        </PaginationPrev>
                        
                        <template v-for="(page, index) in items" :key="index">
                            <PaginationListItem
                                v-if="page.type === 'page'"
                                :value="page.value"
                                class="px-3 py-1.5 rounded-lg border border-gray-300 text-gray-700 font-medium transition-colors data-current:bg-white data-current:border-gray-300 data-current:text-gray-900 hover:border-gray-400 hover:text-gray-900"
                            />
                            <PaginationEllipsis
                                v-else
                                :index="index"
                                class="px-1 py-1 text-gray-600"
                            >
                                &#8230;
                            </PaginationEllipsis>
                        </template>
                        
                        <PaginationNext class="px-2 py-1 text-gray-600 hover:text-gray-800 transition-colors">
                            &gt;
                        </PaginationNext>
                        <PaginationLast class="px-2 py-1 text-gray-600 hover:text-gray-800 transition-colors">
                            &gt;&gt;
                        </PaginationLast>
                    </PaginationList>
                </PaginationRoot>
            </div>

            <!-- Call to Action Banner -->
            <div class="mt-8 bg-primary/10 rounded-lg border border-primary/20 p-6 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div class="flex-1">
                    <h2 class="text-xl font-bold text-gray-900 mb-2">
                        Precisa de um acompanhamento?
                    </h2>
                    <p class="text-base text-gray-700 leading-relaxed">
                        Não deixe sua saúde para depois. Agende uma nova consulta para garantir que tudo está bem.
                    </p>
                </div>
                <Button as-child class="bg-primary hover:bg-primary/90 text-gray-900 font-semibold px-6 py-3 whitespace-nowrap">
                    <Link :href="patientRoutes.scheduleConsultation()">
                        Agendar Nova Consulta
                    </Link>
                </Button>
            </div>
        </div>
    </AppLayout>
</template>

