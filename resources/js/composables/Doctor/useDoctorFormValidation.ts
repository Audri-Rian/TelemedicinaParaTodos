import { type ValidationRule } from '../useRealTimeValidation';

/**
 * Validações compartilhadas para formulários de médico
 * Centraliza regras de validação reutilizáveis entre diferentes fluxos
 */
export const useDoctorFormValidation = () => {
  
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

  // Validação de CRM
  const crmValidation: ValidationRule = {
    required: true,
    min: 4,
    max: 20,
    custom: (value: string) => {
      if (value && !/^[A-Z0-9]+$/.test(value)) {
        return 'CRM deve conter apenas letras maiúsculas e números';
      }
      return null;
    }
  };

  // Validação de especializações (array de UUIDs)
  const specializationsValidation: ValidationRule = {
    required: true,
    custom: (value: string[]) => {
      if (!Array.isArray(value)) {
        return 'Especializações deve ser uma lista';
      }
      if (value.length === 0) {
        return 'Pelo menos uma especialização deve ser selecionada';
      }
      if (value.length > 5) {
        return 'Máximo de 5 especializações permitidas';
      }
      // Validar formato UUID (versão mais flexível para UUIDs v4)
      const uuidRegex = /^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i;
      const invalidUuid = value.find(id => !uuidRegex.test(id));
      if (invalidUuid) {
        return 'ID de especialização inválido';
      }
      return null;
    }
  };

  // Validação de biografia (opcional)
  const biographyValidation: ValidationRule = {
    required: false,
    max: 2000
  };

  // Validação de número de licença (opcional)
  const licenseNumberValidation: ValidationRule = {
    required: false,
    min: 5,
    max: 50,
    custom: (value: string) => {
      if (value && !/^[A-Z0-9\-\s]+$/.test(value)) {
        return 'Número da licença deve conter apenas letras maiúsculas, números, hífens e espaços';
      }
      return null;
    }
  };

  // Validação de data de expiração da licença (opcional)
  const licenseExpiryDateValidation: ValidationRule = {
    required: false,
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
          return 'Data de expiração inválida';
        }
        
        // Validar se não é data muito antiga (mais de 10 anos atrás)
        const today = new Date();
        const tenYearsAgo = new Date(today.getFullYear() - 10, today.getMonth(), today.getDate());
        if (date < tenYearsAgo) {
          return 'Data de expiração muito antiga';
        }
      }
      return null;
    }
  };

  // Validação de taxa de consulta (opcional)
  const consultationFeeValidation: ValidationRule = {
    required: false,
    custom: (value: number) => {
      if (value && (value < 0 || value > 10000)) {
        return 'Taxa de consulta deve estar entre R$ 0,00 e R$ 10.000,00';
      }
      return null;
    }
  };

  // Validação de status
  const statusValidation: ValidationRule = {
    required: false,
    custom: (value: string) => {
      if (value && !['active', 'inactive', 'suspended'].includes(value)) {
        return 'Status deve ser ativo, inativo ou suspenso';
      }
      return null;
    }
  };

  // Validação de string opcional com tamanho máximo
  const optionalStringValidation = (maxLength: number): ValidationRule => ({
    required: false,
    max: maxLength
  });

  // Validação de texto opcional
  const optionalTextValidation: ValidationRule = {
    required: false,
    max: 2000
  };

  return {
    // Validações obrigatórias para registro inicial
    nameValidation,
    emailValidation,
    passwordValidation,
    passwordConfirmationValidation,
    crmValidation,
    specializationsValidation,
    
    // Validações opcionais para perfil complementar
    biographyValidation,
    licenseNumberValidation,
    licenseExpiryDateValidation,
    consultationFeeValidation,
    statusValidation,
    optionalStringValidation,
    optionalTextValidation
  };
};
