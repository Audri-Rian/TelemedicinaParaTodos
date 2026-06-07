<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

interface DocumentPayload {
    id: string;
    issued_at?: string | null;
    valid_until?: string | null;
    type?: string;
    start_date?: string | null;
    end_date?: string | null;
    days?: number;
    status: string;
    signature_status: string;
    signed_at?: string | null;
    doctor_name?: string | null;
    doctor_crm?: string | null;
    patient_name?: string | null;
}

interface Props {
    verificationCode: string;
    documentType: 'prescription' | 'certificate';
    hasLegalValidity: boolean;
    valid: boolean;
    document: DocumentPayload;
}

const props = defineProps<Props>();

const documentLabel = computed(() => (props.documentType === 'prescription' ? 'Prescrição médica' : 'Atestado médico'));

const validityBadge = computed(() => {
    if (!props.valid) {
        return { text: 'INVÁLIDO', color: 'bg-red-100 text-red-800 border-red-200' };
    }
    if (!props.hasLegalValidity) {
        return {
            text: 'AUTÊNTICO (sem validade legal ICP-Brasil)',
            color: 'bg-yellow-100 text-yellow-800 border-yellow-200',
        };
    }
    return { text: 'AUTÊNTICO', color: 'bg-green-100 text-green-800 border-green-200' };
});

const formatDate = (iso?: string | null) => {
    if (!iso) return '—';
    return new Date(iso).toLocaleString('pt-BR');
};
</script>

<template>
    <Head title="Verificação de Documento" />
    <div class="min-h-screen bg-gray-50 px-4 py-12 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-2xl">
            <div class="rounded-lg bg-white p-8 shadow">
                <h1 class="mb-2 text-2xl font-bold text-gray-900">Verificação de Documento</h1>
                <p class="mb-6 text-sm text-gray-500">
                    Código: <span class="font-mono">{{ verificationCode }}</span>
                </p>

                <div class="mb-6 rounded-md border px-4 py-3 text-sm font-medium" :class="validityBadge.color">
                    {{ validityBadge.text }}
                </div>

                <dl class="divide-y divide-gray-200 text-sm">
                    <div class="grid grid-cols-3 gap-2 py-2">
                        <dt class="text-gray-500">Tipo</dt>
                        <dd class="col-span-2 font-medium">{{ documentLabel }}</dd>
                    </div>
                    <div class="grid grid-cols-3 gap-2 py-2">
                        <dt class="text-gray-500">Médico</dt>
                        <dd class="col-span-2">
                            {{ document.doctor_name || '—' }}
                            <span v-if="document.doctor_crm" class="text-gray-500"> · CRM {{ document.doctor_crm }}</span>
                        </dd>
                    </div>
                    <div v-if="documentType === 'prescription'" class="grid grid-cols-3 gap-2 py-2">
                        <dt class="text-gray-500">Emitido em</dt>
                        <dd class="col-span-2">{{ formatDate(document.issued_at) }}</dd>
                    </div>
                    <div v-if="documentType === 'prescription'" class="grid grid-cols-3 gap-2 py-2">
                        <dt class="text-gray-500">Válido até</dt>
                        <dd class="col-span-2">{{ document.valid_until || '—' }}</dd>
                    </div>
                    <div v-if="documentType === 'certificate'" class="grid grid-cols-3 gap-2 py-2">
                        <dt class="text-gray-500">Período</dt>
                        <dd class="col-span-2">
                            {{ document.start_date || '—' }}
                            <span v-if="document.end_date"> a {{ document.end_date }}</span>
                            <span v-if="document.days"> ({{ document.days }} dias)</span>
                        </dd>
                    </div>
                    <div class="grid grid-cols-3 gap-2 py-2">
                        <dt class="text-gray-500">Assinado em</dt>
                        <dd class="col-span-2">{{ formatDate(document.signed_at) }}</dd>
                    </div>
                </dl>

                <div v-if="!hasLegalValidity" class="mt-6 border-t pt-4 text-xs text-gray-500">
                    Este ambiente utiliza driver de assinatura sem certificação ICP-Brasil. Documentos emitidos aqui não têm validade legal perante o
                    CFM (Resolução 2.314/2022, Art. 8). Para uso oficial, contratar provedor e configurar SIGNATURE_DRIVER=icp_brasil.
                </div>
            </div>
        </div>
    </div>
</template>
