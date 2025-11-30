import { ref, computed } from 'vue';

interface CID10Item {
    code: string;
    description: string;
    category?: string;
}

/**
 * Lista de códigos CID-10 mais comuns na prática médica
 * Baseado na Classificação Internacional de Doenças - 10ª Revisão
 */
const cid10Database: CID10Item[] = [
    // Doenças do aparelho respiratório (J00-J99)
    { code: 'J00', description: 'Nasofaringite aguda [resfriado comum]', category: 'Respiratório' },
    { code: 'J01', description: 'Sinusite aguda', category: 'Respiratório' },
    { code: 'J02', description: 'Faringite aguda', category: 'Respiratório' },
    { code: 'J03', description: 'Amigdalite aguda', category: 'Respiratório' },
    { code: 'J04', description: 'Laringite e traqueíte agudas', category: 'Respiratório' },
    { code: 'J06', description: 'Infecções agudas das vias aéreas superiores, de localizações múltiplas ou não especificadas', category: 'Respiratório' },
    { code: 'J10', description: 'Influenza devida a vírus da influenza identificado', category: 'Respiratório' },
    { code: 'J11', description: 'Influenza devida a vírus não identificado', category: 'Respiratório' },
    { code: 'J12', description: 'Pneumonia viral não classificada em outra parte', category: 'Respiratório' },
    { code: 'J13', description: 'Pneumonia devida a Streptococcus pneumoniae', category: 'Respiratório' },
    { code: 'J14', description: 'Pneumonia devida a Haemophilus influenzae', category: 'Respiratório' },
    { code: 'J15', description: 'Pneumonia bacteriana não classificada em outra parte', category: 'Respiratório' },
    { code: 'J18', description: 'Pneumonia por microorganismo não especificado', category: 'Respiratório' },
    { code: 'J20', description: 'Bronquite aguda', category: 'Respiratório' },
    { code: 'J21', description: 'Bronquiolite aguda', category: 'Respiratório' },
    { code: 'J40', description: 'Bronquite não especificada como aguda ou crônica', category: 'Respiratório' },
    { code: 'J41', description: 'Bronquite crônica simples e mucopurulenta', category: 'Respiratório' },
    { code: 'J44', description: 'Outras doenças pulmonares obstrutivas crônicas', category: 'Respiratório' },
    { code: 'J45', description: 'Asma', category: 'Respiratório' },
    { code: 'J46', description: 'Estado de mal asmático', category: 'Respiratório' },
    
    // Doenças do aparelho circulatório (I00-I99)
    { code: 'I10', description: 'Hipertensão essencial (primária)', category: 'Cardiovascular' },
    { code: 'I11', description: 'Doença cardíaca hipertensiva', category: 'Cardiovascular' },
    { code: 'I20', description: 'Angina pectoris', category: 'Cardiovascular' },
    { code: 'I21', description: 'Infarto agudo do miocárdio', category: 'Cardiovascular' },
    { code: 'I25', description: 'Doença isquêmica crônica do coração', category: 'Cardiovascular' },
    { code: 'I50', description: 'Insuficiência cardíaca', category: 'Cardiovascular' },
    { code: 'I63', description: 'Infarto cerebral', category: 'Cardiovascular' },
    { code: 'I64', description: 'Acidente vascular cerebral, não especificado como hemorrágico ou isquêmico', category: 'Cardiovascular' },
    
    // Doenças do aparelho digestivo (K00-K93)
    { code: 'K21', description: 'Doença do refluxo gastroesofágico', category: 'Digestivo' },
    { code: 'K25', description: 'Úlcera gástrica', category: 'Digestivo' },
    { code: 'K29', description: 'Gastrite e duodenite', category: 'Digestivo' },
    { code: 'K30', description: 'Dispepsia', category: 'Digestivo' },
    { code: 'K35', description: 'Apendicite aguda', category: 'Digestivo' },
    { code: 'K40', description: 'Hérnia inguinal', category: 'Digestivo' },
    { code: 'K59', description: 'Outros transtornos funcionais do intestino', category: 'Digestivo' },
    { code: 'K80', description: 'Colelitíase', category: 'Digestivo' },
    
    // Doenças do aparelho geniturinário (N00-N99)
    { code: 'N10', description: 'Nefrite túbulo-intersticial aguda', category: 'Geniturinário' },
    { code: 'N11', description: 'Nefrite túbulo-intersticial crônica', category: 'Geniturinário' },
    { code: 'N18', description: 'Doença renal crônica', category: 'Geniturinário' },
    { code: 'N30', description: 'Cistite', category: 'Geniturinário' },
    { code: 'N39', description: 'Outros transtornos do trato urinário', category: 'Geniturinário' },
    { code: 'N40', description: 'Hiperplasia da próstata', category: 'Geniturinário' },
    
    // Doenças endócrinas, nutricionais e metabólicas (E00-E90)
    { code: 'E10', description: 'Diabetes mellitus insulino-dependente', category: 'Endócrino' },
    { code: 'E11', description: 'Diabetes mellitus não-insulino-dependente', category: 'Endócrino' },
    { code: 'E14', description: 'Diabetes mellitus não especificado', category: 'Endócrino' },
    { code: 'E66', description: 'Obesidade', category: 'Endócrino' },
    { code: 'E78', description: 'Transtornos do metabolismo de lipoproteínas e outras lipidemias', category: 'Endócrino' },
    
    // Doenças do sistema nervoso (G00-G99)
    { code: 'G40', description: 'Epilepsia', category: 'Neurológico' },
    { code: 'G43', description: 'Enxaqueca', category: 'Neurológico' },
    { code: 'G44', description: 'Outras síndromes de cefaleia', category: 'Neurológico' },
    { code: 'G47', description: 'Transtornos do sono', category: 'Neurológico' },
    { code: 'G93', description: 'Outros transtornos do encéfalo', category: 'Neurológico' },
    
    // Doenças do sistema osteomuscular e do tecido conjuntivo (M00-M99)
    { code: 'M25', description: 'Outros transtornos articulares', category: 'Osteomuscular' },
    { code: 'M54', description: 'Dorsalgia', category: 'Osteomuscular' },
    { code: 'M79', description: 'Outros transtornos dos tecidos moles', category: 'Osteomuscular' },
    
    // Doenças da pele e do tecido subcutâneo (L00-L99)
    { code: 'L20', description: 'Dermatite atópica', category: 'Dermatológico' },
    { code: 'L30', description: 'Outras dermatites', category: 'Dermatológico' },
    { code: 'L50', description: 'Urticária', category: 'Dermatológico' },
    { code: 'L70', description: 'Acne', category: 'Dermatológico' },
    
    // Sintomas, sinais e achados anormais (R00-R99)
    { code: 'R05', description: 'Tosse', category: 'Sintomas' },
    { code: 'R06', description: 'Anormalidades da respiração', category: 'Sintomas' },
    { code: 'R50', description: 'Febre de origem desconhecida', category: 'Sintomas' },
    { code: 'R51', description: 'Cefaleia', category: 'Sintomas' },
    { code: 'R53', description: 'Mal-estar, fadiga', category: 'Sintomas' },
    { code: 'R73', description: 'Glicemia alterada', category: 'Sintomas' },
    
    // Lesões, envenenamentos e outras consequências de causas externas (S00-T98)
    { code: 'S00', description: 'Traumatismo superficial da cabeça', category: 'Trauma' },
    { code: 'S09', description: 'Outros traumatismos da cabeça', category: 'Trauma' },
    { code: 'S72', description: 'Fratura do fêmur', category: 'Trauma' },
    
    // Fatores que influenciam o estado de saúde (Z00-Z99)
    { code: 'Z00', description: 'Exame geral e investigação de pessoas sem queixas ou diagnóstico relatado', category: 'Preventivo' },
    { code: 'Z01', description: 'Exame e investigação de outras pessoas para fins de diagnóstico e rastreamento', category: 'Preventivo' },
    { code: 'Z51', description: 'Outros cuidados médicos', category: 'Preventivo' },
];

/**
 * Composable para busca e autocomplete de códigos CID-10
 */
export function useCID10() {
    const searchTerm = ref('');
    const isOpen = ref(false);
    const selectedItem = ref<CID10Item | null>(null);

    /**
     * Filtra os códigos CID-10 baseado no termo de busca
     */
    const filteredItems = computed(() => {
        if (!searchTerm.value || searchTerm.value.length < 1) {
            return [];
        }

        const term = searchTerm.value.toUpperCase().trim();
        
        return cid10Database.filter(item => 
            item.code.toUpperCase().includes(term) ||
            item.description.toUpperCase().includes(term) ||
            item.category?.toUpperCase().includes(term)
        ).slice(0, 10); // Limitar a 10 resultados
    });

    /**
     * Busca um código CID-10 específico
     */
    const findCID10 = (code: string): CID10Item | undefined => {
        return cid10Database.find(item => item.code.toUpperCase() === code.toUpperCase());
    };

    /**
     * Obtém a descrição de um código CID-10
     */
    const getDescription = (code: string): string => {
        const item = findCID10(code);
        return item?.description || '';
    };

    /**
     * Reseta o estado de busca
     */
    const reset = () => {
        searchTerm.value = '';
        isOpen.value = false;
        selectedItem.value = null;
    };

    return {
        searchTerm,
        isOpen,
        selectedItem,
        filteredItems,
        findCID10,
        getDescription,
        reset,
        cid10Database,
    };
}

