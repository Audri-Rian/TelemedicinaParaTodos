import { ref, computed } from 'vue';
import { useRealTimeValidation, type ValidationRule } from '../useRealTimeValidation';
import { useDoctorFormValidation } from './useDoctorFormValidation';

/**
 * Interface para dados do perfil complementar do médico
 * Contém apenas campos opcionais da migration
 */
export interface DoctorProfileData {
  biography: string;
  license_number: string;
  license_expiry_date: string;
  consultation_fee: number | null;
  status: string;
  availability_schedule: Record<string, any> | null;
}

/**
 * Dados iniciais para o formulário de perfil complementar
 */
const initialProfileData: DoctorProfileData = {
  biography: '',
  license_number: '',
  license_expiry_date: '',
  consultation_fee: null,
  status: 'active',
  availability_schedule: null
};

/**
 * Composable para gerenciar o cadastro complementar do médico
 * Gerencia campos opcionais que podem ser preenchidos após o cadastro inicial
 */
export function useDoctorProfileUpdate() {
  const { 
    biographyValidation,
    licenseNumberValidation,
    licenseExpiryDateValidation,
    consultationFeeValidation,
    statusValidation,
    optionalStringValidation,
    optionalTextValidation
  } = useDoctorFormValidation();

  // Regras de validação para campos opcionais
  const validationRules: Record<keyof DoctorProfileData, ValidationRule> = {
    biography: biographyValidation,
    license_number: licenseNumberValidation,
    license_expiry_date: licenseExpiryDateValidation,
    consultation_fee: consultationFeeValidation,
    status: statusValidation,
    availability_schedule: {
      required: false,
      custom: (value: any) => {
        // Validação básica para schedule - pode ser expandida conforme necessário
        if (value && typeof value !== 'object') {
          return 'Agenda deve ser um objeto válido';
        }
        return null;
      }
    }
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
      // await updateDoctorProfile(formData.value);
      
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
  const loadProfileData = (profileData: Partial<DoctorProfileData>) => {
    Object.keys(profileData).forEach(key => {
      const fieldKey = key as keyof DoctorProfileData;
      if (profileData[fieldKey] !== undefined) {
        updateField(fieldKey, profileData[fieldKey] as any);
      }
    });
  };

  // Função para obter mensagem de erro específica do campo
  const getFieldError = (fieldName: keyof DoctorProfileData): string => {
    const field = fields.value[fieldName];
    return field.errors.length > 0 ? field.errors[0] : '';
  };

  // Função para verificar se campo tem erro
  const hasFieldError = (fieldName: keyof DoctorProfileData): boolean => {
    return fields.value[fieldName].errors.length > 0;
  };

  // Função para verificar se campo foi tocado
  const isFieldTouched = (fieldName: keyof DoctorProfileData): boolean => {
    return fields.value[fieldName].touched;
  };

  // Função para limpar apenas campos específicos
  const clearField = (fieldName: keyof DoctorProfileData) => {
    const defaultValue = fieldName === 'consultation_fee' ? null : 
                         fieldName === 'availability_schedule' ? null : '';
    updateField(fieldName, defaultValue);
    touchField(fieldName);
  };

  // Função para formatar taxa de consulta para exibição
  const formatConsultationFee = (fee: number | null): string => {
    if (fee === null || fee === undefined) {
      return '';
    }
    return new Intl.NumberFormat('pt-BR', {
      style: 'currency',
      currency: 'BRL'
    }).format(fee);
  };

  // Função para formatar taxa de consulta para input
  const parseConsultationFee = (value: string): number | null => {
    if (!value || value.trim() === '') {
      return null;
    }
    
    // Remove caracteres não numéricos exceto vírgula e ponto
    const cleanValue = value.replace(/[^\d,.]/g, '');
    
    // Converte vírgula para ponto para parsing
    const normalizedValue = cleanValue.replace(',', '.');
    
    const parsed = parseFloat(normalizedValue);
    return isNaN(parsed) ? null : parsed;
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
    resetForm,
    
    // Utilitários específicos para médico
    formatConsultationFee,
    parseConsultationFee
  };
}
