import { ref, computed } from 'vue';
import { useRealTimeValidation, type ValidationRule } from '../useRealTimeValidation';
import { useRateLimit } from '../useRateLimit';
import { usePatientFormValidation } from './usePatientFormValidation';
import { useAuth, type RegisterCredentials } from '../useAuth';

/**
 * Interface para dados de registro inicial do paciente
 */
export interface PatientRegistrationData {
  name: string;
  email: string;
  password: string;
  password_confirmation: string;
  date_of_birth: string;
  phone_number: string;
  gender: string;
  consent_telemedicine: boolean;
}

/**
 * Dados iniciais para o formulário de registro
 */
const initialData: PatientRegistrationData = {
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  date_of_birth: '',
  phone_number: '',
  gender: '',
  consent_telemedicine: false
};

/**
 * Composable para gerenciar o registro inicial de pacientes via API
 */
export function usePatientRegistration() {
  const { register, registerRateLimit, canRegister } = useAuth();

  const { 
    nameValidation,
    emailValidation,
    passwordValidation,
    passwordConfirmationValidation,
    dateOfBirthValidation,
    phoneValidation,
    genderValidation,
    consentTelemedicineValidation
  } = usePatientFormValidation();

  // Regras de validação para campos obrigatórios
  const validationRules: Record<keyof PatientRegistrationData, ValidationRule> = {
    name: nameValidation,
    email: emailValidation,
    password: passwordValidation,
    password_confirmation: passwordConfirmationValidation,
    date_of_birth: dateOfBirthValidation,
    phone_number: phoneValidation,
    gender: genderValidation,
    consent_telemedicine: consentTelemedicineValidation
  };

  const {
    formData,
    fields,
    isSubmitting: isValidationSubmitting,
    hasErrors,
    isFormValid,
    allErrors,
    updateField,
    touchField,
    validateAll,
    clearErrors,
    resetForm
  } = useRealTimeValidation(initialData, validationRules);

  // Estado local
  const submitError = ref<string | null>(null);
  const showSuccessMessage = ref(false);

  // Computed properties específicas para registro
  const canSubmit = computed(() => {
    return isFormValid.value && 
           !isValidationSubmitting.value && 
           canRegister.value;
  });

  const isProcessing = computed(() => {
    return isValidationSubmitting.value || registerRateLimit.isBlocked.value;
  });

  // Função para converter data brasileira para ISO
  const convertDateToISO = (dateString: string): string => {
    if (!dateString) return '';
    
    // Remove espaços e verifica se está no formato dd/mm/aaaa
    const cleanDate = dateString.trim();
    const dateRegex = /^(\d{2})\/(\d{2})\/(\d{4})$/;
    const match = cleanDate.match(dateRegex);
    
    if (match) {
      const [, day, month, year] = match;
      return `${year}-${month}-${day}`;
    }
    
    // Se já estiver no formato ISO, retorna como está
    return cleanDate;
  };

  // Função para limpar formatação do telefone
  const cleanPhoneNumber = (phoneString: string): string => {
    if (!phoneString) return '';
    
    // Remove todos os caracteres não numéricos
    return phoneString.replace(/\D/g, '');
  };

  // Função para submeter formulário de registro
  const submitForm = async (): Promise<boolean> => {
    if (!canSubmit.value) {
      return false;
    }

    // Validação final
    if (!validateAll()) {
      return false;
    }

    clearErrors();
    submitError.value = null;

    try {
      const credentials: RegisterCredentials = {
        name: formData.value.name,
        email: formData.value.email,
        password: formData.value.password,
        password_confirmation: formData.value.password_confirmation,
        user_type: 'patient',
        date_of_birth: convertDateToISO(formData.value.date_of_birth),
        phone_number: cleanPhoneNumber(formData.value.phone_number),
        gender: formData.value.gender
      };

      const success = await register(credentials);
      
      if (success) {
        showSuccessMessage.value = true;
        resetForm();
        return true;
      }
      
      return false;
    } catch (error: any) {
      submitError.value = error.message;
      
      // Mapear erros específicos para campos
      if (error.response?.data?.errors) {
        const backendErrors = error.response.data.errors;
        Object.keys(backendErrors).forEach(field => {
          const fieldKey = field as keyof PatientRegistrationData;
          if (fields.value[fieldKey]) {
            fields.value[fieldKey].errors = Array.isArray(backendErrors[field]) 
              ? backendErrors[field] 
              : [backendErrors[field]];
            fields.value[fieldKey].touched = true;
          }
        });
      }
      
      return false;
    }
  };

  // Função para obter mensagem de erro específica do campo
  const getFieldError = (fieldName: keyof PatientRegistrationData): string => {
    const field = fields.value[fieldName];
    return field.errors.length > 0 ? field.errors[0] : '';
  };

  // Função para verificar se campo tem erro
  const hasFieldError = (fieldName: keyof PatientRegistrationData): boolean => {
    return fields.value[fieldName].errors.length > 0;
  };

  // Função para verificar se campo foi tocado
  const isFieldTouched = (fieldName: keyof PatientRegistrationData): boolean => {
    return fields.value[fieldName].touched;
  };

  return {
    // Dados do formulário
    formData,
    fields,
    
    // Estado
    isSubmitting: isProcessing,
    hasErrors,
    isFormValid,
    canSubmit,
    
    // Erros
    allErrors,
    submitError,
    showSuccessMessage,
    
    // Rate limiting
    rateLimit: registerRateLimit,
    
    // Funções
    updateField,
    touchField,
    submitForm,
    getFieldError,
    hasFieldError,
    isFieldTouched,
    clearErrors,
    resetForm
  };
}
