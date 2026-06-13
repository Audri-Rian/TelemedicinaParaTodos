<script setup lang="ts">
import type { PatientConsultSharedFile } from '@/components/VideoCall/patientConsultDesign/patientConsultDesignData';
import { Download, Eye, FileText, X } from 'lucide-vue-next';
import { ref } from 'vue';

defineProps<{
    files: PatientConsultSharedFile[];
}>();

const preview = ref<PatientConsultSharedFile | null>(null);

const openPreview = (file: PatientConsultSharedFile) => {
    if (file.viewUrl || file.downloadUrl) preview.value = file;
};

const closePreview = () => {
    preview.value = null;
};

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
            Nenhum documento ainda — o médico pode enviar documentos durante a consulta.
        </div>

        <div v-for="f in files" :key="f.id" class="file-row">
            <button
                type="button"
                :class="'file-icon ' + (f.kind === 'img' ? 'img' : 'pdf')"
                style="cursor: pointer; border: none"
                title="Visualizar"
                @click="openPreview(f)"
            >
                <FileText class="h-4 w-4" />
            </button>
            <div style="min-width: 0; flex: 1">
                <div class="file-name" style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap">{{ f.name }}</div>
                <div class="file-meta">{{ f.size }} · {{ f.from }} · {{ f.when }}</div>
            </div>
            <button type="button" class="file-dl" title="Visualizar" :disabled="!f.viewUrl && !f.downloadUrl" @click="openPreview(f)">
                <Eye class="h-3.5 w-3.5" />
            </button>
            <button type="button" class="file-dl" title="Baixar" :disabled="!f.downloadUrl" @click="downloadFile(f)">
                <Download class="h-3.5 w-3.5" />
            </button>
        </div>

        <Teleport to="body">
            <div v-if="preview" class="fixed inset-0 z-[80] flex flex-col bg-black/80 p-4" @click.self="closePreview">
                <div class="mx-auto flex h-full w-full max-w-4xl flex-col overflow-hidden rounded-xl bg-white">
                    <div class="flex items-center justify-between gap-3 border-b border-gray-200 px-4 py-3">
                        <p class="truncate text-sm font-semibold text-gray-900">{{ preview.name }}</p>
                        <div class="flex items-center gap-2">
                            <button
                                v-if="preview.downloadUrl"
                                type="button"
                                class="inline-flex items-center gap-1.5 rounded-lg bg-teal-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-teal-700"
                                @click="downloadFile(preview)"
                            >
                                <Download class="h-3.5 w-3.5" />
                                Baixar
                            </button>
                            <button
                                type="button"
                                class="grid h-8 w-8 place-items-center rounded-lg text-gray-500 hover:bg-gray-100"
                                title="Fechar"
                                @click="closePreview"
                            >
                                <X class="h-4 w-4" />
                            </button>
                        </div>
                    </div>
                    <div class="min-h-0 flex-1 bg-gray-50">
                        <img
                            v-if="preview.kind === 'img'"
                            :src="preview.viewUrl ?? preview.downloadUrl"
                            :alt="preview.name"
                            class="mx-auto h-full w-full object-contain"
                        />
                        <iframe v-else-if="preview.viewUrl" :src="preview.viewUrl" class="h-full w-full border-0" :title="preview.name" />
                        <div v-else class="flex h-full flex-col items-center justify-center gap-3 p-8 text-center text-sm text-gray-500">
                            <FileText class="h-10 w-10 text-gray-300" />
                            Pré-visualização indisponível. Use o botão Baixar para abrir o documento.
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>
