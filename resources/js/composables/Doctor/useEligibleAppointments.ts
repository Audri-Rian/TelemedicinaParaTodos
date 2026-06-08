import type { EligibleAppointment } from '@/types/clinical-documents';
import { ref, type Ref } from 'vue';

export function useEligibleAppointments(patientId: Ref<string | null | undefined>) {
    const appointments = ref<EligibleAppointment[]>([]);
    const loading = ref(false);
    const error = ref<string | null>(null);

    async function load(): Promise<void> {
        if (!patientId.value) {
            appointments.value = [];
            return;
        }

        loading.value = true;
        error.value = null;

        try {
            const response = await fetch(`/doctor/patients/${patientId.value}/appointments/eligible-for-documents`, {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
            });
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }
            const data = (await response.json()) as { appointments: EligibleAppointment[] };
            appointments.value = data.appointments ?? [];
        } catch {
            appointments.value = [];
            error.value = 'Não foi possível carregar as consultas do paciente.';
        } finally {
            loading.value = false;
        }
    }

    return { appointments, loading, error, load };
}
