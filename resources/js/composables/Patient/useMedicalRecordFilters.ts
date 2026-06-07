import * as patientRoutes from '@/routes/patient';
import type { FilterState } from '@/types/medical-records';
import { router } from '@inertiajs/vue3';
import { computed, reactive } from 'vue';

export function useMedicalRecordFilters(initialFilters?: Record<string, unknown>) {
    let debounceTimer: ReturnType<typeof setTimeout> | null = null;
    const filtersState = reactive<FilterState>({
        search: (initialFilters?.search as string) ?? '',
        date_from: (initialFilters?.date_from as string) ?? '',
        date_to: (initialFilters?.date_to as string) ?? '',
    });

    const hasFilters = computed(() => Boolean(filtersState.search || filtersState.date_from || filtersState.date_to));

    const emptyText = computed(() =>
        hasFilters.value ? 'Nenhum registro encontrado com os filtros atuais.' : 'Nenhum registro disponível nesta seção.',
    );

    function applyFilters() {
        router.get(
            patientRoutes.medicalRecords.url(),
            {
                search: filtersState.search,
                date_from: filtersState.date_from,
                date_to: filtersState.date_to,
            },
            { preserveScroll: true, preserveState: true, replace: true },
        );
    }

    function applyFiltersDebounced(delayMs = 400) {
        if (debounceTimer) clearTimeout(debounceTimer);
        debounceTimer = setTimeout(applyFilters, delayMs);
    }

    function clearFilters() {
        filtersState.search = '';
        filtersState.date_from = '';
        filtersState.date_to = '';
        applyFilters();
    }

    function syncFromProps(filters?: Record<string, unknown>) {
        filtersState.search = (filters?.search as string) ?? '';
        filtersState.date_from = (filters?.date_from as string) ?? '';
        filtersState.date_to = (filters?.date_to as string) ?? '';
    }

    return { filtersState, hasFilters, emptyText, applyFilters, applyFiltersDebounced, clearFilters, syncFromProps };
}
