<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { 
    Calendar, 
    FileText, 
    Activity, 
    RotateCcw, 
    FileCheck, 
    Search, 
    ChevronDown,
    Video,
    Clock,
    CheckCircle2,
    MoreVertical,
    X,
    Users
} from 'lucide-vue-next';
import * as patientRoutes from '@/routes/patient';
import { onMounted, onUnmounted, ref, computed, watch } from 'vue';
import { useRouteGuard } from '@/composables/auth';
import { usePage } from '@inertiajs/vue3';
import { Avatar, AvatarImage, AvatarFallback } from '@/components/ui/avatar';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { useInitials } from '@/composables/useInitials';
import EmptyState from '@/components/EmptyState.vue';
import WelcomeScreen from '@/components/onboarding/WelcomeScreen.vue';
import DashboardTour from '@/components/onboarding/DashboardTour.vue';

// Ref para o container de médicos
const doctorsContainer = ref<HTMLElement | null>(null);
const isDragging = ref(false);
const startX = ref(0);
const scrollLeft = ref(0);

interface UpcomingAppointment {
    id: string;
    doctor_name: string;
    doctor_specialty?: string;
    doctor_image?: string;
    scheduled_at: string;
    scheduled_date?: string;
    scheduled_time?: string;
    duration?: string;
    status: string;
    status_class: string;
}

interface RecentAppointment {
    id: string;
    doctor_name: string;
    scheduled_at: string;
    status: string;
}

interface Doctor {
    id: string;
    name: string;
    specialty: string;
    image?: string;
}

interface Reminder {
    id: string;
    title: string;
    time?: string;
    message?: string;
    icon: 'medication' | 'exam' | 'check';
    completed?: boolean;
}

interface HealthTip {
    id: string;
    title: string;
    description: string;
    image?: string;
}

interface Stats {
    total: number;
    completed: number;
}

interface Onboarding {
    showWelcome?: boolean;
    showTour?: boolean;
    userName?: string;
}

interface Props {
    upcomingAppointments?: UpcomingAppointment[];
    recentAppointments?: RecentAppointment[];
    stats?: Stats;
    doctors?: Doctor[];
    reminders?: Reminder[];
    healthTips?: HealthTip[];
    onboarding?: Onboarding;
}

const props = withDefaults(defineProps<Props>(), {
    upcomingAppointments: () => [],
    recentAppointments: () => [],
    stats: () => ({ total: 0, completed: 0 }),
    doctors: () => [],
    reminders: () => [],
    healthTips: () => [],
    onboarding: () => ({
        showWelcome: false,
        showTour: false,
        userName: '',
    }),
});

const showWelcomeScreen = ref(props.onboarding?.showWelcome ?? false);
const showTour = ref(props.onboarding?.showTour ?? false);

// Atualizar estados quando props mudarem
watch(() => props.onboarding?.showWelcome, (newValue) => {
    showWelcomeScreen.value = newValue ?? false;
});

watch(() => props.onboarding?.showTour, (newValue) => {
    showTour.value = newValue ?? false;
});

const handleStartTour = () => {
    showWelcomeScreen.value = false;
    showTour.value = true;
};

const handleWelcomeClose = () => {
    showWelcomeScreen.value = false;
};

const handleTourComplete = () => {
    showTour.value = false;
};

const handleTourClose = () => {
    showTour.value = false;
};

const { canAccessPatientRoute } = useRouteGuard();
const { getInitials } = useInitials();

// Obter usuário autenticado
const page = usePage();
const authUser = computed(() => {
    const props = page.props as any;
    return props?.auth?.user || null;
});

// Estados locais
const searchQuery = ref('');
const specialtyFilter = ref('');
const insuranceFilter = ref('');

// Funções para drag scroll
const handleMouseDown = (e: MouseEvent) => {
    if (!doctorsContainer.value) return;
    // Verificar se o clique foi em um link ou botão
    const target = e.target as HTMLElement;
    if (target.closest('a') || target.closest('button')) {
        return; // Não iniciar drag se clicou em um link/botão
    }
    isDragging.value = true;
    startX.value = e.pageX - doctorsContainer.value.offsetLeft;
    scrollLeft.value = doctorsContainer.value.scrollLeft;
    doctorsContainer.value.style.cursor = 'grabbing';
    e.preventDefault();
};

const handleMouseLeave = () => {
    if (!doctorsContainer.value) return;
    isDragging.value = false;
    doctorsContainer.value.style.cursor = 'grab';
};

const handleMouseUp = () => {
    if (!doctorsContainer.value) return;
    isDragging.value = false;
    doctorsContainer.value.style.cursor = 'grab';
};

const handleMouseMove = (e: MouseEvent) => {
    if (!isDragging.value || !doctorsContainer.value) return;
    e.preventDefault();
    const x = e.pageX - doctorsContainer.value.offsetLeft;
    const walk = (x - startX.value) * 2; // Velocidade do scroll
    doctorsContainer.value.scrollLeft = scrollLeft.value - walk;
};

// Verificar acesso ao montar componente
onMounted(() => {
    canAccessPatientRoute();
    // Adicionar event listener global para mouseup (caso solte fora do container)
    document.addEventListener('mouseup', handleMouseUp);
    // Configurar cursor inicial
    if (doctorsContainer.value) {
        doctorsContainer.value.style.cursor = 'grab';
    }
});

// Limpar event listener ao desmontar
onUnmounted(() => {
    document.removeEventListener('mouseup', handleMouseUp);
});

// Próxima consulta (primeira da lista ou null)
const nextAppointment = computed<UpcomingAppointment | null>(() => {
    return props.upcomingAppointments.length > 0 ? props.upcomingAppointments[0] : null;
});

// Filtrar médicos
const filteredDoctors = computed<Doctor[]>(() => {
    let result = [...props.doctors];
    
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        result = result.filter(doctor => 
            doctor.name.toLowerCase().includes(query) || 
            doctor.specialty.toLowerCase().includes(query)
        );
    }
    
    if (specialtyFilter.value) {
        result = result.filter(doctor => doctor.specialty === specialtyFilter.value);
    }
    
    return result;
});

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: patientRoutes.dashboard().url,
    },
];
</script>

<template>
    <Head title="Meu Painel" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-6 bg-gray-50">
            <!-- Seção de Boas-vindas e Próxima Consulta -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Seção de Boas-vindas (Esquerda) -->
                <div class="lg:col-span-2 bg-linear-to-br from-primary/10 to-primary/5 rounded-lg shadow-sm border border-gray-200 p-8">
                    <div class="flex flex-col h-full justify-between">
                        <div>
                            <h1 class="text-3xl font-bold text-gray-900 mb-3">
                                Olá, {{ authUser?.name?.split(' ')[0] || 'Bem-vindo' }}! 👋
                            </h1>
                            <p class="text-lg text-gray-700 mb-4">
                                Bem-vindo ao <span class="font-semibold text-primary">Telemedicina Para Todos</span>, sua plataforma completa de saúde digital.
                            </p>
                            <p class="text-base text-gray-600 mb-6">
                                Agende consultas online, converse com médicos especialistas e gerencie sua saúde de forma prática e segura, tudo no conforto da sua casa.
                            </p>

                            <!-- Lista de Médicos Disponíveis -->
                            <div v-if="doctors.length > 0" class="mb-6" data-tour="medicos-disponiveis">
                                <h3 class="text-sm font-semibold text-gray-700 mb-3">Médicos Disponíveis Agora:</h3>
                                <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                                    <Link 
                                        v-for="doctor in doctors.slice(0, 6)"
                                        :key="doctor.id"
                                        :href="patientRoutes.searchConsultations()"
                                        class="bg-white/80 hover:bg-white rounded-lg p-3 border border-gray-200 hover:border-primary/30 hover:shadow-md transition cursor-pointer group">
                                        <div class="flex items-center gap-3">
                                            <Avatar class="w-10 h-10 shrink-0">
                                                <AvatarImage 
                                                    v-if="doctor.image" 
                                                    :src="doctor.image" 
                                                    :alt="doctor.name" 
                                                />
                                                <AvatarFallback class="bg-primary/20 text-gray-900 text-sm group-hover:bg-primary/30 transition" :delay-ms="600">
                                                    {{ getInitials(doctor.name) }}
                                                </AvatarFallback>
                                            </Avatar>
                                            <div class="min-w-0 flex-1">
                                                <p class="font-semibold text-gray-900 text-sm truncate">{{ doctor.name }}</p>
                                                <p class="text-xs text-emerald-700 truncate">{{ doctor.specialty }}</p>
                                            </div>
                                        </div>
                                    </Link>
                                </div>
                                <p class="text-xs text-gray-500 mt-3 italic">
                                    Clique em qualquer médico para agendar uma consulta
                                </p>
                            </div>
                            <div v-else class="mb-6">
                                <EmptyState
                                    :icon="Users"
                                    title="Nenhum médico disponível no momento"
                                    description="Esta área mostra os médicos que estão disponíveis para consultas agora. Quando médicos estiverem online e com horários abertos, eles aparecerão aqui para acesso rápido."
                                    sub-description="Explore nossa lista completa de médicos especialistas e encontre o profissional ideal para sua necessidade."
                                    action-label="Ver todos os médicos"
                                    :action-href="patientRoutes.searchConsultations().url"
                                    :action-icon="Search"
                                    variant="minimal"
                                />
                            </div>
                        </div>
                        
                        <Link 
                            :href="patientRoutes.searchConsultations()"
                            data-tour="agendar-consulta"
                            class="inline-flex items-center justify-center bg-primary hover:bg-primary/90 text-gray-900 font-semibold py-3 px-8 rounded-lg transition shadow-md hover:shadow-lg">
                            <Calendar class="w-5 h-5 mr-2" />
                            <span>Agendar Nova Consulta</span>
                        </Link>
                    </div>
                </div>

                <!-- Próxima Consulta Section (Direita) -->
                <div v-if="nextAppointment" class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden" data-tour="proxima-consulta">
                    <!-- Foto do Médico (Seção Superior) -->
                    <div class="bg-gray-100 flex justify-center items-center py-4 px-4">
                        <Avatar class="w-24 h-24 md:w-28 md:h-28">
                            <AvatarImage 
                                v-if="nextAppointment.doctor_image" 
                                :src="nextAppointment.doctor_image" 
                                :alt="nextAppointment.doctor_name" 
                            />
                            <AvatarFallback class="bg-white text-gray-900 text-3xl" :delay-ms="600">
                                {{ getInitials(nextAppointment.doctor_name) }}
                            </AvatarFallback>
                        </Avatar>
                    </div>

                    <!-- Informações da Consulta (Seção Inferior) -->
                    <div class="bg-white p-4">
                        <p class="text-xs text-gray-500 mb-1">Próxima Consulta</p>
                        <h2 class="text-xl font-bold text-gray-900 mb-1">
                            Dr. {{ nextAppointment.doctor_name }}
                        </h2>
                        <p class="text-sm text-gray-700 mb-3">
                            {{ nextAppointment.doctor_specialty || 'Especialista' }} • 
                            {{ nextAppointment.scheduled_date || nextAppointment.scheduled_at }}, 
                            {{ nextAppointment.scheduled_time }}
                            <span>{{ nextAppointment.duration || ' (45 min)' }}</span>
                        </p>
                        <p class="text-xs text-gray-500 mb-4">
                            Por videochamada
                        </p>

                        <!-- Botões de Ação -->
                        <div class="flex flex-col gap-2">
                            <Link 
                                :href="patientRoutes.videoCall()"
                                class="bg-primary hover:bg-primary/90 text-gray-900 font-semibold py-2 px-4 rounded-lg transition flex items-center justify-center space-x-2 text-sm">
                                <Video class="w-4 h-4" />
                                <span>Entrar na videochamada</span>
                            </Link>
                            
                            <div class="flex gap-2">
                                <Link 
                                    :href="patientRoutes.searchConsultations()"
                                    class="flex-1 bg-primary/20 hover:bg-primary/30 text-gray-900 font-semibold py-2 px-3 rounded-lg transition flex items-center justify-center space-x-1 text-xs">
                                    <Calendar class="w-3 h-3" />
                                    <span>Reagendar</span>
                                </Link>
                                
                                <button 
                                    class="flex-1 text-gray-600 hover:text-gray-900 font-semibold py-2 px-3 text-xs transition rounded-lg hover:bg-gray-100">
                                    Cancelar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Estado vazio para próxima consulta -->
                <div v-else class="bg-white rounded-lg shadow-sm border border-gray-200 p-6" data-tour="proxima-consulta">
                    <EmptyState
                        :icon="Calendar"
                        title="Você ainda não tem consultas agendadas"
                        description="Este espaço mostra sua próxima consulta médica. Quando você agendar uma consulta, verá aqui os detalhes do médico, data, horário e poderá acessar a videochamada diretamente."
                        sub-description="Agende sua primeira consulta e comece a cuidar da sua saúde de forma prática, segura e no conforto da sua casa."
                        action-label="Agendar minha primeira consulta"
                        :action-href="patientRoutes.searchConsultations().url"
                        :action-icon="Calendar"
                        variant="subtle"
                    />
                </div>
            </div>

            <!-- Cards de Acesso Rápido -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6" data-tour="documentos-medicos">
                <Link 
                    :href="patientRoutes.searchConsultations()"
                    class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 hover:shadow-md transition cursor-pointer">
                    <div class="flex flex-col items-center text-center">
                        <div class="w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center mb-4">
                            <RotateCcw class="w-8 h-8 text-gray-700" />
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Histórico de Consultas</h3>
                        <p class="text-sm text-gray-600">Veja suas consultas passadas</p>
                    </div>
                </Link>

                <Link 
                    :href="patientRoutes.medicalRecords()"
                    class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 hover:shadow-md transition cursor-pointer">
                    <div class="flex flex-col items-center text-center">
                        <div class="w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center mb-4">
                            <FileText class="w-8 h-8 text-gray-700" />
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Receitas Prescritas</h3>
                        <p class="text-sm text-gray-600">Acesse suas prescrições médicas</p>
                    </div>
                </Link>

                <Link 
                    :href="patientRoutes.medicalRecords()"
                    class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 hover:shadow-md transition cursor-pointer">
                    <div class="flex flex-col items-center text-center">
                        <div class="w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center mb-4">
                            <FileCheck class="w-8 h-8 text-gray-700" />
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Laudos e Exames</h3>
                        <p class="text-sm text-gray-600">Visualize seus resultados</p>
                    </div>
                </Link>
            </div>

            <!-- Encontrar Médico Section -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6" data-tour="encontrar-medico">
                <h2 class="text-2xl font-bold text-gray-900 mb-6">Encontrar Médico</h2>
                
                <!-- Barra de Busca e Filtros -->
                <div class="flex flex-col md:flex-row gap-4 mb-6">
                    <div class="flex-1 relative">
                        <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-5 h-5" />
                        <Input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Buscar por nome ou especial"
                            class="pl-10 w-full"
                        />
                    </div>
                    
                    <div class="relative">
                        <select 
                            v-model="specialtyFilter"
                            class="appearance-none bg-white border border-gray-300 rounded-lg px-4 py-2 pr-10 text-gray-900 focus:ring-2 focus:ring-primary focus:border-transparent cursor-pointer min-w-[180px]">
                            <option value="">Especialidade</option>
                            <option value="Cardiologista">Cardiologista</option>
                            <option value="Dermatologista">Dermatologista</option>
                            <option value="Clínico Geral">Clínico Geral</option>
                            <option value="Pediatra">Pediatra</option>
                            <option value="Ortopedista">Ortopedista</option>
                        </select>
                        <ChevronDown class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-5 h-5 pointer-events-none" />
                    </div>
                    
                    <div class="relative">
                        <select 
                            v-model="insuranceFilter"
                            class="appearance-none bg-white border border-gray-300 rounded-lg px-4 py-2 pr-10 text-gray-900 focus:ring-2 focus:ring-primary focus:border-transparent cursor-pointer min-w-[180px]">
                            <option value="">Convênio</option>
                            <option value="Unimed">Unimed</option>
                            <option value="Amil">Amil</option>
                            <option value="Bradesco">Bradesco</option>
                            <option value="SulAmérica">SulAmérica</option>
                        </select>
                        <ChevronDown class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-5 h-5 pointer-events-none" />
                    </div>
                </div>

                <!-- Lista de Médicos -->
                <div 
                    v-if="filteredDoctors.length > 0" 
                    ref="doctorsContainer"
                    class="flex gap-4 overflow-x-auto pb-2 scrollbar-hide cursor-grab"
                    style="scrollbar-width: none; -ms-overflow-style: none;"
                    @mousedown="handleMouseDown"
                    @mouseleave="handleMouseLeave"
                    @mouseup="handleMouseUp"
                    @mousemove="handleMouseMove"
                >
                    <!-- Médicos dinâmicos -->
                    <template v-for="doctor in filteredDoctors.slice(0, 6)" :key="doctor.id">
                        <div class="shrink-0 bg-white rounded-lg p-4 min-w-[280px] border border-gray-200 hover:shadow-md transition">
                            <div class="flex items-center gap-4">
                                <Avatar class="w-16 h-16 shrink-0">
                                    <AvatarImage 
                                        v-if="doctor.image" 
                                        :src="doctor.image" 
                                        :alt="doctor.name" 
                                    />
                                    <AvatarFallback class="bg-primary/20 text-gray-900 text-lg" :delay-ms="600">
                                        {{ getInitials(doctor.name) }}
                                    </AvatarFallback>
                                </Avatar>
                                <div class="flex-1 min-w-0">
                                    <h3 class="font-bold text-gray-900 mb-1">{{ doctor.name }}</h3>
                                    <p class="text-sm text-emerald-700">{{ doctor.specialty }}</p>
                                </div>
                                <Link 
                                    :href="patientRoutes.searchConsultations()"
                                    class="shrink-0 bg-primary/30 hover:bg-primary/40 text-gray-900 p-3 rounded-lg transition flex items-center justify-center">
                                    <Calendar class="w-5 h-5" />
                                </Link>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Estado vazio para médicos -->
                <EmptyState
                    v-else
                    :icon="Search"
                    title="Nenhum médico encontrado com esses filtros"
                    description="Não encontramos médicos que correspondam à sua busca atual. Este espaço mostra os profissionais disponíveis para consulta, permitindo que você encontre o médico ideal para sua necessidade."
                    sub-description="Tente ajustar os filtros de especialidade ou convênio, ou explore nossa lista completa de médicos cadastrados."
                    action-label="Ver todos os médicos disponíveis"
                    :action-href="patientRoutes.searchConsultations().url"
                    :action-icon="Search"
                    variant="subtle"
                />
            </div>

            <!-- Lembretes & Dicas de Saúde -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Lembretes -->
                <div class="lg:col-span-2 bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <h2 class="text-2xl font-bold text-gray-900 mb-6">Lembretes & Dicas de Saúde</h2>
                    
                    <div class="space-y-4">
                        <div 
                            v-for="reminder in reminders.slice(0, 2)" 
                            :key="reminder.id"
                            class="flex items-center gap-4 p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition">
                            <div class="shrink-0">
                                <Clock v-if="reminder.icon === 'medication'" class="w-6 h-6 text-gray-700" />
                                <CheckCircle2 v-else-if="reminder.icon === 'exam'" class="w-6 h-6 text-emerald-600" />
                                <CheckCircle2 v-else class="w-6 h-6 text-gray-700" />
                            </div>
                            <div class="flex-1">
                                <p class="font-medium text-gray-900">{{ reminder.title }}</p>
                                <p v-if="reminder.time || reminder.message" class="text-sm text-gray-600 mt-1">
                                    {{ reminder.time || reminder.message }}
                                </p>
                            </div>
                            <button class="shrink-0 text-gray-400 hover:text-gray-600">
                                <MoreVertical class="w-5 h-5" />
                            </button>
                        </div>

                        <!-- Estado vazio para lembretes -->
                        <EmptyState
                            v-if="reminders.length === 0"
                            :icon="Clock"
                            title="Você ainda não tem lembretes configurados"
                            description="Esta área centraliza seus lembretes de saúde, como horários de medicamentos, exames agendados e outras atividades importantes relacionadas ao seu cuidado médico."
                            sub-description="Quando você tiver consultas com prescrições, exames marcados ou outros compromissos de saúde, os lembretes aparecerão automaticamente aqui para te ajudar a manter sua rotina de cuidados."
                            variant="minimal"
                        />
                    </div>
                </div>

                <!-- Dica de Saúde -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <h3 class="text-xl font-semibold text-gray-900 mb-4">Dica de Saúde</h3>
                    
                    <div v-if="healthTips.length > 0" class="space-y-4">
                        <div 
                            v-for="tip in healthTips.slice(0, 1)" 
                            :key="tip.id"
                            class="space-y-3">
                            <div v-if="tip.image" class="w-full h-48 bg-gray-100 rounded-lg overflow-hidden">
                                <img 
                                    :src="tip.image" 
                                    :alt="tip.title"
                                    class="w-full h-full object-cover"
                                />
                            </div>
                            <div v-else class="w-full h-48 bg-linear-to-br from-primary/20 to-primary/5 rounded-lg flex items-center justify-center">
                                <Activity class="w-16 h-16 text-primary/50" />
                            </div>
                            <h4 class="font-semibold text-gray-900">{{ tip.title }}</h4>
                            <p class="text-sm text-gray-600 line-clamp-3">{{ tip.description }}</p>
                        </div>
                    </div>
                    
                    <!-- Estado vazio para dicas de saúde -->
                    <EmptyState
                        v-else
                        :icon="Activity"
                        title="Suas dicas personalizadas de saúde"
                        description="Este espaço traz dicas e orientações de saúde personalizadas para você, baseadas no seu histórico de consultas, prescrições e necessidades específicas."
                        sub-description="Conforme você usa a plataforma e realiza consultas, receberá dicas relevantes para melhorar seu bem-estar e qualidade de vida."
                        variant="minimal"
                    />
                </div>
            </div>
        </div>

        <!-- Componentes de Onboarding -->
        <WelcomeScreen
            :show="showWelcomeScreen"
            :user-name="props.onboarding?.userName || ''"
            @start-tour="handleStartTour"
            @close="handleWelcomeClose"
        />
        <DashboardTour
            :show="showTour"
            @complete="handleTourComplete"
            @close="handleTourClose"
        />
    </AppLayout>
</template>

