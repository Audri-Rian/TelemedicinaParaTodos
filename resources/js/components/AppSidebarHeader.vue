<script setup lang="ts">
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { SidebarTrigger } from '@/components/ui/sidebar';
import UserMenuContent from '@/components/UserMenuContent.vue';
import { getInitials } from '@/composables/useInitials';
import { useNotifications } from '@/composables/useNotifications';
import type { BreadcrumbItemType } from '@/types';
import { router, usePage } from '@inertiajs/vue3';
import { Bell, Calendar, CalendarClock, CalendarX, ClipboardList, FileText, Pill } from 'lucide-vue-next';
import { computed, onMounted } from 'vue';

withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItemType[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);

// Usar o composable de notificações
const { notifications: notificationList, unreadCount, hasUnread, loadUnread, markAsRead, markAllAsRead } = useNotifications();

const page = usePage();
const auth = computed(() => page.props.auth);

// Mapear ícones do sistema para componentes Lucide
const iconMap: Record<string, any> = {
    'calendar-plus': Calendar,
    'calendar-x': CalendarX,
    'calendar-clock': CalendarClock,
    prescription: Pill,
    'clipboard-list': ClipboardList,
    'file-text': FileText,
    bell: Bell,
};

// Mapear cores para classes CSS
const colorMap: Record<string, string> = {
    blue: 'bg-blue-100 text-blue-600',
    red: 'bg-red-100 text-red-600',
    yellow: 'bg-yellow-100 text-yellow-700',
    green: 'bg-green-100 text-green-600',
    purple: 'bg-purple-100 text-purple-600',
    indigo: 'bg-indigo-100 text-indigo-600',
    orange: 'bg-orange-100 text-orange-600',
};

// Computed para notificações formatadas
const notifications = computed(() => {
    return notificationList.value.map((notification) => ({
        ...notification,
        icon: iconMap[notification.icon] || Bell,
        unread: !notification.is_read,
        description: notification.message,
    }));
});

// Handler para clicar em notificação
const handleNotificationClick = async (notification: any) => {
    if (!notification.is_read) {
        await markAsRead(notification.id);
    }
    // Aqui você pode adicionar navegação baseada no tipo de notificação
    // Por exemplo, se for appointment, navegar para a página de consultas
};

// Handler para marcar todas como lidas
const handleMarkAllAsRead = async () => {
    await markAllAsRead();
};

// Handler para ver todas as notificações
const handleViewAll = () => {
    router.visit('/notifications');
};

// Carregar notificações ao montar
onMounted(() => {
    loadUnread();
});
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
                            class="absolute top-0 right-0 flex h-5 w-5 items-center justify-center rounded-full border-2 border-white bg-red-500 text-[11px] font-medium text-white"
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
                        <template v-if="notifications.length === 0">
                            <div class="p-4 text-center text-sm text-muted-foreground">Nenhuma notificação</div>
                        </template>
                        <template v-else>
                            <DropdownMenuItem
                                v-for="notification in notifications"
                                :key="notification.id"
                                class="flex cursor-pointer flex-col items-start gap-2 p-3"
                                :class="{ 'bg-accent/50': notification.unread }"
                                @click="handleNotificationClick(notification)"
                            >
                                <div class="flex w-full items-start gap-3">
                                    <div
                                        class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full"
                                        :class="[
                                            notification.unread ? 'bg-primary/10 text-primary' : 'bg-muted text-muted-foreground',
                                            colorMap[notification.color] || '',
                                        ]"
                                    >
                                        <component :is="notification.icon" class="h-4 w-4" />
                                    </div>
                                    <div class="min-w-0 flex-1 space-y-1">
                                        <div class="flex items-start justify-between gap-2">
                                            <p class="truncate text-sm leading-none font-medium" :class="{ 'font-semibold': notification.unread }">
                                                {{ notification.title }}
                                            </p>
                                            <span v-if="notification.unread" class="mt-1 h-2 w-2 shrink-0 rounded-full bg-primary" />
                                        </div>
                                        <p class="line-clamp-2 text-xs text-muted-foreground">
                                            {{ notification.description }}
                                        </p>
                                        <p class="text-xs text-muted-foreground">
                                            {{ notification.time }}
                                        </p>
                                    </div>
                                </div>
                            </DropdownMenuItem>
                        </template>
                    </div>

                    <DropdownMenuSeparator />
                    <div class="flex flex-col">
                        <DropdownMenuItem
                            v-if="hasUnread"
                            @click="handleMarkAllAsRead"
                            class="cursor-pointer justify-center text-center text-sm font-medium text-primary"
                        >
                            Marcar todas como lidas
                        </DropdownMenuItem>
                        <DropdownMenuItem @click="handleViewAll" class="cursor-pointer justify-center text-center text-sm font-medium text-primary">
                            Ver todas as notificações
                        </DropdownMenuItem>
                    </div>
                </DropdownMenuContent>
            </DropdownMenu>

            <DropdownMenu>
                <DropdownMenuTrigger as-child>
                    <Button variant="ghost" size="icon" class="h-10 w-10 rounded-full bg-primary p-0 hover:bg-primary/90">
                        <Avatar class="h-10 w-10 rounded-full">
                            <AvatarImage v-if="auth.user?.avatar" :src="auth.user.avatar" :alt="auth.user.name" />
                            <AvatarFallback class="bg-primary font-semibold text-gray-900" :delay-ms="600">
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
