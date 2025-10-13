/**
 * Composables de Autenticação
 * 
 * Este módulo exporta todos os composables relacionados à autenticação,
 * controle de acesso e gerenciamento de rotas baseadas em roles.
 */

export { useAuth } from './useAuth';
export { useRoleRoutes } from './useRoleRoutes';
export { useRouteGuard } from './useRouteGuard';

// Re-exportar tipos para facilitar importações
export type { AuthUser, AuthProfile, AuthData } from './useAuth';

