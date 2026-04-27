<script setup lang="ts">
import DocumentA4Preview from '@/components/Doctor/DocumentA4Preview.vue';
import PatientSearchDialog from '@/components/Doctor/PatientSearchDialog.vue';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import AppLayout from '@/layouts/AppLayout.vue';
import * as doctorRoutes from '@/routes/doctor';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { AlertCircle, Pill, Plus, Stethoscope, TestTube2, Trash2, UserRoundSearch, X } from 'lucide-vue-next';
import { computed, ref } from 'vue';

type DocumentKind = 'rx' | 'cert' | 'exams';

type Patient = {
    id: number;
    name: string;
    cpf?: string | null;
    age?: number | null;
    sex?: 'F' | 'M' | null;
};

type DrugCatalogItem = {
    id: number;
    name: string;
    strength: string;
    form: string;
    controlled?: boolean;
    ctrl?: string;
};

type RxLine = DrugCatalogItem & {
    dose: string;
    via: string;
    freq: string;
    dur: string;
    extra: string;
};

type ExamCatalogItem = { code: string; name: string };

type CertForm = {
    type: string;
    days: string;
    startDate: string;
    startTime: string;
    endTime: string;
    cid: string;
    body: string;
};

interface Props {
    patients?: Patient[];
}

const props = withDefaults(defineProps<Props>(), {
    patients: () => [],
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
];

const patientsCatalog = computed<Patient[]>(() => props.patients);

const drugCatalog: DrugCatalogItem[] = [
    { id: 1, name: 'Losartana potássica', strength: '50 mg', form: 'Comprimido', controlled: false },
    { id: 2, name: 'Atorvastatina cálcica', strength: '20 mg', form: 'Comprimido', controlled: false },
    { id: 3, name: 'Metformina', strength: '850 mg', form: 'Comprimido', controlled: false },
    { id: 4, name: 'Clonazepam', strength: '2 mg', form: 'Comprimido', controlled: true, ctrl: 'B1' },
    { id: 5, name: 'Omeprazol', strength: '20 mg', form: 'Cápsula', controlled: false },
    { id: 6, name: 'Amitriptilina', strength: '25 mg', form: 'Comprimido', controlled: true, ctrl: 'C1' },
];

const examCatalog: ExamCatalogItem[] = [
    { code: '40304361', name: 'Hemograma completo' },
    { code: '40302580', name: 'Glicemia de jejum' },
    { code: '40301630', name: 'Creatinina' },
    { code: '40304370', name: 'Perfil lipídico' },
    { code: '40308391', name: 'TSH' },
    { code: '40316521', name: 'TGO / TGP' },
];

const docType = ref<DocumentKind>('rx');
const patient = ref<Patient | null>(patientsCatalog.value[0] ?? null);
const showSearchModal = ref(false);
const drugSearchOpen = ref(false);
const examSearchOpen = ref(false);
const showSuccess = ref(false);
const showError = ref(false);
const signEnabled = ref(false);
const showDraftWarn = ref(false);
const pendingDocType = ref<DocumentKind | null>(null);
const drugQuery = ref('');
const examQuery = ref('');

const rxItems = ref<RxLine[]>([
    {
        ...drugCatalog[0],
        dose: '1 comprimido',
        via: 'Oral',
        freq: 'A cada 24 horas, pela manhã',
        dur: 'Uso contínuo · 90 dias',
        extra: '',
    },
    {
        ...drugCatalog[1],
        dose: '1 comprimido',
        via: 'Oral',
        freq: 'A cada 24 horas, à noite',
        dur: '90 dias',
        extra: 'Reavaliar perfil lipídico em 60 dias',
    },
    {
        ...drugCatalog[5],
        dose: '1 comprimido',
        via: 'Oral',
        freq: 'A cada 24 horas, ao deitar',
        dur: '30 dias',
        extra: 'Não interromper abruptamente',
    },
]);

const certData = ref<CertForm>({
    type: 'afastamento',
    days: '3',
    startDate: new Date().toISOString().slice(0, 10),
    startTime: '',
    endTime: '',
    cid: 'J11.1',
    body: 'Necessitando de repouso domiciliar e afastamento de suas atividades laborais habituais.',
});

const examItems = ref<ExamCatalogItem[]>([examCatalog[0], examCatalog[1], examCatalog[2], examCatalog[3], examCatalog[5]]);

const urgency = ref<'rotina' | 'prioritario' | 'urgente'>('rotina');
const indication = ref('Acompanhamento de hipertensão arterial sistêmica e dislipidemia. Avaliação metabólica.');
const fasting = ref('Jejum de 12 horas. Levar a primeira urina da manhã.');

const filteredDrugs = computed(() => {
    const q = drugQuery.value.trim().toLowerCase();
    if (!q) {
        return drugCatalog;
    }
    return drugCatalog.filter((d) => d.name.toLowerCase().includes(q));
});

const filteredExams = computed(() => {
    const q = examQuery.value.trim().toLowerCase();
    if (!q) {
        return examCatalog;
    }
    return examCatalog.filter((e) => e.name.toLowerCase().includes(q) || e.code.includes(q));
});

const hasDraftChanges = computed(() => {
    if (docType.value === 'rx') {
        return rxItems.value.length > 0;
    }
    if (docType.value === 'cert') {
        return certData.value.body.trim().length > 0 || certData.value.days.trim().length > 0 || certData.value.cid.trim().length > 0;
    }
    return examItems.value.length > 0;
});

function requestDocType(next: DocumentKind) {
    if (next === docType.value) {
        return;
    }
    if (hasDraftChanges.value) {
        pendingDocType.value = next;
        showDraftWarn.value = true;
        return;
    }
    docType.value = next;
}

function confirmDiscardDraft() {
    if (pendingDocType.value) {
        docType.value = pendingDocType.value;
        pendingDocType.value = null;
    }
    showDraftWarn.value = false;
}

function cancelTabSwitch() {
    pendingDocType.value = null;
    showDraftWarn.value = false;
}

function saveDraftAndSwitch() {
    showDraftWarn.value = false;
    if (pendingDocType.value) {
        docType.value = pendingDocType.value;
        pendingDocType.value = null;
    }
}

function addDrugFromCatalog(d: DrugCatalogItem) {
    rxItems.value.push({
        ...d,
        dose: '1 comprimido',
        via: 'Oral',
        freq: 'Conforme orientação médica',
        dur: '30 dias',
        extra: '',
    });
    drugQuery.value = '';
}

function removeRx(i: number) {
    rxItems.value = rxItems.value.filter((_, idx) => idx !== i);
}

function addExam(e: ExamCatalogItem) {
    if (examItems.value.some((x) => x.code === e.code)) {
        return;
    }
    examItems.value.push(e);
    examQuery.value = '';
}

function removeExam(i: number) {
    examItems.value = examItems.value.filter((_, idx) => idx !== i);
}

function selectPatient(p: Patient) {
    patient.value = p;
    showSearchModal.value = false;
}

const canSubmit = computed(() => !!patient.value);
</script>

<template>
    <Head title="Emissão de documentos — Telemedicina Para Todos" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-svh w-full bg-[#f5f5f0] text-zinc-900 antialiased">
            <header class="sticky top-0 z-20 border-b border-zinc-200/80 bg-[#f5f5f0]/95 backdrop-blur">
                <div class="flex w-full flex-wrap items-center justify-between gap-3 px-3 py-3 sm:px-4 lg:px-6">
                    <div class="flex min-w-0 flex-1 items-center gap-3">
                        <div class="min-w-0">
                            <h1 class="truncate text-base font-bold tracking-tight sm:text-lg">Emissão de documentos</h1>
                            <p class="hidden text-xs text-zinc-500 sm:block">
                                Receituário, atestado e pedido de exames com pré-visualização em tempo real.
                            </p>
                        </div>
                    </div>

                    <div class="flex w-full flex-wrap items-center gap-2 sm:w-auto sm:justify-end">
                        <div
                            v-if="patient"
                            class="flex max-w-full min-w-0 items-center gap-2 rounded-xl border border-zinc-200 bg-white px-3 py-1.5 text-sm"
                        >
                            <span class="truncate font-medium text-zinc-800">{{ patient.name }}</span>
                            <button
                                type="button"
                                class="shrink-0 text-xs font-semibold text-teal-700 underline-offset-2 hover:underline"
                                @click="showSearchModal = true"
                            >
                                Trocar
                            </button>
                        </div>
                        <Button v-else type="button" variant="outline" size="sm" class="gap-1.5" @click="showSearchModal = true">
                            <UserRoundSearch class="size-4" />
                            Selecionar paciente
                        </Button>
                        <a
                            href="/doctor/documents/history"
                            class="inline-flex items-center rounded-xl border border-zinc-200 bg-white px-3 py-1.5 text-xs font-semibold text-zinc-700 hover:bg-zinc-50 sm:text-sm"
                        >
                            Histórico de documentos
                        </a>

                        <div class="flex rounded-xl border border-zinc-200 bg-white p-0.5">
                            <button
                                type="button"
                                class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-semibold sm:text-sm"
                                :class="docType === 'rx' ? 'bg-zinc-900 text-white' : 'text-zinc-600 hover:bg-zinc-50'"
                                @click="requestDocType('rx')"
                            >
                                <Pill class="size-3.5 sm:size-4" />
                                Receita
                            </button>
                            <button
                                type="button"
                                class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-semibold sm:text-sm"
                                :class="docType === 'cert' ? 'bg-zinc-900 text-white' : 'text-zinc-600 hover:bg-zinc-50'"
                                @click="requestDocType('cert')"
                            >
                                <Stethoscope class="size-3.5 sm:size-4" />
                                Atestado
                            </button>
                            <button
                                type="button"
                                class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-semibold sm:text-sm"
                                :class="docType === 'exams' ? 'bg-zinc-900 text-white' : 'text-zinc-600 hover:bg-zinc-50'"
                                @click="requestDocType('exams')"
                            >
                                <TestTube2 class="size-3.5 sm:size-4" />
                                Exames
                            </button>
                        </div>
                    </div>
                </div>
            </header>

            <div class="w-full px-3 py-5 sm:px-4 lg:px-6">
                <div v-if="showError" class="mb-4 flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900">
                    <AlertCircle class="mt-0.5 size-4 shrink-0" />
                    <div class="min-w-0 flex-1">
                        <p class="font-semibold">Não foi possível salvar o documento</p>
                        <p class="text-red-800/90">Verifique sua conexão e tente novamente.</p>
                    </div>
                    <button type="button" class="shrink-0 rounded-lg p-1 text-red-700 hover:bg-red-100" @click="showError = false">
                        <X class="size-4" />
                    </button>
                </div>

                <div class="grid items-start gap-7 lg:grid-cols-[minmax(0,1fr)_minmax(0,580px)]">
                    <section class="flex min-w-0 flex-col gap-4">
                        <template>
                            <!-- Receita -->
                            <div v-if="docType === 'rx'" class="space-y-4 rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm sm:p-5">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <h2 class="text-sm font-bold text-zinc-900">Medicamentos</h2>
                                    <Button type="button" variant="outline" size="sm" class="gap-1" @click="drugSearchOpen = !drugSearchOpen">
                                        <Plus class="size-4" />
                                        {{ drugSearchOpen ? 'Fechar catálogo' : 'Adicionar do catálogo' }}
                                    </Button>
                                </div>

                                <div v-if="drugSearchOpen" class="rounded-xl border border-zinc-200 bg-zinc-50/80 p-3">
                                    <input
                                        v-model="drugQuery"
                                        type="search"
                                        placeholder="Buscar por nome…"
                                        class="w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm ring-teal-500/30 outline-none placeholder:text-zinc-400 focus:ring-2"
                                    />
                                    <ul class="mt-2 max-h-48 space-y-1 overflow-y-auto text-sm">
                                        <li
                                            v-for="d in filteredDrugs"
                                            :key="d.id"
                                            class="flex items-center justify-between gap-2 rounded-lg border border-transparent px-2 py-1.5 hover:border-zinc-200 hover:bg-white"
                                        >
                                            <span class="min-w-0 font-medium text-zinc-800">{{ d.name }} · {{ d.strength }}</span>
                                            <Button type="button" size="sm" variant="secondary" class="shrink-0" @click="addDrugFromCatalog(d)">
                                                Adicionar
                                            </Button>
                                        </li>
                                    </ul>
                                </div>

                                <div class="space-y-3">
                                    <div
                                        v-for="(it, i) in rxItems"
                                        :key="`${it.id}-${i}`"
                                        class="rounded-xl border border-zinc-200 bg-zinc-50/50 p-3"
                                    >
                                        <div class="mb-2 flex flex-wrap items-start justify-between gap-2">
                                            <div>
                                                <p class="text-sm font-bold text-zinc-900">{{ it.name }} · {{ it.strength }} · {{ it.form }}</p>
                                                <span
                                                    v-if="it.controlled"
                                                    class="mt-1 inline-block rounded bg-[#fbeeda] px-1.5 py-px text-[10px] font-extrabold text-amber-900"
                                                >
                                                    LISTA {{ it.ctrl }}
                                                </span>
                                            </div>
                                            <button
                                                type="button"
                                                class="rounded-lg p-1.5 text-zinc-500 hover:bg-red-50 hover:text-red-600"
                                                @click="removeRx(i)"
                                            >
                                                <Trash2 class="size-4" />
                                            </button>
                                        </div>
                                        <div class="grid gap-2 sm:grid-cols-2">
                                            <label class="block text-xs font-medium text-zinc-600">
                                                Dose
                                                <input
                                                    v-model="it.dose"
                                                    type="text"
                                                    class="mt-1 w-full rounded-lg border border-zinc-200 bg-white px-2 py-1.5 text-sm ring-teal-500/30 outline-none focus:ring-2"
                                                />
                                            </label>
                                            <label class="block text-xs font-medium text-zinc-600">
                                                Via
                                                <input
                                                    v-model="it.via"
                                                    type="text"
                                                    class="mt-1 w-full rounded-lg border border-zinc-200 bg-white px-2 py-1.5 text-sm ring-teal-500/30 outline-none focus:ring-2"
                                                />
                                            </label>
                                            <label class="block text-xs font-medium text-zinc-600 sm:col-span-2">
                                                Frequência
                                                <input
                                                    v-model="it.freq"
                                                    type="text"
                                                    class="mt-1 w-full rounded-lg border border-zinc-200 bg-white px-2 py-1.5 text-sm ring-teal-500/30 outline-none focus:ring-2"
                                                />
                                            </label>
                                            <label class="block text-xs font-medium text-zinc-600 sm:col-span-2">
                                                Duração
                                                <input
                                                    v-model="it.dur"
                                                    type="text"
                                                    class="mt-1 w-full rounded-lg border border-zinc-200 bg-white px-2 py-1.5 text-sm ring-teal-500/30 outline-none focus:ring-2"
                                                />
                                            </label>
                                            <label class="block text-xs font-medium text-zinc-600 sm:col-span-2">
                                                Observações
                                                <input
                                                    v-model="it.extra"
                                                    type="text"
                                                    class="mt-1 w-full rounded-lg border border-zinc-200 bg-white px-2 py-1.5 text-sm ring-teal-500/30 outline-none focus:ring-2"
                                                />
                                            </label>
                                        </div>
                                    </div>
                                    <p
                                        v-if="!rxItems.length"
                                        class="rounded-xl border border-dashed border-zinc-300 py-8 text-center text-sm text-zinc-500"
                                    >
                                        Nenhum medicamento. Use o catálogo para adicionar.
                                    </p>
                                </div>
                            </div>

                            <!-- Atestado -->
                            <div v-else-if="docType === 'cert'" class="space-y-4 rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm sm:p-5">
                                <h2 class="text-sm font-bold text-zinc-900">Conteúdo do atestado</h2>
                                <div class="grid gap-3 sm:grid-cols-2">
                                    <label class="block text-xs font-medium text-zinc-600">
                                        Tipo
                                        <select
                                            v-model="certData.type"
                                            class="mt-1 w-full rounded-lg border border-zinc-200 bg-white px-2 py-2 text-sm ring-teal-500/30 outline-none focus:ring-2"
                                        >
                                            <option value="afastamento">Afastamento</option>
                                            <option value="comparecimento">Comparecimento</option>
                                            <option value="aptidao">Aptidão</option>
                                        </select>
                                    </label>
                                    <label class="block text-xs font-medium text-zinc-600">
                                        Dias
                                        <input
                                            v-model="certData.days"
                                            type="text"
                                            inputmode="numeric"
                                            class="mt-1 w-full rounded-lg border border-zinc-200 bg-white px-2 py-2 text-sm ring-teal-500/30 outline-none focus:ring-2"
                                        />
                                    </label>
                                    <label class="block text-xs font-medium text-zinc-600">
                                        Início (data)
                                        <input
                                            v-model="certData.startDate"
                                            type="date"
                                            class="mt-1 w-full rounded-lg border border-zinc-200 bg-white px-2 py-2 text-sm ring-teal-500/30 outline-none focus:ring-2"
                                        />
                                    </label>
                                    <label class="block text-xs font-medium text-zinc-600">
                                        CID-10
                                        <input
                                            v-model="certData.cid"
                                            type="text"
                                            class="mt-1 w-full rounded-lg border border-zinc-200 bg-white px-2 py-2 font-mono text-sm ring-teal-500/30 outline-none focus:ring-2"
                                        />
                                    </label>
                                </div>
                                <label class="block text-xs font-medium text-zinc-600">
                                    Texto livre
                                    <textarea
                                        v-model="certData.body"
                                        rows="5"
                                        class="mt-1 w-full resize-y rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm ring-teal-500/30 outline-none placeholder:text-zinc-400 focus:ring-2"
                                    />
                                </label>
                            </div>

                            <!-- Exames -->
                            <div v-else class="space-y-4 rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm sm:p-5">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <h2 class="text-sm font-bold text-zinc-900">Exames solicitados</h2>
                                    <Button type="button" variant="outline" size="sm" class="gap-1" @click="examSearchOpen = !examSearchOpen">
                                        <Plus class="size-4" />
                                        {{ examSearchOpen ? 'Fechar catálogo' : 'Adicionar do catálogo' }}
                                    </Button>
                                </div>

                                <div v-if="examSearchOpen" class="rounded-xl border border-zinc-200 bg-zinc-50/80 p-3">
                                    <input
                                        v-model="examQuery"
                                        type="search"
                                        placeholder="Código ou nome…"
                                        class="w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm ring-teal-500/30 outline-none placeholder:text-zinc-400 focus:ring-2"
                                    />
                                    <ul class="mt-2 max-h-48 space-y-1 overflow-y-auto text-sm">
                                        <li
                                            v-for="e in filteredExams"
                                            :key="e.code"
                                            class="flex items-center justify-between gap-2 rounded-lg border border-transparent px-2 py-1.5 hover:border-zinc-200 hover:bg-white"
                                        >
                                            <span class="font-mono text-xs text-zinc-500">{{ e.code }}</span>
                                            <span class="min-w-0 flex-1 font-medium text-zinc-800">{{ e.name }}</span>
                                            <Button type="button" size="sm" variant="secondary" class="shrink-0" @click="addExam(e)">
                                                Adicionar
                                            </Button>
                                        </li>
                                    </ul>
                                </div>

                                <ul class="space-y-2">
                                    <li
                                        v-for="(e, i) in examItems"
                                        :key="e.code"
                                        class="flex items-center justify-between gap-2 rounded-xl border border-zinc-200 bg-zinc-50/50 px-3 py-2 text-sm"
                                    >
                                        <span
                                            ><span class="font-mono text-xs text-zinc-500">{{ e.code }}</span> {{ e.name }}</span
                                        >
                                        <button
                                            type="button"
                                            class="rounded-lg p-1.5 text-zinc-500 hover:bg-red-50 hover:text-red-600"
                                            @click="removeExam(i)"
                                        >
                                            <Trash2 class="size-4" />
                                        </button>
                                    </li>
                                </ul>
                                <p
                                    v-if="!examItems.length"
                                    class="rounded-xl border border-dashed border-zinc-300 py-6 text-center text-sm text-zinc-500"
                                >
                                    Nenhum exame na lista.
                                </p>

                                <fieldset class="space-y-2">
                                    <legend class="text-xs font-semibold text-zinc-600">Urgência</legend>
                                    <div class="flex flex-wrap gap-2">
                                        <label
                                            v-for="u in ['rotina', 'prioritario', 'urgente'] as const"
                                            :key="u"
                                            class="inline-flex cursor-pointer items-center gap-2 rounded-full border px-3 py-1.5 text-xs font-medium transition"
                                            :class="
                                                urgency === u
                                                    ? 'border-zinc-900 bg-zinc-900 text-white'
                                                    : 'border-zinc-200 bg-white text-zinc-600 hover:bg-zinc-50'
                                            "
                                        >
                                            <input v-model="urgency" type="radio" class="sr-only" :value="u" />
                                            {{ u === 'rotina' ? 'Rotina' : u === 'prioritario' ? 'Prioritário' : 'Urgente' }}
                                        </label>
                                    </div>
                                </fieldset>

                                <label class="block text-xs font-medium text-zinc-600">
                                    Indicação clínica
                                    <textarea
                                        v-model="indication"
                                        rows="3"
                                        class="mt-1 w-full resize-y rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm ring-teal-500/30 outline-none focus:ring-2"
                                    />
                                </label>
                                <label class="block text-xs font-medium text-zinc-600">
                                    Preparo / jejum
                                    <textarea
                                        v-model="fasting"
                                        rows="2"
                                        class="mt-1 w-full resize-y rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm ring-teal-500/30 outline-none focus:ring-2"
                                    />
                                </label>
                            </div>
                        </template>

                        <div
                            class="sticky bottom-4 z-10 flex flex-col gap-3 rounded-2xl border border-zinc-200 bg-white/95 p-4 shadow-lg backdrop-blur sm:flex-row sm:items-center sm:justify-between"
                        >
                            <label class="flex cursor-pointer items-center gap-2 text-sm text-zinc-700">
                                <input v-model="signEnabled" type="checkbox" class="size-4 rounded border-zinc-300 text-teal-600" />
                                Assinar digitalmente (ICP-Brasil)
                            </label>
                            <div class="flex flex-wrap gap-2">
                                <Button type="button" variant="outline" :disabled="!signEnabled || !canSubmit"> Assinar </Button>
                                <Button type="button" :disabled="!canSubmit" @click="showSuccess = true"> Gerar e enviar </Button>
                            </div>
                        </div>
                    </section>

                    <aside class="min-w-0 lg:sticky lg:top-20 lg:self-start">
                        <DocumentA4Preview
                            :doc-type="docType"
                            :patient="patient"
                            :rx-items="rxItems"
                            :cert-data="certData"
                            :exam-items="examItems"
                            :urgency="urgency"
                            :indication="indication"
                            :fasting="fasting"
                        />
                    </aside>
                </div>
            </div>
        </div>

        <PatientSearchDialog
            v-model:open="showSearchModal"
            :patients="patientsCatalog"
            :selected-patient-id="patient?.id ?? null"
            @select="selectPatient"
        />

        <Dialog v-model:open="showDraftWarn">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Rascunho não salvo</DialogTitle>
                    <DialogDescription> Você tem alterações neste documento. Deseja continuar trocando o tipo? </DialogDescription>
                </DialogHeader>
                <DialogFooter class="gap-2 sm:justify-end">
                    <Button type="button" variant="outline" @click="cancelTabSwitch">Cancelar</Button>
                    <Button type="button" variant="secondary" @click="saveDraftAndSwitch">Salvar rascunho e continuar</Button>
                    <Button type="button" variant="destructive" @click="confirmDiscardDraft">Descartar alterações</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <Dialog v-model:open="showSuccess">
            <DialogContent class="text-center sm:max-w-sm">
                <DialogHeader>
                    <DialogTitle>Documento gerado</DialogTitle>
                    <DialogDescription v-if="patient"> {{ patient.name }} receberá o arquivo na área do paciente. </DialogDescription>
                </DialogHeader>
                <DialogFooter class="sm:justify-center">
                    <Button type="button" @click="showSuccess = false">Fechar</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
