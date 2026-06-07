import patientMedicalRecordRoutes from '@/routes/patient/medical-records';
import type { FilterState } from '@/types/medical-records';
import { useForm } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, ref } from 'vue';

export function useMedicalRecordExport(filtersState: FilterState) {
    const exportForm = useForm({
        search: filtersState.search,
        date_from: filtersState.date_from,
        date_to: filtersState.date_to,
    });

    const flashStatus = ref<string | null>(null);
    const exportError = ref<string | null>(null);
    const isExporting = ref(false);

    async function exportRecord() {
        exportForm.search = filtersState.search;
        exportForm.date_from = filtersState.date_from;
        exportForm.date_to = filtersState.date_to;

        flashStatus.value = null;
        exportError.value = null;
        isExporting.value = true;

        try {
            const response = await axios.post(
                patientMedicalRecordRoutes.export.url(),
                {
                    search: exportForm.search,
                    date_from: exportForm.date_from,
                    date_to: exportForm.date_to,
                },
                {
                    headers: {
                        Accept: 'application/json',
                    },
                },
            );

            flashStatus.value =
                typeof response.data?.message === 'string' ? response.data.message : 'Solicitação recebida. O PDF será gerado em segundo plano.';
        } catch (error: unknown) {
            if (axios.isAxiosError(error)) {
                if (error.response?.status === 429) {
                    exportError.value =
                        (error.response.data as { errors?: { export?: string[] }; message?: string } | undefined)?.errors?.export?.[0] ??
                        (error.response.data as { message?: string } | undefined)?.message ??
                        'Você atingiu o limite de exportação. Tente novamente em alguns minutos.';
                } else {
                    exportError.value =
                        (error.response?.data as { message?: string } | undefined)?.message ??
                        'Não foi possível solicitar a exportação agora. Tente novamente.';
                }
            } else {
                exportError.value = 'Não foi possível solicitar a exportação agora. Tente novamente.';
            }
        } finally {
            isExporting.value = false;
        }
    }

    return { isExporting: computed(() => exportForm.processing || isExporting.value), exportRecord, flashStatus, exportError };
}
