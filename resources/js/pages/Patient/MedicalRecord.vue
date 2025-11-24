<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import * as patientRoutes from '@/routes/patient';
import * as doctorRoutes from '@/routes/doctor';
import patientMedicalRecordRoutes from '@/routes/patient/medical-records';
import doctorPatientMedicalRecordRoutes from '@/routes/doctor/patients/medical-record';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, reactive, ref, watch, onMounted } from 'vue';
import { useRouteGuard } from '@/composables/auth';
import { useInitials } from '@/composables/useInitials';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Avatar, AvatarImage, AvatarFallback } from '@/components/ui/avatar';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import {
    Download,
    Upload,
    Search,
    Filter,
    AlertCircle,
    FileText,
    Calendar,
    Stethoscope,
    Pill,
    TestTube,
    FileCheck,
    TrendingUp,
    Clock,
    FilePlus2,
} from 'lucide-vue-next';

interface DoctorSummary {
    id: string;
    user: {
        name: string;
        avatar?: string | null;
    };
    specializations?: Array<{ id: string; name: string }>;
}

interface Appointment {
    id: string;
    scheduled_at?: string;
    started_at?: string | null;
    ended_at?: string | null;
    status: string;
    notes?: string | null;
    doctor: DoctorSummary;
    diagnosis?: string | null;
    cid10?: string | null;
    symptoms?: string | null;
    requested_exams?: string | null;
    instructions?: string | null;
    attachments?: Array<{ name: string; url: string }>;
    prescriptions?: Array<Prescription>;
    examinations?: Array<Examination>;
    documents?: Array<MedicalDocument>;
    vital_signs?: Array<VitalSignEntry>;
}

interface Prescription {
    id: string;
    doctor?: { id: string; name: string };
    medications: Array<Record<string, string>>;
    instructions?: string | null;
    valid_until?: string | null;
    status: string;
    issued_at?: string | null;
}

interface Examination {
    id: string;
    name: string;
    type: string;
    doctor?: { id: string; name: string };
    status: string;
    requested_at?: string | null;
    completed_at?: string | null;
    results?: Record<string, unknown> | null;
    attachment_url?: string | null;
}

interface MedicalDocument {
    id: string;
    name: string;
    category: string;
    file_path: string;
    file_type?: string | null;
    file_size?: number | null;
    description?: string | null;
    visibility?: string;
    uploaded_at?: string | null;
    doctor?: { id: string; name: string } | null;
    uploaded_by?: { id: string; name: string } | null;
}

interface VitalSignEntry {
    id: string;
    recorded_at?: string | null;
    doctor?: { id: string; name: string } | null;
    blood_pressure?: { systolic?: number | null; diastolic?: number | null };
    temperature?: number | null;
    heart_rate?: number | null;
    respiratory_rate?: number | null;
    oxygen_saturation?: number | null;
    weight?: number | null;
    height?: number | null;
    notes?: string | null;
}

interface Metrics {
    total_consultations: number;
    total_prescriptions: number;
    total_examinations: number;
    last_consultation_at?: string | null;
}

interface Props {
    patient: {
        id: string;
        user: {
            name: string;
            avatar?: string | null;
        };
        date_of_birth?: string | null;
        gender: string;
        age?: number | null;
        blood_type?: string | null;
        medical_history?: string | null;
        allergies?: string | null;
        current_medications?: string | null;
        height?: number | null;
        weight?: number | null;
        bmi?: number | null;
        bmi_category?: string | null;
    };
    timeline?: Appointment[];
    consultations?: Appointment[];
    prescriptions?: Prescription[];
    examinations?: Examination[];
    documents?: MedicalDocument[];
    vital_signs?: VitalSignEntry[];
    upcoming_appointments?: Appointment[];
    metrics?: Metrics;
    filters?: Record<string, unknown>;
    context?: {
        mode?: 'patient' | 'doctor';
        viewer?: { id: string; name: string };
    };
}

const props = withDefaults(defineProps<Props>(), {
    timeline: () => [],
    consultations: () => [],
    prescriptions: () => [],
    examinations: () => [],
    documents: () => [],
    vital_signs: () => [],
    upcoming_appointments: () => [],
    metrics: () => ({
        total_consultations: 0,
        total_prescriptions: 0,
        total_examinations: 0,
        last_consultation_at: null,
    }),
    filters: () => ({}),
});

const { canAccessPatientRoute, canAccessDoctorRoute } = useRouteGuard();
const { getInitials } = useInitials();
const page = usePage();
const isDoctorViewer = computed(() => props.context?.mode === 'doctor');

onMounted(() => {
    if (isDoctorViewer.value) {
        canAccessDoctorRoute();
    } else {
        canAccessPatientRoute();
    }
});

const breadcrumbs = computed(() => {
    if (isDoctorViewer.value) {
        return [
            { title: 'Dashboard', href: doctorRoutes.dashboard().url },
            {
                title: 'Prontuário do Paciente',
                href: doctorRoutes.patients.medicalRecord.url({ patient: props.patient.id }),
            },
        ];
    }

    return [
        { title: 'Dashboard', href: patientRoutes.dashboard().url },
        { title: 'Prontuário Médico', href: patientRoutes.medicalRecords().url },
    ];
});

type TabId = 'historico' | 'consultas' | 'prescricoes' | 'exames' | 'documentos' | 'evolucao' | 'consultas-futuras';

const tabs: Array<{ id: TabId; label: string }> = [
    { id: 'historico', label: 'Histórico' },
    { id: 'consultas', label: 'Consultas' },
    { id: 'prescricoes', label: 'Prescrições' },
    { id: 'exames', label: 'Exames' },
    { id: 'documentos', label: 'Documentos' },
    { id: 'evolucao', label: 'Evolução' },
    { id: 'consultas-futuras', label: 'Consultas Futuras' },
];

const activeTab = ref<TabId>('historico');
const expandedItems = ref<Set<string>>(new Set());

const filtersState = reactive({
    search: (props.filters?.search as string) ?? '',
    date_from: (props.filters?.date_from as string) ?? '',
    date_to: (props.filters?.date_to as string) ?? '',
});

watch(
    () => props.filters,
    (newFilters) => {
        filtersState.search = (newFilters?.search as string) ?? '';
        filtersState.date_from = (newFilters?.date_from as string) ?? '';
        filtersState.date_to = (newFilters?.date_to as string) ?? '';
    },
    { deep: true },
);

const hasFilters = computed(() => {
    return Boolean(filtersState.search || filtersState.date_from || filtersState.date_to);
});

const applyFilters = () => {
    const url = isDoctorViewer.value
        ? doctorRoutes.patients.medicalRecord.url({ patient: props.patient.id })
        : patientRoutes.medicalRecords.url();

    router.get(
        url,
        {
            search: filtersState.search,
            date_from: filtersState.date_from,
            date_to: filtersState.date_to,
        },
        {
            preserveScroll: true,
            preserveState: true,
            replace: true,
        },
    );
};

const clearFilters = () => {
    filtersState.search = '';
    filtersState.date_from = '';
    filtersState.date_to = '';
    applyFilters();
};

const exportForm = useForm({
    search: filtersState.search,
    date_from: filtersState.date_from,
    date_to: filtersState.date_to,
});

const exportRecord = () => {
    const url = isDoctorViewer.value
        ? doctorPatientMedicalRecordRoutes.export.url({ patient: props.patient.id })
        : patientMedicalRecordRoutes.export.url();

    exportForm.search = filtersState.search;
    exportForm.date_from = filtersState.date_from;
    exportForm.date_to = filtersState.date_to;

    exportForm.post(url, {
        preserveScroll: true,
        onSuccess: () => {
            exportForm.reset();
        },
    });
};

const documentForm = useForm<{
    file: File | null;
    category: string;
    name: string;
    description: string;
    visibility: string;
    appointment_id: string;
}>({
    file: null,
    category: 'report',
    name: '',
    description: '',
    visibility: 'shared',
    appointment_id: '',
});

const documentStoreUrl = computed(() => {
    return isDoctorViewer.value
        ? doctorPatientMedicalRecordRoutes.documents.store.url({ patient: props.patient.id })
        : patientMedicalRecordRoutes.documents.store.url();
});

const submitDocument = () => {
    documentForm.post(documentStoreUrl.value, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            documentForm.reset();
            documentForm.clearErrors();
        },
    });
};

const handleFileChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    documentForm.file = target.files?.[0] ?? null;
};

const consultations = computed(() => props.consultations?.length ? props.consultations : props.timeline);
const upcomingAppointments = computed(() => props.upcoming_appointments ?? []);

const formatDate = (value?: string | null, withTime = false) => {
    if (!value) return '—';
    const date = new Date(value);
    return date.toLocaleDateString('pt-BR', withTime ? { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' } : { day: '2-digit', month: '2-digit', year: 'numeric' });
};

const formatGender = (gender: string) => {
    const map: Record<string, string> = { male: 'Masculino', female: 'Feminino', other: 'Outro' };
    return map[gender] ?? gender;
};

const formatStatus = (status: string) => {
    return status.replaceAll('_', ' ').replace(/\b\w/g, (char) => char.toUpperCase());
};

const toggleExpand = (id: string) => {
    const newSet = new Set(expandedItems.value);
    if (newSet.has(id)) {
        newSet.delete(id);
    } else {
        newSet.add(id);
    }
    expandedItems.value = newSet;
};

watch(
    () => consultations.value,
    (items) => {
        if (items && items.length > 0 && expandedItems.value.size === 0) {
            expandedItems.value.add(items[0].id);
        }
    },
    { immediate: true },
);

const documentCategories = [
    { id: 'exam', label: 'Exame' },
    { id: 'prescription', label: 'Prescrição' },
    { id: 'report', label: 'Relatório' },
    { id: 'other', label: 'Outro' },
];

const visibilityOptions = [
    { id: 'patient', label: 'Paciente' },
    { id: 'doctor', label: 'Médico' },
    { id: 'shared', label: 'Compartilhado' },
];

const getDocumentUrl = (path: string) => `/storage/${path}`;

const flashStatus = computed(() => page.props.flash?.status ?? null);
const exportError = computed(() => page.props.errors?.export ?? null);
</script>

<template>
    <Head title="Prontuário Médico" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col bg-gray-50">
            <div class="mx-6 mt-6 space-y-4">
                <Card class="border-none shadow-sm">
                    <CardContent class="flex flex-col gap-6 p-6">
                        <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                            <div class="flex items-start gap-4">
                                <Avatar class="h-20 w-20">
                                    <AvatarImage v-if="patient.user.avatar" :src="patient.user.avatar" :alt="patient.user.name" />
                                    <AvatarFallback class="text-lg">
                                        {{ getInitials(patient.user.name) }}
                                    </AvatarFallback>
                                </Avatar>
                                <div>
                                    <h1 class="text-2xl font-semibold text-gray-900">
                                        {{ patient.user.name }}
                                    </h1>
                                    <div class="mt-2 flex flex-wrap gap-3 text-sm text-gray-600">
                                        <span v-if="patient.age">Idade: {{ patient.age }}</span>
                                        <span v-if="patient.date_of_birth">DN: {{ formatDate(patient.date_of_birth) }}</span>
                                        <span>Sexo: {{ formatGender(patient.gender) }}</span>
                                        <span v-if="patient.blood_type">Tipo sanguíneo: {{ patient.blood_type }}</span>
                                    </div>
                                    <p class="mt-2 text-sm text-gray-600">
                                        Historial médico: {{ patient.medical_history || 'Não informado' }}
                                    </p>
                                </div>
                            </div>
                            <div class="flex flex-col gap-2 text-sm text-gray-600">
                                <Button
                                    class="bg-blue-600 text-white hover:bg-blue-700"
                                    :disabled="exportForm.processing"
                                    @click="exportRecord"
                                >
                                    <Download class="mr-2 h-4 w-4" />
                                    Exportar Prontuário (PDF)
                                </Button>
                                <p v-if="flashStatus" class="text-xs text-green-600">
                                    {{ flashStatus }}
                                </p>
                                <p v-if="exportError" class="text-xs text-red-600 flex items-center gap-1">
                                    <AlertCircle class="h-4 w-4" />
                                    {{ exportError }}
                                </p>
                            </div>
                        </div>
                        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                            <div class="rounded-lg border border-gray-200 bg-white p-4">
                                <div class="flex items-center justify-between text-sm text-gray-500">
                                    Consultas
                                    <Stethoscope class="h-4 w-4 text-blue-500" />
                                </div>
                                <p class="mt-2 text-2xl font-semibold text-gray-900">{{ metrics?.total_consultations ?? 0 }}</p>
                                <p class="text-xs text-gray-500">Última: {{ formatDate(metrics?.last_consultation_at) }}</p>
                            </div>
                            <div class="rounded-lg border border-gray-200 bg-white p-4">
                                <div class="flex items-center justify-between text-sm text-gray-500">
                                    Prescrições
                                    <Pill class="h-4 w-4 text-rose-500" />
                                </div>
                                <p class="mt-2 text-2xl font-semibold text-gray-900">{{ metrics?.total_prescriptions ?? 0 }}</p>
                            </div>
                            <div class="rounded-lg border border-gray-200 bg-white p-4">
                                <div class="flex items-center justify-between text-sm text-gray-500">
                                    Exames
                                    <TestTube class="h-4 w-4 text-emerald-500" />
                                </div>
                                <p class="mt-2 text-2xl font-semibold text-gray-900">{{ metrics?.total_examinations ?? 0 }}</p>
                            </div>
                            <div class="rounded-lg border border-gray-200 bg-white p-4">
                                <div class="flex items-center justify-between text-sm text-gray-500">
                                    Próximas consultas
                                    <Clock class="h-4 w-4 text-indigo-500" />
                                </div>
                                <p class="mt-2 text-2xl font-semibold text-gray-900">{{ upcomingAppointments.length }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card class="border border-gray-200">
                    <CardContent class="flex flex-col gap-4 p-4 md:flex-row md:items-end">
                        <div class="flex flex-1 flex-col gap-2">
                            <label class="text-sm font-medium text-gray-700">Busca</label>
                            <div class="flex items-center gap-2">
                                <Input
                                    v-model="filtersState.search"
                                    class="flex-1"
                                    placeholder="Buscar por diagnóstico, médico, sintomas..."
                                />
                                <Search class="h-4 w-4 text-gray-400" />
                            </div>
                        </div>
                        <div class="grid flex-1 grid-cols-1 gap-2 md:grid-cols-2">
                            <div class="flex flex-col gap-2">
                                <label class="text-sm font-medium text-gray-700">De</label>
                                <Input v-model="filtersState.date_from" type="date" />
                            </div>
                            <div class="flex flex-col gap-2">
                                <label class="text-sm font-medium text-gray-700">Até</label>
                                <Input v-model="filtersState.date_to" type="date" />
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <Button class="bg-gray-900 text-white hover:bg-gray-800" @click="applyFilters">
                                <Filter class="mr-2 h-4 w-4" />
                                Aplicar
                            </Button>
                            <Button variant="outline" :disabled="!hasFilters" @click="clearFilters">
                                Limpar
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <div class="mx-6 mt-4 border-b border-gray-200">
                <nav class="flex gap-1 overflow-x-auto">
                    <button
                        v-for="tab in tabs"
                        :key="tab.id"
                        @click="activeTab = tab.id"
                        :class="[
                            'px-4 py-3 text-sm font-medium transition-colors whitespace-nowrap border-b-2',
                            activeTab === tab.id
                                ? 'border-blue-600 text-blue-600'
                                : 'border-transparent text-gray-600 hover:text-gray-900 hover:border-gray-300',
                        ]"
                    >
                        {{ tab.label }}
                    </button>
                </nav>
            </div>

            <div class="mx-6 my-6 flex-1 space-y-6">
                <div v-if="activeTab === 'historico'" class="space-y-4">
                    <Card
                        v-for="consultation in consultations"
                        :key="consultation.id"
                        class="overflow-hidden border border-gray-200 shadow-sm"
                    >
                        <CardHeader class="flex flex-row items-center justify-between gap-4 bg-white">
                            <div class="flex flex-col">
                                <CardTitle class="text-lg font-semibold text-gray-900">
                                    {{ formatDate(consultation.scheduled_at, true) }}
                                </CardTitle>
                                <p class="text-sm text-gray-600">
                                    {{ consultation.doctor.user.name }}
                                    <span v-if="consultation.doctor.specializations?.length">
                                        · {{ consultation.doctor.specializations[0].name }}
                                    </span>
                                </p>
                                <p class="text-xs text-gray-500">
                                    Status: {{ formatStatus(consultation.status) }}
                                </p>
                            </div>
                            <Button variant="ghost" class="text-blue-600" @click="toggleExpand(consultation.id)">
                                {{ expandedItems.has(consultation.id) ? 'Recolher' : 'Detalhes' }}
                            </Button>
                        </CardHeader>
                        <CardContent v-if="expandedItems.has(consultation.id)" class="bg-gray-50">
                            <div class="grid gap-4 md:grid-cols-2">
                                <div>
                                    <p class="text-sm font-semibold text-gray-900">Diagnóstico</p>
                                    <p class="text-sm text-gray-700">
                                        {{ consultation.diagnosis || 'Não informado' }}
                                    </p>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-gray-900">CID-10</p>
                                    <p class="text-sm text-gray-700">{{ consultation.cid10 || '—' }}</p>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-gray-900">Sintomas</p>
                                    <p class="text-sm text-gray-700">
                                        {{ consultation.symptoms || 'Não informado' }}
                                    </p>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-gray-900">Exames solicitados</p>
                                    <p class="text-sm text-gray-700">
                                        {{ consultation.requested_exams || 'Não informado' }}
                                    </p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                    <p v-if="consultations.length === 0" class="text-sm text-gray-500">
                        Nenhuma consulta registrada até o momento.
                    </p>
                </div>

                <div v-if="activeTab === 'consultas'">
                    <Card class="border border-gray-200">
                        <CardContent class="p-0">
                            <table class="min-w-full divide-y divide-gray-200 text-sm">
                                <thead class="bg-gray-50 text-xs font-medium uppercase text-gray-500">
                                    <tr>
                                        <th class="px-4 py-3 text-left">Data</th>
                                        <th class="px-4 py-3 text-left">Médico</th>
                                        <th class="px-4 py-3 text-left">Status</th>
                                        <th class="px-4 py-3 text-left">Diagnóstico</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 bg-white">
                                    <tr v-for="appointment in consultations" :key="appointment.id">
                                        <td class="px-4 py-3">{{ formatDate(appointment.scheduled_at, true) }}</td>
                                        <td class="px-4 py-3">{{ appointment.doctor.user.name }}</td>
                                        <td class="px-4 py-3">{{ formatStatus(appointment.status) }}</td>
                                        <td class="px-4 py-3">{{ appointment.diagnosis || '—' }}</td>
                                    </tr>
                                    <tr v-if="consultations.length === 0">
                                        <td colspan="4" class="px-4 py-6 text-center text-gray-500">
                                            Nenhuma consulta encontrada com os filtros atuais.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </CardContent>
                    </Card>
                </div>

                <div v-if="activeTab === 'prescricoes'" class="grid gap-4 md:grid-cols-2">
                    <Card v-for="prescription in prescriptions" :key="prescription.id" class="border border-gray-200">
                        <CardHeader>
                            <CardTitle class="text-base text-gray-900">
                                Emitida em {{ formatDate(prescription.issued_at) }}
                            </CardTitle>
                            <p class="text-sm text-gray-600">
                                Médico: {{ prescription.doctor?.name || '—' }}
                            </p>
                        </CardHeader>
                        <CardContent class="space-y-2 text-sm text-gray-700">
                            <div>
                                <p class="font-medium text-gray-900">Medicamentos</p>
                                <ul class="ml-4 list-disc">
                                    <li v-for="(med, idx) in prescription.medications" :key="idx">
                                        {{ med.name || 'Medicamento' }} - {{ med.dosage || '' }}
                                        {{ med.frequency || '' }}
                                    </li>
                                </ul>
                            </div>
                            <p><span class="font-medium text-gray-900">Instruções:</span> {{ prescription.instructions || '—' }}</p>
                            <p><span class="font-medium text-gray-900">Validade:</span> {{ formatDate(prescription.valid_until) }}</p>
                            <p><span class="font-medium text-gray-900">Status:</span> {{ formatStatus(prescription.status) }}</p>
                        </CardContent>
                    </Card>
                    <p v-if="prescriptions.length === 0" class="text-sm text-gray-500">
                        Nenhuma prescrição disponível.
                    </p>
                </div>

                <div v-if="activeTab === 'exames'" class="grid gap-4 md:grid-cols-2">
                    <Card v-for="exam in examinations" :key="exam.id" class="border border-gray-200">
                        <CardHeader>
                            <CardTitle class="text-base text-gray-900">{{ exam.name }}</CardTitle>
                            <p class="text-sm text-gray-600">
                                Tipo: {{ formatStatus(exam.type) }} · Status: {{ formatStatus(exam.status) }}
                            </p>
                        </CardHeader>
                        <CardContent class="space-y-2 text-sm text-gray-700">
                            <p><span class="font-medium text-gray-900">Solicitado em:</span> {{ formatDate(exam.requested_at) }}</p>
                            <p><span class="font-medium text-gray-900">Concluído em:</span> {{ formatDate(exam.completed_at) }}</p>
                            <p><span class="font-medium text-gray-900">Resultado:</span> {{ exam.results?.summary || '—' }}</p>
                            <Link
                                v-if="exam.attachment_url"
                                :href="exam.attachment_url"
                                class="inline-flex items-center text-sm text-blue-600 hover:underline"
                                target="_blank"
                            >
                                <FileText class="mr-1 h-4 w-4" />
                                Ver laudo
                            </Link>
                        </CardContent>
                    </Card>
                    <p v-if="examinations.length === 0" class="text-sm text-gray-500">
                        Nenhum exame encontrado.
                    </p>
                </div>

                <div v-if="activeTab === 'documentos'" class="grid gap-6 lg:grid-cols-3">
                    <Card class="lg:col-span-2 border border-gray-200">
                        <CardContent class="p-0">
                            <table class="min-w-full divide-y divide-gray-200 text-sm">
                                <thead class="bg-gray-50 text-xs font-medium uppercase text-gray-500">
                                    <tr>
                                        <th class="px-4 py-3 text-left">Nome</th>
                                        <th class="px-4 py-3 text-left">Categoria</th>
                                        <th class="px-4 py-3 text-left">Data</th>
                                        <th class="px-4 py-3 text-left">Ações</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 bg-white">
                                    <tr v-for="doc in documents" :key="doc.id">
                                        <td class="px-4 py-3">
                                            <p class="font-medium text-gray-900">{{ doc.name }}</p>
                                            <p class="text-xs text-gray-500">{{ doc.description || '—' }}</p>
                                        </td>
                                        <td class="px-4 py-3">{{ formatStatus(doc.category) }}</td>
                                        <td class="px-4 py-3">{{ formatDate(doc.uploaded_at) }}</td>
                                        <td class="px-4 py-3">
                                            <a
                                                class="inline-flex items-center text-blue-600 hover:underline"
                                                :href="getDocumentUrl(doc.file_path)"
                                                target="_blank"
                                            >
                                                <Download class="mr-1 h-4 w-4" />
                                                Baixar
                                            </a>
                                        </td>
                                    </tr>
                                    <tr v-if="documents.length === 0">
                                        <td colspan="4" class="px-4 py-6 text-center text-gray-500">
                                            Nenhum documento encontrado.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </CardContent>
                    </Card>

                    <Card class="border border-dashed border-gray-300">
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2 text-base">
                                <FilePlus2 class="h-5 w-5 text-blue-600" />
                                Upload de Documento
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-3">
                            <div class="flex flex-col gap-1">
                                <label class="text-sm font-medium text-gray-700">Arquivo</label>
                                <input
                                    type="file"
                                    accept=".pdf,.jpg,.jpeg,.png"
                                    class="rounded-md border border-gray-300 px-3 py-2 text-sm"
                                    @change="handleFileChange"
                                />
                                <p class="text-xs text-gray-500">Formatos aceitos: PDF, JPG, PNG (máx. 10MB)</p>
                                <p v-if="documentForm.errors.file" class="text-xs text-red-600">
                                    {{ documentForm.errors.file }}
                                </p>
                            </div>
                            <div class="flex flex-col gap-1">
                                <label class="text-sm font-medium text-gray-700">Categoria</label>
                                <select
                                    v-model="documentForm.category"
                                    class="rounded-md border border-gray-300 px-3 py-2 text-sm"
                                >
                                    <option v-for="option in documentCategories" :key="option.id" :value="option.id">
                                        {{ option.label }}
                                    </option>
                                </select>
                                <p v-if="documentForm.errors.category" class="text-xs text-red-600">
                                    {{ documentForm.errors.category }}
                                </p>
                            </div>
                            <div class="flex flex-col gap-1">
                                <label class="text-sm font-medium text-gray-700">Nome do documento</label>
                                <Input v-model="documentForm.name" placeholder="Ex: Resultado do exame de sangue" />
                                <p v-if="documentForm.errors.name" class="text-xs text-red-600">
                                    {{ documentForm.errors.name }}
                                </p>
                            </div>
                            <div class="flex flex-col gap-1">
                                <label class="text-sm font-medium text-gray-700">Descrição</label>
                                <Textarea
                                    v-model="documentForm.description"
                                    rows="3"
                                    placeholder="Contexto ou observações adicionais"
                                />
                            </div>
                            <div class="flex flex-col gap-1">
                                <label class="text-sm font-medium text-gray-700">Visibilidade</label>
                                <select
                                    v-model="documentForm.visibility"
                                    class="rounded-md border border-gray-300 px-3 py-2 text-sm"
                                >
                                    <option v-for="option in visibilityOptions" :key="option.id" :value="option.id">
                                        {{ option.label }}
                                    </option>
                                </select>
                            </div>
                            <Button
                                class="w-full bg-blue-600 text-white hover:bg-blue-700"
                                :disabled="documentForm.processing"
                                @click="submitDocument"
                            >
                                <Upload class="mr-2 h-4 w-4" />
                                Enviar documento
                            </Button>
                            <p v-if="documentForm.hasErrors" class="text-xs text-red-600">
                                Verifique os campos obrigatórios antes de enviar.
                            </p>
                        </CardContent>
                    </Card>
                </div>

                <div v-if="activeTab === 'evolucao'" class="grid gap-4 md:grid-cols-2">
                    <Card v-for="vital in vital_signs" :key="vital.id" class="border border-gray-200">
                        <CardHeader>
                            <CardTitle class="text-base">
                                Registro em {{ formatDate(vital.recorded_at, true) }}
                            </CardTitle>
                            <p class="text-sm text-gray-600">
                                Registrado por: {{ vital.doctor?.name || '—' }}
                            </p>
                        </CardHeader>
                        <CardContent class="grid grid-cols-2 gap-2 text-sm text-gray-700">
                            <p><span class="font-medium">Pressão:</span> {{ vital.blood_pressure?.systolic || '—' }}/{{ vital.blood_pressure?.diastolic || '—' }} mmHg</p>
                            <p><span class="font-medium">Frequência:</span> {{ vital.heart_rate || '—' }} bpm</p>
                            <p><span class="font-medium">Temperatura:</span> {{ vital.temperature || '—' }} ºC</p>
                            <p><span class="font-medium">SatO₂:</span> {{ vital.oxygen_saturation || '—' }}%</p>
                            <p><span class="font-medium">Peso:</span> {{ vital.weight || '—' }} kg</p>
                            <p><span class="font-medium">Altura:</span> {{ vital.height || '—' }} cm</p>
                            <p class="col-span-2"><span class="font-medium">Notas:</span> {{ vital.notes || '—' }}</p>
                        </CardContent>
                    </Card>
                    <p v-if="vital_signs.length === 0" class="text-sm text-gray-500">
                        Nenhum registro de sinais vitais foi encontrado.
                    </p>
                </div>

                <div v-if="activeTab === 'consultas-futuras'" class="grid gap-4 md:grid-cols-2">
                    <Card v-for="appointment in upcomingAppointments" :key="appointment.id" class="border border-gray-200">
                        <CardHeader>
                            <CardTitle class="text-base">{{ formatDate(appointment.scheduled_at, true) }}</CardTitle>
                            <p class="text-sm text-gray-600">{{ appointment.doctor.user.name }}</p>
                        </CardHeader>
                        <CardContent class="text-sm text-gray-700">
                            <p>Status: {{ formatStatus(appointment.status) }}</p>
                        </CardContent>
                    </Card>
                    <p v-if="upcomingAppointments.length === 0" class="text-sm text-gray-500">
                        Nenhuma consulta futura encontrada.
                    </p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

