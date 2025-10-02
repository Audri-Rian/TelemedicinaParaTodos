import axios, { type AxiosInstance, type AxiosRequestConfig, type AxiosResponse } from 'axios';

/**
 * Interface para dados do token
 */
interface TokenData {
  token: string;
  expires_at: string;
  user_type?: 'doctor' | 'patient';
  user_id?: string;
}

/**
 * Interface para contexto de autenticação
 */
interface AuthContext {
  user: {
    id: string;
    name: string;
    email: string;
    role: 'doctor' | 'patient';
  };
  token: string;
  expires_at: string;
}

/**
 * Configuração do cliente Axios para autenticação via token
 * Implementa interceptors para gerenciamento automático de tokens
 * Suporta contexto de tipo de usuário (doctor/patient)
 */
class ApiClient {
  private client: AxiosInstance;
  private authContext: AuthContext | null = null;
  private refreshPromise: Promise<string> | null = null;

  constructor() {
    this.client = axios.create({
      baseURL: '/api',
      timeout: 10000,
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
    });

    this.setupInterceptors();
    this.loadAuthContextFromStorage();
  }

  /**
   * Configura interceptors para gerenciamento automático de tokens
   */
  private setupInterceptors(): void {
    // Request interceptor - adiciona token e contexto automaticamente
    this.client.interceptors.request.use(
      (config) => {
        if (this.authContext) {
          config.headers.Authorization = `Bearer ${this.authContext.token}`;
          
          // Adicionar contexto do usuário para lógicas específicas
          config.headers['X-User-Type'] = this.authContext.user.role;
          config.headers['X-User-ID'] = this.authContext.user.id;
        }
        return config;
      },
      (error) => Promise.reject(error)
    );

    // Response interceptor - trata expiração de token
    this.client.interceptors.response.use(
      (response: AxiosResponse) => response,
      async (error) => {
        const originalRequest = error.config;

        // Se erro 401 e não é uma tentativa de refresh e temos contexto de autenticação
        if (error.response?.status === 401 && !originalRequest._retry && this.authContext) {
          originalRequest._retry = true;

          try {
            // Tentar renovar token mantendo o contexto
            const newToken = await this.refreshToken();
            if (newToken && this.authContext) {
              originalRequest.headers.Authorization = `Bearer ${newToken}`;
              originalRequest.headers['X-User-Type'] = this.authContext.user.role;
              originalRequest.headers['X-User-ID'] = this.authContext.user.id;
              return this.client(originalRequest);
            }
          } catch (refreshError) {
            // Refresh falhou, limpar contexto e redirecionar para login
            this.logout();
            window.location.href = '/login';
          }
        }

        return Promise.reject(error);
      }
    );
  }

  /**
   * Carrega contexto de autenticação do localStorage
   */
  private loadAuthContextFromStorage(): void {
    const stored = localStorage.getItem('auth_context');
    if (stored) {
      try {
        const context: AuthContext = JSON.parse(stored);
        if (new Date(context.expires_at) > new Date()) {
          this.authContext = context;
        } else {
          this.removeAuthContextFromStorage();
        }
      } catch (error) {
        this.removeAuthContextFromStorage();
      }
    }
  }

  /**
   * Armazena contexto de autenticação no localStorage
   */
  private storeAuthContextInStorage(context: AuthContext): void {
    localStorage.setItem('auth_context', JSON.stringify(context));
  }

  /**
   * Remove contexto de autenticação do localStorage
   */
  private removeAuthContextFromStorage(): void {
    localStorage.removeItem('auth_context');
    this.authContext = null;
  }

  /**
   * Define contexto de autenticação
   */
  public setAuthContext(context: AuthContext): void {
    this.authContext = context;
    this.storeAuthContextInStorage(context);
  }

  /**
   * Obtém contexto de autenticação atual
   */
  public getAuthContext(): AuthContext | null {
    return this.authContext;
  }

  /**
   * Verifica se usuário está autenticado
   */
  public isAuthenticated(): boolean {
    return !!this.authContext;
  }

  /**
   * Verifica se o usuário é médico
   */
  public isDoctor(): boolean {
    return this.authContext?.user.role === 'doctor';
  }

  /**
   * Verifica se o usuário é paciente
   */
  public isPatient(): boolean {
    return this.authContext?.user.role === 'patient';
  }

  /**
   * Obtém o tipo de usuário atual
   */
  public getUserType(): 'doctor' | 'patient' | null {
    return this.authContext?.user.role || null;
  }

  /**
   * Obtém ID do usuário atual
   */
  public getUserId(): string | null {
    return this.authContext?.user.id || null;
  }

  /**
   * Renova token automaticamente mantendo o contexto
   */
  private async refreshToken(): Promise<string> {
    if (this.refreshPromise) {
      return this.refreshPromise;
    }

    this.refreshPromise = this.performRefresh();
    
    try {
      const newToken = await this.refreshPromise;
      return newToken;
    } finally {
      this.refreshPromise = null;
    }
  }

  /**
   * Executa renovação de token
   */
  private async performRefresh(): Promise<string> {
    try {
      const response = await this.client.post('/refresh');
      const { token, expires_at } = response.data.data;
      
      if (this.authContext) {
        // Atualizar token mantendo o contexto
        this.authContext.token = token;
        this.authContext.expires_at = expires_at;
        this.storeAuthContextInStorage(this.authContext);
      }
      
      return token;
    } catch (error) {
      throw error;
    }
  }

  /**
   * Realiza logout e limpa contexto
   */
  public async logout(): Promise<void> {
    try {
      if (this.authContext) {
        await this.client.post('/logout');
      }
    } catch (error) {
      // Ignorar erros de logout
    } finally {
      this.removeAuthContextFromStorage();
    }
  }

  /**
   * Métodos HTTP públicos com contexto automático
   */
  public async get<T = any>(url: string, config?: AxiosRequestConfig): Promise<AxiosResponse<T>> {
    return this.client.get(url, config);
  }

  public async post<T = any>(url: string, data?: any, config?: AxiosRequestConfig): Promise<AxiosResponse<T>> {
    return this.client.post(url, data, config);
  }

  public async put<T = any>(url: string, data?: any, config?: AxiosRequestConfig): Promise<AxiosResponse<T>> {
    return this.client.put(url, data, config);
  }

  public async delete<T = any>(url: string, config?: AxiosRequestConfig): Promise<AxiosResponse<T>> {
    return this.client.delete(url, config);
  }

  /**
   * Métodos específicos para diferentes tipos de usuário
   */
  public async getDoctorData<T = any>(url: string, config?: AxiosRequestConfig): Promise<AxiosResponse<T>> {
    if (!this.isDoctor()) {
      throw new Error('Access denied: Doctor role required');
    }
    return this.client.get(`/doctor${url}`, config);
  }

  public async getPatientData<T = any>(url: string, config?: AxiosRequestConfig): Promise<AxiosResponse<T>> {
    if (!this.isPatient()) {
      throw new Error('Access denied: Patient role required');
    }
    return this.client.get(`/patient${url}`, config);
  }

  public async postDoctorData<T = any>(url: string, data?: any, config?: AxiosRequestConfig): Promise<AxiosResponse<T>> {
    if (!this.isDoctor()) {
      throw new Error('Access denied: Doctor role required');
    }
    return this.client.post(`/doctor${url}`, data, config);
  }

  public async postPatientData<T = any>(url: string, data?: any, config?: AxiosRequestConfig): Promise<AxiosResponse<T>> {
    if (!this.isPatient()) {
      throw new Error('Access denied: Patient role required');
    }
    return this.client.post(`/patient${url}`, data, config);
  }
}

// Instância singleton
export const apiClient = new ApiClient();
export default apiClient;
