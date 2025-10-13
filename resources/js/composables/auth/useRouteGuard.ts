import { router } from '@inertiajs/vue3';
import { useAuth } from './useAuth';
import * as doctorRoutes from '@/routes/doctor';
import * as patientRoutes from '@/routes/patient';
import { login } from '@/routes';

/**
 * Composable para proteção e redirecionamento de rotas no frontend
 * 
 * @returns Funções para verificar e redirecionar baseado em permissões
 * 
 * @example
 * ```vue
 * <script setup>
 * import { onMounted } from 'vue';
 * import { useRouteGuard } from '@/composables/auth/useRouteGuard';
 * 
 * const { canAccessDoctorRoute } = useRouteGuard();
 * 
 * onMounted(() => {
 *   canAccessDoctorRoute();
 * });
 * </script>
 * ```
 */
export function useRouteGuard() {
    const { isDoctor, isPatient, isAuthenticated, role } = useAuth();
    
    /**
     * Redireciona para o dashboard correto se estiver em página errada
     */
    const ensureCorrectDashboard = (): boolean => {
        const currentPath = window.location.pathname;
        
        // Médico tentando acessar rota de paciente
        if (isDoctor.value && currentPath.startsWith('/patient')) {
            router.visit(doctorRoutes.dashboard().url, {
                replace: true,
                onError: () => {
                    console.error('Erro ao redirecionar para dashboard do médico');
                },
            });
            return false;
        }
        
        // Paciente tentando acessar rota de médico
        if (isPatient.value && currentPath.startsWith('/doctor')) {
            router.visit(patientRoutes.dashboard().url, {
                replace: true,
                onError: () => {
                    console.error('Erro ao redirecionar para dashboard do paciente');
                },
            });
            return false;
        }
        
        return true;
    };
    
    /**
     * Verifica se pode acessar rota de médico
     * Redireciona automaticamente se não tiver permissão
     */
    const canAccessDoctorRoute = (): boolean => {
        if (!isAuthenticated.value) {
            router.visit(login().url, {
                replace: true,
            });
            return false;
        }
        
        if (!isDoctor.value) {
            if (isPatient.value) {
                router.visit(patientRoutes.dashboard().url, {
                    replace: true,
                });
            } else {
                router.visit('/', {
                    replace: true,
                });
            }
            return false;
        }
        
        return true;
    };
    
    /**
     * Verifica se pode acessar rota de paciente
     * Redireciona automaticamente se não tiver permissão
     */
    const canAccessPatientRoute = (): boolean => {
        if (!isAuthenticated.value) {
            router.visit(login().url, {
                replace: true,
            });
            return false;
        }
        
        if (!isPatient.value) {
            if (isDoctor.value) {
                router.visit(doctorRoutes.dashboard().url, {
                    replace: true,
                });
            } else {
                router.visit('/', {
                    replace: true,
                });
            }
            return false;
        }
        
        return true;
    };
    
    /**
     * Verifica permissão sem redirecionar
     * Útil para mostrar/ocultar elementos na UI
     */
    const hasPermission = (requiredRole: 'doctor' | 'patient'): boolean => {
        if (requiredRole === 'doctor') {
            return isDoctor.value;
        }
        if (requiredRole === 'patient') {
            return isPatient.value;
        }
        return false;
    };
    
    /**
     * Redireciona para login se não estiver autenticado
     */
    const requireAuth = (): boolean => {
        if (!isAuthenticated.value) {
            router.visit(login().url, {
                replace: true,
            });
            return false;
        }
        return true;
    };
    
    return {
        ensureCorrectDashboard,
        canAccessDoctorRoute,
        canAccessPatientRoute,
        hasPermission,
        requireAuth,
        role,
        isAuthenticated,
        isDoctor,
        isPatient,
    };
}

