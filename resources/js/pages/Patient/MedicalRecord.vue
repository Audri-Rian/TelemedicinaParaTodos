<script setup lang="ts">
import MetricCards from '@/components/Patient/MedicalRecord/MetricCards.vue';
import PatientHeader from '@/components/Patient/MedicalRecord/PatientHeader.vue';
import RecordFilters from '@/components/Patient/MedicalRecord/RecordFilters.vue';
import TabNav from '@/components/Patient/MedicalRecord/TabNav.vue';
import CertificatesTab from '@/components/Patient/MedicalRecord/tabs/CertificatesTab.vue';
import ClinicalNotesTab from '@/components/Patient/MedicalRecord/tabs/ClinicalNotesTab.vue';
import ConsultationsTab from '@/components/Patient/MedicalRecord/tabs/ConsultationsTab.vue';
import DiagnosesTab from '@/components/Patient/MedicalRecord/tabs/DiagnosesTab.vue';
import DocumentsTab from '@/components/Patient/MedicalRecord/tabs/DocumentsTab.vue';
import ExaminationsTab from '@/components/Patient/MedicalRecord/tabs/ExaminationsTab.vue';
import HistoryTab from '@/components/Patient/MedicalRecord/tabs/HistoryTab.vue';
import PrescriptionsTab from '@/components/Patient/MedicalRecord/tabs/PrescriptionsTab.vue';
import UpcomingTab from '@/components/Patient/MedicalRecord/tabs/UpcomingTab.vue';
import VitalSignsTab from '@/components/Patient/MedicalRecord/tabs/VitalSignsTab.vue';
import { useRouteGuard } from '@/composables/auth';
import { useMedicalRecordFilters } from '@/composables/Patient/useMedicalRecordFilters';
import AppLayout from '@/layouts/AppLayout.vue';
import * as patientRoutes from '@/routes/patient';
import { type BreadcrumbItem } from '@/types';
import type {
    Appointment,
    ClinicalNote,
    Diagnosis,
    Examination,
    MedicalCertificate,
    MedicalDocument,
    MedicalMetrics,
    PatientProfile,
    Prescription,
    TabId,
    VitalSignEntry,
} from '@/types/medical-records';
import { Head } from '@inertiajs/vue3';
import { LockKeyhole } from 'lucide-vue-next';
import { computed, onMounted, ref, watch } from 'vue';

interface Props {
    patient: PatientProfile;
    timeline?: Appointment[];
    consultations?: Appointment[];
    prescriptions?: Prescription[];
    examinations?: Examination[];
    documents?: MedicalDocument[];
    vital_signs?: VitalSignEntry[];
    diagnoses?: Diagnosis[];
    clinical_notes?: ClinicalNote[];
    medical_certificates?: MedicalCertificate[];
    upcoming_appointments?: Appointment[];
    metrics?: MedicalMetrics;
    filters?: Record<string, unknown>;
}

const props = withDefaults(defineProps<Props>(), {
    timeline: () => [],
    consultations: () => [],
    prescriptions: () => [],
    examinations: () => [],
    documents: () => [],
    vital_signs: () => [],
    diagnoses: () => [],
    clinical_notes: () => [],
    medical_certificates: () => [],
    upcoming_appointments: () => [],
    metrics: () => ({
        total_consultations: 0,
        total_prescriptions: 0,
        total_examinations: 0,
        last_consultation_at: null,
    }),
    filters: () => ({}),
});

const { canAccessPatientRoute } = useRouteGuard();
const { filtersState, hasFilters, emptyText, applyFilters, clearFilters, syncFromProps } = useMedicalRecordFilters(props.filters);

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: patientRoutes.dashboard().url },
    { title: 'Prontuário Médico', href: patientRoutes.medicalRecords().url },
];

const activeTab = ref<TabId>('historico');

const consultations = computed(() => (props.consultations?.length ? props.consultations : props.timeline));
const upcomingAppointments = computed(() => props.upcoming_appointments ?? []);
const metrics = computed(
    () => props.metrics ?? { total_consultations: 0, total_prescriptions: 0, total_examinations: 0, last_consultation_at: null },
);

const tabs = computed<Array<{ id: TabId; label: string; count: number }>>(() => [
    { id: 'historico', label: 'Histórico', count: consultations.value.length },
    { id: 'consultas', label: 'Consultas', count: consultations.value.length },
    { id: 'prescricoes', label: 'Prescrições', count: props.prescriptions?.length ?? 0 },
    { id: 'exames', label: 'Exames', count: props.examinations?.length ?? 0 },
    { id: 'documentos', label: 'Documentos', count: props.documents?.length ?? 0 },
    { id: 'diagnosticos', label: 'Diagnósticos', count: props.diagnoses?.length ?? 0 },
    { id: 'atestados', label: 'Atestados', count: props.medical_certificates?.length ?? 0 },
    { id: 'vitais', label: 'Sinais Vitais', count: props.vital_signs?.length ?? 0 },
    { id: 'notas', label: 'Anotações', count: props.clinical_notes?.length ?? 0 },
    { id: 'futuras', label: 'Futuras', count: upcomingAppointments.value.length },
]);

onMounted(() => canAccessPatientRoute());

watch(() => props.filters, syncFromProps, { deep: true });
</script>

<template>
    <Head title="Prontuário Médico" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-full bg-[#f4f6f8] p-0 text-gray-950">
            <div class="flex w-full flex-col gap-5 p-4 lg:p-5">
                <PatientHeader :patient="patient" :filters-state="filtersState">
                    <template #metrics>
                        <MetricCards :metrics="metrics" :upcoming-count="upcomingAppointments.length" />
                    </template>
                </PatientHeader>

                <RecordFilters
                    :search="filtersState.search"
                    :date-from="filtersState.date_from"
                    :date-to="filtersState.date_to"
                    :has-filters="hasFilters"
                    @update:search="filtersState.search = $event"
                    @update:date-from="filtersState.date_from = $event"
                    @update:date-to="filtersState.date_to = $event"
                    @apply="applyFilters"
                    @clear="clearFilters"
                />

                <section class="rounded-lg border border-[#dde5ea] bg-white shadow-sm">
                    <TabNav :tabs="tabs" :active-tab="activeTab" @change="activeTab = $event" />

                    <div class="p-4 lg:p-5">
                        <HistoryTab v-if="activeTab === 'historico'" :consultations="consultations" :empty-text="emptyText" />
                        <ConsultationsTab v-if="activeTab === 'consultas'" :consultations="consultations" :empty-text="emptyText" />
                        <PrescriptionsTab v-if="activeTab === 'prescricoes'" :prescriptions="props.prescriptions ?? []" :empty-text="emptyText" />
                        <ExaminationsTab v-if="activeTab === 'exames'" :examinations="props.examinations ?? []" :empty-text="emptyText" />
                        <DocumentsTab
                            v-if="activeTab === 'documentos'"
                            :documents="props.documents ?? []"
                            :consultations="consultations"
                            :empty-text="emptyText"
                        />
                        <DiagnosesTab v-if="activeTab === 'diagnosticos'" :diagnoses="props.diagnoses ?? []" :empty-text="emptyText" />
                        <CertificatesTab
                            v-if="activeTab === 'atestados'"
                            :medical-certificates="props.medical_certificates ?? []"
                            :empty-text="emptyText"
                        />
                        <VitalSignsTab v-if="activeTab === 'vitais'" :vital-signs="props.vital_signs ?? []" :empty-text="emptyText" />
                        <ClinicalNotesTab v-if="activeTab === 'notas'" :clinical-notes="props.clinical_notes ?? []" :empty-text="emptyText" />
                        <UpcomingTab v-if="activeTab === 'futuras'" :upcoming-appointments="upcomingAppointments" :empty-text="emptyText" />
                    </div>
                </section>

                <section class="rounded-lg border border-[#dde5ea] bg-white p-4 shadow-sm">
                    <div class="flex items-start gap-3">
                        <span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-[#e5f1f2] text-[#0f6e78]">
                            <LockKeyhole class="h-5 w-5" />
                        </span>
                        <div>
                            <h2 class="font-black text-gray-950">Privacidade e rastreabilidade</h2>
                            <p class="mt-1 text-sm font-semibold text-gray-600">
                                O acesso ao prontuário é registrado para auditoria e os dados são exibidos conforme permissões clínicas e
                                consentimentos.
                            </p>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </AppLayout>
</template>
