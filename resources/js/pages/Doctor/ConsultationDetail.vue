<script setup lang="ts">
// @ts-expect-error - route helper from Ziggy
declare const route: (name: string, params?: unknown) => string;

import ChiefComplaintCard from '@/components/Doctor/ConsultationDetail/cards/ChiefComplaintCard.vue';
import DiagnosisCard from '@/components/Doctor/ConsultationDetail/cards/DiagnosisCard.vue';
import ExaminationsCard from '@/components/Doctor/ConsultationDetail/cards/ExaminationsCard.vue';
import InstructionsCard from '@/components/Doctor/ConsultationDetail/cards/InstructionsCard.vue';
import NotesCard from '@/components/Doctor/ConsultationDetail/cards/NotesCard.vue';
import PhysicalExamCard from '@/components/Doctor/ConsultationDetail/cards/PhysicalExamCard.vue';
import PrescriptionCard from '@/components/Doctor/ConsultationDetail/cards/PrescriptionCard.vue';
import ConsultationHeader from '@/components/Doctor/ConsultationDetail/ConsultationHeader.vue';
import FinalizeModal from '@/components/Doctor/ConsultationDetail/FinalizeModal.vue';
import PatientSidebar from '@/components/Doctor/ConsultationDetail/PatientSidebar.vue';
import SuccessToast from '@/components/Doctor/ConsultationDetail/SuccessToast.vue';
import { useRouteGuard } from '@/composables/auth/useRouteGuard';
import { useConsultationDraft } from '@/composables/Doctor/useConsultationDraft';
import { useConsultationHotkeys } from '@/composables/Doctor/useConsultationHotkeys';
import { useCollapsibleCards } from '@/composables/useCollapsibleCards';
import { useFormatters } from '@/composables/useFormatters';
import AppLayout from '@/layouts/AppLayout.vue';
import type { AppointmentDetail, ConsultationMode, ConsultationPatient, RecentConsultation } from '@/types/consultation-detail';
import { Head, router, useForm } from '@inertiajs/vue3';
import { CheckCircle2, Clock, Lock } from 'lucide-vue-next';
import { computed, onMounted, ref, watch } from 'vue';

interface Props {
    appointment: AppointmentDetail;
    patient: ConsultationPatient;
    recent_consultations: RecentConsultation[];
    mode: ConsultationMode;
    elapsed_time?: number | null;
    can_edit: boolean;
    can_complement: boolean;
}

const props = defineProps<Props>();

const { formatDatePortuguese } = useFormatters();

const consultationForm = useForm({
    chief_complaint: props.appointment.chief_complaint ?? '',
    physical_exam: props.appointment.physical_exam ?? '',
    diagnosis: props.appointment.diagnosis ?? '',
    cid10: props.appointment.cid10 ?? '',
    instructions: props.appointment.instructions ?? '',
    notes: props.appointment.notes ?? '',
});

const isInProgress = computed(() => props.mode === 'in_progress');
const isCompleted = computed(() => props.mode === 'completed');
const isScheduled = computed(() => props.mode === 'scheduled');

const statusBadge = computed(() => {
    if (isInProgress.value) return { label: 'Em andamento', color: 'bg-green-500', icon: CheckCircle2 };
    if (isCompleted.value) return { label: 'Finalizada', color: 'bg-gray-500', icon: Lock };
    return { label: 'Agendada', color: 'bg-blue-500', icon: Clock };
});

const elapsedTimeFormatted = computed(() => {
    if (!props.elapsed_time) return '00:00';
    const hours = Math.floor(props.elapsed_time / 60);
    const minutes = props.elapsed_time % 60;
    return `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}`;
});

const scheduledDateFormatted = computed(() => formatDatePortuguese(props.appointment.scheduled_at));

const { collapsedCards, toggleCard, expandCard } = useCollapsibleCards({
    chief_complaint: false,
    physical_exam: false,
    diagnosis: false,
    prescription: false,
    examinations: false,
    notes: false,
    instructions: false,
});

const { isSaving, autoSaveStatus, hasUnsavedChanges, lastSaved, showSuccessNotification, saveDraft, triggerAutoSave, startAutoSave, stopAutoSave } =
    useConsultationDraft(consultationForm, isInProgress, () => route('doctor.consultations.detail.save-draft', props.appointment.id));

useConsultationHotkeys(collapsedCards, expandCard, () => saveDraft());

const { canAccessDoctorRoute } = useRouteGuard();

onMounted(() => {
    canAccessDoctorRoute();
    if (isInProgress.value) startAutoSave();
});

const sidebarCollapsed = ref(false);
const showFinalizeModal = ref(false);

watch(
    () => consultationForm.data(),
    () => {
        if (isInProgress.value) triggerAutoSave();
    },
    { deep: true },
);

watch(
    () => props.mode,
    (newMode) => {
        if (newMode === 'in_progress') {
            startAutoSave();
        } else {
            stopAutoSave();
        }
    },
);

const confirmFinalize = async () => {
    try {
        await router.post(
            route('doctor.consultations.detail.finalize', props.appointment.id),
            {},
            {
                onSuccess: () => (showFinalizeModal.value = false),
            },
        );
    } catch {
        // handled by Inertia error flash
    }
};

const startConsultation = async () => {
    try {
        await router.post(route('doctor.consultations.detail.start', props.appointment.id));
    } catch {
        // handled by Inertia error flash
    }
};
</script>

<template>
    <AppLayout>
        <Head :title="`Consulta - ${patient.name}`" />

        <ConsultationHeader
            :patient-name="patient.name"
            :scheduled-date-formatted="scheduledDateFormatted"
            :status-badge="statusBadge"
            :is-in-progress="isInProgress"
            :is-completed="isCompleted"
            :is-scheduled="isScheduled"
            :elapsed-time="elapsed_time"
            :elapsed-time-formatted="elapsedTimeFormatted"
            :auto-save-status="autoSaveStatus"
            :has-unsaved-changes="hasUnsavedChanges"
            :is-saving="isSaving"
            :last-saved="lastSaved"
            @start="startConsultation"
            @save="saveDraft()"
            @finalize="showFinalizeModal = true"
            @pdf="router.get(route('doctor.consultations.detail.pdf', appointment.id))"
            @messages="router.get(route('doctor.messages'))"
        />

        <SuccessToast :show="showSuccessNotification" />

        <div class="container mx-auto px-4 py-6">
            <div class="flex gap-6">
                <PatientSidebar
                    :patient="patient"
                    :recent-consultations="recent_consultations"
                    :collapsed="sidebarCollapsed"
                    @toggle="sidebarCollapsed = !sidebarCollapsed"
                />

                <div class="flex-1 space-y-4">
                    <ChiefComplaintCard
                        v-model="consultationForm.chief_complaint"
                        :collapsed="collapsedCards.chief_complaint"
                        @toggle="toggleCard('chief_complaint')"
                        @change="triggerAutoSave"
                    />
                    <PhysicalExamCard
                        v-model="consultationForm.physical_exam"
                        :collapsed="collapsedCards.physical_exam"
                        @toggle="toggleCard('physical_exam')"
                        @change="triggerAutoSave"
                    />
                    <DiagnosisCard
                        v-model:diagnosis="consultationForm.diagnosis"
                        v-model:cid10="consultationForm.cid10"
                        :collapsed="collapsedCards.diagnosis"
                        @toggle="toggleCard('diagnosis')"
                        @change="triggerAutoSave"
                    />
                    <PrescriptionCard
                        :prescriptions="appointment.prescriptions ?? []"
                        :collapsed="collapsedCards.prescription"
                        @toggle="toggleCard('prescription')"
                    />
                    <ExaminationsCard
                        :examinations="appointment.examinations ?? []"
                        :collapsed="collapsedCards.examinations"
                        @toggle="toggleCard('examinations')"
                    />
                    <NotesCard
                        v-model="consultationForm.notes"
                        :collapsed="collapsedCards.notes"
                        @toggle="toggleCard('notes')"
                        @change="triggerAutoSave"
                    />
                    <InstructionsCard
                        v-model="consultationForm.instructions"
                        :collapsed="collapsedCards.instructions"
                        @toggle="toggleCard('instructions')"
                        @change="triggerAutoSave"
                    />
                </div>
            </div>
        </div>

        <FinalizeModal
            v-model:open="showFinalizeModal"
            :has-chief-complaint="Boolean(consultationForm.chief_complaint)"
            :has-diagnosis="Boolean(consultationForm.diagnosis || consultationForm.cid10)"
            @confirm="confirmFinalize"
        />
    </AppLayout>
</template>
