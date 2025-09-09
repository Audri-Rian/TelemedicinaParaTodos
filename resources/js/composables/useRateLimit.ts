import { ref, computed } from 'vue';

export interface RateLimitConfig {
  maxAttempts: number;
  windowMs: number; // em milissegundos
  blockDurationMs: number; // em milissegundos
}

export interface RateLimitState {
  attempts: number;
  lastAttempt: number;
  blockedUntil: number;
  isBlocked: boolean;
}

export function useRateLimit(config: RateLimitConfig) {
  const state = ref<RateLimitState>({
    attempts: 0,
    lastAttempt: 0,
    blockedUntil: 0,
    isBlocked: false
  });

  // Computed properties
  const isBlocked = computed(() => {
    const now = Date.now();
    if (state.value.blockedUntil > now) {
      return true;
    }
    
    // Reset se o bloqueio expirou
    if (state.value.isBlocked && state.value.blockedUntil <= now) {
      state.value.isBlocked = false;
      state.value.attempts = 0;
    }
    
    return false;
  });

  const remainingAttempts = computed(() => {
    return Math.max(0, config.maxAttempts - state.value.attempts);
  });

  const timeUntilUnblock = computed(() => {
    if (!isBlocked.value) return 0;
    return Math.max(0, state.value.blockedUntil - Date.now());
  });

  const canAttempt = computed(() => {
    return !isBlocked.value && remainingAttempts.value > 0;
  });

  // Função para registrar uma tentativa
  const recordAttempt = (): boolean => {
    const now = Date.now();
    
    // Se está bloqueado, não permite tentativa
    if (isBlocked.value) {
      return false;
    }

    // Reset contador se passou da janela de tempo
    if (now - state.value.lastAttempt > config.windowMs) {
      state.value.attempts = 0;
    }

    // Incrementa tentativas
    state.value.attempts++;
    state.value.lastAttempt = now;

    // Verifica se deve bloquear
    if (state.value.attempts >= config.maxAttempts) {
      state.value.isBlocked = true;
      state.value.blockedUntil = now + config.blockDurationMs;
      return false;
    }

    return true;
  };

  // Função para resetar o rate limit
  const reset = () => {
    state.value = {
      attempts: 0,
      lastAttempt: 0,
      blockedUntil: 0,
      isBlocked: false
    };
  };

  // Função para verificar se pode fazer uma ação
  const canPerformAction = (): boolean => {
    return canAttempt.value;
  };

  // Função para obter mensagem de erro
  const getErrorMessage = (): string => {
    if (isBlocked.value) {
      const minutes = Math.ceil(timeUntilUnblock.value / 60000);
      return `Muitas tentativas. Tente novamente em ${minutes} minuto${minutes > 1 ? 's' : ''}.`;
    }
    
    if (remainingAttempts.value === 0) {
      return 'Limite de tentativas excedido. Tente novamente mais tarde.';
    }
    
    return '';
  };

  // Função para obter status do rate limit
  const getStatus = () => {
    return {
      isBlocked: isBlocked.value,
      remainingAttempts: remainingAttempts.value,
      timeUntilUnblock: timeUntilUnblock.value,
      canAttempt: canAttempt.value,
      errorMessage: getErrorMessage()
    };
  };

  return {
    isBlocked,
    remainingAttempts,
    timeUntilUnblock,
    canAttempt,
    recordAttempt,
    reset,
    canPerformAction,
    getErrorMessage,
    getStatus
  };
}
