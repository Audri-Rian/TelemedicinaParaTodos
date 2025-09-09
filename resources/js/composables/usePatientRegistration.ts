import { ref, computed } from 'vue';
import { useRealTimeValidation, type ValidationRule } from './useRealTimeValidation';
import { useRateLimit } from './useRateLimit';

export interface PatientRegistrationData {
  name: string;
  email: string;
  password: string;
  password_confirmation: string;
  date_of_birth: string;
  emergency_contact: string;
  emergency_phone: string;
  consent_telemedicine: boolean;
}

const initialData: PatientRegistrationData = {
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  date_of_birth: '',
  emergency_contact: '',
  emergency_phone: '',
  consent_telemedicine: false
};

const validationRules: Record<keyof PatientRegistrationData, ValidationRule> = {
  name: {
    required: true,
    min: 2,
    max: 255,
    custom: (value: string) => {
      if (value && !/^[a-zA-ZÀ-ÿ\s]+$/.test(value)) {
        return 'Nome deve conter apenas letras e espaços';
      }
      return null;
    }
  },
  email: {
    required: true,
    email: true,
    max: 255
  },
  password: {
    required: true,
    min: 8,
    custom: (value: string) => {
      if (value && !/(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/.test(value)) {
        return 'Senha deve conter pelo menos uma letra minúscula, uma maiúscula e um número';
      }
      return null;
    }
  },
  password_confirmation: {
    required: true,
    confirmed: 'password'
  },
  date_of_birth: {
    required: true,
    date: true,
    before: 'today',
    custom: (value: string) => {
      if (value) {
        const birthDate = new Date(value);
        const today = new Date();
        const age = today.getFullYear() - birthDate.getFullYear();
        if (age < 0 || age > 120) {
          return 'Data de nascimento inválida';
        }
      }
      return null;
    }
  },
  emergency_contact: {
    required: true,
    min: 2,
    max: 100
  },
  emergency_phone: {
    required: true,
    min: 10,
    max: 20,
    pattern: /^[\d\s\(\)\-\+]+$/
  },
  consent_telemedicine: {
    required: true,
    custom: (value: boolean) => {
      if (!value) {
        return 'Você deve aceitar os termos de telemedicina';
      }
      return null;
    }
  }
};

export function usePatientRegistration() {
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

  // Computed properties específicas
  const canSubmit = computed(() => {
    return isFormValid.value && !isSubmitting.value && rateLimit.canAttempt.value;
  });

  const submitError = computed(() => {
    if (rateLimit.isBlocked.value) {
      return rateLimit.getErrorMessage();
    }
    return null;
  });

  // Função para submeter formulário
  const submitForm = async (): Promise<boolean> => {
    if (!canSubmit.value) {
      return false;
    }

    // Verifica rate limit
    if (!rateLimit.recordAttempt()) {
      return false;
    }

    isSubmitting.value = true;

    try {
      // Validação final
      if (!validateAll()) {
        isSubmitting.value = false;
        return false;
      }

      // Simulação de submissão (substituir pela chamada real da API)
      await new Promise(resolve => setTimeout(resolve, 1000));
      
      // Reset do formulário após sucesso
      resetForm();
      rateLimit.reset();
      
      return true;
    } catch (error) {
      console.error('Erro ao submeter formulário:', error);
      return false;
    } finally {
      isSubmitting.value = false;
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
