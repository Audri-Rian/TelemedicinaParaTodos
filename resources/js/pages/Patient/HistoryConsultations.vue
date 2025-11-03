<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import * as patientRoutes from '@/routes/patient';
import { onMounted, ref, computed } from 'vue';
import { useRouteGuard } from '@/composables/auth';
import { Avatar, AvatarImage, AvatarFallback } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { MoreVertical } from 'lucide-vue-next';
import { useInitials } from '@/composables/useInitials';
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

const { canAccessPatientRoute } = useRouteGuard();
const { getInitials } = useInitials();

// Estado para o filtro ativo
const activeFilter = ref<'upcoming' | 'completed' | 'cancelled' | 'all'>('completed');

// Estado de paginação
const currentPage = ref(1);
const itemsPerPage = 5;

// Dados das consultas (estáticos por enquanto)
const allConsultations = [
    {
        id: 1,
        date: '25 AGO 2024',
        time: '10:30',
        doctorName: 'Dr. Carlos Andrade',
        specialty: 'Cardiologia',
        avatar: null,
        status: 'completed'
    },
    {
        id: 2,
        date: '12 JUL 2024',
        time: '14:00',
        doctorName: 'Dra. Sofia Oliveira',
        specialty: 'Dermatologia',
        avatar: null,
        status: 'completed'
    },
    {
        id: 3,
        date: '05 JUN 2024',
        time: '09:15',
        doctorName: 'Dr. Ricardo Lima',
        specialty: 'Clínico Geral',
        avatar: null,
        status: 'cancelled'
    },
    {
        id: 4,
        date: '28 MAI 2024',
        time: '11:00',
        doctorName: 'Dr. Felipe Santos',
        specialty: 'Ortopedia',
        avatar: null,
        status: 'completed'
    },
    {
        id: 5,
        date: '15 MAI 2024',
        time: '16:30',
        doctorName: 'Dra. Patrícia Costa',
        specialty: 'Pediatria',
        avatar: null,
        status: 'completed'
    },
    {
        id: 6,
        date: '02 ABR 2024',
        time: '08:00',
        doctorName: 'Dr. Rafael Gomes',
        specialty: 'Neurologia',
        avatar: null,
        status: 'completed'
    }
];

// Calcular consultas paginadas
const consultations = computed(() => {
    const startIndex = (currentPage.value - 1) * itemsPerPage;
    return allConsultations.slice(startIndex, startIndex + itemsPerPage);
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
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <!-- Card 1: Total de Consultas -->
                <div class="bg-white rounded-lg border border-gray-200 p-6">
                    <h3 class="text-sm font-medium text-gray-600 mb-1">
                        Total de Consultas
                    </h3>
                    <p class="text-3xl font-bold text-gray-900">
                        12
                    </p>
                </div>

                <!-- Card 2: Última Consulta -->
                <div class="bg-white rounded-lg border border-gray-200 p-6">
                    <h3 class="text-sm font-medium text-gray-600 mb-1">
                        Última Consulta em
                    </h3>
                    <p class="text-3xl font-bold text-gray-900">
                        25/08/2024
                    </p>
                </div>

                <!-- Card 3: Próxima Agendada -->
                <div class="bg-white rounded-lg border border-gray-200 p-6">
                    <h3 class="text-sm font-medium text-gray-600 mb-1">
                        Próxima Agendada
                    </h3>
                    <p class="text-3xl font-bold text-gray-900">
                        15/10/2024
                    </p>
                </div>
            </div>

            <!-- Navigation/Filter Section -->
            <div class="bg-secondary/50 rounded-lg border border-gray-200 p-4">
                <div class="grid grid-cols-4 gap-2">
                    <button
                        @click="activeFilter = 'upcoming'"
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
                        @click="activeFilter = 'completed'"
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
                        @click="activeFilter = 'cancelled'"
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
                        @click="activeFilter = 'all'"
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
                <div
                    v-for="consultation in consultations"
                    :key="consultation.id"
                    class="bg-white rounded-lg border border-gray-200 p-4 flex items-center gap-4"
                >
                    <!-- Avatar -->
                    <Avatar class="h-16 w-16 flex-shrink-0">
                        <AvatarImage :src="consultation.avatar" />
                        <AvatarFallback class="bg-primary/10 text-primary text-lg font-semibold">
                            {{ getInitials(consultation.doctorName) }}
                        </AvatarFallback>
                    </Avatar>

                    <!-- Details -->
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="text-sm font-medium text-primary">
                                {{ consultation.date }}, {{ consultation.time }}
                            </span>
                        </div>
                        <h3 class="text-base font-bold text-gray-900 mb-0.5">
                            {{ consultation.doctorName }}
                        </h3>
                        <p class="text-sm text-gray-600">
                            {{ consultation.specialty }}
                        </p>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center gap-2">
                        <!-- Status Badge -->
                        <span
                            :class="[
                                'px-3 py-1 rounded-full text-xs font-medium',
                                consultation.status === 'completed'
                                    ? 'bg-green-100 text-green-700'
                                    : consultation.status === 'cancelled'
                                    ? 'bg-red-100 text-red-700'
                                    : 'bg-yellow-100 text-yellow-700'
                            ]"
                        >
                            {{ consultation.status === 'completed' ? 'Concluída' : consultation.status === 'cancelled' ? 'Cancelada' : 'Agendada' }}
                        </span>

                        <!-- View Details Button -->
                        <Button
                            :variant="consultation.status === 'completed' ? 'default' : 'outline'"
                            :class="[
                                consultation.status === 'completed'
                                    ? 'bg-primary hover:bg-primary/90 text-gray-900'
                                    : 'border border-gray-300 text-gray-900 hover:bg-gray-50'
                            ]"
                        >
                            Ver detalhes
                        </Button>

                        <!-- More Options -->
                        <button class="p-1.5 rounded-full hover:bg-gray-100 transition-colors">
                            <MoreVertical class="h-5 w-5 text-gray-600" />
                        </button>
                    </div>
                </div>
            </div>

            <!-- Pagination -->
            <div class="mt-6 flex justify-end">
                <PaginationRoot 
                    :page="currentPage" 
                    :default-page="1"
                    :items-per-page="itemsPerPage"
                    :total="allConsultations.length"
                    @update:page="currentPage = $event"
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

