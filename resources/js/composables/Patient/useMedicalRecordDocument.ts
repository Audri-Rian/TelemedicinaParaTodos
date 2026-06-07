import { useFormatters } from '@/composables/useFormatters';
import patientMedicalRecordRoutes from '@/routes/patient/medical-records';
import type { Appointment } from '@/types/medical-records';
import { useForm } from '@inertiajs/vue3';
import { computed, type Ref } from 'vue';

export function useMedicalRecordDocument(consultations: Ref<Appointment[]>, patientId?: Ref<string | undefined>) {
    const { formatDate } = useFormatters();

    const documentForm = useForm<{
        file: File | null;
        category: string;
        name: string;
        description: string;
        visibility: string;
        appointment_id: string;
    }>({
        file: null,
        category: 'report',
        name: '',
        description: '',
        visibility: 'shared',
        appointment_id: '',
    });

    const appointmentOptions = computed(() =>
        consultations.value.map((appointment) => ({
            id: appointment.id,
            label: `${formatDate(appointment.scheduled_at, true)} · ${appointment.doctor.user.name}`,
        })),
    );

    function submitDocument() {
        if (!documentForm.file) {
            documentForm.setError('file', 'Selecione um arquivo antes de enviar.');
            return;
        }

        const uploadUrl = patientId?.value
            ? `/doctor/patients/${patientId.value}/medical-record/documents`
            : patientMedicalRecordRoutes.documents.store.url();

        documentForm.post(uploadUrl, {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                documentForm.reset();
                documentForm.clearErrors();
            },
        });
    }

    const MAX_FILE_BYTES = 10 * 1024 * 1024;
    const ALLOWED_MIME_TYPES = ['application/pdf', 'image/jpeg', 'image/png'];

    function handleFileChange(event: Event) {
        const target = event.target as HTMLInputElement;
        const file = target.files?.[0] ?? null;

        if (file) {
            if (file.size > MAX_FILE_BYTES) {
                documentForm.setError('file', 'Arquivo deve ter no máximo 10 MB.');
                documentForm.setData('file', null);
                return;
            }
            if (!ALLOWED_MIME_TYPES.includes(file.type)) {
                documentForm.setError('file', 'Apenas PDF, JPEG e PNG são permitidos.');
                documentForm.setData('file', null);
                return;
            }
            documentForm.clearErrors('file');
        }

        documentForm.setData('file', file);
    }

    return { documentForm, appointmentOptions, submitDocument, handleFileChange };
}
