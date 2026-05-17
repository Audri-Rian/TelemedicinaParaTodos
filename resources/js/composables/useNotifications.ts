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

const csrfToken = (): string => {
    const token = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content;
    if (!token) throw new Error('CSRF token not found');
    return token;
};

export function useNotifications() {
    const page = usePage();
    const currentUserId = (page.props.auth as any)?.user?.id;
    let echo: Echo | null = null;
    let wsRetryDelay = 1000;
    const notifications = ref<Notification[]>([]);
    const allNotifications = ref<Notification[]>([]);
    const unreadCount = ref(0);
    const loading = ref(false);
    const channel = ref<any>(null);

    /**
     * Carregar notificações não lidas
     */
    const loadUnread = async () => {
        try {
            loading.value = true;
            const response = await fetch('/api/notifications/unread');
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

    /**
     * Carregar todas as notificações para a modal
     */
    const loadAll = async (page = 1) => {
        try {
            loading.value = true;
            const response = await fetch(`/api/notifications?per_page=20&page=${page}`);
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

    /**
     * Carregar contador de não lidas
     */
    const loadUnreadCount = async () => {
        try {
            const response = await fetch('/api/notifications/unread-count');
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

    /**
     * Marcar notificação como lida
     */
    const markAsRead = async (notificationId: string) => {
        try {
            const response = await fetch(`/api/notifications/${notificationId}/read`, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                },
                credentials: 'same-origin',
            });

            if (response.ok) {
                const index = notifications.value.findIndex((n) => n.id === notificationId);
                const allIndex = allNotifications.value.findIndex((n) => n.id === notificationId);
                const wasUnread =
                    (index !== -1 && !notifications.value[index].is_read) || (allIndex !== -1 && !allNotifications.value[allIndex].is_read);

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

    /**
     * Marcar todas como lidas
     */
    const markAllAsRead = async () => {
        try {
            const response = await fetch('/api/notifications/read-all', {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                },
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

    /**
     * Adicionar nova notificação
     */
    const addNotification = (notification: Notification) => {
        notifications.value.unshift(notification);
        allNotifications.value.unshift(notification);
        if (!notification.is_read) {
            unreadCount.value++;
        }
    };

    /**
     * Remover notificação
     */
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

    /**
     * Inicializar listener de notificações em tempo real
     */
    const initializeRealtime = () => {
        if (!currentUserId || echo) {
            return;
        }

        try {
            // Usar configuração do Reverb do Inertia (mesmo padrão do useMessages)
            const reverbConfig = (page.props as any)?.reverb;

            if (!reverbConfig?.key) {
                // Reverb/Pusher não configurado ou REVERB_APP_KEY ausente no .env — notificações em tempo real desativadas
                return;
            }

            echo = new Echo({
                broadcaster: 'reverb',
                key: reverbConfig.key,
                wsHost: reverbConfig.host,
                wsPort: reverbConfig.port,
                wssPort: reverbConfig.port,
                forceTLS: reverbConfig.scheme === 'https',
                enabledTransports: ['ws', 'wss'],
            });

            // Conectar ao canal privado de notificações
            channel.value = echo.private(`notifications.${currentUserId}`);

            // Escutar evento de nova notificação
            channel.value.listen('.notification.created', (data: Notification | { data: Notification }) => {
                addNotification('data' in data ? data.data : data);
            });

            echo.connector.pusher.connection.bind('connected', () => {
                wsRetryDelay = 1000;
                loadUnreadCount();
            });

            echo.connector.pusher.connection.bind('error', () => {
                setTimeout(
                    () => {
                        if (echo) {
                            echo.disconnect();
                            echo = null;
                        }
                        initializeRealtime();
                    },
                    wsRetryDelay + Math.random() * 500,
                );
                wsRetryDelay = Math.min(wsRetryDelay * 2, 30_000);
            });
        } catch {
            setTimeout(() => initializeRealtime(), wsRetryDelay + Math.random() * 500);
            wsRetryDelay = Math.min(wsRetryDelay * 2, 30_000);
        }
    };

    /**
     * Desconectar do canal
     */
    const disconnect = () => {
        if (channel.value) {
            channel.value.stopListening('.notification.created');
            channel.value = null;
        }
        if (echo) {
            echo.disconnect();
            echo = null;
        }
    };

    // Computed
    const hasUnread = computed(() => unreadCount.value > 0);
    const unreadNotifications = computed(() => notifications.value.filter((n) => !n.is_read));

    // Lifecycle
    onMounted(() => {
        if (currentUserId) {
            loadUnreadCount();
            loadUnread();
            initializeRealtime();
        }
    });

    onUnmounted(() => {
        disconnect();
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
