export interface ConsultPatientVital {
    label: string;
    value: string;
    unit: string;
}

export interface ConsultPatientMedication {
    name: string;
    dose: string;
}

export interface ConsultPatientHistoryItem {
    id: number;
    title: string;
    date: string;
    who: string;
    icon: 'act' | 'flask';
}

export interface ConsultPatient {
    name: string;
    initials: string;
    age: number;
    gender: string;
    cpf: string;
    pronoun: string;
    bloodType: string;
    allergies: string[];
    conditions: string[];
    medications: ConsultPatientMedication[];
    vitals: {
        pa: ConsultPatientVital;
        fc: ConsultPatientVital;
        tax: ConsultPatientVital;
        sat: ConsultPatientVital;
    };
    chiefComplaint: string;
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
    id: number;
    name: string;
    size: string;
    from: string;
    when: string;
    kind: 'pdf' | 'img';
}

export interface ConsultSoapNotes {
    S: string;
    O: string;
    A: string;
    P: string;
}

export const MOCK_CONSULT_PATIENT: ConsultPatient = {
    name: 'Mariana Costa Andrade',
    initials: 'MA',
    age: 34,
    gender: 'Feminino',
    cpf: '***.***.789-22',
    pronoun: 'ela/dela',
    bloodType: 'O+',
    allergies: ['Dipirona', 'Penicilina'],
    conditions: ['Hipotireoidismo', 'Enxaqueca crônica'],
    medications: [
        { name: 'Levotiroxina sódica', dose: '50 mcg · 1x ao dia (manhã, em jejum)' },
        { name: 'Sumatriptano', dose: '50 mg · em crise · até 2x/dia' },
        { name: 'Vitamina D3', dose: '2.000 UI · 1x ao dia' },
    ],
    vitals: {
        pa: { label: 'Pressão arterial', value: '118/76', unit: 'mmHg' },
        fc: { label: 'Freq. cardíaca', value: '72', unit: 'bpm' },
        tax: { label: 'Temperatura', value: '36,4', unit: '°C' },
        sat: { label: 'Saturação O₂', value: '98', unit: '%' },
    },
    chiefComplaint: 'Dor de cabeça há 6 dias, com piora ao fim do dia. Refere náusea associada e fotofobia leve.',
    history: [
        {
            id: 1,
            title: 'Consulta de retorno · Endocrinologia',
            date: '14 abr 2026',
            who: 'Dra. Larissa Menezes',
            icon: 'act',
        },
        {
            id: 2,
            title: 'Hemograma completo + TSH',
            date: '02 abr 2026',
            who: 'Resultado anexado',
            icon: 'flask',
        },
        {
            id: 3,
            title: 'Consulta inicial · Neurologia',
            date: '18 mar 2026',
            who: 'Dr. Eduardo Saraiva',
            icon: 'act',
        },
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

export const MOCK_SHARED_FILES: ConsultSharedFile[] = [
    { id: 1, name: 'diario-cefaleia.pdf', size: '284 KB', from: 'Mariana', when: 'agora', kind: 'pdf' },
    { id: 2, name: 'hemograma-02-04-2026.pdf', size: '1.2 MB', from: 'Tele · Prontuário', when: '02 abr', kind: 'pdf' },
    { id: 3, name: 'tsh-t4-livre.pdf', size: '412 KB', from: 'Tele · Prontuário', when: '02 abr', kind: 'pdf' },
    { id: 4, name: 'ressonancia-cranio.jpg', size: '3.4 MB', from: 'Mariana', when: '18 mar', kind: 'img' },
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
