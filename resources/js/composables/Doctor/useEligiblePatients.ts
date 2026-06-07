import type { EligibleDocumentPatient } from '@/types/clinical-documents';
import { ref } from 'vue';

export function useEligiblePatients() {
    const patients = ref<EligibleDocumentPatient[]>([]);
    const relationshipDays = ref(10);
    const loading = ref(false);
    const error = ref<string | null>(null);

    async function load(): Promise<void> {
        loading.value = true;
        error.value = null;

        try {
            const response = await fetch('/doctor/patients/eligible-for-documents', {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
            });
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }
            const data = (await response.json()) as { patients: EligibleDocumentPatient[]; relationship_days: number };
            patients.value = data.patients ?? [];
            relationshipDays.value = data.relationship_days ?? 10;
        } catch {
            patients.value = [];
            error.value = 'Não foi possível carregar os pacientes elegíveis.';
        } finally {
            loading.value = false;
        }
    }

    return { patients, relationshipDays, loading, error, load };
}
