import patientMedicalRecordRoutes from '@/routes/patient/medical-records';
import type { FilterState } from '@/types/medical-records';
import { useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

export function useMedicalRecordExport(filtersState: FilterState) {
    const page = usePage();

    const exportForm = useForm({
        search: filtersState.search,
        date_from: filtersState.date_from,
        date_to: filtersState.date_to,
    });

    const flashStatus = computed(() => (page.props.flash as Record<string, unknown>)?.status ?? null);
    const exportError = computed(() => (page.props.errors as Record<string, unknown>)?.export ?? null);

    function exportRecord() {
        exportForm.search = filtersState.search;
        exportForm.date_from = filtersState.date_from;
        exportForm.date_to = filtersState.date_to;

        exportForm.post(patientMedicalRecordRoutes.export.url(), {
            preserveScroll: true,
            onSuccess: () => exportForm.reset(),
        });
    }

    return { exportForm, exportRecord, flashStatus, exportError };
}
