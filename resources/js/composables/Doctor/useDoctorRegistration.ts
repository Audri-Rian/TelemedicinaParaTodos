import { ref, computed, watch } from 'vue';
import { useRealTimeValidation, type ValidationRule } from '../useRealTimeValidation';
import { useRateLimit } from '../useRateLimit';
import { useDoctorFormValidation } from './useDoctorFormValidation';
import { useAuth, type RegisterCredentials } from '../useAuth';

/**
 * Interface para dados de registro inicial do médico
 */
export interface DoctorRegistrationData {
  name: string;
  email: string;
  password: string;
  password_confirmation: string;
  crm: string;
  specializations: string[];
  terms_accepted: boolean;
}

/**
 * Interface apenas para campos obrigatórios (para validação)
 */
interface RequiredDoctorFields {
  name: string;
  email: string;
  password: string;
  password_confirmation: string;
  crm: string;
  specializations: string[];
}

/**
 * Dados iniciais para o formulário de registro
 */
const initialData: DoctorRegistrationData = {
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  crm: '',
  specializations: [],
  terms_accepted: false
};

/**
 * Composable para gerenciar o registro inicial de médicos via API
 */
export function useDoctorRegistration() {
  const { register, registerRateLimit, canRegister } = useAuth();

  const { 
    nameValidation,
    emailValidation,
    passwordValidation,
    passwordConfirmationValidation,
    crmValidation,
    specializationsValidation
  } = useDoctorFormValidation();

  // Validação para termos
  const termsValidation: ValidationRule = {
    required: true,
    custom: (value: boolean) => {
      if (!value) {
        return 'Você deve aceitar os termos de serviço';
      }
      return null;
    }
  };

  // Regras de validação para campos obrigatórios
  const validationRules: Record<keyof RequiredDoctorFields, ValidationRule> = {
    name: nameValidation,
    email: emailValidation,
    password: passwordValidation,
    password_confirmation: passwordConfirmationValidation,
    crm: crmValidation,
    specializations: specializationsValidation
  };

  // Dados apenas para validação (sem terms_accepted)
  const requiredData: RequiredDoctorFields = {
    name: initialData.name,
    email: initialData.email,
    password: initialData.password,
    password_confirmation: initialData.password_confirmation,
    crm: initialData.crm,
    specializations: initialData.specializations
  };

  const {
    formData: validationFormData,
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
  } = useRealTimeValidation(requiredData, validationRules);

  // FormData completo (incluindo terms_accepted)
  const formData = ref<DoctorRegistrationData>(initialData);

  // Estado local
  const submitError = ref<string | null>(null);
  const showSuccessMessage = ref(false);

  // Sincronizar dados entre formData completo e validationFormData
  watch(() => formData.value.name, (newValue) => {
    validationFormData.value.name = newValue;
  });
  
  watch(() => formData.value.email, (newValue) => {
    validationFormData.value.email = newValue;
  });
  
  watch(() => formData.value.password, (newValue) => {
    validationFormData.value.password = newValue;
  });
  
  watch(() => formData.value.password_confirmation, (newValue) => {
    validationFormData.value.password_confirmation = newValue;
  });
  
  watch(() => formData.value.crm, (newValue) => {
    validationFormData.value.crm = newValue;
  });
  
  watch(() => formData.value.specializations, (newValue) => {
    validationFormData.value.specializations = newValue;
  }, { deep: true });

  // Computed properties específicas para registro
  const canSubmit = computed(() => {
    return isFormValid.value && 
           !isValidationSubmitting.value && 
           canRegister.value &&
           formData.value.terms_accepted;
  });

  const isProcessing = computed(() => {
    return isValidationSubmitting.value || registerRateLimit.isBlocked.value;
  });

  // Função para submeter formulário de registro
  const submitForm = async (): Promise<boolean> => {
    if (!canSubmit.value) {
      return false;
    }

    // Validação final dos campos obrigatórios
    if (!validateAll()) {
      return false;
    }

    // Validação dos termos
    if (!formData.value.terms_accepted) {
      submitError.value = 'Você deve aceitar os termos de serviço';
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
        user_type: 'doctor',
        crm: formData.value.crm,
        specializations: formData.value.specializations
      };

      // Log detalhado para debug
      console.group('🔍 DEBUG: Doctor Registration Request');
      console.log('📤 Credentials being sent:', JSON.stringify(credentials, null, 2));
      console.log('📊 Form data state:', JSON.stringify(formData.value, null, 2));
      console.log('✅ Form validation state:', {
        isFormValid: isFormValid.value,
        canSubmit: canSubmit.value,
        hasErrors: hasErrors.value,
        termsAccepted: formData.value.terms_accepted
      });
      console.groupEnd();

      const success = await register(credentials);
      
      if (success) {
        showSuccessMessage.value = true;
        resetForm();
        formData.value = { ...initialData };
        return true;
      }
      
      return false;
    } catch (error: any) {
      // Log detalhado do erro para debug
      console.group('❌ DEBUG: Doctor Registration Error');
      console.error('🚨 Error object:', error);
      console.error('📡 Response status:', error?.response?.status);
      console.error('📡 Response data:', error?.response?.data);
      console.error('📡 Response headers:', error?.response?.headers);
      console.error('🔍 Error message:', error?.message);
      console.error('🔍 Error stack:', error?.stack);
      console.groupEnd();

      submitError.value = error?.message || 'Erro ao registrar usuário';
      
      // Mapear erros específicos para campos
      if (error?.response?.data?.errors) {
        console.log('🎯 Backend validation errors:', error?.response?.data?.errors);
        const backendErrors = error?.response?.data?.errors;
        Object.keys(backendErrors).forEach(field => {
          const fieldKey = field as keyof RequiredDoctorFields;
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
  const getFieldError = (fieldName: keyof RequiredDoctorFields): string => {
    const field = fields.value[fieldName];
    return field.errors.length > 0 ? field.errors[0] : '';
  };

  // Função para verificar se campo tem erro
  const hasFieldError = (fieldName: keyof RequiredDoctorFields): boolean => {
    return fields.value[fieldName].errors.length > 0;
  };

  // Função para verificar se campo foi tocado
  const isFieldTouched = (fieldName: keyof RequiredDoctorFields): boolean => {
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
