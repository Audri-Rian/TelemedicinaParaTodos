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
