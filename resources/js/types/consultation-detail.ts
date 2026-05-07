export interface AppointmentDetail {
    id: string;
    scheduled_at: string;
    started_at?: string | null;
    ended_at?: string | null;
    status: string;
    notes?: string | null;
    chief_complaint?: string;
    physical_exam?: string;
    diagnosis?: string;
    cid10?: string;
    instructions?: string;
    prescriptions?: Array<Record<string, unknown>>;
    examinations?: Array<Record<string, unknown>>;
    diagnoses?: Array<Record<string, unknown>>;
    clinical_notes?: Array<Record<string, unknown>>;
}

export interface ConsultationPatient {
    id: string;
    name: string;
    age: number;
    gender: string;
    blood_type?: string | null;
    allergies: string[];
    current_medications?: string | null;
    medical_history?: string | null;
    height?: number | null;
    weight?: number | null;
    bmi?: number | null;
}

export interface RecentConsultation {
    id: string;
    date: string;
    diagnosis?: string | null;
    cid10?: string | null;
}

export type ConsultationMode = 'scheduled' | 'in_progress' | 'completed';

export type AutoSaveStatus = 'idle' | 'saving' | 'saved' | 'error';

export interface ConsultationFormFields {
    chief_complaint: string;
    physical_exam: string;
    diagnosis: string;
    cid10: string;
    instructions: string;
    notes: string;
}
