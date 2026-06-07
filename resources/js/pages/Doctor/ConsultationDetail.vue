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
import NotaComplementarCard from '@/components/Doctor/ConsultationDetail/NotaComplementarCard.vue';
import PatientSidebar from '@/components/Doctor/ConsultationDetail/PatientSidebar.vue';
import StateBanner from '@/components/Doctor/ConsultationDetail/StateBanner.vue';
import SuccessToast from '@/components/Doctor/ConsultationDetail/SuccessToast.vue';
import { useRouteGuard } from '@/composables/auth/useRouteGuard';
import { useConsultationDraft } from '@/composables/Doctor/useConsultationDraft';
import { useConsultationHotkeys } from '@/composables/Doctor/useConsultationHotkeys';
import { useCollapsibleCards } from '@/composables/useCollapsibleCards';
import { useFormatters } from '@/composables/useFormatters';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import type { AppointmentDetail, ConsultationMode, ConsultationPatient, RecentConsultation } from '@/types/consultation-detail';
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, onMounted, ref, watch } from 'vue';

interface ComplementEntry {
    date: string;
    time: string;
    author: string;
    body: string;
}

interface Props {
    appointment: AppointmentDetail;
    patient: ConsultationPatient;
    recent_consultations: RecentConsultation[];
    mode: ConsultationMode;
    elapsed_time?: number | null;
    can_edit: boolean;
    can_complement: boolean;
    doctor_name?: string;
}

const props = defineProps<Props>();

const { formatDatePortuguese } = useFormatters();

const fromHistory = typeof window !== 'undefined' && new URLSearchParams(window.location.search).get('from') === 'history';

const breadcrumbs = computed<BreadcrumbItem[]>(() => {
    const base: BreadcrumbItem[] = [{ title: 'Dashboard', href: '/doctor/dashboard' }];
    if (fromHistory) {
        base.push({ title: 'Histórico', href: '/doctor/history' });
    } else {
        base.push({ title: 'Consultas', href: '/doctor/consultations' });
    }
    base.push({ title: props.patient.name, href: '#' });
    return base;
});

const backHref = computed(() => breadcrumbs.value[breadcrumbs.value.length - 2]?.href ?? '/doctor/consultations');

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

const locked = computed(() => !props.can_edit);

const statusBadge = computed(() => {
    if (isInProgress.value) return { label: 'Em andamento' };
    if (isCompleted.value) return { label: 'Finalizada' };
    return { label: 'Agendada' };
});

const elapsedTimeFormatted = computed(() => {
    if (!props.elapsed_time) return '00:00';
    const hours = Math.floor(props.elapsed_time / 60);
    const minutes = props.elapsed_time % 60;
    return `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}`;
});

const scheduledDateFormatted = computed(() => formatDatePortuguese(props.appointment.scheduled_at));

const dataState = computed(() => {
    if (isInProgress.value) return 'ativa';
    if (isCompleted.value) return 'final';
    return 'agendada';
});

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
const composerOpen = ref(false);
const complements = ref<ComplementEntry[]>([]);

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

const handleComplementSubmit = async (body: string) => {
    try {
        await router.post(route('doctor.consultations.detail.complement', props.appointment.id), { complementary_notes: body });
        const now = new Date();
        complements.value.push({
            date: now.toLocaleDateString('pt-BR', { day: '2-digit', month: 'short', year: 'numeric' }),
            time: now.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' }),
            author: props.doctor_name ?? 'Dr.',
            body,
        });
    } catch {
        // handled by Inertia error flash
    }
};

const collapsedCount = computed(() => Object.values(collapsedCards).filter(Boolean).length);
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="`Consulta - ${patient.name}`" />

        <div class="consultation-page" :data-state="dataState">
            <ConsultationHeader
                :back-href="backHref"
                :patient-name="patient.name"
                :patient-age="patient.age"
                :patient-gender="patient.gender"
                :scheduled-date-formatted="scheduledDateFormatted"
                :status-badge="statusBadge"
                :mode="mode"
                :elapsed-time="elapsed_time"
                :elapsed-time-formatted="elapsedTimeFormatted"
                :auto-save-status="autoSaveStatus"
                :has-unsaved-changes="hasUnsavedChanges"
                :is-saving="isSaving"
                :last-saved="lastSaved"
                :msg-count="0"
                @start="startConsultation"
                @save="saveDraft()"
                @finalize="showFinalizeModal = true"
                @pdf="router.get(route('doctor.consultations.detail.pdf', appointment.id))"
                @messages="router.get(route('doctor.messages'))"
                @add-complement="composerOpen = true"
            />

            <SuccessToast :show="showSuccessNotification" />

            <StateBanner :mode="mode" :patient-name="patient.name" />

            <div class="cp-workspace" :data-sidebar="sidebarCollapsed ? 'collapsed' : 'expanded'">
                <PatientSidebar
                    :patient="patient"
                    :recent-consultations="recent_consultations"
                    :collapsed="sidebarCollapsed"
                    @toggle="sidebarCollapsed = !sidebarCollapsed"
                />

                <main class="cp-center" aria-label="Prontuário da consulta">
                    <div class="cp-section-title">
                        <h2>
                            {{ isScheduled ? 'Prontuário (aguardando início)' : isInProgress ? 'Prontuário desta consulta' : 'Prontuário selado' }}
                        </h2>
                        <span class="cp-section-meta">7 seções · {{ collapsedCount }} recolhidas</span>
                    </div>

                    <ChiefComplaintCard
                        v-model="consultationForm.chief_complaint"
                        :collapsed="collapsedCards.chief_complaint"
                        :locked="locked"
                        @toggle="toggleCard('chief_complaint')"
                        @change="triggerAutoSave"
                    />
                    <PhysicalExamCard
                        v-model="consultationForm.physical_exam"
                        :collapsed="collapsedCards.physical_exam"
                        :locked="locked"
                        @toggle="toggleCard('physical_exam')"
                        @change="triggerAutoSave"
                    />
                    <DiagnosisCard
                        v-model:diagnosis="consultationForm.diagnosis"
                        v-model:cid10="consultationForm.cid10"
                        :collapsed="collapsedCards.diagnosis"
                        :locked="locked"
                        @toggle="toggleCard('diagnosis')"
                        @change="triggerAutoSave"
                    />
                    <PrescriptionCard
                        :prescriptions="appointment.prescriptions ?? []"
                        :collapsed="collapsedCards.prescription"
                        :mode="mode"
                        @toggle="toggleCard('prescription')"
                    />
                    <ExaminationsCard
                        :examinations="appointment.examinations ?? []"
                        :collapsed="collapsedCards.examinations"
                        :mode="mode"
                        @toggle="toggleCard('examinations')"
                    />
                    <NotesCard
                        v-model="consultationForm.notes"
                        :collapsed="collapsedCards.notes"
                        :locked="locked"
                        @toggle="toggleCard('notes')"
                        @change="triggerAutoSave"
                    />
                    <InstructionsCard
                        v-model="consultationForm.instructions"
                        :collapsed="collapsedCards.instructions"
                        :locked="locked"
                        @toggle="toggleCard('instructions')"
                        @change="triggerAutoSave"
                    />

                    <!-- Nota complementar (only on completed) -->
                    <NotaComplementarCard
                        v-if="isCompleted"
                        :entries="complements"
                        :doctor-name="doctor_name ?? 'Dr.'"
                        :composer-open="composerOpen"
                        @open-composer="composerOpen = true"
                        @close-composer="composerOpen = false"
                        @submit="handleComplementSubmit"
                    />

                    <!-- Keyboard shortcut bar -->
                    <div v-if="isInProgress" class="cp-shortcut-bar">
                        <b>Atalhos:</b>
                        <span><kbd class="cp-kbd">⌘</kbd> <kbd class="cp-kbd">1–7</kbd> focar campo</span>
                        <span><kbd class="cp-kbd">⌘</kbd> <kbd class="cp-kbd">S</kbd> salvar</span>
                        <span><kbd class="cp-kbd">⌘</kbd> <kbd class="cp-kbd">↵</kbd> finalizar consulta</span>
                        <span class="cp-shortcut-info">Tudo o que você escreve é salvo automaticamente</span>
                    </div>
                    <div v-if="isScheduled" class="cp-shortcut-bar">
                        <b>Atalho:</b>
                        <span><kbd class="cp-kbd">⌘</kbd> <kbd class="cp-kbd">↵</kbd> iniciar consulta</span>
                        <span class="cp-shortcut-info">Revise alergias e medicações antes de começar</span>
                    </div>
                </main>
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

<style scoped>
.consultation-page {
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    background: #f4f7f7;
}
.consultation-page[data-state='ativa'] {
    background: linear-gradient(180deg, #f0faf7 0%, #f4f7f7 320px);
}
.consultation-page[data-state='final'] {
    background: linear-gradient(180deg, #eff3f2 0%, #f4f7f7 280px);
}

.cp-workspace {
    display: grid;
    grid-template-columns: 320px minmax(0, 1fr);
    gap: 24px;
    padding: 24px 32px 80px;
    max-width: 1480px;
    margin: 0 auto;
    width: 100%;
}
.cp-workspace[data-sidebar='collapsed'] {
    grid-template-columns: 64px minmax(0, 1fr);
}

.cp-center {
    display: flex;
    flex-direction: column;
    gap: 14px;
    min-width: 0;
}

.cp-section-title {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 4px 4px;
}
.cp-section-title h2 {
    margin: 0;
    font-size: 13px;
    font-weight: 700;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    color: var(--cp-ink-400, #8fa2a0);
    font-family: var(--cp-font-sans, 'Plus Jakarta Sans', sans-serif);
}
.cp-section-meta {
    font-size: 11.5px;
    color: var(--cp-ink-400, #8fa2a0);
    font-family: var(--cp-font-sans, 'Plus Jakarta Sans', sans-serif);
}

.cp-shortcut-bar {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 13px 22px;
    background: var(--cp-surface, #fff);
    border: 1px solid var(--cp-line, #e3eae9);
    border-radius: 14px;
    font-size: 12.5px;
    color: var(--cp-ink-500, #5a726f);
    flex-wrap: wrap;
    font-family: var(--cp-font-sans, 'Plus Jakarta Sans', sans-serif);
}
.cp-shortcut-bar b {
    color: var(--cp-ink-700, #1f3a38);
    font-weight: 600;
    margin-right: 2px;
}
.cp-shortcut-info {
    margin-left: auto;
    color: var(--cp-ink-400, #8fa2a0);
}

.cp-kbd {
    display: inline-flex;
    align-items: center;
    font-family: var(--cp-font-mono, monospace);
    font-size: 11px;
    padding: 2px 6px;
    border: 1px solid var(--cp-line-strong, #c8d4d2);
    border-bottom-width: 2px;
    border-radius: 5px;
    background: var(--cp-surface-2, #fafbfb);
    color: var(--cp-ink-700, #1f3a38);
    font-weight: 500;
}

@media (max-width: 1100px) {
    .cp-workspace {
        grid-template-columns: 1fr;
        padding: 16px;
    }
}
</style>
