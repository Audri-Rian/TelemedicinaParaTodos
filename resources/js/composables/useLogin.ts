import { ref, computed } from 'vue';
import { useAuth, type LoginCredentials } from './useAuth';
import { useRealTimeValidation, type ValidationRule } from './useRealTimeValidation';

/**
 * Interface para dados de login
 */
interface LoginData {
  email: string;
  password: string;
}

/**
 * Dados iniciais do formulário
 */
const initialData: LoginData = {
  email: '',
  password: ''
};

/**
 * Regras de validação
 */
const validationRules: Record<keyof LoginData, ValidationRule> = {
  email: {
    required: true,
    email: true,
    max: 255
  },
  password: {
    required: true,
    min: 1 // Mínimo 1 para permitir login
  }
};

/**
 * Composable para gerenciar login via API
 */
export function useLogin() {
  const { login, isLoading, loginRateLimit, canLogin } = useAuth();

  const {
    formData,
    fields,
    isSubmitting,
    hasErrors,
    isFormValid,
    updateField,
    touchField,
    validateAll,
    clearErrors,
    resetForm
  } = useRealTimeValidation(initialData, validationRules);

  // Estado local
  const submitError = ref<string | null>(null);
  const showSuccessMessage = ref(false);

  // Computed properties
  const canSubmit = computed(() => {
    return isFormValid.value && !isSubmitting.value && canLogin.value;
  });

  const isProcessing = computed(() => {
    return isSubmitting.value || isLoading.value;
  });

  /**
   * Submete formulário de login
   */
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
      const credentials: LoginCredentials = {
        email: formData.value.email,
        password: formData.value.password
      };

      const success = await login(credentials);
      
      if (success) {
        showSuccessMessage.value = true;
        resetForm();
        return true;
      }
      
      return false;
    } catch (error: any) {
      submitError.value = error.message;
      
      // Mapear erros específicos para campos
      if (error.message.includes('email')) {
        fields.value.email.errors = [error.message];
        fields.value.email.touched = true;
      } else if (error.message.includes('senha') || error.message.includes('password')) {
        fields.value.password.errors = [error.message];
        fields.value.password.touched = true;
      }
      
      return false;
    }
  };

  /**
   * Funções de utilidade
   */
  const getFieldError = (fieldName: keyof LoginData): string => {
    const field = fields.value[fieldName];
    return field.errors.length > 0 ? field.errors[0] : '';
  };

  const hasFieldError = (fieldName: keyof LoginData): boolean => {
    return fields.value[fieldName].errors.length > 0;
  };

  const isFieldTouched = (fieldName: keyof LoginData): boolean => {
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
    submitError,
    showSuccessMessage,
    
    // Rate limiting
    rateLimit: loginRateLimit,
    
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