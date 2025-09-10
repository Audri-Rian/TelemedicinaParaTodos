import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { useRealTimeValidation, type ValidationRule } from '../useRealTimeValidation';
import { useRateLimit } from '../useRateLimit';
import { usePatientFormValidation } from './usePatientFormValidation';

/**
 * Interface para dados de registro inicial do paciente
 * Contém apenas campos obrigatórios para criação da conta
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
 * Composable para gerenciar o registro inicial de pacientes
 * Focado apenas nos campos obrigatórios para criação da conta
 */
export function usePatientRegistration() {
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
    isSubmitting,
    hasErrors,
    isFormValid,
    allErrors,
    updateField,
    touchField,
    validateAll,
    clearErrors,
    resetForm
  } = useRealTimeValidation(initialData, validationRules);

  // Rate limiting: 5 tentativas por 15 minutos, bloqueio por 1 hora
  const rateLimit = useRateLimit({
    maxAttempts: 5,
    windowMs: 15 * 60 * 1000, // 15 minutos
    blockDurationMs: 60 * 60 * 1000 // 1 hora
  });

  // Computed properties específicas para registro
  const canSubmit = computed(() => {
    return isFormValid.value && !isSubmitting.value && rateLimit.canAttempt.value;
  });

  const submitError = computed(() => {
    if (rateLimit.isBlocked.value) {
      return rateLimit.getErrorMessage();
    }
    return null;
  });

  // Função para submeter formulário de registro
  const submitForm = async (): Promise<boolean> => {
    if (!canSubmit.value) {
      return false;
    }

    // Verifica rate limit
    if (!rateLimit.recordAttempt()) {
      return false;
    }

    // Validação final
    if (!validateAll()) {
      return false;
    }

    // Usar Inertia.js para submissão
    router.post('/register/patient', formData.value, {
      onStart: () => {
        isSubmitting.value = true;
      },
      onSuccess: () => {
        // Sucesso - o Laravel já redireciona para dashboard
        resetForm();
        rateLimit.reset();
        return true;
      },
      onError: (errors) => {
        // Mapear erros do backend para os campos do frontend
        Object.keys(errors).forEach(field => {
          const fieldKey = field as keyof PatientRegistrationData;
          if (fields.value[fieldKey]) {
            fields.value[fieldKey].errors = Array.isArray(errors[field]) 
              ? errors[field] 
              : [errors[field]];
            fields.value[fieldKey].touched = true;
          }
        });
        return false;
      },
      onFinish: () => {
        isSubmitting.value = false;
      }
    });

    return true;
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
    isSubmitting,
    hasErrors,
    isFormValid,
    canSubmit,
    
    // Erros
    allErrors,
    submitError,
    
    // Rate limiting
    rateLimit: rateLimit.getStatus(),
    
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
