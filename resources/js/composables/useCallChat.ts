import { usePage } from '@inertiajs/vue3';
import { echo } from '@laravel/echo-vue';
import axios from 'axios';
import { onUnmounted, ref, watch, type Ref } from 'vue';

export interface CallChatMessage {
    id: string;
    type: 'system' | 'them' | 'me';
    text?: string;
    author?: string;
    time?: string;
    pending?: boolean;
    failed?: boolean;
}

interface ApiMessage {
    id: string;
    sender_id: string | number;
    receiver_id: string | number;
    content: string;
    created_at: string;
}

interface UseCallChatOptions {
    isInCall: () => boolean;
    otherUserId: () => string | number | null | undefined;
    otherUserName: () => string;
    appointmentId: () => string | null | undefined;
}

const formatTime = (isoDate: string) => new Date(isoDate).toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });

export function useCallChat(options: UseCallChatOptions): {
    messages: Ref<CallChatMessage[]>;
    send: (text: string) => Promise<void>;
} {
    const page = usePage();
    const messages = ref<CallChatMessage[]>([]);
    let channelName: string | null = null;
    let loadedFor: string | null = null;
    let readReceiptTimer: ReturnType<typeof setTimeout> | null = null;

    const myUserId = () => String((page.props.auth as { user: { id: string | number } }).user.id);

    const toMessage = (msg: ApiMessage): CallChatMessage => {
        const mine = String(msg.sender_id) === myUserId();

        return {
            id: String(msg.id),
            type: mine ? 'me' : 'them',
            author: mine ? 'Você' : options.otherUserName(),
            text: msg.content,
            time: formatTime(msg.created_at),
        };
    };

    const upsert = (message: CallChatMessage) => {
        const index = messages.value.findIndex((existing) => existing.id === message.id);
        if (index >= 0) {
            messages.value.splice(index, 1, message);
        } else {
            messages.value.push(message);
        }
    };

    const loadConversation = async () => {
        const otherId = options.otherUserId();
        if (!otherId || loadedFor === String(otherId)) return;
        loadedFor = String(otherId);

        try {
            const response = await axios.get<{ success: boolean; data: ApiMessage[] }>(`/api/messages/${otherId}`, {
                params: { limit: 50 },
            });
            if (response.data.success) {
                messages.value = response.data.data.map(toMessage);
                void axios.post(`/api/messages/${otherId}/read`).catch(() => {});
            }
        } catch {
            // Histórico indisponível não bloqueia o chat ao vivo
        }
    };

    const subscribe = () => {
        const echoInstance = echo();
        if (!echoInstance || channelName) return;

        channelName = `messages.${myUserId()}`;
        echoInstance.private(channelName).listen('.message.sent', (payload: ApiMessage) => {
            const otherId = options.otherUserId();
            if (!otherId || String(payload.sender_id) !== String(otherId)) return;

            upsert(toMessage(payload));

            if (readReceiptTimer) clearTimeout(readReceiptTimer);
            readReceiptTimer = setTimeout(() => {
                void axios.post(`/api/messages/${otherId}/read`).catch(() => {});
                readReceiptTimer = null;
            }, 1000);
        });
    };

    const unsubscribe = () => {
        if (!channelName) return;
        if (readReceiptTimer) {
            clearTimeout(readReceiptTimer);
            readReceiptTimer = null;
        }
        const echoInstance = echo();
        echoInstance?.private(channelName).stopListening('.message.sent');
        echoInstance?.leave(channelName);
        channelName = null;
    };

    const send = async (text: string) => {
        const otherId = options.otherUserId();
        const content = text.trim();
        if (!otherId || !content) return;

        const tempId = `temp-${Date.now()}`;
        messages.value.push({
            id: tempId,
            type: 'me',
            author: 'Você',
            text: content,
            time: formatTime(new Date().toISOString()),
            pending: true,
        });

        try {
            const response = await axios.post<{ success: boolean; data: ApiMessage }>('/api/messages', {
                receiver_id: otherId,
                content,
                appointment_id: options.appointmentId() ?? undefined,
            });

            const index = messages.value.findIndex((message) => message.id === tempId);
            if (index >= 0 && response.data.success) {
                messages.value.splice(index, 1, toMessage(response.data.data));
            }
        } catch {
            const failed = messages.value.find((message) => message.id === tempId);
            if (failed) {
                failed.pending = false;
                failed.failed = true;
                failed.text = `${content} (falha no envio)`;
            }
        }
    };

    watch(
        () => options.isInCall(),
        (active) => {
            if (active) {
                void loadConversation();
                subscribe();
            } else {
                unsubscribe();
            }
        },
        { immediate: true },
    );

    onUnmounted(unsubscribe);

    return { messages, send };
}
