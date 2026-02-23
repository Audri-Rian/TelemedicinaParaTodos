import { ref, computed, onMounted, onUnmounted } from 'vue';
import axios from 'axios';
import { router, usePage } from '@inertiajs/vue3';
import Echo from 'laravel-echo';

export type MessageStatus = 'sending' | 'sent' | 'delivered' | 'failed';

export interface Message {
    id: string;
    sender_id: string;
    receiver_id: string;
    content: string;
    read_at: string | null;
    status?: MessageStatus;
    delivered_at?: string | null;
    created_at: string;
    sender?: {
        id: string;
        name: string;
        avatar_path: string | null;
    };
    receiver?: {
        id: string;
        name: string;
        avatar_path: string | null;
    };
    // Campos locais para controle de estado
    _localId?: string; // ID temporário para mensagens sendo enviadas
    _retryCount?: number; // Contador de tentativas
}

export interface Conversation {
    id: string;
    name: string;
    avatar: string | null;
    lastMessage: string;
    lastMessageTime: string | null;
    unread: number;
}

export function useMessages() {
    const conversations = ref<Conversation[]>([]);
    const messages = ref<Message[]>([]);
    const selectedConversationId = ref<string | null>(null);
    const isLoading = ref(false);
    const isSending = ref(false);
    const error = ref<string | null>(null);
    const unreadCount = ref(0);
    
    const page = usePage();
    const currentUserId = (page.props.auth as any)?.user?.id;
    let echo: Echo | null = null;
    
    // Controle de mensagens pendentes e retry
    const pendingMessages = ref<Map<string, { message: Message; retryCount: number; receiverId: string }>>(new Map());
    const MAX_RETRY_ATTEMPTS = 3;
    const RETRY_DELAY = 2000; // 2 segundos

    /**
     * Carregar conversas do usuário
     */
    const loadConversations = async () => {
        isLoading.value = true;
        error.value = null;

        try {
            const response = await axios.get('/api/messages/conversations');
            
            if (response.data.success) {
                conversations.value = response.data.data.map((conv: any) => ({
                    id: conv.id,
                    name: conv.name,
                    avatar: conv.avatar,
                    lastMessage: conv.lastMessage,
                    lastMessageTime: conv.lastMessageTime,
                    unread: conv.unread,
                }));
            } else {
                error.value = response.data.message || 'Erro ao carregar conversas';
            }
        } catch (err: any) {
            error.value = err.response?.data?.message || 'Erro ao carregar conversas';
            console.error('Erro ao carregar conversas:', err);
        } finally {
            isLoading.value = false;
        }
    };

    /**
     * Carregar mensagens de uma conversa
     */
    const loadMessages = async (userId: string) => {
        isLoading.value = true;
        error.value = null;
        selectedConversationId.value = userId;

        try {
            const response = await axios.get(`/api/messages/${userId}`);

            if (response.data.success) {
                messages.value = response.data.data.map((msg: any) => ({
                    ...msg,
                    // Formatar data para exibição
                    formattedTime: new Date(msg.created_at).toLocaleTimeString('pt-BR', {
                        hour: '2-digit',
                        minute: '2-digit',
                    }),
                }));

                // Marcar mensagens como lidas
                await markAsRead(userId);
            } else {
                error.value = response.data.message || 'Erro ao carregar mensagens';
            }
        } catch (err: any) {
            error.value = err.response?.data?.message || 'Erro ao carregar mensagens';
            console.error('Erro ao carregar mensagens:', err);
        } finally {
            isLoading.value = false;
        }
    };

    /**
     * Enviar mensagem com retry automático e status tracking
     */
    const sendMessage = async (receiverId: string, content: string, appointmentId?: string): Promise<boolean> => {
        if (!content.trim() || isSending.value) {
            return false;
        }

        isSending.value = true;
        error.value = null;

        // Criar mensagem temporária com status "sending"
        const tempId = `temp_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
        const tempMessage: Message = {
            id: tempId,
            _localId: tempId,
            sender_id: currentUserId,
            receiver_id: receiverId,
            content: content.trim(),
            status: 'sending',
            created_at: new Date().toISOString(),
            sender: {
                id: currentUserId,
                name: (page.props.auth as any)?.user?.name || '',
                avatar_path: (page.props.auth as any)?.user?.avatar_path || null,
            },
        };

        // Adicionar mensagem temporária à lista (será substituída pelo broadcast)
        if (selectedConversationId.value === receiverId) {
            messages.value.push(tempMessage);
        }

        // Atualizar última mensagem na lista de conversas
        const conversation = conversations.value.find(c => c.id === receiverId);
        if (conversation) {
            conversation.lastMessage = content.trim();
            conversation.lastMessageTime = tempMessage.created_at;
        }

        // Tentar enviar com retry
        return await sendMessageWithRetry(receiverId, content.trim(), appointmentId, tempId, 0);
    };

    /**
     * Enviar mensagem com retry automático
     */
    const sendMessageWithRetry = async (
        receiverId: string,
        content: string,
        appointmentId: string | undefined,
        tempId: string,
        retryCount: number
    ): Promise<boolean> => {
        try {
            const response = await axios.post('/api/messages', {
                receiver_id: receiverId,
                content: content,
                appointment_id: appointmentId || null,
            });

            if (response.data.success) {
                // Remover mensagem temporária (será substituída pelo broadcast)
                const tempIndex = messages.value.findIndex(m => m._localId === tempId);
                if (tempIndex !== -1) {
                    messages.value.splice(tempIndex, 1);
                }

                // Remover de pendentes
                pendingMessages.value.delete(tempId);

                // Atualizar última mensagem na lista de conversas
                const conversation = conversations.value.find(c => c.id === receiverId);
                if (conversation) {
                    conversation.lastMessage = content;
                    conversation.lastMessageTime = response.data.data.created_at;
                }

                isSending.value = false;
                return true;
            } else {
                throw new Error(response.data.message || 'Erro ao enviar mensagem');
            }
        } catch (err: any) {
            // Se falhou e ainda há tentativas, tentar novamente
            if (retryCount < MAX_RETRY_ATTEMPTS) {
                console.warn(`Tentativa ${retryCount + 1} falhou, tentando novamente em ${RETRY_DELAY}ms...`);
                
                // Atualizar status da mensagem temporária para "failed" temporariamente
                const tempMessage = messages.value.find(m => m._localId === tempId);
                if (tempMessage) {
                    tempMessage.status = 'failed';
                }

                // Guardar para retry
                pendingMessages.value.set(tempId, {
                    message: tempMessage!,
                    retryCount: retryCount + 1,
                    receiverId,
                });

                // Retry após delay
                setTimeout(async () => {
                    await sendMessageWithRetry(receiverId, content, appointmentId, tempId, retryCount + 1);
                }, RETRY_DELAY);

                return false;
            } else {
                // Máximo de tentativas excedido
                error.value = err.response?.data?.message || 'Erro ao enviar mensagem após várias tentativas';
                console.error('Erro ao enviar mensagem após', MAX_RETRY_ATTEMPTS, 'tentativas:', err);

                // Atualizar status da mensagem para "failed"
                const tempMessage = messages.value.find(m => m._localId === tempId);
                if (tempMessage) {
                    tempMessage.status = 'failed';
                }

                isSending.value = false;
                return false;
            }
        }
    };

    /**
     * Marcar mensagens como lidas
     */
    const markAsRead = async (userId: string) => {
        try {
            await axios.post(`/api/messages/${userId}/read`);
            
            // Atualizar contador local
            const conversation = conversations.value.find(c => c.id === userId);
            if (conversation) {
                conversation.unread = 0;
            }

            // Marcar mensagens como lidas localmente
            messages.value.forEach(msg => {
                if (msg.receiver_id === userId && !msg.read_at) {
                    msg.read_at = new Date().toISOString();
                }
            });
        } catch (err) {
            console.error('Erro ao marcar mensagens como lidas:', err);
        }
    };

    /**
     * Carregar contador de mensagens não lidas
     */
    const loadUnreadCount = async () => {
        try {
            const response = await axios.get('/api/messages/unread/count');
            if (response.data.success) {
                unreadCount.value = response.data.count;
            }
        } catch (err) {
            console.error('Erro ao carregar contador de não lidas:', err);
        }
    };

    /**
     * Verificar se a mensagem foi enviada pelo usuário atual
     */
    const isMyMessage = (message: Message, currentUserId: string): boolean => {
        return message.sender_id === currentUserId;
    };

    /**
     * Formatar data da mensagem
     */
    const formatMessageTime = (dateString: string): string => {
        const date = new Date(dateString);
        const now = new Date();
        const diff = now.getTime() - date.getTime();
        const days = Math.floor(diff / (1000 * 60 * 60 * 24));

        if (days === 0) {
            return date.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
        } else if (days === 1) {
            return 'Ontem';
        } else if (days < 7) {
            return date.toLocaleDateString('pt-BR', { weekday: 'short' });
        } else {
            return date.toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit' });
        }
    };

    /**
     * Configurar escuta de eventos em tempo real
     */
    const setupRealtimeListener = () => {
        if (!currentUserId || echo) {
            return;
        }

        try {
            // Usar configuração do Reverb do Inertia (mesmo padrão do useVideoCall)
            const reverbConfig = (page.props as any)?.reverb;

            if (!reverbConfig?.key) {
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

            // Escutar canal privado de mensagens do usuário atual
            const channel = echo.private(`messages.${currentUserId}`);
            
            channel.listen('.message.sent', (data: any) => {
                handleNewMessage(data);
            });

            // Tratamento de reconexão
            echo.connector.pusher.connection.bind('connected', () => {
                console.log('WebSocket conectado - verificando mensagens perdidas...');
                // Se estava desconectado, recarregar mensagens da conversa atual
                if (selectedConversationId.value) {
                    loadMessages(selectedConversationId.value);
                }
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
                        setupRealtimeListener();
                    }
                }, 5000);
            });
        } catch (err) {
            console.error('Erro ao configurar Echo:', err);
            // Tentar novamente após delay
            setTimeout(() => {
                setupRealtimeListener();
            }, 5000);
        }
    };

    /**
     * Lidar com nova mensagem recebida via websocket
     */
    const handleNewMessage = async (data: any) => {
        const newMessage: Message = {
            id: data.id,
            sender_id: data.sender_id,
            receiver_id: data.receiver_id,
            content: data.content,
            read_at: data.read_at,
            status: data.status || 'sent',
            delivered_at: data.delivered_at,
            created_at: data.created_at,
            sender: data.sender,
            receiver: data.receiver,
            formattedTime: new Date(data.created_at).toLocaleTimeString('pt-BR', {
                hour: '2-digit',
                minute: '2-digit',
            }),
        };

        // Se a mensagem é para o usuário atual, marcar como delivered
        if (newMessage.receiver_id === currentUserId && newMessage.status === 'sent') {
            // Marcar como delivered no backend
            try {
                await axios.post(`/api/messages/${newMessage.id}/delivered`);
                newMessage.status = 'delivered';
                newMessage.delivered_at = new Date().toISOString();
            } catch (err) {
                console.error('Erro ao marcar mensagem como entregue:', err);
            }
        }

        // Se a mensagem é da conversa atual, adicionar à lista
        if (selectedConversationId.value) {
            const isFromCurrentConversation = 
                (newMessage.sender_id === selectedConversationId.value && newMessage.receiver_id === currentUserId) ||
                (newMessage.receiver_id === selectedConversationId.value && newMessage.sender_id === currentUserId);

            if (isFromCurrentConversation) {
                // Verificar se a mensagem já não existe (evitar duplicatas)
                const exists = messages.value.some(msg => msg.id === newMessage.id);
                if (!exists) {
                    messages.value.push(newMessage);
                }
            }
        }

        // Atualizar última mensagem na lista de conversas
        const conversationId = newMessage.sender_id === currentUserId 
            ? newMessage.receiver_id 
            : newMessage.sender_id;

        const conversation = conversations.value.find(c => c.id === conversationId);
        if (conversation) {
            conversation.lastMessage = newMessage.content;
            conversation.lastMessageTime = newMessage.created_at;
            if (newMessage.receiver_id === currentUserId) {
                conversation.unread += 1;
            }
        } else {
            // Se não existe conversa, recarregar lista
            loadConversations();
        }

        // Atualizar contador de não lidas
        if (newMessage.receiver_id === currentUserId) {
            unreadCount.value += 1;
        }
    };

    /**
     * Limpar escuta de eventos
     */
    const cleanupRealtimeListener = () => {
        if (echo) {
            try {
                echo.leave(`messages.${currentUserId}`);
                echo.disconnect();
            } catch (err) {
                console.error('Erro ao desconectar Echo:', err);
            }
            echo = null;
        }
    };

    /**
     * Verificar e reenviar mensagens pendentes
     */
    const retryPendingMessages = async () => {
        for (const [tempId, pending] of pendingMessages.value.entries()) {
            if (pending.retryCount < MAX_RETRY_ATTEMPTS) {
                await sendMessageWithRetry(
                    pending.receiverId,
                    pending.message.content,
                    undefined,
                    tempId,
                    pending.retryCount
                );
            } else {
                // Remover se excedeu tentativas
                pendingMessages.value.delete(tempId);
            }
        }
    };

    // Configurar escuta ao montar
    let retryInterval: ReturnType<typeof setInterval> | null = null;
    
    onMounted(() => {
        setupRealtimeListener();
        
        // Verificar mensagens pendentes periodicamente
        retryInterval = setInterval(() => {
            if (pendingMessages.value.size > 0) {
                retryPendingMessages();
            }
        }, 10000); // Verificar a cada 10 segundos
    });

    // Limpar ao desmontar
    onUnmounted(() => {
        if (retryInterval) {
            clearInterval(retryInterval);
        }
        cleanupRealtimeListener();
    });

    return {
        // Estado
        conversations,
        messages,
        selectedConversationId,
        isLoading,
        isSending,
        error,
        unreadCount,

        // Métodos
        loadConversations,
        loadMessages,
        sendMessage,
        markAsRead,
        loadUnreadCount,
        isMyMessage,
        formatMessageTime,
    };
}

