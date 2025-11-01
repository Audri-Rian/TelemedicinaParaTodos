<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarInput, SidebarGroup, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/vue3';
import { BookOpen, Calendar, Folder, Home, Activity, Monitor, Users, History, FileText, MessageCircle, Search, Video, Stethoscope } from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';
import { useAuth } from '@/composables/auth';
import { computed, ref } from 'vue';
import * as doctorRoutes from '@/routes/doctor';
import * as patientRoutes from '@/routes/patient';

const { isDoctor, isPatient } = useAuth();

// Estado da busca
const searchQuery = ref('');

// Navegação para Médicos
const doctorNavItems = computed<NavItem[]>(() => [
    {
        title: 'Dashboard',
        href: doctorRoutes.dashboard(),
        icon: Home,
    },
    {
        title: 'Agenda',
        href: doctorRoutes.appointments(),
        icon: Calendar,
    },
    {
        title: 'Pacientes',
        href: '/doctor/patients',
        icon: Users,
    },
    {
        title: 'Consultas',
        href: doctorRoutes.consultations(),
        icon: Monitor,
    },
    {
        title: 'Mensagens',
        href: '/doctor/messages',
        icon: MessageCircle,
    },
    {
        title: 'Histórico',
        href: '/doctor/history',
        icon: History,
    },
    {
        title: 'Documentos',
        href: '/doctor/documents',
        icon: FileText,
    },
]);

// Navegação para Pacientes
const patientNavItems = computed<NavItem[]>(() => [
    {
        title: 'Dashboard',
        href: patientRoutes.dashboard(),
        icon: Home,
    },
    {
        title: 'Pesquisar Médicos',
        href: patientRoutes.searchConsultations(),
        icon: Stethoscope,
    },
    {
        title: 'Mensagens',
        href: patientRoutes.messages(),
        icon: MessageCircle,
    },
    {
        title: 'Videoconferência',
        href: patientRoutes.videoCall(),
        icon: Video,
    },
    {
        title: 'Prontuário',
        href: patientRoutes.healthRecords(),
        icon: Activity,
    },
]);

// Selecionar navegação baseada no role
const mainNavItems = computed(() => {
    if (isDoctor.value) {
        return doctorNavItems.value;
    }
    if (isPatient.value) {
        return patientNavItems.value;
    }
    return [];
});

const footerNavItems: NavItem[] = [
    {
        title: 'Github Repo',
        href: 'https://github.com/laravel/vue-starter-kit',
        icon: Folder,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits#vue',
        icon: BookOpen,
    },
];

// Dashboard link baseado no role
const dashboardLink = computed(() => {
    if (isDoctor.value) {
        return doctorRoutes.dashboard();
    }
    if (isPatient.value) {
        return patientRoutes.dashboard();
    }
    return { url: '/', method: 'get' };
});
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboardLink">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <SidebarGroup>
                <div class="relative">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground pointer-events-none" />
                    <SidebarInput 
                        v-model="searchQuery"
                        placeholder="Buscar..."
                        class="pl-9"
                    />
                </div>
            </SidebarGroup>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
