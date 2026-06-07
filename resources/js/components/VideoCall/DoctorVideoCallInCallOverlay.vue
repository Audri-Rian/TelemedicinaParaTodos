<script setup lang="ts">
import DoctorConsultChatPanel from '@/components/VideoCall/doctorConsultDesign/DoctorConsultChatPanel.vue';
import DoctorConsultControlsBar from '@/components/VideoCall/doctorConsultDesign/DoctorConsultControlsBar.vue';
import { type ConsultPatient, type ConsultSharedFile } from '@/components/VideoCall/doctorConsultDesign/doctorConsultDesignData';
import DoctorConsultEndModal from '@/components/VideoCall/doctorConsultDesign/DoctorConsultEndModal.vue';
import DoctorConsultFilesPanel from '@/components/VideoCall/doctorConsultDesign/DoctorConsultFilesPanel.vue';
import DoctorConsultNotesPanel from '@/components/VideoCall/doctorConsultDesign/DoctorConsultNotesPanel.vue';
import DoctorConsultPatientPanel from '@/components/VideoCall/doctorConsultDesign/DoctorConsultPatientPanel.vue';
import DoctorConsultTopbar from '@/components/VideoCall/doctorConsultDesign/DoctorConsultTopbar.vue';
import DoctorConsultVideoStage from '@/components/VideoCall/doctorConsultDesign/DoctorConsultVideoStage.vue';
import DoctorVideoCallInCallModal, { type InCallDocumentKind } from '@/components/VideoCall/DoctorVideoCallInCallModal.vue';
import { useCallChat } from '@/composables/useCallChat';
import { useCallSharedDocuments } from '@/composables/useCallSharedDocuments';
import doctorMedicalRecordDocuments from '@/routes/doctor/patients/medical-record/documents';
import { formatCallDocumentSize, formatCallDocumentTime, type CallSharedDocument } from '@/types/call-documents';
import { FileText, MessageSquare, NotebookPen, PanelRight, User } from 'lucide-vue-next';
import { computed, onUnmounted, ref, watch } from 'vue';

import '../../../css/doctor-consult-video-call-design.css';

export interface CallClinicalSummary {
    age: number | null;
    gender: string | null;
    blood_type: string | null;
    allergies: string | null;
    medical_history: string | null;
    current_medications: string | null;
}

export interface CallPatientHistoryEntry {
    id: string;
    date: string;
    title: string;
    summary: string | null;
}

const props = withDefaults(
    defineProps<{
        isInCall: boolean;
        localStream: MediaStream | null;
        remoteStreams: Map<string, MediaStream>;
        isMicEnabled: boolean;
        isCameraEnabled: boolean;
        isEnding: boolean;
        patientDisplayName?: string | null;
        appointmentId?: string | null;
        patientId?: string | null;
        patientUserId?: string | number | null;
        chiefComplaint?: string | null;
        clinicalSummary?: CallClinicalSummary | null;
        patientHistory?: CallPatientHistoryEntry[];
        sharedDocuments?: CallSharedDocument[];
    }>(),
    {
        patientDisplayName: null,
        appointmentId: null,
        patientId: null,
        patientUserId: null,
        chiefComplaint: null,
        clinicalSummary: null,
        patientHistory: () => [],
        sharedDocuments: () => [],
    },
);

const emit = defineEmits<{
    toggleMic: [];
    toggleCamera: [];
    end: [];
}>();

const TAB_DEFS = [
    { id: 'notes' as const, label: 'Anotações', icon: NotebookPen },
    { id: 'patient' as const, label: 'Paciente', icon: User },
    { id: 'chat' as const, label: 'Chat', icon: MessageSquare },
    { id: 'files' as const, label: 'Arquivos', icon: FileText },
];

const tab = ref<(typeof TAB_DEFS)[number]['id']>('notes');
const sideOpen = ref(true);
const panelWidth = ref(420);
const showCaptions = ref(false);

const screenSharing = ref(false);
const handRaised = ref(false);

const endModalOpen = ref(false);
const toast = ref<{ message: string } | null>(null);
let toastTimer: ReturnType<typeof setTimeout> | null = null;

const roomRef = ref<HTMLElement | null>(null);

const displayPatientName = computed(() => props.patientDisplayName?.trim() || 'Paciente');

const splitTextList = (value: string | null | undefined, separator: RegExp): string[] =>
    (value ?? '')
        .split(separator)
        .map((item) => item.trim())
        .filter(Boolean);

const patientForPanel = computed<ConsultPatient>(() => ({
    name: displayPatientName.value,
    initials:
        displayPatientName.value
            .split(/\s+/)
            .filter(Boolean)
            .map((w) => w[0])
            .slice(0, 2)
            .join('')
            .toUpperCase() || 'P',
    age: props.clinicalSummary?.age ?? null,
    gender: props.clinicalSummary?.gender ?? null,
    bloodType: props.clinicalSummary?.blood_type ?? null,
    allergies: splitTextList(props.clinicalSummary?.allergies, /[,;\n]/),
    conditions: props.clinicalSummary?.medical_history?.trim() || null,
    medications: splitTextList(props.clinicalSummary?.current_medications, /[;\n]/),
    chiefComplaint: props.chiefComplaint?.trim() || null,
    history: props.patientHistory.map((entry) => ({
        id: entry.id,
        title: entry.title,
        date: entry.date,
        summary: entry.summary,
    })),
}));

const patientFirstName = computed(() => displayPatientName.value.split(/\s+/)[0] || displayPatientName.value);

const { messages, send: sendChatMessage } = useCallChat({
    isInCall: () => props.isInCall,
    otherUserId: () => props.patientUserId,
    otherUserName: () => displayPatientName.value,
    appointmentId: () => props.appointmentId,
});

const chatCount = computed(() => messages.value.filter((m) => m.type !== 'system').length);

const seconds = ref(0);
let elapsedTimer: ReturnType<typeof setInterval> | null = null;

watch(
    () => props.isInCall,
    (v) => {
        if (v) {
            seconds.value = 0;
            elapsedTimer = setInterval(() => {
                seconds.value += 1;
            }, 1000);
        } else if (elapsedTimer) {
            clearInterval(elapsedTimer);
            elapsedTimer = null;
        }
    },
    { immediate: true },
);

onUnmounted(() => {
    if (elapsedTimer) {
        clearInterval(elapsedTimer);
        elapsedTimer = null;
    }
});

const elapsed = computed(() => {
    const mm = Math.floor(seconds.value / 60)
        .toString()
        .padStart(2, '0');
    const ss = (seconds.value % 60).toString().padStart(2, '0');
    return `${mm}:${ss}`;
});

const showToast = (message: string) => {
    toast.value = { message };
    if (toastTimer) clearTimeout(toastTimer);
    toastTimer = setTimeout(() => {
        toast.value = null;
    }, 2400);
};

const onCtrlToggle = (key: 'mic' | 'cam' | 'screen' | 'captions' | 'hand' | 'more') => {
    if (key === 'mic') {
        emit('toggleMic');
        showToast(props.isMicEnabled ? 'Microfone desligado' : 'Microfone ligado');
        return;
    }
    if (key === 'cam') {
        emit('toggleCamera');
        showToast(props.isCameraEnabled ? 'Câmera desligada' : 'Câmera ligada');
        return;
    }
    if (key === 'screen') {
        screenSharing.value = !screenSharing.value;
        showToast(screenSharing.value ? 'Você está compartilhando a tela' : 'Compartilhamento encerrado');
        return;
    }
    if (key === 'captions') {
        showCaptions.value = !showCaptions.value;
        showToast(showCaptions.value ? 'Legendas em tempo real ativadas' : 'Legendas desativadas');
        return;
    }
    if (key === 'hand') {
        handRaised.value = !handRaised.value;
        showToast(handRaised.value ? 'Você levantou a mão' : 'Mão abaixada');
        return;
    }
    showToast('Mais opções');
};

const { documents: callDocuments } = useCallSharedDocuments({
    isInCall: () => props.isInCall,
    appointmentId: () => props.appointmentId,
    initialDocuments: () => props.sharedDocuments,
    hiddenVisibility: 'patient',
    onNewDocument: (doc) => showToast(`Documento disponível na consulta: ${doc.name}`),
});

const sharedFiles = computed<ConsultSharedFile[]>(() =>
    callDocuments.value.map((doc) => ({
        id: doc.id,
        name: doc.name,
        size: formatCallDocumentSize(doc.file_size),
        from: doc.visibility === 'shared' ? 'Compartilhado' : 'Prontuário',
        when: formatCallDocumentTime(doc.created_at),
        kind: doc.file_type?.startsWith('image/') ? 'img' : 'pdf',
        downloadUrl:
            doc.download_url ??
            (props.patientId ? doctorMedicalRecordDocuments.download.url({ patient: props.patientId, document: doc.id }) : undefined),
    })),
);

const openMedicalRecord = (tab?: string) => {
    if (!props.patientId) {
        showToast('Disponível apenas em chamadas com consulta vinculada');
        return;
    }
    const query = tab ? `?tab=${tab}` : '';
    window.open(`/doctor/patients/${props.patientId}/medical-record${query}`, '_blank', 'noopener,noreferrer');
};

// Ações rápidas abrem modal dentro da chamada (D2) — sem nova aba, sem derrubar streams
const documentModalKind = ref<InCallDocumentKind | null>(null);

const onNotesAction = (kind: InCallDocumentKind) => {
    if (!props.patientId || !props.appointmentId) {
        showToast('Disponível apenas em chamadas com consulta vinculada');
        return;
    }
    documentModalKind.value = kind;
};

const onDocumentIssued = (kind: InCallDocumentKind) => {
    documentModalKind.value = null;
    const labels: Record<InCallDocumentKind, string> = {
        rx: 'Prescrição emitida',
        exam: 'Exame solicitado',
        certificate: 'Atestado emitido',
    };
    showToast(`${labels[kind]} — disponível no prontuário do paciente`);
};

const handleFullscreen = () => {
    roomRef.value?.requestFullscreen?.().catch(() => {});
};

const openEndModal = () => {
    endModalOpen.value = true;
};

const confirmEnd = () => {
    endModalOpen.value = false;
    showToast('Consulta encerrada');
    emit('end');
};
</script>

<template>
    <Teleport to="body">
        <div v-if="isInCall" ref="roomRef" class="fixed inset-0 z-[60] flex flex-col bg-[var(--stage-bg)]">
            <div class="dcv-doctor-consult app flex min-h-0 flex-1 flex-col" :class="{ 'side-closed': !sideOpen }">
                <div class="app-top shrink-0">
                    <DoctorConsultTopbar :patient-name="displayPatientName" :elapsed="elapsed" />
                </div>

                <div class="app-stage min-h-0">
                    <DoctorConsultVideoStage
                        :patient-name="displayPatientName"
                        :captions-on="showCaptions"
                        :side-open="sideOpen"
                        :local-stream="localStream"
                        :remote-streams="remoteStreams"
                        :is-mic-enabled="isMicEnabled"
                        :is-camera-enabled="isCameraEnabled"
                        @open-side="sideOpen = true"
                    />
                </div>

                <aside class="app-side min-h-0" :style="{ width: sideOpen ? `${panelWidth}px` : '0px' }">
                    <div class="side" :style="{ width: `${panelWidth}px` }">
                        <div class="side-header">
                            <div class="side-tabs">
                                <button
                                    v-for="t in TAB_DEFS"
                                    :key="t.id"
                                    type="button"
                                    class="side-tab"
                                    :class="{ active: tab === t.id }"
                                    @click="tab = t.id"
                                >
                                    <component :is="t.icon" class="h-3.5 w-3.5 shrink-0" />
                                    <span :style="{ display: panelWidth >= 400 ? 'inline' : 'none' }">{{ t.label }}</span>
                                    <span v-if="t.id === 'chat' && chatCount > 0" class="ct">{{ chatCount }}</span>
                                </button>
                            </div>
                            <button type="button" class="side-close" title="Fechar painel" @click="sideOpen = false">
                                <PanelRight class="h-4 w-4" />
                            </button>
                        </div>

                        <div class="side-body">
                            <DoctorConsultNotesPanel
                                v-if="tab === 'notes'"
                                :patient-id="patientId"
                                :appointment-id="appointmentId"
                                @action="onNotesAction"
                                @saved="showToast('Anotações salvas no prontuário')"
                            />
                            <DoctorConsultPatientPanel v-else-if="tab === 'patient'" :patient="patientForPanel" @open-history="openMedicalRecord()" />
                            <DoctorConsultChatPanel
                                v-else-if="tab === 'chat'"
                                :messages="messages"
                                :patient-first-name="patientFirstName"
                                @send="sendChatMessage"
                            />
                            <DoctorConsultFilesPanel
                                v-else-if="tab === 'files'"
                                :files="sharedFiles"
                                :patient-id="patientId"
                                :appointment-id="appointmentId"
                                @uploaded="showToast('Documento enviado ao paciente')"
                            />
                        </div>
                    </div>
                </aside>

                <div class="app-ctrl shrink-0">
                    <DoctorConsultControlsBar
                        :mic-on="isMicEnabled"
                        :cam-on="isCameraEnabled"
                        :screen-sharing="screenSharing"
                        :captions-on="showCaptions"
                        :hand-raised="handRaised"
                        :is-ending="isEnding"
                        @toggle="onCtrlToggle"
                        @end="openEndModal"
                        @fullscreen="handleFullscreen"
                    />
                </div>
            </div>

            <div v-if="toast" class="toast">{{ toast.message }}</div>

            <DoctorConsultEndModal :open="endModalOpen" @close="endModalOpen = false" @confirm-end="confirmEnd" />

            <DoctorVideoCallInCallModal
                v-if="documentModalKind && patientId && appointmentId"
                :open="!!documentModalKind"
                :kind="documentModalKind"
                :patient-id="patientId"
                :appointment-id="appointmentId"
                @close="documentModalKind = null"
                @issued="onDocumentIssued"
            />
        </div>
    </Teleport>
</template>
