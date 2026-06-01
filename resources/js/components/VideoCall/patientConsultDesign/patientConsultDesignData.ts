export interface PatientConsultDoctor {
    name: string;
    short: string;
    initials: string;
    specialty: string;
    crm: string;
    rqe: string;
    yearsActive: number;
    rating: number;
    reviews: number;
    bio: string;
    languages: string[];
    nextSlot: string;
    clinic: string;
}

export interface PatientConsultChecklistItem {
    id: number;
    text: string;
    done: boolean;
}

export interface PatientConsultSharedItem {
    id: number;
    kind: 'rx' | 'exam';
    title: string;
    summary: string;
    issuedAt: string;
    status: string;
    icon: 'pill' | 'flask';
}

export type PatientChatType = 'system' | 'them' | 'me';

export interface PatientConsultChatMessage {
    id: number;
    type: PatientChatType;
    text?: string;
    author?: string;
    time?: string;
}

export interface PatientConsultSharedFile {
    id: number;
    name: string;
    size: string;
    from: string;
    when: string;
    kind: 'pdf' | 'img';
}

export const MOCK_PATIENT_DOCTOR: PatientConsultDoctor = {
    name: 'Dr. Renato Aleixo',
    short: 'Dr. Renato',
    initials: 'RA',
    specialty: 'Clínica geral',
    crm: 'CRM-SP 145.387',
    rqe: 'RQE 38.221',
    yearsActive: 12,
    rating: 4.9,
    reviews: 312,
    bio: 'Médico generalista com 12 anos de experiência em atendimento primário e cuidado longitudinal. Pós-graduado em medicina de família e comunidade.',
    languages: ['Português', 'Inglês', 'Espanhol'],
    nextSlot: 'Qua, 04 jun · 09:30',
    clinic: 'Clínica Núcleo · São Paulo, SP',
};

export const MOCK_PATIENT_MY_NOTES: PatientConsultChecklistItem[] = [
    { id: 1, text: 'Perguntar se posso voltar a praticar exercícios', done: true },
    { id: 2, text: 'Confirmar dose da levotiroxina antes do exame', done: false },
];

export const MOCK_PATIENT_SHARED_ITEMS: PatientConsultSharedItem[] = [
    {
        id: 1,
        kind: 'rx',
        title: 'Prescrição médica',
        summary: 'Amitriptilina 25 mg · 1 comprimido à noite por 60 dias',
        issuedAt: 'agora',
        status: 'Pronto para download',
        icon: 'pill',
    },
    {
        id: 2,
        kind: 'exam',
        title: 'Solicitação de exame',
        summary: 'Ressonância magnética de crânio · com contraste',
        issuedAt: 'há 3 min',
        status: 'Aguardando autorização do convênio',
        icon: 'flask',
    },
];

export const MOCK_PATIENT_CHAT: PatientConsultChatMessage[] = [
    { id: 1, type: 'system', text: 'Você entrou na consulta · 14:02' },
    { id: 2, type: 'me', author: 'Você', text: 'Boa tarde, doutor! Está me ouvindo bem?', time: '14:02' },
    {
        id: 3,
        type: 'them',
        author: 'Dr. Renato',
        text: 'Boa tarde, Mariana. Ouvindo perfeitamente. Como você está hoje?',
        time: '14:02',
    },
    {
        id: 4,
        type: 'me',
        author: 'Você',
        text: 'Vou te mandar a foto do diário de cefaleia que você pediu na última consulta.',
        time: '14:04',
    },
    { id: 5, type: 'system', text: 'Você compartilhou um arquivo: diario-cefaleia.pdf' },
];

export const MOCK_PATIENT_FILES: PatientConsultSharedFile[] = [
    { id: 1, name: 'prescricao-2026-05-27.pdf', size: '82 KB', from: 'Dr. Renato · agora', when: 'agora', kind: 'pdf' },
    { id: 2, name: 'solicitacao-rm-cranio.pdf', size: '94 KB', from: 'Dr. Renato · há 3 min', when: 'há 3 min', kind: 'pdf' },
    { id: 3, name: 'diario-cefaleia.pdf', size: '284 KB', from: 'Você', when: '14:04', kind: 'pdf' },
    { id: 4, name: 'hemograma-02-04-2026.pdf', size: '1.2 MB', from: 'Tele · Prontuário', when: '02 abr', kind: 'pdf' },
];
