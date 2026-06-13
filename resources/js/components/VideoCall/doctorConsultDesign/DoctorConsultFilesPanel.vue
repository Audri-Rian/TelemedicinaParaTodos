<script setup lang="ts">
import type { ConsultSharedFile } from '@/components/VideoCall/doctorConsultDesign/doctorConsultDesignData';
import doctorMedicalRecordDocuments from '@/routes/doctor/patients/medical-record/documents';
import { useForm } from '@inertiajs/vue3';
import { Download, FileText, Loader2, RotateCcw, Upload } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps<{
    files: ConsultSharedFile[];
    patientId?: string | null;
    appointmentId?: string | null;
}>();

const emit = defineEmits<{ uploaded: [] }>();

const fileInput = ref<HTMLInputElement | null>(null);
const dragOver = ref(false);
// Mantém o último arquivo para permitir "tentar novamente" em caso de falha de rede.
const lastFile = ref<File | null>(null);

const MAX_FILE_BYTES = 10 * 1024 * 1024;
const ALLOWED_MIME_TYPES = ['application/pdf', 'image/jpeg', 'image/png'];

const form = useForm<{
    file: File | null;
    category: string;
    name: string;
    description: string;
    visibility: string;
    appointment_id: string;
}>({
    file: null,
    category: 'other',
    name: '',
    description: '',
    visibility: 'shared',
    appointment_id: '',
});

const canUpload = computed(() => Boolean(props.patientId && props.appointmentId));

const submit = (file: File) => {
    if (!props.patientId || !props.appointmentId || form.processing) return;

    if (file.size > MAX_FILE_BYTES) {
        form.setError('file', 'Arquivo deve ter no máximo 10 MB.');
        return;
    }
    if (!ALLOWED_MIME_TYPES.includes(file.type)) {
        form.setError('file', 'Apenas PDF, JPEG e PNG são permitidos.');
        return;
    }

    form.clearErrors();
    form.file = file;
    form.appointment_id = props.appointmentId;
    lastFile.value = file;

    // preserveState evita remontar a página da chamada (streams continuam vivos); a lista atualiza via broadcast
    form.post(doctorMedicalRecordDocuments.store.url({ patient: props.patientId }), {
        forceFormData: true,
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            form.reset();
            lastFile.value = null;
            emit('uploaded');
        },
        onError: (errors) => {
            // Falha de rede sem erro de validação específico: mensagem amigável genérica.
            if (!errors.file) {
                form.setError('file', 'Falha ao enviar o documento. Tente novamente.');
            }
        },
    });
};

const retryUpload = () => {
    if (lastFile.value) submit(lastFile.value);
};

const onFileChange = (event: Event) => {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0];
    if (file) submit(file);
    input.value = '';
};

const onDrop = (event: DragEvent) => {
    dragOver.value = false;
    if (!canUpload.value) return;
    const file = event.dataTransfer?.files?.[0];
    if (file) submit(file);
};

const downloadFile = (file: ConsultSharedFile) => {
    if (file.downloadUrl) window.location.href = file.downloadUrl;
};
</script>

<template>
    <div class="files">
        <input ref="fileInput" type="file" accept="application/pdf,image/jpeg,image/png" style="display: none" @change="onFileChange" />

        <div
            style="
                font-size: 12px;
                color: var(--muted-foreground);
                padding: 4px 2px 8px;
                display: flex;
                justify-content: space-between;
                align-items: center;
            "
        >
            <span>{{ files.length }} {{ files.length === 1 ? 'arquivo' : 'arquivos' }} · consulta atual</span>
            <button
                type="button"
                class="pat-add"
                :disabled="!canUpload || form.processing"
                :title="canUpload ? 'Compartilhar arquivo com o paciente' : 'Disponível apenas em chamadas com consulta vinculada'"
                @click="fileInput?.click()"
            >
                <Loader2 v-if="form.processing" class="mr-1 inline h-3 w-3 animate-spin" />
                <Upload v-else class="mr-1 inline h-3 w-3" />
                {{ form.processing ? 'Enviando…' : 'Enviar' }}
            </button>
        </div>

        <div v-if="form.progress" style="padding: 0 2px 8px">
            <div style="height: 4px; border-radius: 999px; background: var(--muted); overflow: hidden">
                <div
                    style="height: 100%; background: var(--primary, #0f766e); transition: width 0.2s ease"
                    :style="{ width: `${form.progress.percentage ?? 0}%` }"
                />
            </div>
            <div style="font-size: 11px; color: var(--muted-foreground); margin-top: 4px">Enviando… {{ form.progress.percentage ?? 0 }}%</div>
        </div>

        <div
            v-if="form.errors.file"
            style="
                font-size: 12px;
                color: var(--destructive, #dc2626);
                padding: 0 2px 8px;
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 8px;
            "
        >
            <span>{{ form.errors.file }}</span>
            <button v-if="lastFile && !form.processing" type="button" class="pat-add" @click="retryUpload">
                <RotateCcw class="mr-1 inline h-3 w-3" />
                Tentar novamente
            </button>
        </div>

        <div v-if="files.length === 0" style="padding: 14px; text-align: center; color: var(--muted-foreground); font-size: 12.5px">
            Nenhum documento ainda — envie um arquivo para compartilhar com o paciente durante a consulta.
        </div>

        <div v-for="f in files" :key="f.id" class="file-row">
            <div :class="'file-icon ' + (f.kind === 'img' ? 'img' : 'pdf')">
                <FileText class="h-4 w-4" />
            </div>
            <div style="min-width: 0; flex: 1">
                <div class="file-name" style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap">{{ f.name }}</div>
                <div class="file-meta">{{ f.size }} · {{ f.from }} · {{ f.when }}</div>
            </div>
            <button type="button" class="file-dl" title="Baixar" :disabled="!f.downloadUrl" @click="downloadFile(f)">
                <Download class="h-3.5 w-3.5" />
            </button>
        </div>

        <div
            class="files-drop"
            :style="{ opacity: canUpload ? 1 : 0.5, borderColor: dragOver ? 'var(--primary)' : undefined }"
            @dragover.prevent="dragOver = canUpload"
            @dragleave="dragOver = false"
            @drop.prevent="onDrop"
        >
            <template v-if="canUpload">
                Arraste arquivos aqui para compartilhar com o paciente<br />
                <span style="font-size: 11px">PDF, JPG, PNG · até 10 MB</span>
            </template>
            <template v-else> Compartilhamento disponível apenas em chamadas com consulta vinculada </template>
        </div>
    </div>
</template>
