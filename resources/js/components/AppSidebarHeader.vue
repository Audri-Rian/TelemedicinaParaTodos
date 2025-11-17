<script setup lang="ts">
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { Button } from '@/components/ui/button';
import { 
    DropdownMenu, 
    DropdownMenuContent, 
    DropdownMenuItem, 
    DropdownMenuLabel, 
    DropdownMenuSeparator, 
    DropdownMenuTrigger 
} from '@/components/ui/dropdown-menu';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import type { BreadcrumbItemType } from '@/types';
import { Bell, Calendar, CheckCircle, FileText, UserPlus } from 'lucide-vue-next';
import { ref, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import UserMenuContent from '@/components/UserMenuContent.vue';
import { getInitials } from '@/composables/useInitials';

withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItemType[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);

// Mock de notificações para demonstração visual
const notifications = ref([
    {
        id: 1,
        icon: Calendar,
        title: 'Nova consulta agendada',
        description: 'João Silva marcou consulta para amanhã às 14h',
        time: '5 min atrás',
        unread: true,
    },
    {
        id: 2,
        icon: UserPlus,
        title: 'Novo paciente cadastrado',
        description: 'Maria Santos se cadastrou na plataforma',
        time: '1 hora atrás',
        unread: true,
    },
    {
        id: 3,
        icon: FileText,
        title: 'Documento enviado',
        description: 'Novo exame disponível para análise',
        time: '2 horas atrás',
        unread: false,
    },
    {
        id: 4,
        icon: CheckCircle,
        title: 'Consulta finalizada',
        description: 'Relatório da consulta com Pedro Costa foi gerado',
        time: '1 dia atrás',
        unread: false,
    },
]);

const unreadCount = ref(notifications.value.filter(n => n.unread).length);

const page = usePage();
const auth = computed(() => page.props.auth);
</script>

<template>
    <header
        class="flex h-16 shrink-0 items-center justify-between gap-2 border-b border-sidebar-border/70 px-6 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-4"
    >
        <div class="flex items-center gap-2">
            <SidebarTrigger class="-ml-1" />
            <template v-if="breadcrumbs && breadcrumbs.length > 0">
                <Breadcrumbs :breadcrumbs="breadcrumbs" />
            </template>
        </div>
        
        <div class="flex items-center gap-2">
            <DropdownMenu>
                <DropdownMenuTrigger as-child>
                    <Button variant="ghost" size="icon" class="relative h-10 w-10 rounded-full bg-primary hover:bg-primary/90">
                        <Bell class="h-5 w-5 text-gray-900" />
                        <span 
                            v-if="unreadCount > 0" 
                            class="absolute right-0 top-0 flex h-5 w-5 items-center justify-center rounded-full bg-red-500 text-[11px] font-medium text-white border-2 border-white"
                        >
                            {{ unreadCount }}
                        </span>
                    </Button>
                </DropdownMenuTrigger>
                
                <DropdownMenuContent align="end" class="w-80">
                    <DropdownMenuLabel class="flex items-center justify-between">
                        <span>Notificações</span>
                        <span v-if="unreadCount > 0" class="text-xs font-normal text-muted-foreground">
                            {{ unreadCount }} não lida{{ unreadCount > 1 ? 's' : '' }}
                        </span>
                    </DropdownMenuLabel>
                    <DropdownMenuSeparator />
                    
                    <div class="max-h-[400px] overflow-y-auto">
                        <DropdownMenuItem 
                            v-for="notification in notifications" 
                            :key="notification.id"
                            class="flex cursor-pointer flex-col items-start gap-2 p-3"
                            :class="{ 'bg-accent/50': notification.unread }"
                        >
                            <div class="flex w-full items-start gap-3">
                                <div 
                                    class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full"
                                    :class="notification.unread ? 'bg-primary/10 text-primary' : 'bg-muted text-muted-foreground'"
                                >
                                    <component :is="notification.icon" class="h-4 w-4" />
                                </div>
                                <div class="flex-1 space-y-1">
                                    <div class="flex items-start justify-between gap-2">
                                        <p class="text-sm font-medium leading-none">
                                            {{ notification.title }}
                                        </p>
                                        <span 
                                            v-if="notification.unread" 
                                            class="h-2 w-2 shrink-0 rounded-full bg-primary"
                                        />
                                    </div>
                                    <p class="text-xs text-muted-foreground">
                                        {{ notification.description }}
                                    </p>
                                    <p class="text-xs text-muted-foreground">
                                        {{ notification.time }}
                                    </p>
                                </div>
                            </div>
                        </DropdownMenuItem>
                    </div>
                    
                    <DropdownMenuSeparator />
                    <DropdownMenuItem class="justify-center text-center text-sm font-medium text-primary">
                        Ver todas as notificações
                    </DropdownMenuItem>
                </DropdownMenuContent>
            </DropdownMenu>

            <DropdownMenu>
                <DropdownMenuTrigger as-child>
                    <Button variant="ghost" size="icon" class="h-10 w-10 rounded-full bg-primary hover:bg-primary/90 p-0">
                        <Avatar class="h-10 w-10 rounded-full">
                            <AvatarImage v-if="auth.user?.avatar" :src="auth.user.avatar" :alt="auth.user.name" />
                            <AvatarFallback class="bg-primary text-gray-900 font-semibold" :delay-ms="600">
                                {{ getInitials(auth.user?.name) }}
                            </AvatarFallback>
                        </Avatar>
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end" class="w-56">
                    <UserMenuContent :user="auth.user" />
                </DropdownMenuContent>
            </DropdownMenu>
        </div>
    </header>
</template>
