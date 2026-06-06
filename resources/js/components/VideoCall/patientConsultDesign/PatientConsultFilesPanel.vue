<script setup lang="ts">
import type { PatientConsultSharedFile } from '@/components/VideoCall/patientConsultDesign/patientConsultDesignData';
import { Download, FileText } from 'lucide-vue-next';

defineProps<{
    files: PatientConsultSharedFile[];
}>();

const downloadFile = (file: PatientConsultSharedFile) => {
    if (file.downloadUrl) window.location.href = file.downloadUrl;
};
</script>

<template>
    <div class="files">
        <div style="font-size: 12px; color: var(--muted-foreground); padding: 4px 2px 8px">
            <span>{{ files.length }} {{ files.length === 1 ? 'arquivo' : 'arquivos' }} · consulta atual</span>
        </div>

        <div v-if="files.length === 0" style="padding: 14px; text-align: center; color: var(--muted-foreground); font-size: 12.5px">
            Documentos que o médico compartilhar durante a consulta aparecem aqui.
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
    </div>
</template>
