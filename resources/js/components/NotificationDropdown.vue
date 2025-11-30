<template>
    <div
        class="absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-lg border border-gray-200 z-50 max-h-96 overflow-hidden flex flex-col"
    >
        <div class="p-4 border-b border-gray-200 flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-900">Notificações</h3>
            <button
                v-if="hasUnread"
                @click="handleMarkAllAsRead"
                class="text-sm text-blue-600 hover:text-blue-800"
            >
                Marcar todas como lidas
            </button>
        </div>

        <div class="overflow-y-auto flex-1">
            <div v-if="loading" class="p-4 text-center text-gray-500">
                Carregando...
            </div>
            <div v-else-if="notifications.length === 0" class="p-4 text-center text-gray-500">
                Nenhuma notificação
            </div>
            <div v-else class="divide-y divide-gray-200">
                <div
                    v-for="notification in notifications"
                    :key="notification.id"
                    @click="handleNotificationClick(notification)"
                    class="p-4 hover:bg-gray-50 cursor-pointer transition-colors"
                    :class="{ 'bg-blue-50': !notification.is_read }"
                >
                    <div class="flex items-start space-x-3">
                        <div
                            class="flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center"
                            :class="getColorClass(notification.color)"
                        >
                            <span class="text-white text-lg">{{ getIcon(notification.icon) }}</span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p
                                class="text-sm font-medium text-gray-900"
                                :class="{ 'font-bold': !notification.is_read }"
                            >
                                {{ notification.title }}
                            </p>
                            <p class="text-sm text-gray-500 mt-1">
                                {{ notification.message }}
                            </p>
                            <p class="text-xs text-gray-400 mt-1">
                                {{ notification.time }}
                            </p>
                        </div>
                        <div v-if="!notification.is_read" class="flex-shrink-0">
                            <span class="w-2 h-2 bg-blue-500 rounded-full block"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="p-2 border-t border-gray-200 text-center">
            <a
                href="/notifications"
                class="text-sm text-blue-600 hover:text-blue-800"
                @click="$emit('close')"
            >
                Ver todas as notificações
            </a>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, onMounted } from 'vue';
import { useNotifications } from '@/composables/useNotifications';

const emit = defineEmits(['close']);

const {
    notifications,
    loading,
    hasUnread,
    loadUnread,
    markAsRead,
    markAllAsRead,
} = useNotifications();

const handleNotificationClick = async (notification: Notification) => {
    if (!notification.is_read) {
        await markAsRead(notification.id);
    }
    emit('close');
    // Aqui você pode navegar para a página relevante baseado no tipo
};

const handleMarkAllAsRead = async () => {
    await markAllAsRead();
};

const getColorClass = (color: string) => {
    const colors: Record<string, string> = {
        blue: 'bg-blue-500',
        red: 'bg-red-500',
        yellow: 'bg-yellow-500',
        green: 'bg-green-500',
        purple: 'bg-purple-500',
        indigo: 'bg-indigo-500',
        orange: 'bg-orange-500',
    };
    return colors[color] || 'bg-gray-500';
};

const getIcon = (icon: string) => {
    // Retornar emoji ou ícone baseado no tipo
    const icons: Record<string, string> = {
        'calendar-plus': '📅',
        'calendar-x': '❌',
        'calendar-clock': '🔄',
        'prescription': '💊',
        'clipboard-list': '🔬',
        'file-text': '📄',
        'bell': '🔔',
    };
    return icons[icon] || '🔔';
};

onMounted(() => {
    loadUnread();
});
</script>


