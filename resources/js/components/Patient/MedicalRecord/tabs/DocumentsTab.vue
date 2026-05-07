<script setup lang="ts">
import EmptyBlock from '@/components/Patient/MedicalRecord/EmptyBlock.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { useMedicalRecordDocument } from '@/composables/Patient/useMedicalRecordDocument';
import { useFormatters } from '@/composables/useFormatters';
import patientMedicalRecordRoutes from '@/routes/patient/medical-records';
import type { Appointment, MedicalDocument } from '@/types/medical-records';
import { Link } from '@inertiajs/vue3';
import { Download, Loader2, Upload } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
    documents: MedicalDocument[];
    consultations: Appointment[];
    emptyText: string;
}>();

const { formatStatus, formatFileSize } = useFormatters();
const consultationsRef = computed(() => props.consultations);
const { documentForm, appointmentOptions, submitDocument, handleFileChange } = useMedicalRecordDocument(consultationsRef);

const documentCategories = [
    { id: 'exam', label: 'Exame' },
    { id: 'prescription', label: 'Prescrição' },
    { id: 'report', label: 'Relatório' },
    { id: 'other', label: 'Outro' },
];

const visibilityOptions = [
    { id: 'patient', label: 'Paciente' },
    { id: 'shared', label: 'Compartilhado' },
];
</script>

<template>
    <div class="grid gap-5 xl:grid-cols-[360px_minmax(0,1fr)]">
        <form class="rounded-lg border border-[#dde5ea] bg-[#f7f8f9] p-4" @submit.prevent="submitDocument">
            <h2 class="text-lg font-black text-gray-950">Enviar documento</h2>
            <p class="mt-1 text-sm font-semibold text-gray-500">Anexe laudos, relatórios ou arquivos complementares.</p>

            <div class="mt-4 space-y-3">
                <label class="block">
                    <span class="text-sm font-black text-gray-700">Arquivo</span>
                    <input
                        accept="application/pdf,image/jpeg,image/png"
                        class="mt-2 block w-full text-sm font-semibold text-gray-700"
                        type="file"
                        @change="handleFileChange"
                    />
                    <p v-if="documentForm.errors.file" class="mt-1 text-xs font-semibold text-rose-700">
                        {{ documentForm.errors.file }}
                    </p>
                </label>

                <label class="block">
                    <span class="text-sm font-black text-gray-700">Nome</span>
                    <Input v-model="documentForm.name" class="mt-2 border-[#dde5ea]" placeholder="Nome do documento" />
                </label>

                <label class="block">
                    <span class="text-sm font-black text-gray-700">Categoria</span>
                    <select
                        v-model="documentForm.category"
                        class="mt-2 h-10 w-full rounded-lg border border-[#dde5ea] bg-white px-3 text-sm font-semibold"
                    >
                        <option v-for="category in documentCategories" :key="category.id" :value="category.id">
                            {{ category.label }}
                        </option>
                    </select>
                </label>

                <label class="block">
                    <span class="text-sm font-black text-gray-700">Visibilidade</span>
                    <select
                        v-model="documentForm.visibility"
                        class="mt-2 h-10 w-full rounded-lg border border-[#dde5ea] bg-white px-3 text-sm font-semibold"
                    >
                        <option v-for="option in visibilityOptions" :key="option.id" :value="option.id">
                            {{ option.label }}
                        </option>
                    </select>
                </label>

                <label class="block">
                    <span class="text-sm font-black text-gray-700">Consulta relacionada</span>
                    <select
                        v-model="documentForm.appointment_id"
                        class="mt-2 h-10 w-full rounded-lg border border-[#dde5ea] bg-white px-3 text-sm font-semibold"
                    >
                        <option value="">Não relacionar</option>
                        <option v-for="option in appointmentOptions" :key="option.id" :value="option.id">
                            {{ option.label }}
                        </option>
                    </select>
                </label>

                <label class="block">
                    <span class="text-sm font-black text-gray-700">Descrição</span>
                    <Textarea v-model="documentForm.description" class="mt-2 border-[#dde5ea]" :rows="3" />
                </label>

                <Button class="w-full bg-[#0f6e78] font-black text-white hover:bg-[#0a4f57]" :disabled="documentForm.processing">
                    <Loader2 v-if="documentForm.processing" class="mr-2 h-4 w-4 animate-spin" />
                    <Upload v-else class="mr-2 h-4 w-4" />
                    Enviar documento
                </Button>
            </div>
        </form>

        <div class="grid gap-3 md:grid-cols-2">
            <article v-for="document in documents" :key="document.id" class="rounded-lg border border-[#dde5ea] bg-white p-4">
                <h2 class="font-black text-gray-950">{{ document.name }}</h2>
                <p class="mt-1 text-sm font-semibold text-gray-500">
                    {{ formatStatus(document.category) }} · {{ formatFileSize(document.file_size) }}
                </p>
                <p v-if="document.description" class="mt-3 text-sm font-medium text-gray-600">{{ document.description }}</p>
                <Link
                    :href="patientMedicalRecordRoutes.documents.download.url({ document: document.id })"
                    class="mt-4 inline-flex items-center font-black text-[#0f6e78] hover:underline"
                >
                    <Download class="mr-1 h-4 w-4" />
                    Baixar arquivo
                </Link>
            </article>
            <EmptyBlock v-if="documents.length === 0" :text="emptyText" />
        </div>
    </div>
</template>
