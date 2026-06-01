import { usePage } from '@inertiajs/vue3';
import Echo from 'laravel-echo';
import { computed, onMounted, onUnmounted, ref } from 'vue';

export interface Notification {
    id: string;
    type: string;
    title: string;
    message: string;
    icon: string;
    color: string;
    time: string;
    timestamp: string;
    metadata: Record<string, unknown>;
    is_read: boolean;
    read_at: string | null;
}

interface NotificationsIndexResponse {
    data?: Notification[];
}

interface UnreadNotificationsResponse {
    data?: Notification[];
    count?: number;
}

interface UnreadCountResponse {
    count?: number;
}

const MAX_WS_RETRIES = 8;

const notifications = ref<Notification[]>([]);
const allNotifications = ref<Notification[]>([]);
const unreadCount = ref(0);
const loading = ref(false);

let echo: any = null;

let channel: any = null;
let wsRetryDelay = 1000;
let wsRetryCount = 0;
let subscriberCount = 0;

const csrfToken = (): string => {
    const token = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content;
    if (!token) throw new Error('CSRF token not found');
    return token;
};

const xsrfToken = (): string | null => {
    const cookie = document.cookie.split('; ').find((row) => row.startsWith('XSRF-TOKEN='));

    if (!cookie) {
        return null;
    }

    return decodeURIComponent(cookie.substring('XSRF-TOKEN='.length));
};

const jsonHeaders = (): HeadersInit => ({
    Accept: 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
});

const csrfHeaders = (): HeadersInit => {
    const token = xsrfToken();

    return {
        ...jsonHeaders(),
        'Content-Type': 'application/json',
        ...(token ? { 'X-XSRF-TOKEN': token } : { 'X-CSRF-TOKEN': csrfToken() }),
    };
};

const loadUnread = async () => {
    try {
        loading.value = true;
        const response = await fetch('/api/notifications/unread', {
            headers: jsonHeaders(),
            credentials: 'same-origin',
        });
        if (!response.ok) {
            if (response.status === 401) window.location.href = '/login';
            throw new Error(`HTTP ${response.status}`);
        }
        const data = (await response.json()) as UnreadNotificationsResponse;
        notifications.value = data.data || [];
        unreadCount.value = data.count || 0;
    } catch (error) {
        console.error('Erro ao carregar notificações:', error);
    } finally {
        loading.value = false;
    }
};

const loadAll = async (page = 1) => {
    try {
        loading.value = true;
        const response = await fetch(`/api/notifications?per_page=20&page=${page}`, {
            headers: jsonHeaders(),
            credentials: 'same-origin',
        });
        if (!response.ok) {
            if (response.status === 401) window.location.href = '/login';
            throw new Error(`HTTP ${response.status}`);
        }
        const data = (await response.json()) as NotificationsIndexResponse;
        if (page === 1) {
            allNotifications.value = data.data || [];
        } else {
            allNotifications.value.push(...(data.data || []));
        }
    } catch (error) {
        console.error('Erro ao carregar todas as notificações:', error);
    } finally {
        loading.value = false;
    }
};

const loadUnreadCount = async () => {
    try {
        const response = await fetch('/api/notifications/unread-count', {
            headers: jsonHeaders(),
            credentials: 'same-origin',
        });
        if (!response.ok) {
            if (response.status === 401) window.location.href = '/login';
            throw new Error(`HTTP ${response.status}`);
        }
        const data = (await response.json()) as UnreadCountResponse;
        unreadCount.value = data.count || 0;
    } catch (error) {
        console.error('Erro ao carregar contador:', error);
    }
};

const markAsRead = async (notificationId: string) => {
    try {
        const response = await fetch(`/api/notifications/${notificationId}/read`, {
            method: 'POST',
            headers: csrfHeaders(),
            credentials: 'same-origin',
        });

        if (response.ok) {
            const index = notifications.value.findIndex((n) => n.id === notificationId);
            const allIndex = allNotifications.value.findIndex((n) => n.id === notificationId);
            const wasUnread = (index !== -1 && !notifications.value[index].is_read) || (allIndex !== -1 && !allNotifications.value[allIndex].is_read);

            if (index !== -1) {
                notifications.value[index].is_read = true;
                notifications.value[index].read_at = new Date().toISOString();
            }

            if (allIndex !== -1) {
                allNotifications.value[allIndex].is_read = true;
                allNotifications.value[allIndex].read_at = new Date().toISOString();
            }

            if (wasUnread) {
                unreadCount.value = Math.max(0, unreadCount.value - 1);
            }
        }
    } catch (error) {
        console.error('Erro ao marcar como lida:', error);
    }
};

const markAllAsRead = async () => {
    try {
        const response = await fetch('/api/notifications/read-all', {
            method: 'POST',
            headers: csrfHeaders(),
            credentials: 'same-origin',
        });

        if (response.ok) {
            notifications.value.forEach((n) => {
                n.is_read = true;
                n.read_at = new Date().toISOString();
            });
            allNotifications.value.forEach((n) => {
                n.is_read = true;
                n.read_at = new Date().toISOString();
            });
            unreadCount.value = 0;
        }
    } catch (error) {
        console.error('Erro ao marcar todas como lidas:', error);
    }
};

const addNotification = (notification: Notification) => {
    const alreadyExistsInUnread = notifications.value.some((item) => item.id === notification.id);
    const alreadyExistsInAll = allNotifications.value.some((item) => item.id === notification.id);

    if (alreadyExistsInUnread || alreadyExistsInAll) {
        return;
    }

    notifications.value.unshift(notification);
    allNotifications.value.unshift(notification);
    if (!notification.is_read) {
        unreadCount.value++;
    }
};

const removeNotification = (notificationId: string) => {
    const index = notifications.value.findIndex((n) => n.id === notificationId);
    if (index !== -1) {
        if (!notifications.value[index].is_read) {
            unreadCount.value = Math.max(0, unreadCount.value - 1);
        }
        notifications.value.splice(index, 1);
    }

    const allIndex = allNotifications.value.findIndex((n) => n.id === notificationId);
    if (allIndex !== -1) {
        allNotifications.value.splice(allIndex, 1);
    }
};

const disconnect = () => {
    if (channel) {
        channel.stopListening('.notification.created');
        channel = null;
    }
    if (echo) {
        echo.disconnect();
        echo = null;
    }
    wsRetryCount = 0;
};

const initializeRealtime = (currentUserId: string, reverbConfig: { key: string; host: string; port: number; scheme: string } | null) => {
    if (!currentUserId || echo) {
        return;
    }

    if (!reverbConfig?.key) {
        return;
    }

    try {
        echo = new Echo({
            broadcaster: 'reverb',
            key: reverbConfig.key,
            wsHost: reverbConfig.host,
            wsPort: reverbConfig.port,
            wssPort: reverbConfig.port,
            forceTLS: reverbConfig.scheme === 'https',
            enabledTransports: ['ws', 'wss'],
        });

        channel = echo.private(`notifications.${currentUserId}`);

        channel.listen('.notification.created', (data: Notification | { data: Notification }) => {
            addNotification('data' in data ? data.data : data);
        });

        echo.connector.pusher.connection.bind('connected', () => {
            wsRetryDelay = 1000;
            wsRetryCount = 0;
            void loadUnread();
        });

        echo.connector.pusher.connection.bind('error', () => {
            scheduleRealtimeRetry(currentUserId, reverbConfig);
        });
    } catch {
        scheduleRealtimeRetry(currentUserId, reverbConfig);
    }
};

const scheduleRealtimeRetry = (currentUserId: string, reverbConfig: { key: string; host: string; port: number; scheme: string } | null) => {
    if (wsRetryCount >= MAX_WS_RETRIES) {
        return;
    }

    wsRetryCount++;
    setTimeout(
        () => {
            if (echo) {
                echo.disconnect();
                echo = null;
                channel = null;
            }
            initializeRealtime(currentUserId, reverbConfig);
        },
        wsRetryDelay + Math.random() * 500,
    );
    wsRetryDelay = Math.min(wsRetryDelay * 2, 30_000);
};

export function useNotifications() {
    const page = usePage();
    const currentUserId = (page.props.auth as { user?: { id?: string } })?.user?.id;
    const reverbConfig = (page.props as { reverb?: { key: string; host: string; port: number; scheme: string } })?.reverb ?? null;

    const hasUnread = computed(() => unreadCount.value > 0);
    const unreadNotifications = computed(() => notifications.value.filter((n) => !n.is_read));

    onMounted(() => {
        if (!currentUserId) {
            return;
        }

        subscriberCount++;

        if (subscriberCount === 1) {
            void loadUnread();
            initializeRealtime(currentUserId, reverbConfig);
        }
    });

    onUnmounted(() => {
        if (!currentUserId) {
            return;
        }

        subscriberCount = Math.max(0, subscriberCount - 1);

        if (subscriberCount === 0) {
            disconnect();
        }
    });

    return {
        notifications,
        allNotifications,
        unreadCount,
        loading,
        hasUnread,
        unreadNotifications,
        loadUnread,
        loadAll,
        loadUnreadCount,
        markAsRead,
        markAllAsRead,
        addNotification,
        removeNotification,
    };
}
