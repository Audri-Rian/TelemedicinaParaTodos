<script setup lang="ts">
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import * as doctorRoutes from '@/routes/doctor';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { Clock3, FileClock } from 'lucide-vue-next';
import { computed, ref } from 'vue';

type PatientOption = {
    id: string;
    name: string;
};

type HistoryDocument = {
    id: string;
    name: string;
    category: string;
    categoryLabel: string;
    patient: {
        id: string | null;
        name: string;
    };
    uploadedAt?: string | null;
    visibility?: string | null;
    fileUrl?: string | null;
};

type Filters = {
    patient_id?: string | null;
    category?: string | null;
    period_days?: number | null;
};

interface Props {
    patients?: PatientOption[];
    documents?: HistoryDocument[];
    filters?: Filters;
}

const props = withDefaults(defineProps<Props>(), {
    patients: () => [],
    documents: () => [],
    filters: () => ({}),
});

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: doctorRoutes.dashboard().url,
    },
    {
        title: 'Documentos',
        href: doctorRoutes.documents().url,
    },
    {
        title: 'Histórico de documentos',
        href: '/doctor/documents/history',
    },
];

const patientFilter = ref(props.filters.patient_id ?? '');
const categoryFilter = ref(props.filters.category ?? '');
const periodFilter = ref(String(props.filters.period_days ?? 30));

const filteredDocuments = computed(() => {
    return props.documents.filter((doc) => {
        const byPatient = !patientFilter.value || String(doc.patient.id ?? '') === String(patientFilter.value);
        const byCategory = !categoryFilter.value || doc.category === categoryFilter.value;

        if (!byPatient || !byCategory) {
            return false;
        }

        const periodDays = Number(periodFilter.value);
        if (!Number.isFinite(periodDays) || periodDays <= 0) {
            return true;
        }

        if (!doc.uploadedAt) {
            return false;
        }

        const uploadedAt = new Date(doc.uploadedAt).getTime();
        if (Number.isNaN(uploadedAt)) {
            return false;
        }

        const cutoff = Date.now() - periodDays * 24 * 60 * 60 * 1000;
        return uploadedAt >= cutoff;
    });
});

function applyFilters() {
    router.get(
        '/doctor/documents/history',
        {
            patient_id: patientFilter.value || undefined,
            category: categoryFilter.value || undefined,
            period_days: Number(periodFilter.value) || undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            only: ['documents', 'filters'],
        },
    );
}

function formatDateTime(date?: string | null) {
    if (!date) {
        return 'Data indisponível';
    }

    const parsed = new Date(date);
    if (Number.isNaN(parsed.getTime())) {
        return 'Data indisponível';
    }

    return parsed.toLocaleString('pt-BR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}
</script>

<template>
    <Head title="Histórico de documentos — Telemedicina Para Todos" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-svh w-full bg-[#f5f5f0] px-3 py-5 text-zinc-900 antialiased sm:px-4 lg:px-6">
            <header class="mb-4 rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm sm:p-5">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 class="text-base font-bold tracking-tight sm:text-lg">Histórico de documentos</h1>
                        <p class="text-xs text-zinc-500 sm:text-sm">Consulte os documentos já emitidos pelo médico.</p>
                    </div>

                    <div class="flex rounded-xl border border-zinc-200 bg-zinc-50 p-1">
                        <a
                            href="/doctor/documents"
                            class="rounded-lg px-3 py-1.5 text-xs font-semibold text-zinc-600 transition hover:bg-white hover:text-zinc-900 sm:text-sm"
                        >
                            Emissão de documentos
                        </a>
                        <span class="rounded-lg bg-zinc-900 px-3 py-1.5 text-xs font-semibold text-white sm:text-sm">Histórico de documentos</span>
                    </div>
                </div>
            </header>

            <section class="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm sm:p-5">
                <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
                    <select
                        v-model="patientFilter"
                        class="w-full rounded-lg border border-zinc-200 bg-white px-2 py-2 text-xs ring-teal-500/30 outline-none focus:ring-2"
                    >
                        <option value="">Todos os pacientes</option>
                        <option v-for="p in props.patients" :key="p.id" :value="String(p.id)">
                            {{ p.name }}
                        </option>
                    </select>

                    <select
                        v-model="categoryFilter"
                        class="w-full rounded-lg border border-zinc-200 bg-white px-2 py-2 text-xs ring-teal-500/30 outline-none focus:ring-2"
                    >
                        <option value="">Todas as categorias</option>
                        <option value="prescription">Prescrição</option>
                        <option value="report">Relatório</option>
                        <option value="exam">Exame</option>
                        <option value="other">Outro</option>
                    </select>

                    <select
                        v-model="periodFilter"
                        class="w-full rounded-lg border border-zinc-200 bg-white px-2 py-2 text-xs ring-teal-500/30 outline-none focus:ring-2"
                    >
                        <option value="7">Últimos 7 dias</option>
                        <option value="30">Últimos 30 dias</option>
                        <option value="90">Últimos 90 dias</option>
                        <option value="180">Últimos 180 dias</option>
                    </select>

                    <Button type="button" variant="outline" @click="applyFilters">Aplicar filtros</Button>
                </div>

                <ul v-if="filteredDocuments.length" class="mt-4 space-y-2">
                    <li
                        v-for="doc in filteredDocuments"
                        :key="doc.id"
                        class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-zinc-200 bg-zinc-50/70 p-3"
                    >
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-zinc-900">{{ doc.name }}</p>
                            <p class="text-xs text-zinc-500">{{ doc.categoryLabel }} · {{ doc.patient.name }}</p>
                            <p class="mt-1 inline-flex items-center gap-1 text-[11px] text-zinc-500">
                                <Clock3 class="size-3" />
                                {{ formatDateTime(doc.uploadedAt) }}
                            </p>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <a
                                v-if="doc.fileUrl"
                                :href="doc.fileUrl"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="rounded-lg border border-zinc-200 bg-white px-2.5 py-1.5 text-xs font-medium text-zinc-700 hover:bg-zinc-100"
                            >
                                Visualizar
                            </a>
                            <a
                                v-if="doc.patient.id"
                                :href="`/doctor/patients/${doc.patient.id}/medical-record`"
                                class="rounded-lg border border-zinc-200 bg-white px-2.5 py-1.5 text-xs font-medium text-zinc-700 hover:bg-zinc-100"
                            >
                                Prontuário
                            </a>
                        </div>
                    </li>
                </ul>

                <div v-else class="mt-4 rounded-xl border border-dashed border-zinc-300 py-10 text-center text-sm text-zinc-500">
                    <div class="mx-auto mb-2 inline-flex items-center justify-center rounded-lg bg-zinc-100 p-2 text-zinc-500">
                        <FileClock class="size-4" />
                    </div>
                    <p>Nenhum documento encontrado para os filtros selecionados.</p>
                </div>
            </section>
        </div>
    </AppLayout>
</template>
