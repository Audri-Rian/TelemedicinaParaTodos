export interface DoctorSummary {
    id: string;
    user: {
        name: string;
        avatar?: string | null;
    };
    specializations?: Array<{ id: string; name: string }>;
}

export interface Appointment {
    id: string;
    scheduled_at?: string;
    started_at?: string | null;
    ended_at?: string | null;
    status: string;
    notes?: string | null;
    doctor: DoctorSummary;
    diagnosis?: string | null;
    cid10?: string | null;
    symptoms?: string | null;
    requested_exams?: string | null;
    instructions?: string | null;
    prescriptions?: Prescription[];
    examinations?: Examination[];
    documents?: MedicalDocument[];
    vital_signs?: VitalSignEntry[];
}

export interface Prescription {
    id: string;
    doctor?: { id: string; name: string };
    medications: Array<Record<string, string>>;
    instructions?: string | null;
    valid_until?: string | null;
    status: string;
    issued_at?: string | null;
}

export interface Examination {
    id: string;
    name: string;
    type: string;
    doctor?: { id: string; name: string };
    status: string;
    requested_at?: string | null;
    completed_at?: string | null;
    results?: Record<string, unknown> | null;
    attachment_url?: string | null;
    source?: string | null;
    partner?: { name: string } | null;
    received_from_partner_at?: string | null;
}

export interface MedicalDocument {
    id: string;
    name: string;
    category: string;
    file_path: string;
    file_type?: string | null;
    file_size?: number | null;
    description?: string | null;
    visibility?: string;
    uploaded_at?: string | null;
    doctor?: { id: string; name: string } | null;
    uploaded_by?: { id: string; name: string } | null;
}

export interface VitalSignEntry {
    id: string;
    recorded_at?: string | null;
    doctor?: { id: string; name: string } | null;
    blood_pressure?: { systolic?: number | null; diastolic?: number | null };
    temperature?: number | null;
    heart_rate?: number | null;
    respiratory_rate?: number | null;
    oxygen_saturation?: number | null;
    weight?: number | null;
    height?: number | null;
    notes?: string | null;
}

export interface Diagnosis {
    id: string;
    appointment_id: string;
    cid10_code: string;
    cid10_description?: string | null;
    type: 'principal' | 'secondary';
    description?: string | null;
    doctor: { id: string; name: string };
    created_at?: string | null;
}

export interface ClinicalNote {
    id: string;
    appointment_id: string;
    title: string;
    content: string;
    is_private: boolean;
    category: string;
    tags?: string[] | null;
    version: number;
    doctor: { id: string; name: string };
    created_at?: string | null;
}

export interface MedicalCertificate {
    id: string;
    appointment_id: string;
    type: string;
    start_date?: string | null;
    end_date?: string | null;
    days?: number | null;
    reason: string;
    restrictions?: string | null;
    status: string;
    verification_code: string;
    pdf_url?: string | null;
    doctor: { id: string; name: string; crm?: string | null };
    created_at?: string | null;
}

export interface MedicalMetrics {
    total_consultations: number;
    total_prescriptions: number;
    total_examinations: number;
    last_consultation_at?: string | null;
}

export interface PatientProfile {
    id: string;
    user: {
        name: string;
        avatar?: string | null;
    };
    date_of_birth?: string | null;
    gender: string;
    age?: number | null;
    blood_type?: string | null;
    medical_history?: string | null;
    allergies?: string | null;
    current_medications?: string | null;
    height?: number | null;
    weight?: number | null;
    bmi?: number | null;
    bmi_category?: string | null;
}

export interface FilterState {
    search: string;
    date_from: string;
    date_to: string;
}

export type TabId =
    | 'historico'
    | 'consultas'
    | 'prescricoes'
    | 'exames'
    | 'documentos'
    | 'diagnosticos'
    | 'atestados'
    | 'vitais'
    | 'notas'
    | 'futuras';
