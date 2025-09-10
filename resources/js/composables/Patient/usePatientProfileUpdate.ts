import { ref, computed } from 'vue';
import { useRealTimeValidation, type ValidationRule } from '../useRealTimeValidation';
import { usePatientFormValidation } from './usePatientFormValidation';

/**
 * Interface para dados do perfil complementar do paciente
 * Contém apenas campos opcionais da migration
 */
export interface PatientProfileData {
  emergency_contact: string;
  emergency_phone: string;
  medical_history: string;
  allergies: string;
  current_medications: string;
  blood_type: string;
  height: number | null;
  weight: number | null;
  insurance_provider: string;
  insurance_number: string;
}

/**
 * Dados iniciais para o formulário de perfil complementar
 */
const initialProfileData: PatientProfileData = {
  emergency_contact: '',
  emergency_phone: '',
  medical_history: '',
  allergies: '',
  current_medications: '',
  blood_type: '',
  height: null,
  weight: null,
  insurance_provider: '',
  insurance_number: ''
};

/**
 * Composable para gerenciar o cadastro complementar do paciente
 * Gerencia campos opcionais que podem ser preenchidos após o cadastro inicial
 */
export function usePatientProfileUpdate() {
  const { 
    optionalPhoneValidation,
    optionalTextValidation,
    heightValidation,
    weightValidation,
    bloodTypeValidation,
    optionalStringValidation
  } = usePatientFormValidation();

  // Regras de validação para campos opcionais
  const validationRules: Record<keyof PatientProfileData, ValidationRule> = {
    emergency_contact: optionalStringValidation(100),
    emergency_phone: optionalPhoneValidation,
    medical_history: optionalTextValidation,
    allergies: optionalTextValidation,
    current_medications: optionalTextValidation,
    blood_type: bloodTypeValidation,
    height: heightValidation,
    weight: weightValidation,
    insurance_provider: optionalStringValidation(100),
    insurance_number: optionalStringValidation(50)
  };

  const {
    formData,
    fields,
    isSubmitting,
    hasErrors,
    isFormValid,
    allErrors,
    updateField,
    touchField,
    validateAll,
    clearErrors,
    resetForm
  } = useRealTimeValidation(initialProfileData, validationRules);

  // Computed properties específicas para perfil
  const canSubmit = computed(() => {
    // Para perfil complementar, sempre pode submeter (campos são opcionais)
    return !isSubmitting.value;
  });

  const hasAnyData = computed(() => {
    return Object.values(formData.value).some(value => 
      value !== '' && value !== null && value !== undefined
    );
  });

  const submitError = ref<string | null>(null);

  // Função para submeter dados do perfil
  const submitProfile = async (): Promise<boolean> => {
    if (!canSubmit.value) {
      return false;
    }

    isSubmitting.value = true;
    submitError.value = null;

    try {
      // Validação final (apenas campos preenchidos)
      if (!validateAll()) {
        isSubmitting.value = false;
        return false;
      }

      // TODO: Implementar chamada real da API para atualizar perfil
      // await updatePatientProfile(formData.value);
      
      // Simulação de submissão
      await new Promise(resolve => setTimeout(resolve, 1000));
      
      return true;
    } catch (error) {
      console.error('Erro ao atualizar perfil:', error);
      submitError.value = 'Erro ao salvar dados do perfil. Tente novamente.';
      return false;
    } finally {
      isSubmitting.value = false;
    }
  };

  // Função para carregar dados existentes do perfil
  const loadProfileData = (profileData: Partial<PatientProfileData>) => {
    Object.keys(profileData).forEach(key => {
      const fieldKey = key as keyof PatientProfileData;
      if (profileData[fieldKey] !== undefined) {
        updateField(fieldKey, profileData[fieldKey] as any);
      }
    });
  };

  // Função para obter mensagem de erro específica do campo
  const getFieldError = (fieldName: keyof PatientProfileData): string => {
    const field = fields.value[fieldName];
    return field.errors.length > 0 ? field.errors[0] : '';
  };

  // Função para verificar se campo tem erro
  const hasFieldError = (fieldName: keyof PatientProfileData): boolean => {
    return fields.value[fieldName].errors.length > 0;
  };

  // Função para verificar se campo foi tocado
  const isFieldTouched = (fieldName: keyof PatientProfileData): boolean => {
    return fields.value[fieldName].touched;
  };

  // Função para limpar apenas campos específicos
  const clearField = (fieldName: keyof PatientProfileData) => {
    updateField(fieldName, fieldName === 'height' || fieldName === 'weight' ? null : '');
    touchField(fieldName);
  };

  return {
    // Dados do formulário
    formData,
    fields,
    
    // Estado
    isSubmitting,
    hasErrors,
    isFormValid,
    canSubmit,
    hasAnyData,
    
    // Erros
    allErrors,
    submitError,
    
    // Funções
    updateField,
    touchField,
    submitProfile,
    loadProfileData,
    getFieldError,
    hasFieldError,
    isFieldTouched,
    clearErrors,
    clearField,
    resetForm
  };
}
