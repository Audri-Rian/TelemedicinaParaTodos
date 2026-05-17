<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import type { Notification } from '@/composables/useNotifications';
import {
    Bell,
    Calendar,
    CalendarClock,
    CalendarX,
    Check,
    ClipboardList,
    FileText,
    Inbox,
    Pill,
    Search,
    Settings,
    type LucideIcon,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps<{
    open: boolean;
    notifications: Notification[];
    loading?: boolean;
    hasUnread?: boolean;
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
    'mark-all-read': [];
}>();

const activeFilter = ref<'all' | 'unread' | 'important'>('all');
const activeCategory = ref('all');
const search = ref('');

const iconMap: Record<string, LucideIcon> = {
    'calendar-plus': Calendar,
    'calendar-x': CalendarX,
    'calendar-clock': CalendarClock,
    prescription: Pill,
    'clipboard-list': ClipboardList,
    'file-text': FileText,
    bell: Bell,
};

const colorMap: Record<string, string> = {
    blue: 'bg-sky-50 text-sky-700',
    red: 'bg-red-50 text-red-700',
    yellow: 'bg-amber-50 text-amber-800',
    green: 'bg-emerald-50 text-emerald-700',
    purple: 'bg-violet-50 text-violet-700',
    indigo: 'bg-indigo-50 text-indigo-700',
    orange: 'bg-orange-50 text-orange-700',
};

const formattedNotifications = computed(() =>
    props.notifications.map((notification) => ({
        ...notification,
        category: categoryForNotification(notification),
        iconComponent: iconMap[notification.icon] || Bell,
        important: ['red', 'yellow', 'orange'].includes(notification.color),
        iconClass: colorMap[notification.color] || 'bg-muted text-muted-foreground',
    })),
);

const unreadCount = computed(() => formattedNotifications.value.filter((notification) => !notification.is_read).length);
const importantCount = computed(() => formattedNotifications.value.filter((notification) => notification.important).length);

const categories = computed(() => [
    { id: 'all', label: 'Todas', icon: Inbox, count: formattedNotifications.value.length },
    { id: 'agenda', label: 'Agenda', icon: Calendar, count: countByCategory('agenda') },
    { id: 'documentos', label: 'Documentos', icon: FileText, count: countByCategory('documentos') },
    { id: 'sistema', label: 'Sistema', icon: Settings, count: countByCategory('sistema') },
]);

const filteredNotifications = computed(() => {
    const query = search.value.trim().toLowerCase();

    return formattedNotifications.value.filter((notification) => {
        if (activeFilter.value === 'unread' && notification.is_read) {
            return false;
        }

        if (activeFilter.value === 'important' && !notification.important) {
            return false;
        }

        if (activeCategory.value !== 'all' && notification.category !== activeCategory.value) {
            return false;
        }

        if (!query) {
            return true;
        }

        return `${notification.title} ${notification.message} ${notification.type}`.toLowerCase().includes(query);
    });
});

const groupedNotifications = computed(() => {
    const groups: Record<string, typeof filteredNotifications.value> = {};

    filteredNotifications.value.forEach((notification) => {
        const label = dateLabel(notification.timestamp);
        groups[label] = groups[label] || [];
        groups[label].push(notification);
    });

    return Object.entries(groups);
});

function categoryForNotification(notification: Notification) {
    if (['calendar-plus', 'calendar-x', 'calendar-clock', 'bell'].includes(notification.icon)) {
        return 'agenda';
    }

    if (['prescription', 'clipboard-list', 'file-text'].includes(notification.icon)) {
        return 'documentos';
    }

    return 'sistema';
}

function countByCategory(category: string) {
    return formattedNotifications.value.filter((notification) => notification.category === category).length;
}

function dateLabel(timestamp: string) {
    const date = new Date(timestamp);
    const today = new Date();
    const yesterday = new Date();
    yesterday.setDate(today.getDate() - 1);

    if (date.toDateString() === today.toDateString()) {
        return 'Hoje';
    }

    if (date.toDateString() === yesterday.toDateString()) {
        return 'Ontem';
    }

    return new Intl.DateTimeFormat('pt-BR', {
        day: '2-digit',
        month: 'short',
    }).format(date);
}
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="flex max-h-[88vh] overflow-hidden rounded-2xl p-0 sm:max-w-5xl">
            <DialogHeader class="border-b border-zinc-200 px-5 py-4 pr-12">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center">
                    <div class="flex min-w-0 flex-1 items-start gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-teal-50 text-teal-700">
                            <Bell class="h-5 w-5" />
                        </div>
                        <div class="min-w-0">
                            <DialogTitle class="text-xl font-extrabold tracking-tight text-zinc-950">Central de notificações</DialogTitle>
                            <DialogDescription class="mt-1 text-sm text-zinc-500">
                                <span v-if="unreadCount > 0" class="font-semibold text-teal-700"
                                    >{{ unreadCount }} não lida{{ unreadCount > 1 ? 's' : '' }}</span
                                >
                                <span v-if="unreadCount > 0"> · </span>
                                {{ formattedNotifications.length }} no total
                            </DialogDescription>
                        </div>
                    </div>

                    <div class="relative w-full lg:w-80">
                        <Search class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-zinc-400" />
                        <input
                            v-model="search"
                            type="search"
                            class="h-10 w-full rounded-lg border border-zinc-200 bg-zinc-50 pr-3 pl-9 text-sm transition-colors outline-none placeholder:text-zinc-400 focus:border-teal-500 focus:bg-white"
                            placeholder="Buscar por conteúdo..."
                        />
                    </div>
                </div>
            </DialogHeader>

            <div class="grid min-h-0 flex-1 bg-zinc-50 lg:grid-cols-[220px_minmax(0,1fr)]">
                <aside class="hidden border-r border-zinc-200 bg-white p-4 lg:block">
                    <div class="mb-2 text-[11px] font-bold tracking-wide text-zinc-400 uppercase">Categorias</div>
                    <div class="space-y-1">
                        <button
                            v-for="category in categories"
                            :key="category.id"
                            type="button"
                            class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-sm font-semibold transition-colors"
                            :class="
                                activeCategory === category.id ? 'bg-teal-50 text-teal-800' : 'text-zinc-600 hover:bg-zinc-50 hover:text-zinc-950'
                            "
                            @click="activeCategory = category.id"
                        >
                            <component :is="category.icon" class="h-4 w-4" />
                            <span class="min-w-0 flex-1 truncate">{{ category.label }}</span>
                            <span class="text-xs text-zinc-400">{{ category.count }}</span>
                        </button>
                    </div>

                    <div class="mt-6 mb-2 text-[11px] font-bold tracking-wide text-zinc-400 uppercase">Atalhos</div>
                    <button
                        type="button"
                        class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-sm font-semibold text-zinc-600 transition-colors hover:bg-zinc-50 hover:text-zinc-950 disabled:pointer-events-none disabled:text-zinc-300"
                        :disabled="!hasUnread"
                        @click="emit('mark-all-read')"
                    >
                        <Check class="h-4 w-4" />
                        Marcar tudo como lido
                    </button>
                </aside>

                <div class="flex min-h-0 flex-col">
                    <div class="flex gap-1 border-b border-zinc-200 bg-white p-2">
                        <button
                            type="button"
                            class="rounded-lg px-3 py-2 text-sm font-bold transition-colors"
                            :class="activeFilter === 'all' ? 'bg-zinc-950 text-white' : 'text-zinc-500 hover:bg-zinc-50 hover:text-zinc-950'"
                            @click="activeFilter = 'all'"
                        >
                            Todas <span class="ml-1 opacity-70">{{ formattedNotifications.length }}</span>
                        </button>
                        <button
                            type="button"
                            class="rounded-lg px-3 py-2 text-sm font-bold transition-colors"
                            :class="activeFilter === 'unread' ? 'bg-zinc-950 text-white' : 'text-zinc-500 hover:bg-zinc-50 hover:text-zinc-950'"
                            @click="activeFilter = 'unread'"
                        >
                            Não lidas <span class="ml-1 opacity-70">{{ unreadCount }}</span>
                        </button>
                        <button
                            type="button"
                            class="rounded-lg px-3 py-2 text-sm font-bold transition-colors"
                            :class="activeFilter === 'important' ? 'bg-zinc-950 text-white' : 'text-zinc-500 hover:bg-zinc-50 hover:text-zinc-950'"
                            @click="activeFilter = 'important'"
                        >
                            Importantes <span class="ml-1 opacity-70">{{ importantCount }}</span>
                        </button>
                    </div>

                    <div class="flex items-center justify-between border-b border-zinc-200 bg-white px-4 py-3">
                        <span class="text-sm font-medium text-zinc-500">
                            {{ filteredNotifications.length }} notificação{{ filteredNotifications.length === 1 ? '' : 'es' }} visível{{
                                filteredNotifications.length === 1 ? '' : 'is'
                            }}
                        </span>
                        <Button v-if="hasUnread" type="button" variant="outline" size="sm" @click="emit('mark-all-read')"
                            >Marcar todas como lidas</Button
                        >
                    </div>

                    <div class="min-h-0 flex-1 overflow-y-auto p-4">
                        <div v-if="loading" class="py-16 text-center text-sm text-zinc-500">Carregando notificações...</div>

                        <div v-else-if="filteredNotifications.length === 0" class="py-16 text-center text-sm text-zinc-500">
                            <Inbox class="mx-auto mb-3 h-9 w-9 text-zinc-300" />
                            <p class="font-bold text-zinc-700">Nada por aqui</p>
                            <p class="mt-1">Nenhuma notificação corresponde aos filtros selecionados.</p>
                        </div>

                        <div v-else class="space-y-5">
                            <section v-for="[date, notificationsGroup] in groupedNotifications" :key="date">
                                <div class="mb-2 text-xs font-bold tracking-wide text-zinc-400 uppercase">{{ date }}</div>

                                <div class="space-y-2">
                                    <article
                                        v-for="notification in notificationsGroup"
                                        :key="notification.id"
                                        class="rounded-xl border bg-white p-4 shadow-sm transition-colors"
                                        :class="notification.is_read ? 'border-zinc-200' : 'border-teal-200 ring-1 ring-teal-100'"
                                    >
                                        <div class="flex gap-3">
                                            <div
                                                class="mt-0.5 flex h-10 w-10 shrink-0 items-center justify-center rounded-xl ring-1 ring-current/10 ring-inset"
                                                :class="notification.iconClass"
                                            >
                                                <component :is="notification.iconComponent" class="h-4 w-4" />
                                            </div>

                                            <div class="min-w-0 flex-1 space-y-2">
                                                <div class="flex items-start justify-between gap-3">
                                                    <div class="min-w-0">
                                                        <h3 class="text-sm font-bold text-zinc-950">
                                                            <span
                                                                v-if="notification.important"
                                                                class="mr-1 rounded-full bg-red-50 px-1.5 py-0.5 text-[10px] font-bold text-red-700"
                                                            >
                                                                Importante
                                                            </span>
                                                            {{ notification.title }}
                                                        </h3>
                                                        <p class="mt-1 flex items-center gap-1.5 text-xs font-medium text-zinc-400">
                                                            <span>{{ notification.time }}</span>
                                                            <span class="h-1 w-1 rounded-full bg-zinc-300" />
                                                            <span>{{ notification.type }}</span>
                                                        </p>
                                                    </div>

                                                    <span
                                                        class="shrink-0 rounded-full px-2 py-0.5 text-xs font-bold"
                                                        :class="notification.is_read ? 'bg-zinc-100 text-zinc-500' : 'bg-teal-600 text-white'"
                                                    >
                                                        {{ notification.is_read ? 'Lida' : 'Nova' }}
                                                    </span>
                                                </div>

                                                <p class="text-sm leading-6 text-zinc-600">
                                                    {{ notification.message }}
                                                </p>
                                            </div>
                                        </div>
                                    </article>
                                </div>
                            </section>
                        </div>
                    </div>
                </div>
            </div>

            <DialogFooter class="border-t border-zinc-200 bg-white px-5 py-4">
                <Button type="button" @click="emit('update:open', false)">Fechar</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
