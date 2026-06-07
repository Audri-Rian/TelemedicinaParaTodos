import { computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { useRealTimeValidation, type ValidationRule } from '../useRealTimeValidation';
import { useRateLimit } from '../useRateLimit';
import { useToast } from '../useToast';
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
  const toast = useToast();
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

  const fieldLabels: Record<keyof PatientRegistrationData, string> = {
    name: 'nome completo',
    email: 'e-mail',
    password: 'senha',
    password_confirmation: 'confirmação de senha',
    date_of_birth: 'data de nascimento',
    phone_number: 'telefone',
    gender: 'gênero',
    consent_telemedicine: 'termos de telemedicina'
  };

  const describeInvalidFields = (): string => {
    const invalidKeys = (Object.keys(fields.value) as Array<keyof PatientRegistrationData>)
      .filter((k) => fields.value[k].errors.length > 0);

    if (!invalidKeys.length) return '';

    const labels = invalidKeys.map((k) => fieldLabels[k]);
    if (labels.length === 1) return labels[0];
    if (labels.length === 2) return `${labels[0]} e ${labels[1]}`;
    return `${labels.slice(0, -1).join(', ')} e ${labels[labels.length - 1]}`;
  };

  // Função para submeter formulário de registro
  const submitForm = async (): Promise<boolean> => {
    // 1. Rate limit já bloqueado
    if (rateLimit.isBlocked.value) {
      toast.error(rateLimit.getErrorMessage(), {
        title: 'Limite de tentativas atingido',
      });
      return false;
    }

    // 2. Validações client-side falharam
    if (!isFormValid.value) {
      validateAll();
      const invalid = describeInvalidFields();
      toast.warning(
        invalid
          ? `Revise ${invalid} antes de enviar.`
          : 'Preencha os campos obrigatórios antes de enviar.',
        { title: 'Formulário incompleto' },
      );
      return false;
    }

    // 3. Já está submetendo
    if (isSubmitting.value) {
      return false;
    }

    // 4. Registrar tentativa. A validação já foi feita no passo #2 — não
    // chamamos validateAll() de novo pois consumiria uma tentativa do rate
    // limit sem realmente tentar submeter.
    if (!rateLimit.recordAttempt()) {
      toast.error(rateLimit.getErrorMessage(), {
        title: 'Limite de tentativas atingido',
      });
      return false;
    }

    // Usar Inertia.js para submissão
    router.post('/register/patient', formData.value, {
      onStart: () => {
        isSubmitting.value = true;
      },
      onSuccess: () => {
        toast.success('Bem-vindo(a)! Redirecionando para seu painel...', {
          title: 'Conta criada',
          durationMs: 3000,
        });
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

        const firstError = Object.values(errors)[0];
        const message = Array.isArray(firstError) ? firstError[0] : firstError;
        toast.error(
          typeof message === 'string' && message
            ? message
            : 'Não foi possível concluir o registro. Verifique os campos destacados.',
          { title: 'Falha no registro' },
        );
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
