import { useAuth } from './useAuth';
import * as doctorRoutes from '@/routes/doctor';
import * as patientRoutes from '@/routes/patient';
import type { RouteDefinition } from '@/wayfinder';

/**
 * Mapeamento de rotas por tipo de usuário
 * Facilita escalabilidade para novos tipos (ex: admin, nurse)
 */
const routesByRole = {
    doctor: doctorRoutes,
    patient: patientRoutes,
    user: {}, // Fallback para usuários sem perfil específico
} as const;

type UserRole = keyof typeof routesByRole;

/**
 * Composable para gerenciar rotas baseadas no tipo de usuário
 * 
 * @returns Funções para obter rotas específicas baseadas no role
 * 
 * @example
 * ```vue
 * <script setup>
 * import { useRoleRoutes } from '@/composables/auth/useRoleRoutes';
 * 
 * const { dashboardRoute, appointmentsRoute, routes } = useRoleRoutes();
 * </script>
 * 
 * <template>
 *   <Link :href="dashboardRoute()">Dashboard</Link>
 *   <!-- Ou acesso direto ao namespace de rotas -->
 *   <Link :href="routes.appointments()">Appointments</Link>
 * </template>
 * ```
 */
export function useRoleRoutes() {
    const { isDoctor, isPatient, role } = useAuth();
    
    /**
     * Retorna o namespace de rotas baseado no role atual
     * Reduz duplicação e torna escalável para novos tipos de usuário
     */
    const routes = routesByRole[role.value as UserRole] ?? routesByRole.user;
    
    /**
     * Retorna a rota do dashboard baseado no tipo de usuário
     * Usa o mapeamento de rotas para escalar facilmente
     */
    const dashboardRoute = (): RouteDefinition<'get'> => {
        if ('dashboard' in routes && typeof routes.dashboard === 'function') {
            return routes.dashboard();
        }
        return { url: '/', method: 'get' };
    };
    
    /**
     * Retorna a rota de appointments baseado no tipo de usuário
     */
    const appointmentsRoute = (): RouteDefinition<'get'> | null => {
        if ('appointments' in routes && typeof routes.appointments === 'function') {
            return routes.appointments();
        }
        return null;
    };
    
    /**
     * Retorna a rota de consultations baseado no tipo de usuário
     */
    const consultationsRoute = (): RouteDefinition<'get'> | null => {
        if ('consultations' in routes && typeof routes.consultations === 'function') {
            return routes.consultations();
        }
        return null;
    };
    
    /**
     * Retorna a rota de health records (apenas para pacientes)
     */
    const healthRecordsRoute = (): RouteDefinition<'get'> | null => {
        if ('healthRecords' in routes && typeof routes.healthRecords === 'function') {
            return routes.healthRecords();
        }
        return null;
    };
    
    /**
     * Retorna o objeto de rotas completo baseado no role
     * Permite acesso direto a qualquer rota do namespace
     * 
     * @example
     * ```ts
     * const { getRoutes } = useRoleRoutes();
     * const allRoutes = getRoutes(); // Retorna doctorRoutes ou patientRoutes
     * ```
     */
    const getRoutes = () => routes;
    
    return {
        // Rotas individuais (backward compatibility)
        dashboardRoute,
        appointmentsRoute,
        consultationsRoute,
        healthRecordsRoute,
        
        // Acesso direto ao namespace de rotas
        routes,
        getRoutes,
        
        // Informações do usuário
        role,
        isDoctor,
        isPatient,
    };
}

