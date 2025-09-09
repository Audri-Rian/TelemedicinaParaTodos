import { ref, computed, watch } from 'vue';

export interface ValidationRule {
  required?: boolean;
  min?: number;
  max?: number;
  email?: boolean;
  confirmed?: string;
  unique?: string;
  date?: boolean;
  before?: string;
  after?: string;
  pattern?: RegExp;
  custom?: (value: any) => string | null;
}

export interface ValidationErrors {
  [key: string]: string[];
}

export interface FormField {
  value: any;
  rules: ValidationRule;
  touched: boolean;
  errors: string[];
}

export function useRealTimeValidation<T extends Record<string, any>>(
  initialData: T,
  validationRules: Record<keyof T, ValidationRule>
) {
  const formData = ref<T>({ ...initialData });
  const fields = ref<Record<keyof T, FormField>>({} as Record<keyof T, FormField>);
  const isSubmitting = ref(false);
  const submitAttempted = ref(false);

  // Inicializar campos
  Object.keys(validationRules).forEach((key) => {
    const fieldKey = key as keyof T;
    fields.value[fieldKey] = {
      value: formData.value[fieldKey],
      rules: validationRules[fieldKey],
      touched: false,
      errors: []
    };
  });

  // Validação individual de campo
  const validateField = (fieldName: keyof T, value: any): string[] => {
    const field = fields.value[fieldName];
    const rules = field.rules;
    const errors: string[] = [];

    // Required
    if (rules.required && (!value || (typeof value === 'string' && value.trim() === ''))) {
      errors.push(`${String(fieldName)} é obrigatório`);
    }

    // Min length
    if (rules.min && value && value.length < rules.min) {
      errors.push(`${String(fieldName)} deve ter pelo menos ${rules.min} caracteres`);
    }

    // Max length
    if (rules.max && value && value.length > rules.max) {
      errors.push(`${String(fieldName)} deve ter no máximo ${rules.max} caracteres`);
    }

    // Email
    if (rules.email && value) {
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (!emailRegex.test(value)) {
        errors.push(`${String(fieldName)} deve ser um email válido`);
      }
    }

    // Confirmed
    if (rules.confirmed && value) {
      const confirmField = fields.value[rules.confirmed as keyof T];
      if (confirmField && value !== confirmField.value) {
        errors.push(`${String(fieldName)} deve ser igual a ${String(rules.confirmed)}`);
      }
    }

    // Date
    if (rules.date && value) {
      const date = new Date(value);
      if (isNaN(date.getTime())) {
        errors.push(`${String(fieldName)} deve ser uma data válida`);
      }
    }

    // Before
    if (rules.before && value) {
      const fieldDate = new Date(value);
      const beforeDate = rules.before === 'today' ? new Date() : new Date(rules.before);
      if (fieldDate >= beforeDate) {
        errors.push(`${String(fieldName)} deve ser anterior a ${rules.before}`);
      }
    }

    // After
    if (rules.after && value) {
      const fieldDate = new Date(value);
      const afterDate = rules.after === 'today' ? new Date() : new Date(rules.after);
      if (fieldDate <= afterDate) {
        errors.push(`${String(fieldName)} deve ser posterior a ${rules.after}`);
      }
    }

    // Pattern
    if (rules.pattern && value && !rules.pattern.test(value)) {
      errors.push(`${String(fieldName)} tem formato inválido`);
    }

    // Custom validation
    if (rules.custom && value) {
      const customError = rules.custom(value);
      if (customError) {
        errors.push(customError);
      }
    }

    return errors;
  };

  // Atualizar campo
  const updateField = (fieldName: keyof T, value: any) => {
    const field = fields.value[fieldName];
    field.value = value;
    field.touched = true;
    field.errors = validateField(fieldName, value);
    formData.value[fieldName] = value;
  };

  // Validar todos os campos
  const validateAll = (): boolean => {
    let isValid = true;
    Object.keys(fields.value).forEach((key) => {
      const fieldKey = key as keyof T;
      const field = fields.value[fieldKey];
      field.touched = true;
      field.errors = validateField(fieldKey, field.value);
      if (field.errors.length > 0) {
        isValid = false;
      }
    });
    return isValid;
  };

  // Computed properties
  const hasErrors = computed(() => {
    return Object.values(fields.value).some(field => field.errors.length > 0);
  });

  const isFormValid = computed(() => {
    return !hasErrors.value && Object.values(fields.value).every(field => field.touched);
  });

  const allErrors = computed(() => {
    const errors: ValidationErrors = {};
    Object.keys(fields.value).forEach((key) => {
      const field = fields.value[key as keyof T];
      if (field.errors.length > 0) {
        errors[key] = field.errors;
      }
    });
    return errors;
  });

  // Watch para validação em tempo real
  Object.keys(fields.value).forEach((key) => {
    const fieldKey = key as keyof T;
    watch(
      () => fields.value[fieldKey].value,
      (newValue) => {
        if (fields.value[fieldKey].touched) {
          fields.value[fieldKey].errors = validateField(fieldKey, newValue);
        }
      }
    );
  });

  // Função para marcar campo como tocado
  const touchField = (fieldName: keyof T) => {
    fields.value[fieldName].touched = true;
  };

  // Função para limpar erros
  const clearErrors = () => {
    Object.keys(fields.value).forEach((key) => {
      fields.value[key as keyof T].errors = [];
      fields.value[key as keyof T].touched = false;
    });
  };

  // Função para resetar formulário
  const resetForm = () => {
    formData.value = { ...initialData };
    Object.keys(fields.value).forEach((key) => {
      const fieldKey = key as keyof T;
      fields.value[fieldKey].value = formData.value[fieldKey];
      fields.value[fieldKey].touched = false;
      fields.value[fieldKey].errors = [];
    });
  };

  return {
    formData,
    fields,
    isSubmitting,
    submitAttempted,
    hasErrors,
    isFormValid,
    allErrors,
    updateField,
    touchField,
    validateAll,
    clearErrors,
    resetForm
  };
}
