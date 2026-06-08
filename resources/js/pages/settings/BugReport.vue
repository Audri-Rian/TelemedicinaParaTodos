<script setup lang="ts">
import BugReportModal from '@/components/modals/BugReportModal.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, usePage } from '@inertiajs/vue3';
import { AlertCircle, AlertTriangle, ArrowRight, Bug, CheckCircle, MessageCircle, Search, X } from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface BugReport {
    id: string;
    title: string;
    description: string;
    status: 'open' | 'in_analysis' | 'resolved' | 'closed';
    severity: 'low' | 'medium' | 'high' | 'critical';
    reporter: string;
    date: string;
    steps_to_reproduce?: string;
    expected_behavior?: string;
    actual_behavior?: string;
}

const page = usePage();
const auth = computed(() => page.props.auth as { isDoctor?: boolean; role?: string | null } | undefined);
const isDoctor = computed(() => auth.value?.isDoctor === true || auth.value?.role === 'doctor');

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Painel de Notificação de Bugs',
        href: '/settings/bug-report',
    },
];

// Estado do modal
const isModalOpen = ref(false);

// Estado de busca e filtros
const searchQuery = ref('');
const statusFilter = ref<string>('');
const severityFilter = ref<string>('');
const dateFilter = ref<string>('');
const authorFilter = ref<string>('');

// Dados mockados - substituir por dados reais do backend
const bugs = ref<BugReport[]>([
    {
        id: '1',
        title: 'Erro ao carregar prontuário do paciente X',
        description: 'Ao tentar acessar o prontuário do paciente, a página não carrega completamente.',
        status: 'in_analysis',
        severity: 'high',
        reporter: 'Dr. Ana Silva',
        date: '25/10/2023',
    },
    {
        id: '2',
        title: "Botão 'Salvar' não responde na tela de prescrição",
        description: 'O botão de salvar prescrição não responde quando clicado.',
        status: 'open',
        severity: 'critical',
        reporter: 'Enf. Carlos Souza',
        date: '24/10/2023',
    },
    {
        id: '3',
        title: 'Lentidão ao gerar relatório de atendimentos',
        description: 'O sistema está muito lento ao gerar relatórios de atendimentos.',
        status: 'resolved',
        severity: 'medium',
        reporter: 'Admin',
        date: '22/10/2023',
    },
    {
        id: '4',
        title: 'Tradução incorreta no menu de configurações',
        description: 'Alguns textos no menu de configurações estão com tradução incorreta.',
        status: 'closed',
        severity: 'low',
        reporter: 'Dr. Maria Oliveira',
        date: '20/10/2023',
    },
]);

// Configurações de status
const statusConfig = {
    open: { label: 'Aberto', class: 'bg-blue-100 text-blue-700' },
    in_analysis: { label: 'Em análise', class: 'bg-orange-100 text-orange-700' },
    resolved: { label: 'Resolvido', class: 'bg-green-100 text-green-700' },
    closed: { label: 'Fechado', class: 'bg-gray-100 text-gray-700' },
};

// Configurações de gravidade
const severityConfig = {
    low: { label: 'Baixo', class: 'bg-green-100 text-green-700', icon: CheckCircle },
    medium: { label: 'Médio', class: 'bg-yellow-100 text-yellow-700', icon: AlertTriangle },
    high: { label: 'Alto', class: 'bg-orange-100 text-orange-700', icon: AlertCircle },
    critical: { label: 'Crítico', class: 'bg-red-100 text-red-700', icon: X },
};

// Filtros
const statusOptions = [
    { value: '', label: 'Todos' },
    { value: 'open', label: 'Aberto' },
    { value: 'in_analysis', label: 'Em análise' },
    { value: 'resolved', label: 'Resolvido' },
    { value: 'closed', label: 'Fechado' },
];

const severityOptions = [
    { value: '', label: 'Todas' },
    { value: 'low', label: 'Baixo' },
    { value: 'medium', label: 'Médio' },
    { value: 'high', label: 'Alto' },
    { value: 'critical', label: 'Crítico' },
];

const dateOptions = [
    { value: '', label: 'Todas' },
    { value: 'today', label: 'Hoje' },
    { value: 'week', label: 'Esta semana' },
    { value: 'month', label: 'Este mês' },
];

// Filtrar bugs
const filteredBugs = computed(() => {
    let result = bugs.value;

    // Filtro de busca
    if (searchQuery.value.trim()) {
        const query = searchQuery.value.toLowerCase();
        result = result.filter((bug) => bug.title.toLowerCase().includes(query) || bug.description.toLowerCase().includes(query));
    }

    // Filtro de status
    if (statusFilter.value) {
        result = result.filter((bug) => bug.status === statusFilter.value);
    }

    // Filtro de gravidade
    if (severityFilter.value) {
        result = result.filter((bug) => bug.severity === severityFilter.value);
    }

    // Filtro de autor
    if (authorFilter.value.trim()) {
        const author = authorFilter.value.toLowerCase();
        result = result.filter((bug) => bug.reporter.toLowerCase().includes(author));
    }

    return result;
});

const handleBugSaved = () => {
    // Recarregar lista de bugs
    // TODO: Implementar recarregamento real do backend
    // router.reload({ only: ['bugs'] });
};

const openModal = () => {
    isModalOpen.value = true;
};

const closeModal = () => {
    isModalOpen.value = false;
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Painel de Notificação de Bugs" />

        <SettingsLayout :full-width="true">
            <div v-if="isDoctor" class="w-full space-y-5">
                <section id="bug" class="rounded-[14px] border border-slate-200 bg-white px-5 py-6 shadow-xs sm:px-7">
                    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <div class="flex items-start gap-3">
                            <span class="grid size-10 shrink-0 place-items-center rounded-[10px] bg-teal-50 text-teal-800">
                                <Bug class="size-5" />
                            </span>
                            <div>
                                <p class="text-[11px] font-semibold tracking-[0.08em] text-slate-500 uppercase">Suporte</p>
                                <h2 class="mt-1 text-lg font-semibold tracking-normal text-slate-950">Notificar bug</h2>
                                <p class="mt-1 max-w-2xl text-[13.5px] text-slate-500">
                                    Quanto mais detalhes, mais rápido a gente resolve. Respondemos por e-mail em até 1 dia útil.
                                </p>
                            </div>
                        </div>

                        <Button
                            @click="openModal"
                            class="h-9 shrink-0 rounded-[9px] bg-teal-700 px-4 text-[13.5px] font-medium text-white hover:bg-teal-800"
                        >
                            <Bug class="mr-1 size-4" />
                            Reportar novo bug
                        </Button>
                    </div>

                    <div class="grid gap-3 lg:grid-cols-[minmax(220px,1fr)_auto]">
                        <div class="relative">
                            <Search class="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-slate-400" />
                            <Input
                                v-model="searchQuery"
                                placeholder="Buscar por título do bug..."
                                class="h-10 rounded-[9px] border-slate-300 pl-9 text-sm focus-visible:border-teal-700 focus-visible:ring-teal-700/20"
                            />
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <select
                                v-model="statusFilter"
                                class="h-10 rounded-[9px] border border-slate-300 bg-white px-3 text-sm text-slate-700 outline-none focus:border-teal-700 focus:ring-3 focus:ring-teal-700/20"
                            >
                                <option v-for="option in statusOptions" :key="option.value" :value="option.value">
                                    {{ option.label }}
                                </option>
                            </select>
                            <select
                                v-model="severityFilter"
                                class="h-10 rounded-[9px] border border-slate-300 bg-white px-3 text-sm text-slate-700 outline-none focus:border-teal-700 focus:ring-3 focus:ring-teal-700/20"
                            >
                                <option v-for="option in severityOptions" :key="option.value" :value="option.value">
                                    {{ option.label }}
                                </option>
                            </select>
                            <select
                                v-model="dateFilter"
                                class="h-10 rounded-[9px] border border-slate-300 bg-white px-3 text-sm text-slate-700 outline-none focus:border-teal-700 focus:ring-3 focus:ring-teal-700/20"
                            >
                                <option v-for="option in dateOptions" :key="option.value" :value="option.value">
                                    {{ option.label }}
                                </option>
                            </select>
                            <Input
                                v-model="authorFilter"
                                placeholder="Autor"
                                class="h-10 w-32 rounded-[9px] border-slate-300 text-sm focus-visible:border-teal-700 focus-visible:ring-teal-700/20"
                            />
                        </div>
                    </div>
                </section>

                <section id="bug-history" class="rounded-[14px] border border-slate-200 bg-white px-5 py-6 shadow-xs sm:px-7">
                    <div class="mb-5">
                        <p class="text-[11px] font-semibold tracking-[0.08em] text-slate-500 uppercase">Seus relatos</p>
                        <h2 class="mt-1 text-lg font-semibold tracking-normal text-slate-950">Bugs reportados recentemente</h2>
                        <p class="mt-1 text-[13.5px] text-slate-500">Acompanhe o status dos seus relatos e filtre pelo que precisa revisar.</p>
                    </div>

                    <div v-if="filteredBugs.length > 0" class="grid grid-cols-1 gap-3 lg:grid-cols-2">
                        <article
                            v-for="bug in filteredBugs"
                            :key="bug.id"
                            class="rounded-[12px] border border-slate-200 bg-white p-4 transition-shadow hover:shadow-sm"
                        >
                            <div class="mb-3 flex items-start justify-between gap-3">
                                <h3 class="line-clamp-2 text-sm font-semibold text-slate-950">{{ bug.title }}</h3>
                                <span class="shrink-0 font-mono text-[11.5px] text-slate-400">#{{ bug.id }}</span>
                            </div>
                            <p class="mb-4 line-clamp-3 text-sm text-slate-600">{{ bug.description }}</p>
                            <div class="mb-4 flex flex-wrap gap-2">
                                <span
                                    :class="['inline-flex h-6 items-center rounded-full px-2.5 text-xs font-medium', statusConfig[bug.status].class]"
                                >
                                    {{ statusConfig[bug.status].label }}
                                </span>
                                <span
                                    :class="[
                                        'inline-flex h-6 items-center gap-1 rounded-full px-2.5 text-xs font-medium',
                                        severityConfig[bug.severity].class,
                                    ]"
                                >
                                    <component :is="severityConfig[bug.severity].icon" class="size-3 shrink-0" />
                                    {{ severityConfig[bug.severity].label }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between gap-3 text-[12.5px] text-slate-500">
                                <span class="truncate">{{ bug.reporter }}</span>
                                <span>{{ bug.date }}</span>
                            </div>
                        </article>
                    </div>

                    <div v-else class="rounded-[12px] border border-dashed border-slate-300 py-12 text-center">
                        <Bug class="mx-auto mb-4 size-12 text-slate-400" />
                        <p class="text-sm text-slate-600">Nenhum bug encontrado com os filtros aplicados.</p>
                    </div>
                </section>

                <div class="rounded-[14px] border border-slate-200 bg-slate-50/70 px-5 py-5 shadow-xs sm:px-6">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start">
                        <span class="grid size-9 shrink-0 place-items-center rounded-[10px] bg-white text-teal-700 shadow-xs">
                            <MessageCircle class="size-[18px]" />
                        </span>
                        <div class="min-w-0 flex-1">
                            <h2 class="text-sm font-semibold text-slate-950">Não é um bug?</h2>
                            <p class="mt-1 text-[12.5px] text-slate-500">Para dúvidas, falamos com você por chat de seg a sáb, das 8h às 22h.</p>
                        </div>
                        <Button type="button" variant="outline" class="h-9 rounded-[9px] border-slate-300 px-3 text-[13px]">
                            Abrir chat
                            <ArrowRight class="size-3.5" />
                        </Button>
                    </div>
                </div>
            </div>

            <div v-else class="w-full space-y-6">
                <!-- Card principal -->
                <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                    <!-- Header -->
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary/10">
                                <Bug class="h-5 w-5 text-primary" />
                            </div>
                            <div>
                                <h2 class="text-lg font-semibold text-gray-800">Notificar Bug</h2>
                                <p class="mt-0.5 text-sm text-gray-500">Acompanhe e reporte problemas encontrados no sistema.</p>
                            </div>
                        </div>
                        <Button @click="openModal" class="shrink-0 rounded-xl bg-primary text-white hover:bg-primary/90">
                            <Bug class="mr-2 h-4 w-4" />
                            Reportar Novo Bug
                        </Button>
                    </div>

                    <!-- Busca e Filtros -->
                    <div class="mt-6 flex flex-col gap-4 sm:flex-row">
                        <div class="relative flex-1">
                            <Search class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-gray-400" />
                            <Input
                                v-model="searchQuery"
                                placeholder="Buscar por título do bug..."
                                class="rounded-xl border-primary/20 pl-9 focus-visible:ring-primary/30"
                            />
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <select
                                v-model="statusFilter"
                                class="flex h-10 rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none"
                            >
                                <option v-for="option in statusOptions" :key="option.value" :value="option.value">
                                    {{ option.label }}
                                </option>
                            </select>
                            <select
                                v-model="severityFilter"
                                class="flex h-10 rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none"
                            >
                                <option v-for="option in severityOptions" :key="option.value" :value="option.value">
                                    {{ option.label }}
                                </option>
                            </select>
                            <select
                                v-model="dateFilter"
                                class="flex h-10 rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none"
                            >
                                <option v-for="option in dateOptions" :key="option.value" :value="option.value">
                                    {{ option.label }}
                                </option>
                            </select>
                            <Input v-model="authorFilter" placeholder="Autor" class="w-32" />
                        </div>
                    </div>

                    <!-- Lista de Bugs -->
                    <div v-if="filteredBugs.length > 0" class="mt-6 grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                        <Card
                            v-for="bug in filteredBugs"
                            :key="bug.id"
                            class="rounded-2xl border-gray-100 shadow-sm transition-shadow hover:shadow-md"
                        >
                            <CardHeader>
                                <div class="mb-2 flex items-start justify-between gap-2">
                                    <CardTitle class="line-clamp-2 text-base font-semibold">{{ bug.title }}</CardTitle>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <span
                                        :class="[
                                            'inline-flex items-center gap-1 rounded-full px-3 py-1 text-xs font-medium',
                                            statusConfig[bug.status].class,
                                        ]"
                                    >
                                        {{ statusConfig[bug.status].label }}
                                    </span>
                                    <span
                                        :class="[
                                            'inline-flex items-center gap-1 rounded-full px-3 py-1 text-xs font-medium',
                                            severityConfig[bug.severity].class,
                                        ]"
                                    >
                                        <component :is="severityConfig[bug.severity].icon" class="h-3 w-3 shrink-0" />
                                        {{ severityConfig[bug.severity].label }}
                                    </span>
                                </div>
                            </CardHeader>
                            <CardContent>
                                <p class="mb-4 line-clamp-3 text-sm text-gray-600">{{ bug.description }}</p>
                                <div class="flex items-center justify-between text-xs text-gray-500">
                                    <span>{{ bug.reporter }}</span>
                                    <span>{{ bug.date }}</span>
                                </div>
                                <Button variant="link" class="mt-3 h-auto p-0 text-primary hover:text-primary/80"> Ver detalhes </Button>
                            </CardContent>
                        </Card>
                    </div>

                    <!-- Estado vazio -->
                    <div v-else class="py-12 text-center">
                        <Bug class="mx-auto mb-4 h-12 w-12 text-gray-400" />
                        <p class="text-gray-600">Nenhum bug encontrado com os filtros aplicados.</p>
                    </div>
                </div>
            </div>
        </SettingsLayout>

        <!-- Modal de Reportar Bug -->
        <BugReportModal :is-open="isModalOpen" @close="closeModal" @saved="handleBugSaved" />
    </AppLayout>
</template>
