export interface ConsultPatientHistoryItem {
    id: string;
    title: string;
    date: string;
    summary?: string | null;
}

export interface ConsultPatient {
    name: string;
    initials: string;
    age: number | null;
    gender: string | null;
    bloodType: string | null;
    allergies: string[];
    conditions: string | null;
    medications: string[];
    chiefComplaint: string | null;
    history: ConsultPatientHistoryItem[];
}

export type ChatMessageType = 'system' | 'them' | 'me';

export interface ConsultChatMessage {
    id: number;
    type: ChatMessageType;
    text?: string;
    author?: string;
    time?: string;
}

export interface ConsultSharedFile {
    id: string;
    name: string;
    size: string;
    from: string;
    when: string;
    kind: 'pdf' | 'img';
    downloadUrl?: string;
}

export interface ConsultSoapNotes {
    S: string;
    O: string;
    A: string;
    P: string;
}

// Apenas referência de design (não usado em runtime — o overlay recebe dados reais via props)
export const MOCK_CONSULT_PATIENT: ConsultPatient = {
    name: 'Mariana Costa Andrade',
    initials: 'MA',
    age: 34,
    gender: 'Feminino',
    bloodType: 'O+',
    allergies: ['Dipirona', 'Penicilina'],
    conditions: 'Hipotireoidismo; enxaqueca crônica.',
    medications: ['Levotiroxina sódica 50 mcg · 1x ao dia', 'Sumatriptano 50 mg · em crise'],
    chiefComplaint: 'Dor de cabeça há 6 dias, com piora ao fim do dia. Refere náusea associada e fotofobia leve.',
    history: [
        { id: '1', title: 'Consulta finalizada', date: '14/04/2026', summary: 'Retorno · ajuste de dose' },
        { id: '2', title: 'Consulta finalizada', date: '18/03/2026', summary: 'Consulta inicial' },
    ],
};

export const MOCK_INITIAL_CHAT: ConsultChatMessage[] = [
    { id: 1, type: 'system', text: 'Mariana entrou na consulta · 14:02' },
    { id: 2, type: 'them', author: 'Mariana', text: 'Boa tarde, doutor! Está me ouvindo bem?', time: '14:02' },
    {
        id: 3,
        type: 'me',
        author: 'Você',
        text: 'Boa tarde, Mariana. Ouvindo perfeitamente. Como você está hoje?',
        time: '14:02',
    },
    {
        id: 4,
        type: 'them',
        author: 'Mariana',
        text: 'Vou te mandar a foto do diário de cefaleia que você pediu na última consulta.',
        time: '14:04',
    },
    { id: 5, type: 'system', text: 'Mariana compartilhou um arquivo: diario-cefaleia.pdf' },
];

// Apenas referência de design (não usado em runtime — o overlay recebe documentos reais via props)
export const MOCK_SHARED_FILES: ConsultSharedFile[] = [
    { id: '1', name: 'diario-cefaleia.pdf', size: '284 KB', from: 'Mariana', when: 'agora', kind: 'pdf' },
    { id: '2', name: 'hemograma-02-04-2026.pdf', size: '1.2 MB', from: 'Tele · Prontuário', when: '02 abr', kind: 'pdf' },
    { id: '3', name: 'tsh-t4-livre.pdf', size: '412 KB', from: 'Tele · Prontuário', when: '02 abr', kind: 'pdf' },
    { id: '4', name: 'ressonancia-cranio.jpg', size: '3.4 MB', from: 'Mariana', when: '18 mar', kind: 'img' },
];

export const QUICK_TEMPLATES_S = ['Refere dor há __ dias', 'Nega febre e calafrios', 'Em uso contínuo de __', 'Nega alergias medicamentosas'];

export const QUICK_TEMPLATES_O = [
    'Bom estado geral, corado, hidratado',
    'PA, FC e SatO₂ estáveis',
    'Ausculta cardiopulmonar sem alterações',
    'Abdome flácido, indolor à palpação',
];

export const MOCK_INITIAL_NOTES: ConsultSoapNotes = {
    S: 'Refere cefaleia há 6 dias, contínua, com piora ao fim do dia. Náusea associada e fotofobia leve. Nega vômitos, febre ou sintomas neurológicos focais. Em uso de Sumatriptano 50mg em crise, com alívio parcial.',
    O: '',
    A: '',
    P: '',
};
