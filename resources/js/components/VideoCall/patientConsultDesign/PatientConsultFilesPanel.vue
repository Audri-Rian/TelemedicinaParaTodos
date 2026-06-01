<script setup lang="ts">
import type { ConsultSharedFile } from '@/components/VideoCall/patientConsultDesign/patientConsultDesignData';
import { Download, FileText, Upload } from 'lucide-vue-next';

defineProps<{
    files: ConsultSharedFile[];
}>();
</script>

<template>
    <div class="files">
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
            <span>{{ files.length }} arquivos · sessão atual + prontuário</span>
            <button type="button" class="pat-add">
                <Upload class="mr-1 inline h-3 w-3" />
                Enviar
            </button>
        </div>

        <div v-for="f in files" :key="f.id" class="file-row">
            <div :class="'file-icon ' + (f.kind === 'img' ? 'img' : 'pdf')">
                <FileText class="h-4 w-4" />
            </div>
            <div style="min-width: 0; flex: 1">
                <div class="file-name" style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap">{{ f.name }}</div>
                <div class="file-meta">{{ f.size }} · {{ f.from }} · {{ f.when }}</div>
            </div>
            <button type="button" class="file-dl" title="Baixar">
                <Download class="h-3.5 w-3.5" />
            </button>
        </div>

        <div class="files-drop">
            Arraste arquivos aqui para compartilhar com o médico<br />
            <span style="font-size: 11px">PDF, JPG, PNG · até 25 MB</span>
        </div>
    </div>
</template>
