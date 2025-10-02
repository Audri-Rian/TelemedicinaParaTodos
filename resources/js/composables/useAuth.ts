import { ref, computed, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import apiClient from '@/lib/axios';
import { useRateLimit } from './useRateLimit';

/**
 * Interfaces para autenticação
 */
export interface User {
  id: string;
  name: string;
  email: string;
  doctor?: {
    id: string;
    crm: string;
    specializations: Array<{
      id: string;
      name: string;
    }>;
  };
  patient?: {
    id: string;
    gender: string;
    date_of_birth: string;
    phone_number: string;
  };
}

export interface LoginCredentials {
  email: string;
  password: string;
}

export interface RegisterCredentials {
  name: string;
  email: string;
  password: string;
  password_confirmation: string;
  user_type: 'doctor' | 'patient';
  // Campos específicos de médico
  crm?: string;
  specializations?: string[];
  // Campos específicos de paciente
  date_of_birth?: string;
  phone_number?: string;
  gender?: string;
}

export interface AuthResponse {
  success: boolean;
  message: string;
  data: {
    user: User;
    token: string;
    token_type: string;
    expires_at: string;
    redirect_to: string;
  };
}

/**
 * Estado global de autenticação
 */
const user = ref<User | null>(null);
const isAuthenticated = ref(false);
const isLoading = ref(false);

/**
 * Composable principal de autenticação
 */
export function useAuth() {
  // Rate limiting para login
  const loginRateLimit = useRateLimit({
    maxAttempts: 5,
    windowMs: 15 * 60 * 1000, // 15 minutos
    blockDurationMs: 60 * 60 * 1000 // 1 hora
  });

  // Rate limiting para registro
  const registerRateLimit = useRateLimit({
    maxAttempts: 3,
    windowMs: 60 * 60 * 1000, // 1 hora
    blockDurationMs: 24 * 60 * 60 * 1000 // 24 horas
  });

  /**
   * Inicializa estado de autenticação
   */
  const initializeAuth = async (): Promise<void> => {
    isLoading.value = true;
    
    try {
      const authContext = apiClient.getAuthContext();
      if (authContext) {
        // Verificar se o token ainda é válido
        const tokenExpiry = new Date(authContext.expires_at);
        if (tokenExpiry > new Date()) {
          // Atualizar estado com dados do contexto
          user.value = {
            id: authContext.user.id,
            name: authContext.user.name,
            email: authContext.user.email,
            doctor: authContext.user.role === 'doctor' ? {
              id: '',
              crm: '',
              specializations: []
            } : undefined,
            patient: authContext.user.role === 'patient' ? {
              id: '',
              gender: '',
              date_of_birth: '',
              phone_number: ''
            } : undefined
          };
          isAuthenticated.value = true;
          
          // Buscar dados completos do usuário apenas se o token for válido
          // e não estivermos em uma página pública
          if (!window.location.pathname.includes('/register') && 
              !window.location.pathname.includes('/login')) {
            await refreshUserData();
          }
        } else {
          // Token expirado, limpar contexto
          apiClient.logout();
          user.value = null;
          isAuthenticated.value = false;
        }
      } else {
        // Sem contexto de autenticação, garantir estado limpo
        user.value = null;
        isAuthenticated.value = false;
      }
    } catch (error) {
      // Contexto inválido, limpar estado
      console.warn('Auth initialization failed:', error);
      user.value = null;
      isAuthenticated.value = false;
    } finally {
      isLoading.value = false;
    }
  };

  /**
   * Atualiza dados completos do usuário
   */
  const refreshUserData = async (): Promise<void> => {
    try {
      const response = await apiClient.get('/user');
      const userData = response.data.data.user;
      
      user.value = userData;
      
      // Atualizar contexto com dados completos
      const authContext = apiClient.getAuthContext();
      if (authContext) {
        authContext.user = {
          id: userData.id,
          name: userData.name,
          email: userData.email,
          role: (userData.doctor ? 'doctor' : 'patient') as 'doctor' | 'patient'
        };
        apiClient.setAuthContext(authContext);
      }
    } catch (error) {
      console.error('Error refreshing user data:', error);
    }
  };

  /**
   * Realiza login
   */
  const login = async (credentials: LoginCredentials): Promise<boolean> => {
    if (!loginRateLimit.canAttempt.value) {
      throw new Error(loginRateLimit.getErrorMessage());
    }

    isLoading.value = true;

    try {
      const response = await apiClient.post<AuthResponse>('/login', credentials);
      const { user: userData, token, expires_at, redirect_to } = response.data.data;

      // Criar contexto de autenticação
      const authContext = {
        user: {
          id: userData.id,
          name: userData.name,
          email: userData.email,
          role: (userData.doctor ? 'doctor' : 'patient') as 'doctor' | 'patient'
        },
        token,
        expires_at
      };

      // Armazenar contexto
      apiClient.setAuthContext(authContext);
      
      // Atualizar estado
      user.value = userData;
      isAuthenticated.value = true;
      
      // Resetar rate limit
      loginRateLimit.reset();
      
      // Redirecionar
      window.location.href = redirect_to;
      
      return true;
    } catch (error: any) {
      // Registrar tentativa de login
      loginRateLimit.recordAttempt();
      
      const errorMessage = error.response?.data?.message || 'Erro ao fazer login';
      throw new Error(errorMessage);
    } finally {
      isLoading.value = false;
    }
  };

  /**
   * Realiza registro
   */
  const register = async (credentials: RegisterCredentials): Promise<boolean> => {
    if (!registerRateLimit.canAttempt.value) {
      throw new Error(registerRateLimit.getErrorMessage());
    }

    isLoading.value = true;

    try {
      // Log detalhado da requisição HTTP
      console.group('🌐 DEBUG: HTTP Register Request');
      console.log('📤 Request URL:', '/register');
      console.log('📤 Request payload:', JSON.stringify(credentials, null, 2));
      console.log('📤 Request headers:', 'Content-Type: application/json, Accept: application/json');
      console.groupEnd();

      const response = await apiClient.post<AuthResponse>('/register', credentials);
      const { user: userData, token, expires_at, redirect_to } = response.data.data;

      // Criar contexto de autenticação
      const authContext = {
        user: {
          id: userData.id,
          name: userData.name,
          email: userData.email,
          role: (userData.doctor ? 'doctor' : 'patient') as 'doctor' | 'patient'
        },
        token,
        expires_at
      };

      // Armazenar contexto
      apiClient.setAuthContext(authContext);
      
      // Atualizar estado
      user.value = userData;
      isAuthenticated.value = true;
      
      // Resetar rate limit
      registerRateLimit.reset();
      
      // Redirecionar
      window.location.href = redirect_to;
      
      return true;
    } catch (error: any) {
      // Log detalhado do erro HTTP
      console.group('🚨 DEBUG: HTTP Register Error');
      console.error('📡 Error object:', error);
      console.error('📡 Error response:', error?.response);
      console.error('📡 Error status:', error?.response?.status);
      console.error('📡 Error data:', error?.response?.data);
      console.error('📡 Error headers:', error?.response?.headers);
      console.error('🔍 Error message:', error?.message);
      console.error('🔍 Error config:', error?.config);
      console.groupEnd();

      // Registrar tentativa de registro
      registerRateLimit.recordAttempt();
      
      const errorMessage = error?.response?.data?.message || error?.message || 'Erro ao registrar usuário';
      throw new Error(errorMessage);
    } finally {
      isLoading.value = false;
    }
  };

  /**
   * Realiza logout
   */
  const logout = async (): Promise<void> => {
    isLoading.value = true;

    try {
      await apiClient.logout();
    } finally {
      // Limpar estado independente do resultado da API
      user.value = null;
      isAuthenticated.value = false;
      isLoading.value = false;
      
      // Redirecionar para login
      window.location.href = '/login';
    }
  };

  /**
   * Renova token manualmente
   */
  const refreshToken = async (): Promise<boolean> => {
    try {
      const response = await apiClient.post('/refresh');
      const { token, expires_at } = response.data.data;
      
      // Atualizar contexto
      const authContext = apiClient.getAuthContext();
      if (authContext) {
        authContext.token = token;
        authContext.expires_at = expires_at;
        apiClient.setAuthContext(authContext);
      }
      
      return true;
    } catch (error) {
      await logout();
      return false;
    }
  };

  /**
   * Obtém dados do usuário atual
   */
  const getCurrentUser = async (): Promise<User | null> => {
    if (!isAuthenticated.value) {
      return null;
    }

    try {
      const response = await apiClient.get('/user');
      user.value = response.data.data.user;
      return user.value;
    } catch (error) {
      await logout();
      return null;
    }
  };

  /**
   * Computed properties
   */
  const userRole = computed(() => {
    if (!user.value) return null;
    return user.value.doctor ? 'doctor' : 'patient';
  });

  const isDoctor = computed(() => userRole.value === 'doctor');
  const isPatient = computed(() => userRole.value === 'patient');

  const canLogin = computed(() => {
    return !isLoading.value && loginRateLimit.canAttempt.value;
  });

  const canRegister = computed(() => {
    return !isLoading.value && registerRateLimit.canAttempt.value;
  });

  // Inicializar autenticação quando o composable é criado
  initializeAuth();

  return {
    // Estado
    user: computed(() => user.value),
    isAuthenticated: computed(() => isAuthenticated.value),
    isLoading: computed(() => isLoading.value),
    userRole,
    isDoctor,
    isPatient,

    // Rate limiting
    loginRateLimit: loginRateLimit.getStatus(),
    registerRateLimit: registerRateLimit.getStatus(),

    // Validações
    canLogin,
    canRegister,

    // Métodos
    login,
    register,
    logout,
    refreshToken,
    getCurrentUser,
    refreshUserData,
    initializeAuth
  };
}
