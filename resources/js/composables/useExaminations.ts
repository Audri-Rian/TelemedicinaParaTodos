import { ref, computed } from 'vue';

interface Examination {
    code: string;
    name: string;
    category: string;
    description?: string;
}

/**
 * Catálogo de exames laboratoriais e de imagem mais comuns
 */
const examinationsDatabase: Examination[] = [
    // Exames Laboratoriais - Hematologia
    { code: 'HEMO', name: 'Hemograma Completo', category: 'Hematologia', description: 'Avaliação das células sanguíneas' },
    { code: 'COAG', name: 'Coagulograma', category: 'Hematologia', description: 'Avaliação da coagulação sanguínea' },
    { code: 'VHS', name: 'Velocidade de Hemossedimentação (VHS)', category: 'Hematologia' },
    { code: 'PCR', name: 'Proteína C Reativa (PCR)', category: 'Hematologia', description: 'Marcador de inflamação' },
    
    // Exames Laboratoriais - Bioquímica
    { code: 'GLIC', name: 'Glicemia de Jejum', category: 'Bioquímica', description: 'Avaliação da glicose sanguínea' },
    { code: 'HBA1C', name: 'Hemoglobina Glicada (HbA1c)', category: 'Bioquímica', description: 'Controle glicêmico médio' },
    { code: 'COLEST', name: 'Colesterol Total', category: 'Bioquímica' },
    { code: 'HDL', name: 'HDL Colesterol', category: 'Bioquímica', description: 'Colesterol bom' },
    { code: 'LDL', name: 'LDL Colesterol', category: 'Bioquímica', description: 'Colesterol ruim' },
    { code: 'TRIG', name: 'Triglicerídeos', category: 'Bioquímica' },
    { code: 'UREIA', name: 'Ureia', category: 'Bioquímica', description: 'Função renal' },
    { code: 'CREAT', name: 'Creatinina', category: 'Bioquímica', description: 'Função renal' },
    { code: 'TGO', name: 'TGO (AST)', category: 'Bioquímica', description: 'Função hepática' },
    { code: 'TGP', name: 'TGP (ALT)', category: 'Bioquímica', description: 'Função hepática' },
    { code: 'BILT', name: 'Bilirrubina Total', category: 'Bioquímica', description: 'Função hepática' },
    { code: 'ALBUM', name: 'Albumina', category: 'Bioquímica', description: 'Função hepática' },
    { code: 'ACIDO', name: 'Ácido Úrico', category: 'Bioquímica' },
    
    // Exames Laboratoriais - Hormônios
    { code: 'TSH', name: 'TSH (Hormônio Tireoestimulante)', category: 'Hormonal', description: 'Função tireoidiana' },
    { code: 'T4L', name: 'T4 Livre', category: 'Hormonal', description: 'Função tireoidiana' },
    { code: 'T3L', name: 'T3 Livre', category: 'Hormonal', description: 'Função tireoidiana' },
    { code: 'VITD', name: 'Vitamina D (25-OH)', category: 'Hormonal', description: 'Níveis de vitamina D' },
    { code: 'VITB12', name: 'Vitamina B12', category: 'Hormonal' },
    { code: 'FERRIT', name: 'Ferritina', category: 'Hormonal', description: 'Reservas de ferro' },
    
    // Exames Laboratoriais - Urina
    { code: 'EAS', name: 'Exame de Urina (EAS)', category: 'Urina', description: 'Análise de urina tipo 1' },
    { code: 'URCUL', name: 'Urocultura', category: 'Urina', description: 'Cultura de urina' },
    { code: 'CREATU', name: 'Creatinina Urinária', category: 'Urina' },
    
    // Exames Laboratoriais - Fezes
    { code: 'COPRO', name: 'Coproparasitológico', category: 'Fezes', description: 'Pesquisa de parasitas' },
    { code: 'SANGOC', name: 'Sangue Oculto nas Fezes', category: 'Fezes' },
    
    // Exames de Imagem - Radiologia
    { code: 'RXTOR', name: 'Radiografia de Tórax', category: 'Imagem', description: 'Raio-X do tórax' },
    { code: 'RXABD', name: 'Radiografia de Abdome', category: 'Imagem', description: 'Raio-X do abdome' },
    { code: 'RXOSSE', name: 'Radiografia Óssea', category: 'Imagem' },
    { code: 'MAMOG', name: 'Mamografia', category: 'Imagem', description: 'Rastreamento de câncer de mama' },
    
    // Exames de Imagem - Ultrassonografia
    { code: 'USGABD', name: 'Ultrassonografia de Abdome Total', category: 'Imagem', description: 'USG do abdome' },
    { code: 'USGPEL', name: 'Ultrassonografia Pélvica', category: 'Imagem' },
    { code: 'USGTIR', name: 'Ultrassonografia de Tireoide', category: 'Imagem' },
    { code: 'USGMAM', name: 'Ultrassonografia de Mamas', category: 'Imagem' },
    { code: 'USGART', name: 'Ultrassonografia Arterial', category: 'Imagem', description: 'Doppler arterial' },
    { code: 'USGVEN', name: 'Ultrassonografia Venosa', category: 'Imagem', description: 'Doppler venoso' },
    
    // Exames de Imagem - Tomografia
    { code: 'TCTOR', name: 'Tomografia Computadorizada de Tórax', category: 'Imagem', description: 'TC do tórax' },
    { code: 'TCABD', name: 'Tomografia Computadorizada de Abdome', category: 'Imagem', description: 'TC do abdome' },
    { code: 'TCCRAN', name: 'Tomografia Computadorizada de Crânio', category: 'Imagem', description: 'TC do crânio' },
    
    // Exames de Imagem - Ressonância
    { code: 'RMCRAN', name: 'Ressonância Magnética de Crânio', category: 'Imagem', description: 'RM do crânio' },
    { code: 'RMCOL', name: 'Ressonância Magnética de Coluna', category: 'Imagem' },
    { code: 'RMART', name: 'Ressonância Magnética de Articulação', category: 'Imagem' },
    
    // Exames Especiais
    { code: 'ECG', name: 'Eletrocardiograma (ECG)', category: 'Cardiologia', description: 'Avaliação cardíaca' },
    { code: 'ECO', name: 'Ecocardiograma', category: 'Cardiologia', description: 'USG do coração' },
    { code: 'TESTE', name: 'Teste Ergométrico', category: 'Cardiologia', description: 'Teste de esforço' },
    { code: 'HOLTER', name: 'Holter 24h', category: 'Cardiologia', description: 'Monitoramento cardíaco' },
    { code: 'MAP', name: 'Monitorização Ambulatorial da Pressão (MAPA)', category: 'Cardiologia' },
    
    // Exames Endoscópicos
    { code: 'ENDO', name: 'Endoscopia Digestiva Alta', category: 'Endoscopia', description: 'Endoscopia do estômago' },
    { code: 'COLON', name: 'Colonoscopia', category: 'Endoscopia', description: 'Endoscopia do cólon' },
    
    // Exames Ginecológicos
    { code: 'PAP', name: 'Papanicolau', category: 'Ginecologia', description: 'Prevenção de câncer de colo uterino' },
    { code: 'COLP', name: 'Colposcopia', category: 'Ginecologia' },
    
    // Exames Microbiológicos
    { code: 'HELICO', name: 'Teste do H. pylori', category: 'Microbiologia', description: 'Pesquisa de H. pylori' },
    { code: 'CULT', name: 'Cultura de Secreção', category: 'Microbiologia' },
    { code: 'ANTIBIO', name: 'Antibiograma', category: 'Microbiologia', description: 'Sensibilidade a antibióticos' },
];

/**
 * Composable para busca e catálogo de exames
 */
export function useExaminations() {
    const searchTerm = ref('');
    const isOpen = ref(false);

    /**
     * Filtra os exames baseado no termo de busca
     */
    const filteredExaminations = computed(() => {
        if (!searchTerm.value || searchTerm.value.length < 2) {
            return [];
        }

        const term = searchTerm.value.toUpperCase().trim();
        
        return examinationsDatabase.filter(exam => 
            exam.code.toUpperCase().includes(term) ||
            exam.name.toUpperCase().includes(term) ||
            exam.category.toUpperCase().includes(term) ||
            exam.description?.toUpperCase().includes(term)
        ).slice(0, 10); // Limitar a 10 resultados
    });

    /**
     * Busca um exame específico
     */
    const findExamination = (code: string): Examination | undefined => {
        return examinationsDatabase.find(exam => 
            exam.code.toUpperCase() === code.toUpperCase()
        );
    };

    /**
     * Obtém exames por categoria
     */
    const getExaminationsByCategory = (category: string): Examination[] => {
        return examinationsDatabase.filter(exam => 
            exam.category.toUpperCase() === category.toUpperCase()
        );
    };

    /**
     * Lista todas as categorias disponíveis
     */
    const categories = computed(() => {
        const cats = new Set(examinationsDatabase.map(exam => exam.category));
        return Array.from(cats).sort();
    });

    return {
        searchTerm,
        isOpen,
        filteredExaminations,
        findExamination,
        getExaminationsByCategory,
        categories,
        examinationsDatabase,
    };
}

