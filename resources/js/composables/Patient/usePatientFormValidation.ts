import { type ValidationRule } from '../useRealTimeValidation';

/**
 * Validações compartilhadas para formulários de paciente
 * Centraliza regras de validação reutilizáveis entre diferentes fluxos
 */
export const usePatientFormValidation = () => {
  
  // Validação de nome completo
  const nameValidation: ValidationRule = {
    required: true,
    min: 2,
    max: 255,
    custom: (value: string) => {
      if (value && !/^[a-zA-ZÀ-ÿ\s]+$/.test(value)) {
        return 'Nome deve conter apenas letras e espaços';
      }
      return null;
    }
  };

  // Validação de email
  const emailValidation: ValidationRule = {
    required: true,
    email: true,
    max: 255
  };

  // Validação de senha
  const passwordValidation: ValidationRule = {
    required: true,
    min: 8,
    custom: (value: string) => {
      if (value && !/(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/.test(value)) {
        return 'Senha deve conter pelo menos uma letra minúscula, uma maiúscula e um número';
      }
      return null;
    }
  };

  // Validação de confirmação de senha
  const passwordConfirmationValidation: ValidationRule = {
    required: true,
    confirmed: 'password'
  };

  // Validação de data de nascimento
  const dateOfBirthValidation: ValidationRule = {
    required: true,
    pattern: /^\d{2}\/\d{2}\/\d{4}$/,
    custom: (value: string) => {
      if (value) {
        // Validar formato dd/mm/aaaa
        const dateRegex = /^(\d{2})\/(\d{2})\/(\d{4})$/;
        const match = value.match(dateRegex);
        
        if (!match) {
          return 'Data deve estar no formato dd/mm/aaaa';
        }
        
        const [, day, month, year] = match;
        const dayNum = parseInt(day, 10);
        const monthNum = parseInt(month, 10);
        const yearNum = parseInt(year, 10);
        
        // Validar se a data é válida
        const date = new Date(yearNum, monthNum - 1, dayNum);
        if (date.getDate() !== dayNum || date.getMonth() !== monthNum - 1 || date.getFullYear() !== yearNum) {
          return 'Data de nascimento inválida';
        }
        
        // Validar se não é data futura
        const today = new Date();
        if (date >= today) {
          return 'Data de nascimento não pode ser no futuro';
        }
        
        // Validar idade (entre 0 e 120 anos)
        const age = today.getFullYear() - yearNum;
        if (age < 0 || age > 120) {
          return 'Data de nascimento inválida';
        }
      }
      return null;
    }
  };

  // Validação de telefone
  const phoneValidation: ValidationRule = {
    required: true,
    min: 10,
    max: 20,
    pattern: /^[\d\s\(\)\-\+]+$/
  };

  // Validação de telefone opcional
  const optionalPhoneValidation: ValidationRule = {
    required: false,
    min: 10,
    max: 20,
    pattern: /^[\d\s\(\)\-\+]+$/
  };

  // Validação de gênero
  const genderValidation: ValidationRule = {
    required: true,
    custom: (value: string) => {
      if (!value || !['male', 'female', 'other'].includes(value)) {
        return 'Selecione um gênero válido';
      }
      return null;
    }
  };

  // Validação de consentimento de telemedicina
  const consentTelemedicineValidation: ValidationRule = {
    required: true,
    custom: (value: boolean) => {
      if (!value) {
        return 'Você deve aceitar os termos de telemedicina';
      }
      return null;
    }
  };

  // Validação de texto opcional
  const optionalTextValidation: ValidationRule = {
    required: false,
    max: 1000
  };

  // Validação de altura (em cm)
  const heightValidation: ValidationRule = {
    required: false,
    custom: (value: number) => {
      if (value && (value < 50 || value > 300)) {
        return 'Altura deve estar entre 50cm e 300cm';
      }
      return null;
    }
  };

  // Validação de peso (em kg)
  const weightValidation: ValidationRule = {
    required: false,
    custom: (value: number) => {
      if (value && (value < 10 || value > 500)) {
        return 'Peso deve estar entre 10kg e 500kg';
      }
      return null;
    }
  };

  // Validação de tipo sanguíneo
  const bloodTypeValidation: ValidationRule = {
    required: false,
    custom: (value: string) => {
      if (value && !/^(A|B|AB|O)[+-]$/.test(value)) {
        return 'Tipo sanguíneo deve estar no formato A+, B-, AB+, etc.';
      }
      return null;
    }
  };

  // Validação de string opcional com tamanho máximo
  const optionalStringValidation = (maxLength: number): ValidationRule => ({
    required: false,
    max: maxLength
  });

  return {
    // Validações obrigatórias
    nameValidation,
    emailValidation,
    passwordValidation,
    passwordConfirmationValidation,
    dateOfBirthValidation,
    phoneValidation,
    genderValidation,
    consentTelemedicineValidation,
    
    // Validações opcionais
    optionalPhoneValidation,
    optionalTextValidation,
    heightValidation,
    weightValidation,
    bloodTypeValidation,
    optionalStringValidation
  };
};
