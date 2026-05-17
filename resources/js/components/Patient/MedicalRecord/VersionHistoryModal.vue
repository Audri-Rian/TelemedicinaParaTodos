<script setup lang="ts">
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { useFormatters } from '@/composables/useFormatters';
import type { ClinicalRecordVersion, ClinicalVersionableType } from '@/types/medical-records';
import axios from 'axios';
import { History } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

const props = defineProps<{
    isOpen: boolean;
    patientId: string;
    recordType: ClinicalVersionableType;
    recordId: string;
    recordTitle?: string;
    /** 'doctor' shows full diff; 'patient' shows field names only (no sensitive values) */
    audience?: 'doctor' | 'patient';
}>();

const emit = defineEmits<{ close: [] }>();

const { formatDate } = useFormatters();

const versions = ref<ClinicalRecordVersion[]>([]);
const loading = ref(false);
const error = ref<string | null>(null);

const fieldLabels: Record<string, string> = {
    title: 'Título',
    content: 'Conteúdo',
    is_private: 'Privado',
    category: 'Categoria',
    tags: 'Etiquetas',
    medications: 'Medicamentos',
    instructions: 'Instruções',
    valid_until: 'Válido até',
    status: 'Status',
    type: 'Tipo',
    start_date: 'Data início',
    end_date: 'Data fim',
    days: 'Dias',
    reason: 'Motivo',
    restrictions: 'Restrições',
};

function labelFor(field: string): string {
    return fieldLabels[field] ?? field;
}

function formatValue(value: unknown): string {
    if (value === null || value === undefined) return '—';
    if (typeof value === 'boolean') return value ? 'Sim' : 'Não';
    if (Array.isArray(value)) return value.join(', ') || '—';
    if (typeof value === 'object') return JSON.stringify(value);
    return String(value);
}

const apiUrl = computed(() => {
    const audience = props.audience ?? 'doctor';
    if (audience === 'patient') {
        return `/patient/medical-records/${props.recordType}/${props.recordId}/versions`;
    }
    return `/doctor/patients/${props.patientId}/medical-record/${props.recordType}/${props.recordId}/versions`;
});

const showDiff = computed(() => (props.audience ?? 'doctor') === 'doctor');

async function fetchVersions(): Promise<void> {
    loading.value = true;
    error.value = null;
    try {
        const response = await axios.get<{ versions: ClinicalRecordVersion[] }>(apiUrl.value);
        versions.value = response.data.versions;
    } catch {
        error.value = 'Não foi possível carregar o histórico de versões.';
    } finally {
        loading.value = false;
    }
}

watch(
    () => props.isOpen,
    (open) => {
        if (open) {
            versions.value = [];
            fetchVersions();
        }
    },
);
</script>

<template>
    <Dialog
        :open="isOpen"
        @update:open="
            (v) => {
                if (!v) emit('close');
            }
        "
    >
        <DialogContent class="flex max-h-[85vh] flex-col sm:max-w-2xl">
            <DialogHeader class="shrink-0">
                <div class="flex items-center gap-3">
                    <div class="flex h-9 w-9 items-center justify-center rounded-full bg-[#e5f1f2]">
                        <History class="h-4 w-4 text-[#0f6e78]" />
                    </div>
                    <DialogTitle class="text-lg font-bold text-gray-900">
                        Histórico de alterações
                        <span v-if="recordTitle" class="block text-sm font-medium text-gray-500">{{ recordTitle }}</span>
                    </DialogTitle>
                </div>
            </DialogHeader>

            <div class="mt-4 min-h-0 flex-1 overflow-y-auto pr-1">
                <div v-if="loading" class="flex items-center justify-center py-12 text-sm text-gray-500">Carregando histórico...</div>

                <div v-else-if="error" class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                    {{ error }}
                </div>

                <div v-else-if="versions.length === 0" class="py-12 text-center text-sm text-gray-500">Nenhuma alteração registrada.</div>

                <ol v-else class="relative border-l border-[#dde5ea]">
                    <li v-for="version in [...versions].reverse()" :key="version.version_number" class="mb-6 ml-4">
                        <div class="absolute -left-1.5 mt-1.5 h-3 w-3 rounded-full border-2 border-white bg-[#0f6e78]" />

                        <div class="rounded-lg border border-[#dde5ea] bg-white p-4">
                            <div class="mb-3 flex items-center justify-between">
                                <span class="rounded-full bg-[#e5f1f2] px-2 py-0.5 text-xs font-black text-[#0f6e78]">
                                    v{{ version.version_number }}
                                </span>
                                <div class="text-right text-xs text-gray-500">
                                    <span class="font-semibold text-gray-700">{{ version.changed_by }}</span>
                                    · {{ formatDate(version.created_at) }}
                                </div>
                            </div>

                            <p v-if="version.change_reason" class="mb-3 text-xs text-gray-500 italic">"{{ version.change_reason }}"</p>

                            <template v-if="version.version_number === 1">
                                <p class="text-xs font-semibold text-gray-500">Criação do registro</p>
                            </template>

                            <!-- Doctor sees full diff; patient sees field names only -->
                            <template v-else-if="showDiff">
                                <div class="space-y-3">
                                    <div v-for="field in version.changed_fields" :key="field" class="rounded border border-[#dde5ea] text-xs">
                                        <div class="border-b border-[#dde5ea] bg-gray-50 px-3 py-1.5 font-semibold text-gray-700">
                                            {{ labelFor(field) }}
                                        </div>
                                        <div class="grid grid-cols-2 divide-x divide-[#dde5ea]">
                                            <div class="bg-red-50 px-3 py-2">
                                                <p class="mb-1 font-semibold text-red-500">Antes</p>
                                                <p class="break-words whitespace-pre-wrap text-gray-700">
                                                    {{ formatValue(version.old_values[field]) }}
                                                </p>
                                            </div>
                                            <div class="bg-green-50 px-3 py-2">
                                                <p class="mb-1 font-semibold text-green-600">Depois</p>
                                                <p class="break-words whitespace-pre-wrap text-gray-700">
                                                    {{ formatValue(version.new_values[field]) }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>

                            <!-- Patient: show only which fields changed, no values -->
                            <template v-else>
                                <p class="text-xs text-gray-500">
                                    Campos alterados:
                                    <span class="font-semibold text-gray-700">
                                        {{ version.changed_fields.map(labelFor).join(', ') }}
                                    </span>
                                </p>
                            </template>
                        </div>
                    </li>
                </ol>
            </div>
        </DialogContent>
    </Dialog>
</template>
