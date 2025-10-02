import { onMounted } from 'vue';
import { useAuth } from './useAuth';
import { router } from '@inertiajs/vue3';

/**
 * Composable para proteção de rotas
 */
export function useAuthGuard() {
  const { isAuthenticated, isLoading, initializeAuth } = useAuth();

  /**
   * Protege uma rota - redireciona para login se não autenticado
   */
  const protectRoute = async (): Promise<boolean> => {
    if (isLoading.value) {
      // Aguardar inicialização
      await new Promise(resolve => {
        const unwatch = watch(isLoading, (loading) => {
          if (!loading) {
            unwatch();
            resolve(true);
          }
        });
      });
    }

    if (!isAuthenticated.value) {
      router.visit('/login');
      return false;
    }

    return true;
  };

  /**
   * Protege rota para usuários não autenticados (redireciona se já logado)
   */
  const protectGuestRoute = async (): Promise<boolean> => {
    if (isLoading.value) {
      await new Promise(resolve => {
        const unwatch = watch(isLoading, (loading) => {
          if (!loading) {
            unwatch();
            resolve(true);
          }
        });
      });
    }

    if (isAuthenticated.value) {
      // Redirecionar para dashboard apropriado baseado no tipo de usuário
      const { user } = useAuth();
      if (user.value?.doctor) {
        router.visit('/doctor/dashboard');
      } else if (user.value?.patient) {
        router.visit('/patient/dashboard');
      } else {
        router.visit('/dashboard');
      }
      return false;
    }

    return true;
  };

  /**
   * Protege rota para médicos
   */
  const protectDoctorRoute = async (): Promise<boolean> => {
    const isAuth = await protectRoute();
    if (!isAuth) return false;

    const { isDoctor } = useAuth();
    if (!isDoctor.value) {
      router.visit('/unauthorized');
      return false;
    }

    return true;
  };

  /**
   * Protege rota para pacientes
   */
  const protectPatientRoute = async (): Promise<boolean> => {
    const isAuth = await protectRoute();
    if (!isAuth) return false;

    const { isPatient } = useAuth();
    if (!isPatient.value) {
      router.visit('/unauthorized');
      return false;
    }

    return true;
  };

  return {
    protectRoute,
    protectGuestRoute,
    protectDoctorRoute,
    protectPatientRoute
  };
}
