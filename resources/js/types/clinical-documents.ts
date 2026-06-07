export interface EligibleAppointment {
    id: string;
    scheduled_at: string;
    label: string;
    status: string;
}

export const APPOINTMENT_STATUS_LABELS: Record<string, string> = {
    in_progress: 'Em andamento',
    scheduled: 'Agendada',
    rescheduled: 'Reagendada',
    completed: 'Concluída',
};

export function eligibleAppointmentLabel(appointment: EligibleAppointment): string {
    const status = APPOINTMENT_STATUS_LABELS[appointment.status] ?? appointment.status;
    return `${appointment.label} · ${status}`;
}

export interface EligibleDocumentPatient {
    id: string;
    name: string;
    cpf: string | null;
    age: number | null;
    sex: 'F' | 'M' | null;
}

export interface DoctorSignatureState {
    status: 'not_integrated' | 'pending' | 'active' | 'expired' | 'revoked';
    active: boolean;
    required: boolean;
}

export const SIGNATURE_STATUS_LABELS: Record<DoctorSignatureState['status'], string> = {
    not_integrated: 'Não integrada',
    pending: 'Integração pendente',
    active: 'Ativa',
    expired: 'Expirada',
    revoked: 'Revogada',
};
