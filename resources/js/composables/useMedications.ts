import { ref, computed } from 'vue';

interface Medication {
    name: string;
    activeIngredient?: string;
    dosage?: string;
    category?: string;
}

/**
 * Lista de medicamentos comuns na prática médica
 * Baseado em medicamentos mais prescritos no Brasil
 */
const medicationsDatabase: Medication[] = [
    // Analgésicos e Antitérmicos
    { name: 'Paracetamol', activeIngredient: 'Paracetamol', dosage: '500mg, 750mg, 1g', category: 'Analgésico/Antitérmico' },
    { name: 'Dipirona', activeIngredient: 'Dipirona sódica', dosage: '500mg, 1g', category: 'Analgésico/Antitérmico' },
    { name: 'Ibuprofeno', activeIngredient: 'Ibuprofeno', dosage: '200mg, 400mg, 600mg', category: 'Analgésico/Anti-inflamatório' },
    { name: 'Diclofenaco', activeIngredient: 'Diclofenaco sódico', dosage: '50mg, 75mg, 100mg', category: 'Anti-inflamatório' },
    { name: 'Naproxeno', activeIngredient: 'Naproxeno sódico', dosage: '250mg, 500mg, 550mg', category: 'Anti-inflamatório' },
    
    // Antibióticos
    { name: 'Amoxicilina', activeIngredient: 'Amoxicilina', dosage: '250mg, 500mg, 875mg', category: 'Antibiótico' },
    { name: 'Azitromicina', activeIngredient: 'Azitromicina', dosage: '500mg', category: 'Antibiótico' },
    { name: 'Cefalexina', activeIngredient: 'Cefalexina', dosage: '250mg, 500mg', category: 'Antibiótico' },
    { name: 'Ciprofloxacino', activeIngredient: 'Ciprofloxacino', dosage: '250mg, 500mg, 750mg', category: 'Antibiótico' },
    { name: 'Doxiciclina', activeIngredient: 'Doxiciclina', dosage: '100mg', category: 'Antibiótico' },
    { name: 'Penicilina V', activeIngredient: 'Penicilina V potássica', dosage: '250mg, 500mg', category: 'Antibiótico' },
    
    // Anti-hipertensivos
    { name: 'Losartana', activeIngredient: 'Losartana potássica', dosage: '25mg, 50mg, 100mg', category: 'Anti-hipertensivo' },
    { name: 'Atenolol', activeIngredient: 'Atenolol', dosage: '25mg, 50mg, 100mg', category: 'Anti-hipertensivo' },
    { name: 'Captopril', activeIngredient: 'Captopril', dosage: '12,5mg, 25mg, 50mg', category: 'Anti-hipertensivo' },
    { name: 'Enalapril', activeIngredient: 'Enalapril maleato', dosage: '5mg, 10mg, 20mg', category: 'Anti-hipertensivo' },
    { name: 'Hidroclorotiazida', activeIngredient: 'Hidroclorotiazida', dosage: '12,5mg, 25mg, 50mg', category: 'Diurético' },
    
    // Antidiabéticos
    { name: 'Metformina', activeIngredient: 'Metformina cloridrato', dosage: '500mg, 850mg, 1g', category: 'Antidiabético' },
    { name: 'Glibenclamida', activeIngredient: 'Glibenclamida', dosage: '2,5mg, 5mg', category: 'Antidiabético' },
    { name: 'Gliclazida', activeIngredient: 'Gliclazida', dosage: '30mg, 60mg, 80mg', category: 'Antidiabético' },
    
    // Antialérgicos
    { name: 'Loratadina', activeIngredient: 'Loratadina', dosage: '10mg', category: 'Antialérgico' },
    { name: 'Cetirizina', activeIngredient: 'Cetirizina dicloridrato', dosage: '10mg', category: 'Antialérgico' },
    { name: 'Desloratadina', activeIngredient: 'Desloratadina', dosage: '5mg', category: 'Antialérgico' },
    { name: 'Fexofenadina', activeIngredient: 'Fexofenadina cloridrato', dosage: '120mg, 180mg', category: 'Antialérgico' },
    
    // Antiespasmódicos
    { name: 'Hioscina', activeIngredient: 'Hioscina butilbrometo', dosage: '10mg', category: 'Antiespasmódico' },
    { name: 'Buscopan', activeIngredient: 'Hioscina butilbrometo', dosage: '10mg', category: 'Antiespasmódico' },
    
    // Antitussígenos
    { name: 'Xarope de Codeína', activeIngredient: 'Codeína', dosage: '3mg/ml', category: 'Antitussígeno' },
    { name: 'Dextrometorfano', activeIngredient: 'Dextrometorfano', dosage: '15mg/5ml', category: 'Antitussígeno' },
    
    // Expectorantes
    { name: 'Ambroxol', activeIngredient: 'Ambroxol cloridrato', dosage: '15mg/5ml, 30mg/5ml', category: 'Expectorante' },
    { name: 'Acetilcisteína', activeIngredient: 'Acetilcisteína', dosage: '100mg, 200mg, 600mg', category: 'Expectorante' },
    
    // Antiácidos
    { name: 'Omeprazol', activeIngredient: 'Omeprazol', dosage: '20mg, 40mg', category: 'Antiácido' },
    { name: 'Pantoprazol', activeIngredient: 'Pantoprazol sódico', dosage: '20mg, 40mg', category: 'Antiácido' },
    { name: 'Ranitidina', activeIngredient: 'Ranitidina cloridrato', dosage: '150mg, 300mg', category: 'Antiácido' },
    
    // Corticosteroides
    { name: 'Prednisona', activeIngredient: 'Prednisona', dosage: '5mg, 20mg', category: 'Corticosteroide' },
    { name: 'Dexametasona', activeIngredient: 'Dexametasona', dosage: '0,5mg, 4mg', category: 'Corticosteroide' },
    { name: 'Betametasona', activeIngredient: 'Betametasona', dosage: '0,5mg, 1mg', category: 'Corticosteroide' },
    
    // Antidepressivos
    { name: 'Sertralina', activeIngredient: 'Sertralina cloridrato', dosage: '50mg, 100mg', category: 'Antidepressivo' },
    { name: 'Fluoxetina', activeIngredient: 'Fluoxetina cloridrato', dosage: '20mg', category: 'Antidepressivo' },
    { name: 'Amitriptilina', activeIngredient: 'Amitriptilina cloridrato', dosage: '25mg, 50mg', category: 'Antidepressivo' },
    
    // Ansiolíticos
    { name: 'Diazepam', activeIngredient: 'Diazepam', dosage: '5mg, 10mg', category: 'Ansiolítico' },
    { name: 'Alprazolam', activeIngredient: 'Alprazolam', dosage: '0,25mg, 0,5mg, 1mg, 2mg', category: 'Ansiolítico' },
    { name: 'Clonazepam', activeIngredient: 'Clonazepam', dosage: '0,5mg, 1mg, 2mg', category: 'Ansiolítico' },
    
    // Vitaminas e Suplementos
    { name: 'Vitamina D', activeIngredient: 'Colecalciferol', dosage: '400UI, 1000UI, 2000UI', category: 'Vitamina' },
    { name: 'Ácido Fólico', activeIngredient: 'Ácido fólico', dosage: '1mg, 5mg', category: 'Vitamina' },
    { name: 'Ferro', activeIngredient: 'Sulfato ferroso', dosage: '40mg, 60mg', category: 'Suplemento' },
    { name: 'Cálcio', activeIngredient: 'Carbonato de cálcio', dosage: '500mg, 1g', category: 'Suplemento' },
];

/**
 * Composable para busca e autocomplete de medicamentos
 */
export function useMedications() {
    const searchTerm = ref('');
    const isOpen = ref(false);

    /**
     * Filtra os medicamentos baseado no termo de busca
     */
    const filteredMedications = computed(() => {
        if (!searchTerm.value || searchTerm.value.length < 2) {
            return [];
        }

        const term = searchTerm.value.toUpperCase().trim();
        
        return medicationsDatabase.filter(med => 
            med.name.toUpperCase().includes(term) ||
            med.activeIngredient?.toUpperCase().includes(term) ||
            med.category?.toUpperCase().includes(term)
        ).slice(0, 10); // Limitar a 10 resultados
    });

    /**
     * Busca um medicamento específico
     */
    const findMedication = (name: string): Medication | undefined => {
        return medicationsDatabase.find(med => 
            med.name.toUpperCase() === name.toUpperCase()
        );
    };

    return {
        searchTerm,
        isOpen,
        filteredMedications,
        findMedication,
        medicationsDatabase,
    };
}

