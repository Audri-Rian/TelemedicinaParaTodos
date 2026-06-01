<script setup lang="ts">
import PatientConsultChatPanel from '@/components/VideoCall/patientConsultDesign/PatientConsultChatPanel.vue';
import PatientConsultControlsBar from '@/components/VideoCall/patientConsultDesign/PatientConsultControlsBar.vue';
import type { PatientConsultChatMessage } from '@/components/VideoCall/patientConsultDesign/patientConsultDesignData';
import {
    MOCK_PATIENT_CHAT,
    MOCK_PATIENT_DOCTOR,
    MOCK_PATIENT_FILES,
    MOCK_PATIENT_MY_NOTES,
    MOCK_PATIENT_SHARED_ITEMS,
    type PatientConsultChecklistItem,
    type PatientConsultDoctor,
    type PatientConsultSharedItem,
} from '@/components/VideoCall/patientConsultDesign/patientConsultDesignData';
import PatientConsultDoctorPanel from '@/components/VideoCall/patientConsultDesign/PatientConsultDoctorPanel.vue';
import PatientConsultEndModal from '@/components/VideoCall/patientConsultDesign/PatientConsultEndModal.vue';
import PatientConsultFilesPanel from '@/components/VideoCall/patientConsultDesign/PatientConsultFilesPanel.vue';
import PatientConsultSummaryPanel from '@/components/VideoCall/patientConsultDesign/PatientConsultSummaryPanel.vue';
import PatientConsultTopbar from '@/components/VideoCall/patientConsultDesign/PatientConsultTopbar.vue';
import PatientConsultTweaksPanel from '@/components/VideoCall/patientConsultDesign/PatientConsultTweaksPanel.vue';
import PatientConsultVideoStage from '@/components/VideoCall/patientConsultDesign/PatientConsultVideoStage.vue';
import { FileText, MessageSquare, NotebookPen, PanelRight, Stethoscope } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

import '../../../css/patient-consult-video-call-design.css';

const props = withDefaults(
    defineProps<{
        isInCall: boolean;
        localStream: MediaStream | null;
        remoteStreams: Map<string, MediaStream>;
        isMicEnabled: boolean;
        isCameraEnabled: boolean;
        isEnding: boolean;
        doctorDisplayName?: string | null;
        patientDisplayName?: string | null;
        chiefComplaint?: string | null;
    }>(),
    { doctorDisplayName: null, patientDisplayName: null, chiefComplaint: null },
);

const emit = defineEmits<{ toggleMic: []; toggleCamera: []; end: [] }>();

const TAB_DEFS = [
    { id: 'summary' as const, label: 'Consulta', icon: NotebookPen },
    { id: 'doctor' as const, label: 'Médico', icon: Stethoscope },
    { id: 'chat' as const, label: 'Chat', icon: MessageSquare },
    { id: 'files' as const, label: 'Arquivos', icon: FileText },
];

const tab = ref<(typeof TAB_DEFS)[number]['id']>('summary');
const sideOpen = ref(true);
const panelWidth = ref(420);
const showCaptions = ref(false);
const showRecording = ref(true);
const stageView = ref<'doctor-main' | 'patient-main'>('doctor-main');
const accent = ref('#0f766e');

const screenSharing = ref(false);
const handRaised = ref(false);

const notes = ref<PatientConsultChecklistItem[]>([...MOCK_PATIENT_MY_NOTES]);
const sharedItems = ref<PatientConsultSharedItem[]>([...MOCK_PATIENT_SHARED_ITEMS]);
const messages = ref<PatientConsultChatMessage[]>([...MOCK_PATIENT_CHAT]);

const tweaksOpen = ref(false);
const endModalOpen = ref(false);
const toast = ref<{ message: string } | null>(null);
let toastTimer: ReturnType<typeof setTimeout> | null = null;

const roomRef = ref<HTMLElement | null>(null);

const doctor = computed<PatientConsultDoctor>(() => ({
    ...MOCK_PATIENT_DOCTOR,
    name: props.doctorDisplayName?.trim() || MOCK_PATIENT_DOCTOR.name,
    short: (props.doctorDisplayName?.trim() || MOCK_PATIENT_DOCTOR.short).split(/\s+/).slice(0, 2).join(' '),
}));

const patientName = computed(() => props.patientDisplayName?.trim() || 'Você');
const complaint = computed(() => props.chiefComplaint?.trim() || 'Relata dor de cabeça há 6 dias, com piora ao fim do dia e fotofobia leve.');

const chatCount = computed(() => messages.value.filter((m) => m.type !== 'system').length);

const seconds = ref(14 * 60 + 32);
let elapsedTimer: ReturnType<typeof setInterval> | null = null;

watch(
    () => props.isInCall,
    (v) => {
        if (v) elapsedTimer = setInterval(() => (seconds.value += 1), 1000);
        else if (elapsedTimer) {
            clearInterval(elapsedTimer);
            elapsedTimer = null;
        }
    },
    { immediate: true },
);

const elapsed = computed(() => {
    const mm = Math.floor(seconds.value / 60)
        .toString()
        .padStart(2, '0');
    const ss = (seconds.value % 60).toString().padStart(2, '0');
    return `${mm}:${ss}`;
});

const shellStyle = computed(() => ({ '--primary': accent.value }));

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
        showToast(showCaptions.value ? 'Legendas ativadas' : 'Legendas desativadas');
        return;
    }
    if (key === 'hand') {
        handRaised.value = !handRaised.value;
        showToast(handRaised.value ? 'Você levantou a mão' : 'Mão abaixada');
        return;
    }
    showToast('Mais opções');
};

const onSummaryAction = (kind: 'download' | 'view' | 'repeat' | 'doubt') => {
    if (kind === 'download') showToast('Baixando documento…');
    if (kind === 'view') showToast('Abrindo documento…');
    if (kind === 'repeat') showToast('Sinalizado: pode repetir, por favor?');
    if (kind === 'doubt') {
        handRaised.value = true;
        showToast('Você levantou uma dúvida');
    }
};

const handleFullscreen = () => {
    roomRef.value?.requestFullscreen?.().catch(() => {});
};

const confirmEnd = () => {
    endModalOpen.value = false;
    showToast('Você saiu da consulta');
    emit('end');
};
</script>

<template>
    <Teleport to="body">
        <div v-if="isInCall" ref="roomRef" class="fixed inset-0 z-[60] flex flex-col bg-[var(--stage-bg)]" :style="shellStyle">
            <div class="pcv-patient-consult app flex min-h-0 flex-1 flex-col" :class="{ 'side-closed': !sideOpen }">
                <div class="app-top shrink-0">
                    <PatientConsultTopbar :doctor="doctor" :elapsed="elapsed" :recording="showRecording" @open-tweaks="tweaksOpen = true" />
                </div>

                <div class="app-stage min-h-0">
                    <PatientConsultVideoStage
                        :doctor-name="doctor.name"
                        :doctor-short="doctor.short"
                        :patient-name="patientName"
                        :stage-view="stageView"
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
                                    <span v-if="t.id === 'summary' && sharedItems.length > 0" class="ct">{{ sharedItems.length }}</span>
                                    <span v-if="t.id === 'chat' && chatCount > 0" class="ct">{{ chatCount }}</span>
                                </button>
                            </div>
                            <button type="button" class="side-close" title="Fechar painel" @click="sideOpen = false">
                                <PanelRight class="h-4 w-4" />
                            </button>
                        </div>

                        <div class="side-body">
                            <PatientConsultSummaryPanel
                                v-if="tab === 'summary'"
                                :items="sharedItems"
                                :notes="notes"
                                :complaint="complaint"
                                @update:notes="notes = $event"
                                @action="onSummaryAction"
                            />
                            <PatientConsultDoctorPanel v-else-if="tab === 'doctor'" :doctor="doctor" @book="showToast('Abrindo agenda do médico…')" />
                            <PatientConsultChatPanel v-else-if="tab === 'chat'" v-model:messages="messages" :patient-first-name="doctor.short" />
                            <PatientConsultFilesPanel v-else-if="tab === 'files'" :files="MOCK_PATIENT_FILES" />
                        </div>
                    </div>
                </aside>

                <div class="app-ctrl shrink-0">
                    <PatientConsultControlsBar
                        :mic-on="isMicEnabled"
                        :cam-on="isCameraEnabled"
                        :screen-sharing="screenSharing"
                        :captions-on="showCaptions"
                        :hand-raised="handRaised"
                        :recording="showRecording"
                        :is-ending="isEnding"
                        @toggle="onCtrlToggle"
                        @end="endModalOpen = true"
                        @fullscreen="handleFullscreen"
                        @open-settings="tweaksOpen = true"
                    />
                </div>
            </div>

            <div v-if="toast" class="toast">{{ toast.message }}</div>

            <PatientConsultEndModal :open="endModalOpen" @close="endModalOpen = false" @confirm-end="confirmEnd" />

            <PatientConsultTweaksPanel
                :open="tweaksOpen"
                :panel-width="panelWidth"
                :show-captions="showCaptions"
                :show-recording="showRecording"
                :stage-view="stageView"
                :accent="accent"
                @close="tweaksOpen = false"
                @update:panel-width="panelWidth = $event"
                @update:show-captions="showCaptions = $event"
                @update:show-recording="showRecording = $event"
                @update:stage-view="stageView = $event"
                @update:accent="accent = $event"
            />
        </div>
    </Teleport>
</template>
