import { ref, computed } from 'vue';
import { useRealTimeValidation, type ValidationRule } from './useRealTimeValidation';
import { useRateLimit } from './useRateLimit';

export interface DoctorRegistrationData {
  name: string;
  email: string;
  password: string;
  password_confirmation: string;
  crm: string;
  specialty: string;
  license_number?: string;
  license_expiry_date?: string;
}

const initialData: DoctorRegistrationData = {
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  crm: '',
  specialty: '',
  license_number: '',
  license_expiry_date: ''
};

const validationRules: Record<keyof DoctorRegistrationData, ValidationRule> = {
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
  crm: {
    required: true,
    min: 4,
    max: 20,
    pattern: /^[\d\/]+$/,
    custom: (value: string) => {
      if (value && !/^\d{4,6}\/[A-Z]{2}$/.test(value)) {
        return 'CRM deve estar no formato: 123456/SP';
      }
      return null;
    }
  },
  specialty: {
    required: true,
    min: 2,
    max: 100
  },
  license_number: {
    min: 5,
    max: 50,
    pattern: /^[A-Z0-9\-\/]+$/
  },
  license_expiry_date: {
    date: true,
    after: 'today',
    custom: (value: string) => {
      if (value) {
        const expiryDate = new Date(value);
        const today = new Date();
        if (expiryDate <= today) {
          return 'Data de expiração deve ser futura';
        }
      }
      return null;
    }
  }
};

export function useDoctorRegistration() {
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

  // Rate limiting: 3 tentativas por 15 minutos, bloqueio por 2 horas
  const rateLimit = useRateLimit({
    maxAttempts: 3,
    windowMs: 15 * 60 * 1000, // 15 minutos
    blockDurationMs: 2 * 60 * 60 * 1000 // 2 horas
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
  const getFieldError = (fieldName: keyof DoctorRegistrationData): string => {
    const field = fields.value[fieldName];
    return field.errors.length > 0 ? field.errors[0] : '';
  };

  // Função para verificar se campo tem erro
  const hasFieldError = (fieldName: keyof DoctorRegistrationData): boolean => {
    return fields.value[fieldName].errors.length > 0;
  };

  // Função para verificar se campo foi tocado
  const isFieldTouched = (fieldName: keyof DoctorRegistrationData): boolean => {
    return fields.value[fieldName].touched;
  };

  // Lista de especialidades médicas
  const specialties = [
    'Cardiologia',
    'Dermatologia',
    'Endocrinologia',
    'Gastroenterologia',
    'Neurologia',
    'Pediatria',
    'Psiquiatria',
    'Ortopedia',
    'Oftalmologia',
    'Urologia',
    'Ginecologia',
    'Obstetrícia',
    'Anestesiologia',
    'Radiologia',
    'Patologia',
    'Medicina Interna',
    'Cirurgia Geral',
    'Cirurgia Plástica',
    'Otorrinolaringologia',
    'Pneumologia'
  ];

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
    
    // Dados específicos
    specialties,
    
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
