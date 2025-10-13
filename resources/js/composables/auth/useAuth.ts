import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

export interface AuthUser {
    id: string;
    name: string;
    email: string;
    email_verified_at: string | null;
}

export interface AuthProfile {
    id: string;
    user_id: string;
    [key: string]: any; // Permite outros campos dinâmicos
}

export interface AuthData {
    user: AuthUser | null;
    role: 'doctor' | 'patient' | 'user' | null;
    isDoctor: boolean;
    isPatient: boolean;
    profile: AuthProfile | null;
}

/**
 * Composable para acessar dados de autenticação do usuário
 * 
 * @returns Dados de autenticação e métodos úteis
 * 
 * @example
 * ```vue
 * <script setup>
 * import { useAuth } from '@/composables/auth/useAuth';
 * 
 * const { user, isDoctor, isPatient, role } = useAuth();
 * </script>
 * ```
 */
export function useAuth() {
    const page = usePage();
    
    const auth = computed(() => page.props.auth as unknown as AuthData);
    const user = computed(() => auth.value?.user ?? null);
    const role = computed(() => auth.value?.role ?? null);
    const isDoctor = computed(() => auth.value?.isDoctor ?? false);
    const isPatient = computed(() => auth.value?.isPatient ?? false);
    const profile = computed(() => auth.value?.profile ?? null);
    
    const isAuthenticated = computed(() => user.value !== null);
    
    const userName = computed(() => user.value?.name ?? 'Usuário');
    const userEmail = computed(() => user.value?.email ?? '');
    
    /**
     * Verifica se o usuário é de um tipo específico
     */
    const isRole = (roleToCheck: 'doctor' | 'patient' | 'user') => {
        return role.value === roleToCheck;
    };
    
    /**
     * Verifica se o usuário pode acessar um determinado role
     * Método utilitário para controle de acesso limpo e escalável
     * 
     * @param requiredRole - Role necessário para acesso
     * @returns true se o usuário tem o role especificado
     * 
     * @example
     * ```ts
     * if (canAccess('doctor')) {
     *   // Mostrar funcionalidade específica de médico
     * }
     * ```
     */
    const canAccess = (requiredRole: 'doctor' | 'patient' | 'user') => {
        return requiredRole === auth.value?.role;
    };
    
    /**
     * Verifica se o usuário pode acessar múltiplos roles
     * 
     * @param roles - Array de roles permitidos
     * @returns true se o usuário tem um dos roles especificados
     * 
     * @example
     * ```ts
     * if (canAccessAny(['doctor', 'admin'])) {
     *   // Funcionalidade para médicos ou admins
     * }
     * ```
     */
    const canAccessAny = (roles: Array<'doctor' | 'patient' | 'user'>) => {
        return roles.includes(auth.value?.role as any);
    };
    
    return {
        auth,
        user,
        role,
        isDoctor,
        isPatient,
        profile,
        isAuthenticated,
        userName,
        userEmail,
        isRole,
        canAccess,
        canAccessAny,
    };
}

