<script setup lang="ts">
import DoctorConsultChatPanel from '@/components/VideoCall/doctorConsultDesign/DoctorConsultChatPanel.vue';
import DoctorConsultControlsBar from '@/components/VideoCall/doctorConsultDesign/DoctorConsultControlsBar.vue';
import {
    MOCK_CONSULT_PATIENT,
    MOCK_INITIAL_CHAT,
    MOCK_SHARED_FILES,
    type ConsultChatMessage,
    type ConsultPatient,
} from '@/components/VideoCall/doctorConsultDesign/doctorConsultDesignData';
import DoctorConsultEndModal from '@/components/VideoCall/doctorConsultDesign/DoctorConsultEndModal.vue';
import DoctorConsultFilesPanel from '@/components/VideoCall/doctorConsultDesign/DoctorConsultFilesPanel.vue';
import DoctorConsultNotesPanel from '@/components/VideoCall/doctorConsultDesign/DoctorConsultNotesPanel.vue';
import DoctorConsultPatientPanel from '@/components/VideoCall/doctorConsultDesign/DoctorConsultPatientPanel.vue';
import DoctorConsultTopbar from '@/components/VideoCall/doctorConsultDesign/DoctorConsultTopbar.vue';
import DoctorConsultVideoStage from '@/components/VideoCall/doctorConsultDesign/DoctorConsultVideoStage.vue';
import { FileText, MessageSquare, NotebookPen, PanelRight, User } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

import '../../../css/doctor-consult-video-call-design.css';

const props = withDefaults(
    defineProps<{
        isInCall: boolean;
        localStream: MediaStream | null;
        remoteStreams: Map<string, MediaStream>;
        isMicEnabled: boolean;
        isCameraEnabled: boolean;
        isEnding: boolean;
        patientDisplayName?: string | null;
    }>(),
    { patientDisplayName: null },
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
const showRecording = ref(true);

const screenSharing = ref(false);
const handRaised = ref(false);

const messages = ref<ConsultChatMessage[]>([...MOCK_INITIAL_CHAT]);
const endModalOpen = ref(false);
const toast = ref<{ message: string } | null>(null);
let toastTimer: ReturnType<typeof setTimeout> | null = null;

const roomRef = ref<HTMLElement | null>(null);

const displayPatientName = computed(() => props.patientDisplayName?.trim() || MOCK_CONSULT_PATIENT.name);

const patientForPanel = computed<ConsultPatient>(() => ({
    ...MOCK_CONSULT_PATIENT,
    name: displayPatientName.value,
    initials:
        displayPatientName.value
            .split(/\s+/)
            .filter(Boolean)
            .map((w) => w[0])
            .slice(0, 2)
            .join('')
            .toUpperCase() || 'P',
}));

const patientFirstName = computed(() => displayPatientName.value.split(/\s+/)[0] || displayPatientName.value);

const chatCount = computed(() => messages.value.filter((m) => m.type !== 'system').length);

const seconds = ref(14 * 60 + 32);
let elapsedTimer: ReturnType<typeof setInterval> | null = null;

watch(
    () => props.isInCall,
    (v) => {
        if (v) {
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

const onNotesAction = (kind: 'rx' | 'exam' | 'certificate') => {
    if (kind === 'rx') showToast('Abrindo prescrição digital…');
    if (kind === 'exam') showToast('Abrindo solicitação de exames…');
    if (kind === 'certificate') showToast('Abrindo atestado médico…');
};

const onToggleRecording = () => {
    showRecording.value = !showRecording.value;
    showToast(!showRecording.value ? 'Gravação pausada' : 'Gravação iniciada · com consentimento');
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
                    <DoctorConsultTopbar
                        :patient-name="displayPatientName"
                        :elapsed="elapsed"
                        :recording="showRecording"
                        @toggle-recording="onToggleRecording"
                    />
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
                            <DoctorConsultNotesPanel v-if="tab === 'notes'" @action="onNotesAction" />
                            <DoctorConsultPatientPanel
                                v-else-if="tab === 'patient'"
                                :patient="patientForPanel"
                                @open-history="showToast('Abrindo histórico completo…')"
                            />
                            <DoctorConsultChatPanel v-else-if="tab === 'chat'" v-model:messages="messages" :patient-first-name="patientFirstName" />
                            <DoctorConsultFilesPanel v-else-if="tab === 'files'" :files="MOCK_SHARED_FILES" />
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
                        :recording="showRecording"
                        :is-ending="isEnding"
                        @toggle="onCtrlToggle"
                        @end="openEndModal"
                        @fullscreen="handleFullscreen"
                    />
                </div>
            </div>

            <div v-if="toast" class="toast">{{ toast.message }}</div>

            <DoctorConsultEndModal :open="endModalOpen" @close="endModalOpen = false" @confirm-end="confirmEnd" />
        </div>
    </Teleport>
</template>
