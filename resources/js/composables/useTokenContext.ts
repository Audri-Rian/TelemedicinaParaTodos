import { computed } from 'vue';
import apiClient from '@/lib/axios';
import { useAuth } from './useAuth';

/**
 * Composable para lógica específica baseada no tipo de token
 * Permite implementar comportamentos diferentes para doctor/patient
 */
export function useTokenContext() {
  const { user, isAuthenticated, isDoctor, isPatient } = useAuth();

  /**
   * Executa função específica baseada no tipo de token
   */
  const executeByTokenType = async <T>(
    doctorAction: () => Promise<T>,
    patientAction: () => Promise<T>,
    fallbackAction?: () => Promise<T>
  ): Promise<T> => {
    if (!isAuthenticated.value) {
      throw new Error('User not authenticated');
    }

    if (isDoctor.value) {
      return await doctorAction();
    } else if (isPatient.value) {
      return await patientAction();
    } else if (fallbackAction) {
      return await fallbackAction();
    } else {
      throw new Error('Unknown user type');
    }
  };

  /**
   * Obtém dados específicos baseados no tipo de token
   */
  const getDataByTokenType = async <T>(
    doctorEndpoint: string,
    patientEndpoint: string
  ): Promise<T> => {
    if (isDoctor.value) {
      const response = await apiClient.getDoctorData<T>(doctorEndpoint);
      return response.data;
    } else if (isPatient.value) {
      const response = await apiClient.getPatientData<T>(patientEndpoint);
      return response.data;
    } else {
      throw new Error('Unknown user type');
    }
  };

  /**
   * Envia dados específicos baseados no tipo de token
   */
  const postDataByTokenType = async <T>(
    doctorEndpoint: string,
    patientEndpoint: string,
    data: any
  ): Promise<T> => {
    if (isDoctor.value) {
      const response = await apiClient.postDoctorData<T>(doctorEndpoint, data);
      return response.data;
    } else if (isPatient.value) {
      const response = await apiClient.postPatientData<T>(patientEndpoint, data);
      return response.data;
    } else {
      throw new Error('Unknown user type');
    }
  };

  /**
   * Computed properties para configurações específicas
   */
  const dashboardConfig = computed(() => {
    if (isDoctor.value) {
      return {
        title: 'Dashboard Médico',
        welcomeMessage: `Bem-vindo, Dr. ${user.value?.name}`,
        primaryColor: 'green',
        icon: 'stethoscope',
        features: ['agenda', 'pacientes', 'consultas', 'prontuários'],
        navigation: [
          { label: 'Agenda', route: '/doctor/schedule', icon: 'calendar' },
          { label: 'Pacientes', route: '/doctor/patients', icon: 'users' },
          { label: 'Consultas', route: '/doctor/appointments', icon: 'video' },
          { label: 'Prontuários', route: '/doctor/records', icon: 'file-text' }
        ]
      };
    } else if (isPatient.value) {
      return {
        title: 'Dashboard Paciente',
        welcomeMessage: `Olá, ${user.value?.name}`,
        primaryColor: 'blue',
        icon: 'heart',
        features: ['consultas', 'histórico', 'medicamentos', 'receitas'],
        navigation: [
          { label: 'Minhas Consultas', route: '/patient/appointments', icon: 'calendar' },
          { label: 'Histórico Médico', route: '/patient/history', icon: 'file-text' },
          { label: 'Medicamentos', route: '/patient/medications', icon: 'pills' },
          { label: 'Receitas', route: '/patient/prescriptions', icon: 'receipt' }
        ]
      };
    }
    
    return null;
  });

  const apiEndpoints = computed(() => {
    if (isDoctor.value) {
      return {
        profile: '/doctor/profile',
        appointments: '/doctor/appointments',
        patients: '/doctor/patients',
        schedule: '/doctor/schedule',
        records: '/doctor/records'
      };
    } else if (isPatient.value) {
      return {
        profile: '/patient/profile',
        appointments: '/patient/appointments',
        history: '/patient/history',
        medications: '/patient/medications',
        prescriptions: '/patient/prescriptions'
      };
    }
    
    return null;
  });

  const permissions = computed(() => {
    if (isDoctor.value) {
      return {
        canViewPatients: true,
        canCreateAppointments: true,
        canEditMedicalRecords: true,
        canPrescribeMedications: true,
        canAccessVideoCalls: true,
        canManageSchedule: true
      };
    } else if (isPatient.value) {
      return {
        canViewOwnHistory: true,
        canBookAppointments: true,
        canJoinVideoCalls: true,
        canViewPrescriptions: true,
        canRequestRefills: true,
        canEditProfile: true
      };
    }
    
    return {};
  });

  return {
    // Métodos utilitários
    executeByTokenType,
    getDataByTokenType,
    postDataByTokenType,
    
    // Configurações específicas
    dashboardConfig,
    apiEndpoints,
    permissions,
    
    // Estado do token
    tokenType: computed(() => apiClient.getUserType()),
    userId: computed(() => apiClient.getUserId()),
    isDoctorToken: computed(() => apiClient.isDoctor()),
    isPatientToken: computed(() => apiClient.isPatient())
  };
}