import { ref, computed, onMounted, onUnmounted } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import Echo from 'laravel-echo';

interface Notification {
    id: string;
    type: string;
    title: string;
    message: string;
    icon: string;
    color: string;
    time: string;
    timestamp: string;
    metadata: Record<string, any>;
    is_read: boolean;
    read_at: string | null;
}

export function useNotifications() {
    const page = usePage();
    const currentUserId = (page.props.auth as any)?.user?.id;
    let echo: Echo | null = null;
    const notifications = ref<Notification[]>([]);
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
            const data = await response.json();
            notifications.value = data.data || [];
            unreadCount.value = data.count || 0;
        } catch (error) {
            console.error('Erro ao carregar notificações:', error);
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
            const data = await response.json();
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
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
            });

            if (response.ok) {
                // Atualizar notificação localmente
                const index = notifications.value.findIndex(n => n.id === notificationId);
                if (index !== -1) {
                    notifications.value[index].is_read = true;
                    notifications.value[index].read_at = new Date().toISOString();
                }
                unreadCount.value = Math.max(0, unreadCount.value - 1);
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
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
            });

            if (response.ok) {
                notifications.value.forEach(n => {
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
        if (!notification.is_read) {
            unreadCount.value++;
        }
    };

    /**
     * Remover notificação
     */
    const removeNotification = (notificationId: string) => {
        const index = notifications.value.findIndex(n => n.id === notificationId);
        if (index !== -1) {
            if (!notifications.value[index].is_read) {
                unreadCount.value = Math.max(0, unreadCount.value - 1);
            }
            notifications.value.splice(index, 1);
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

            if (!reverbConfig) {
                console.warn('Reverb não configurado. Adicione os dados no middleware HandleInertiaRequests.');
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
            channel.value.listen('.notification.created', (data: { data: Notification }) => {
                addNotification(data.data);
            });

            // Tratamento de reconexão
            echo.connector.pusher.connection.bind('connected', () => {
                console.log('WebSocket conectado - notificações ativas');
                // Recarregar notificações ao reconectar
                loadUnreadCount();
            });

            echo.connector.pusher.connection.bind('disconnected', () => {
                console.warn('WebSocket desconectado - tentando reconectar...');
            });

            echo.connector.pusher.connection.bind('error', (err: any) => {
                console.error('Erro no WebSocket:', err);
                // Tentar reconectar após delay
                setTimeout(() => {
                    if (echo) {
                        echo.disconnect();
                        echo = null;
                        initializeRealtime();
                    }
                }, 5000);
            });
        } catch (err) {
            console.error('Erro ao configurar Echo:', err);
            // Tentar novamente após delay
            setTimeout(() => {
                initializeRealtime();
            }, 5000);
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
    const unreadNotifications = computed(() => 
        notifications.value.filter(n => !n.is_read)
    );

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
        unreadCount,
        loading,
        hasUnread,
        unreadNotifications,
        loadUnread,
        loadUnreadCount,
        markAsRead,
        markAllAsRead,
        addNotification,
        removeNotification,
    };
}

