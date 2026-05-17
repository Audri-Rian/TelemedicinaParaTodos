<script setup lang="ts">
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import NotificationsModal from '@/components/NotificationsModal.vue';
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
import { useNotifications, type Notification } from '@/composables/useNotifications';
import type { BreadcrumbItemType } from '@/types';
import { usePage } from '@inertiajs/vue3';
import {
    Bell,
    Calendar,
    CalendarClock,
    CalendarX,
    CheckCircle2,
    ChevronRight,
    ClipboardList,
    FileText,
    Inbox,
    Pill,
    type LucideIcon,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';

withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItemType[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);

// Usar o composable de notificações
const { notifications: notificationList, allNotifications, unreadCount, loading, hasUnread, loadAll, markAsRead, markAllAsRead } = useNotifications();
const isNotificationsModalOpen = ref(false);
const activeNotificationTab = ref<'all' | 'unread' | 'important'>('all');

const page = usePage();
const auth = computed(() => page.props.auth);

// Mapear ícones do sistema para componentes Lucide
const iconMap: Record<string, LucideIcon> = {
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
        important: ['red', 'yellow', 'orange'].includes(notification.color),
        unread: !notification.is_read,
        description: notification.message,
    }));
});

const unreadNotificationsCount = computed(() => notifications.value.filter((notification) => notification.unread).length);
const importantNotificationsCount = computed(() => notifications.value.filter((notification) => notification.important).length);

const visibleNotifications = computed(() => {
    if (activeNotificationTab.value === 'unread') {
        return notifications.value.filter((notification) => notification.unread);
    }

    if (activeNotificationTab.value === 'important') {
        return notifications.value.filter((notification) => notification.important);
    }

    return notifications.value;
});

const openNotificationsModal = async () => {
    isNotificationsModalOpen.value = true;
    await loadAll();
};

// Handler para clicar em notificação
const handleNotificationClick = async (notification: Notification) => {
    if (!notification.is_read) {
        await markAsRead(notification.id);
    }

    await openNotificationsModal();
};

// Handler para marcar todas como lidas
const handleMarkAllAsRead = async () => {
    await markAllAsRead();
};

// Handler para ver todas as notificações
const handleViewAll = () => {
    openNotificationsModal();
};
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
                    <Button
                        variant="ghost"
                        size="icon"
                        class="relative h-9 w-9 rounded-lg border border-zinc-200 bg-white text-zinc-700 shadow-sm transition-colors hover:bg-zinc-50 hover:text-zinc-950"
                    >
                        <Bell class="h-4 w-4" />
                        <span
                            v-if="unreadCount > 0"
                            class="absolute -top-1 -right-1 flex h-5 min-w-5 items-center justify-center rounded-full border-2 border-white bg-red-600 px-1 text-[10px] font-bold text-white shadow-sm"
                        >
                            {{ unreadCount > 9 ? '9+' : unreadCount }}
                        </span>
                    </Button>
                </DropdownMenuTrigger>

                <DropdownMenuContent align="end" class="w-[420px] overflow-hidden rounded-xl border-zinc-200 p-0 shadow-2xl">
                    <div class="flex items-center gap-3 px-4 py-3">
                        <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-teal-50 text-teal-700">
                            <Inbox class="h-4 w-4" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <DropdownMenuLabel class="p-0 text-[15px] font-bold text-zinc-950">Notificações</DropdownMenuLabel>
                            <p class="mt-0.5 text-xs text-zinc-500">
                                {{ notifications.length }} no total
                                <span v-if="unreadCount > 0"> · {{ unreadCount }} nova{{ unreadCount > 1 ? 's' : '' }}</span>
                            </p>
                        </div>
                        <button
                            type="button"
                            class="rounded-md px-2 py-1 text-xs font-semibold text-teal-700 transition-colors hover:bg-teal-50 disabled:pointer-events-none disabled:text-zinc-300"
                            :disabled="!hasUnread"
                            @click="handleMarkAllAsRead"
                        >
                            Marcar lidas
                        </button>
                    </div>

                    <div class="grid grid-cols-3 gap-1 border-y border-zinc-100 bg-zinc-50 p-2">
                        <button
                            type="button"
                            class="rounded-md px-2 py-1.5 text-xs font-semibold transition-colors"
                            :class="
                                activeNotificationTab === 'all'
                                    ? 'bg-white text-zinc-950 shadow-sm'
                                    : 'text-zinc-500 hover:bg-white/70 hover:text-zinc-800'
                            "
                            @click="activeNotificationTab = 'all'"
                        >
                            Todas <span class="ml-1 text-[10px] text-zinc-400">{{ notifications.length }}</span>
                        </button>
                        <button
                            type="button"
                            class="rounded-md px-2 py-1.5 text-xs font-semibold transition-colors"
                            :class="
                                activeNotificationTab === 'unread'
                                    ? 'bg-white text-zinc-950 shadow-sm'
                                    : 'text-zinc-500 hover:bg-white/70 hover:text-zinc-800'
                            "
                            @click="activeNotificationTab = 'unread'"
                        >
                            Não lidas <span class="ml-1 text-[10px] text-zinc-400">{{ unreadNotificationsCount }}</span>
                        </button>
                        <button
                            type="button"
                            class="rounded-md px-2 py-1.5 text-xs font-semibold transition-colors"
                            :class="
                                activeNotificationTab === 'important'
                                    ? 'bg-white text-zinc-950 shadow-sm'
                                    : 'text-zinc-500 hover:bg-white/70 hover:text-zinc-800'
                            "
                            @click="activeNotificationTab = 'important'"
                        >
                            Importantes <span class="ml-1 text-[10px] text-zinc-400">{{ importantNotificationsCount }}</span>
                        </button>
                    </div>

                    <div class="max-h-[410px] overflow-y-auto p-2">
                        <template v-if="visibleNotifications.length === 0">
                            <div class="px-4 py-10 text-center text-sm text-zinc-500">
                                <CheckCircle2 class="mx-auto mb-3 h-7 w-7 text-teal-600" />
                                <p class="font-semibold text-zinc-800">Tudo em dia</p>
                                <p class="mt-1 text-xs">Sem notificações neste filtro.</p>
                            </div>
                        </template>
                        <template v-else>
                            <DropdownMenuItem
                                v-for="notification in visibleNotifications.slice(0, 5)"
                                :key="notification.id"
                                class="cursor-pointer rounded-lg p-0 focus:bg-transparent"
                                @click="handleNotificationClick(notification)"
                            >
                                <div
                                    class="flex w-full items-start gap-3 rounded-lg border border-transparent p-3 transition-colors hover:border-zinc-200 hover:bg-zinc-50"
                                    :class="notification.unread ? 'bg-teal-50/70' : 'bg-white'"
                                >
                                    <div
                                        class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-lg"
                                        :class="[notification.unread ? 'ring-1 ring-current/10 ring-inset' : '', colorMap[notification.color] || '']"
                                    >
                                        <component :is="notification.icon" class="h-4 w-4" />
                                    </div>
                                    <div class="min-w-0 flex-1 space-y-1.5">
                                        <div class="flex items-start justify-between gap-2">
                                            <p class="line-clamp-1 text-sm leading-5 font-semibold text-zinc-950">
                                                <span
                                                    v-if="notification.important"
                                                    class="mr-1 rounded-full bg-red-50 px-1.5 py-0.5 text-[10px] font-bold text-red-700"
                                                >
                                                    Importante
                                                </span>
                                                {{ notification.title }}
                                            </p>
                                            <span v-if="notification.unread" class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-teal-600" />
                                        </div>
                                        <p class="line-clamp-2 text-xs leading-5 text-zinc-600">
                                            {{ notification.description }}
                                        </p>
                                        <p class="flex items-center gap-1.5 text-[11px] font-medium text-zinc-400">
                                            <span>{{ notification.time }}</span>
                                            <span class="h-1 w-1 rounded-full bg-zinc-300" />
                                            <span>{{ notification.type }}</span>
                                        </p>
                                    </div>
                                </div>
                            </DropdownMenuItem>
                        </template>
                    </div>

                    <DropdownMenuSeparator class="m-0" />
                    <div class="flex items-center justify-between bg-white px-4 py-3">
                        <span class="text-xs font-medium text-zinc-500">Central de notificações</span>
                        <DropdownMenuItem
                            @click="handleViewAll"
                            class="cursor-pointer rounded-md px-2 py-1 text-xs font-bold text-teal-700 focus:bg-teal-50 focus:text-teal-800"
                        >
                            Abrir central
                            <ChevronRight class="h-3 w-3" />
                        </DropdownMenuItem>
                    </div>
                </DropdownMenuContent>
            </DropdownMenu>

            <NotificationsModal
                v-model:open="isNotificationsModalOpen"
                :notifications="allNotifications"
                :loading="loading"
                :has-unread="hasUnread"
                @mark-all-read="handleMarkAllAsRead"
            />

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
