import { ref, computed, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { useRealTimeValidation, type ValidationRule } from '../useRealTimeValidation';
import { useRateLimit } from '../useRateLimit';
import { useToast } from '../useToast';
import { useDoctorFormValidation } from './useDoctorFormValidation';

/**
 * Interface para dados de registro inicial do médico
 * Contém apenas campos obrigatórios para criação da conta
 */
export interface DoctorRegistrationData {
  name: string;
  email: string;
  password: string;
  password_confirmation: string;
  crm: string;
  cns: string;
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
  cns: '',
  specializations: [],
  terms_accepted: false
};

/**
 * Composable para gerenciar o registro inicial de médicos
 * Focado apenas nos campos obrigatórios para criação da conta
 */
export function useDoctorRegistration() {
  const toast = useToast();
  const {
    nameValidation,
    emailValidation,
    passwordValidation,
    passwordConfirmationValidation,
    crmValidation,
    specializationsValidation
  } = useDoctorFormValidation();

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
    isSubmitting,
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

  /**
   * Rotulos amigáveis para exibir quais campos têm erro nas notificações.
   */
  const fieldLabels: Record<keyof RequiredDoctorFields, string> = {
    name: 'nome completo',
    email: 'e-mail profissional',
    password: 'senha',
    password_confirmation: 'confirmação de senha',
    crm: 'CRM',
    specializations: 'especialização'
  };

  /**
   * Lista os rótulos dos campos com erro, em ordem e formatados como
   * "nome, CRM e especialização".
   */
  const describeInvalidFields = (): string => {
    const invalidKeys = (Object.keys(fields.value) as Array<keyof RequiredDoctorFields>)
      .filter((k) => fields.value[k].errors.length > 0);

    if (!invalidKeys.length) return '';

    const labels = invalidKeys.map((k) => fieldLabels[k]);
    if (labels.length === 1) return labels[0];
    if (labels.length === 2) return `${labels[0]} e ${labels[1]}`;
    return `${labels.slice(0, -1).join(', ')} e ${labels[labels.length - 1]}`;
  };

  // Função para submeter formulário de registro
  const submitForm = async (): Promise<boolean> => {
    // 1. Rate limit bloqueado → mensagem explícita
    if (rateLimit.isBlocked.value) {
      toast.error(rateLimit.getErrorMessage(), {
        title: 'Limite de tentativas atingido',
      });
      return false;
    }

    // 2. Validações client-side falharam (campos obrigatórios não preenchidos)
    if (!isFormValid.value) {
      // Marcar todos os campos como tocados para exibir os erros inline
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

    // 4. Registrar tentativa (pode bloquear agora mesmo)
    // A checagem #2 (isFormValid) acima já cobre validação — não chamamos
    // validateAll() de novo pois isso consumiria uma tentativa do rate limit
    // sem de fato tentar submeter.
    if (!rateLimit.recordAttempt()) {
      toast.error(rateLimit.getErrorMessage(), {
        title: 'Limite de tentativas atingido',
      });
      return false;
    }

    // Usar Inertia.js para submissão
    router.post('/register/doctor', formData.value, {
      onStart: () => {
        isSubmitting.value = true;
      },
      onSuccess: () => {
        toast.success('Bem-vindo! Redirecionando para seu painel...', {
          title: 'Conta criada',
          durationMs: 3000,
        });
        resetForm();
        rateLimit.reset();
        return true;
      },
      onError: (errors) => {
        // Mapear erros do backend para os campos do frontend.
        // Alguns campos do backend (ex.: terms_accepted, cns) não estão mapeados
        // no objeto de validação — nesses casos exibimos a mensagem via toast.
        const unmappedMessages: string[] = [];

        Object.keys(errors).forEach((field) => {
          const value = errors[field];
          const message = Array.isArray(value) ? value[0] : value;

          if (Object.prototype.hasOwnProperty.call(fields.value, field)) {
            const fieldKey = field as keyof RequiredDoctorFields;
            fields.value[fieldKey].errors = Array.isArray(value) ? value : [value];
            fields.value[fieldKey].touched = true;
          } else if (typeof message === 'string' && message) {
            unmappedMessages.push(message);
          }
        });

        // Toast com mensagens do backend. Prioriza os campos não mapeados (que
        // não aparecem inline no form) e cai para a primeira mensagem como fallback.
        const firstError = Object.values(errors)[0];
        const fallback = Array.isArray(firstError) ? firstError[0] : firstError;
        const message = unmappedMessages[0]
          ?? (typeof fallback === 'string' && fallback ? fallback : null);

        toast.error(
          message ?? 'Não foi possível concluir o registro. Verifique os campos destacados.',
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
