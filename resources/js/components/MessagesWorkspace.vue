<script setup lang="ts">
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { useInitials } from '@/composables/useInitials';
import { useMessages, type Message } from '@/composables/useMessages';
import { Link, router, usePage } from '@inertiajs/vue3';
import {
    AlertCircle,
    ArrowLeft,
    Check,
    CheckCheck,
    Clock,
    MessageCircle,
    RefreshCw,
    Search,
    Send,
    ShieldCheck,
    Stethoscope,
    UserRound,
    XCircle,
} from 'lucide-vue-next';
import { computed, nextTick, onMounted, ref } from 'vue';

type Perspective = 'patient' | 'doctor';

interface Conversation {
    id: string;
    name: string;
    avatar: string | null;
    lastMessage: string;
    lastMessageTime: string | null;
    unread: number;
}

const props = defineProps<{
    conversations: Conversation[];
    perspective: Perspective;
    title: string;
    subtitle: string;
    emptyDescription: string;
    primaryHref: string;
    primaryLabel: string;
    contactLabel: string;
    contactMeta: string;
    profileHref?: (conversation: Conversation) => string | null;
}>();

const page = usePage();
const currentUserId = (page.props.auth as any)?.user?.id;
const { getInitials } = useInitials();

const {
    messages,
    isLoading,
    isSending,
    error,
    loadMessages,
    sendMessage: sendMessageApi,
    markAsRead,
    isMyMessage,
    formatMessageTime,
} = useMessages();

const conversations = ref<Conversation[]>(props.conversations.map((conversation) => ({ ...conversation })));
const selectedConversation = ref<Conversation | null>(null);
const searchQuery = ref('');
const newMessage = ref('');

const isDoctor = computed(() => props.perspective === 'doctor');
const theme = computed(() => {
    if (isDoctor.value) {
        return {
            page: 'bg-[#f4f6f8]',
            accent: 'text-[#0f6e78]',
            accentBg: 'bg-[#0f6e78]',
            accentHover: 'hover:bg-[#0a4f57]',
            accentSoft: 'bg-[#e5f1f2]',
            accentBorder: 'border-[#0f6e78]',
            ring: 'focus:ring-[#0f6e78]/20 focus:border-[#0f6e78]',
            bubble: 'bg-[#0f6e78] text-white',
            badgeText: 'text-white',
        };
    }

    return {
        page: 'bg-[#f5f5f0]',
        accent: 'text-teal-700',
        accentBg: 'bg-teal-500',
        accentHover: 'hover:bg-teal-400',
        accentSoft: 'bg-teal-50',
        accentBorder: 'border-teal-500',
        ring: 'focus:ring-teal-600/20 focus:border-teal-600',
        bubble: 'bg-teal-500 text-gray-950',
        badgeText: 'text-gray-950',
    };
});

const filteredConversations = computed(() => {
    const query = searchQuery.value.trim().toLowerCase();

    if (!query) {
        return conversations.value;
    }

    return conversations.value.filter((conversation) => conversation.name.toLowerCase().includes(query));
});

const unreadTotal = computed(() => conversations.value.reduce((total, conversation) => total + Number(conversation.unread || 0), 0));
const activeProfileHref = computed(() => (selectedConversation.value ? props.profileHref?.(selectedConversation.value) : null));

onMounted(async () => {
    if (conversations.value.length > 0) {
        await selectConversation(conversations.value[0]);
    }
});

const selectConversation = async (conversation: Conversation) => {
    selectedConversation.value = conversation;
    await loadMessages(conversation.id);
    await markAsRead(conversation.id);
    conversation.unread = 0;
    await nextTick();
    scrollToBottom();
};

const sendMessage = async () => {
    if (!newMessage.value.trim() || !selectedConversation.value || isSending.value) {
        return;
    }

    const receiverId = selectedConversation.value.id;
    const messageText = newMessage.value.trim();
    const success = await sendMessageApi(receiverId, messageText);

    if (success) {
        const conversation = conversations.value.find((item) => item.id === receiverId);
        if (conversation) {
            conversation.lastMessage = messageText;
            conversation.lastMessageTime = new Date().toISOString();
        }

        newMessage.value = '';
        await nextTick();
        scrollToBottom();
    }
};

const scrollToBottom = () => {
    const messagesContainer = document.getElementById('messages-container');
    if (messagesContainer) {
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }
};

const isMyMsg = (message: Message) => isMyMessage(message, currentUserId);

const visitProfile = (conversation: Conversation, event?: Event) => {
    const href = props.profileHref?.(conversation);

    if (!href) {
        return;
    }

    event?.stopPropagation();
    router.visit(href);
};

const statusLabel = (message: Message) => {
    if (message.status === 'sending') {
        return 'Enviando';
    }

    if (message.status === 'delivered') {
        return 'Entregue';
    }

    if (message.status === 'failed') {
        return 'Falha';
    }

    if (message.read_at) {
        return 'Lido';
    }

    return 'Enviado';
};
</script>

<template>
    <div class="flex min-h-0 flex-1 p-0 text-gray-950" :class="theme.page">
        <div class="flex min-h-0 w-full flex-1 flex-col overflow-hidden rounded-lg border border-[#dfe6e9] bg-white shadow-sm">
            <header class="border-b border-[#e6ebee] px-5 py-4">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <div
                            class="mb-2 inline-flex items-center gap-2 rounded-full border border-[#e6ebee] bg-[#f7f8f9] px-3 py-1 text-xs font-black text-gray-600"
                        >
                            <ShieldCheck class="h-3.5 w-3.5" :class="theme.accent" />
                            Comunicação segura
                        </div>
                        <h1 class="text-3xl font-black text-gray-950">{{ title }}</h1>
                        <p class="mt-1 text-sm font-semibold text-gray-600">{{ subtitle }}</p>
                    </div>

                    <div class="grid grid-cols-2 gap-2 sm:flex sm:items-center">
                        <div class="rounded-lg border border-[#e6ebee] bg-[#f7f8f9] px-4 py-2">
                            <p class="text-[11px] font-black text-gray-500 uppercase">Conversas</p>
                            <p class="text-xl font-black text-gray-950">{{ conversations.length }}</p>
                        </div>
                        <div class="rounded-lg border border-[#e6ebee] px-4 py-2" :class="theme.accentSoft">
                            <p class="text-[11px] font-black text-gray-500 uppercase">Não lidas</p>
                            <p class="text-xl font-black text-gray-950">{{ unreadTotal }}</p>
                        </div>
                        <Button
                            as-child
                            class="col-span-2 h-11 font-black sm:col-span-1"
                            :class="[theme.accentBg, theme.accentHover, isDoctor ? 'text-white' : 'text-gray-950']"
                        >
                            <Link :href="primaryHref">{{ primaryLabel }}</Link>
                        </Button>
                    </div>
                </div>
            </header>

            <div class="grid min-h-0 flex-1 md:grid-cols-[360px_minmax(0,1fr)] xl:grid-cols-[400px_minmax(0,1fr)]">
                <aside class="min-h-0 flex-col border-r border-[#e6ebee] bg-white" :class="selectedConversation ? 'hidden md:flex' : 'flex'">
                    <div class="border-b border-[#e6ebee] p-4">
                        <div class="relative">
                            <Search class="pointer-events-none absolute top-1/2 left-4 h-4 w-4 -translate-y-1/2 text-gray-400" />
                            <input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Buscar conversas..."
                                class="h-11 w-full rounded-lg border border-[#dde5ea] bg-[#f7f8f9] pr-4 pl-11 text-sm font-semibold text-gray-800 outline-none focus:ring-2"
                                :class="theme.ring"
                            />
                        </div>
                    </div>

                    <div class="min-h-0 flex-1 overflow-y-auto">
                        <div v-if="conversations.length === 0" class="flex h-full flex-col items-center justify-center px-6 text-center">
                            <MessageCircle class="h-12 w-12 text-gray-300" />
                            <h2 class="mt-4 text-lg font-black text-gray-950">Nenhuma conversa disponível</h2>
                            <p class="mt-2 text-sm font-medium text-gray-500">{{ emptyDescription }}</p>
                        </div>

                        <div v-else-if="filteredConversations.length === 0" class="px-6 py-12 text-center">
                            <Search class="mx-auto h-10 w-10 text-gray-300" />
                            <p class="mt-3 text-sm font-bold text-gray-500">Nenhuma conversa encontrada.</p>
                        </div>

                        <button
                            v-for="conversation in filteredConversations"
                            v-else
                            :key="conversation.id"
                            type="button"
                            class="flex w-full items-center gap-3 border-b border-[#eef2f4] p-4 text-left transition hover:bg-[#f7f8f9]"
                            :class="selectedConversation?.id === conversation.id ? `${theme.accentSoft} border-l-4 ${theme.accentBorder} pl-3` : ''"
                            @click="selectConversation(conversation)"
                        >
                            <Avatar class="h-12 w-12 shrink-0 border border-[#e6ebee]" @click="visitProfile(conversation, $event)">
                                <AvatarImage v-if="conversation.avatar" :src="conversation.avatar" :alt="conversation.name" />
                                <AvatarFallback class="bg-[#eef6f6] text-sm font-black text-gray-700">
                                    {{ getInitials(conversation.name) }}
                                </AvatarFallback>
                            </Avatar>

                            <div class="min-w-0 flex-1">
                                <div class="mb-1 flex items-center justify-between gap-3">
                                    <h3 class="truncate text-sm font-black text-gray-950">{{ conversation.name }}</h3>
                                    <span class="shrink-0 text-[11px] font-bold text-gray-400">
                                        {{ conversation.lastMessageTime ? formatMessageTime(conversation.lastMessageTime) : '' }}
                                    </span>
                                </div>
                                <div class="flex items-center justify-between gap-2">
                                    <p
                                        class="truncate text-sm"
                                        :class="conversation.unread > 0 ? 'font-extrabold text-gray-950' : 'font-semibold text-gray-500'"
                                    >
                                        {{ conversation.lastMessage || 'Sem mensagens ainda' }}
                                    </p>
                                    <span
                                        v-if="conversation.unread > 0"
                                        class="grid h-5 min-w-5 shrink-0 place-items-center rounded-full px-1.5 text-[11px] font-black"
                                        :class="[theme.accentBg, theme.badgeText]"
                                    >
                                        {{ conversation.unread }}
                                    </span>
                                </div>
                            </div>
                        </button>
                    </div>
                </aside>

                <main class="min-h-0 flex-col bg-[#f7f8f9]" :class="selectedConversation ? 'flex' : 'hidden md:flex'">
                    <div v-if="!selectedConversation" class="flex flex-1 items-center justify-center px-6 text-center">
                        <div>
                            <MessageCircle class="mx-auto h-16 w-16 text-gray-300" />
                            <h2 class="mt-4 text-xl font-black text-gray-950">Selecione uma conversa</h2>
                            <p class="mt-2 text-sm font-medium text-gray-500">Escolha um contato para abrir o histórico de mensagens.</p>
                        </div>
                    </div>

                    <template v-else>
                        <header class="flex items-center gap-3 border-b border-[#e6ebee] bg-white px-4 py-3">
                            <button
                                type="button"
                                class="grid h-9 w-9 place-items-center rounded-lg border border-[#dde5ea] bg-white text-gray-600 md:hidden"
                                @click="selectedConversation = null"
                            >
                                <ArrowLeft class="h-4 w-4" />
                            </button>

                            <Avatar class="h-11 w-11 border border-[#e6ebee]" @click="visitProfile(selectedConversation, $event)">
                                <AvatarImage v-if="selectedConversation.avatar" :src="selectedConversation.avatar" :alt="selectedConversation.name" />
                                <AvatarFallback class="bg-[#eef6f6] text-sm font-black text-gray-700">
                                    {{ getInitials(selectedConversation.name) }}
                                </AvatarFallback>
                            </Avatar>

                            <div class="min-w-0 flex-1">
                                <h2 class="truncate text-base font-black text-gray-950">{{ selectedConversation.name }}</h2>
                                <p class="flex items-center gap-1.5 text-xs font-bold text-gray-500">
                                    <span class="h-2 w-2 rounded-full bg-emerald-500" />
                                    {{ contactLabel }} · {{ contactMeta }}
                                </p>
                            </div>

                            <Button
                                v-if="activeProfileHref"
                                as-child
                                variant="outline"
                                class="hidden border-[#dde5ea] bg-white font-extrabold text-gray-700 md:inline-flex"
                            >
                                <Link :href="activeProfileHref">
                                    <UserRound class="mr-2 h-4 w-4" />
                                    Perfil
                                </Link>
                            </Button>
                        </header>

                        <div id="messages-container" class="min-h-0 flex-1 space-y-4 overflow-y-auto px-4 py-5">
                            <div v-if="isLoading" class="space-y-3">
                                <div class="h-16 w-2/3 animate-pulse rounded-2xl bg-[#e8edf0]" />
                                <div class="ml-auto h-16 w-1/2 animate-pulse rounded-2xl bg-[#d8eeee]" />
                                <div class="h-20 w-3/5 animate-pulse rounded-2xl bg-[#e8edf0]" />
                            </div>

                            <div v-else-if="error" class="flex h-full items-center justify-center">
                                <div class="rounded-lg border border-rose-100 bg-white px-6 py-8 text-center shadow-sm">
                                    <AlertCircle class="mx-auto h-10 w-10 text-rose-500" />
                                    <h3 class="mt-3 text-lg font-black text-gray-950">Erro ao carregar mensagens</h3>
                                    <p class="mt-1 text-sm font-semibold text-gray-500">{{ error }}</p>
                                </div>
                            </div>

                            <div v-else-if="messages.length === 0" class="flex h-full items-center justify-center">
                                <div class="max-w-sm text-center">
                                    <MessageCircle class="mx-auto h-12 w-12 text-gray-300" />
                                    <h3 class="mt-4 text-lg font-black text-gray-950">Conversa vazia</h3>
                                    <p class="mt-2 text-sm font-medium text-gray-500">
                                        Envie a primeira mensagem mantendo orientações clínicas claras e objetivas.
                                    </p>
                                </div>
                            </div>

                            <div
                                v-for="message in messages"
                                v-else
                                :key="message.id"
                                class="flex"
                                :class="isMyMsg(message) ? 'justify-end' : 'justify-start'"
                            >
                                <div class="max-w-[78%] space-y-1">
                                    <div
                                        class="rounded-2xl px-4 py-3 shadow-sm"
                                        :class="
                                            isMyMsg(message)
                                                ? `${theme.bubble} rounded-br-md`
                                                : 'rounded-bl-md border border-[#e6ebee] bg-white text-gray-950'
                                        "
                                    >
                                        <p class="text-sm leading-6 font-semibold whitespace-pre-wrap">{{ message.content }}</p>
                                    </div>
                                    <div
                                        class="flex items-center gap-1.5 px-1 text-[11px] font-bold"
                                        :class="isMyMsg(message) ? 'justify-end text-gray-500' : 'text-gray-400'"
                                    >
                                        <span>{{ formatMessageTime(message.created_at) }}</span>
                                        <template v-if="isMyMsg(message)">
                                            <Clock v-if="message.status === 'sending'" class="h-3 w-3" />
                                            <XCircle v-else-if="message.status === 'failed'" class="h-3 w-3 text-rose-500" />
                                            <CheckCheck
                                                v-else-if="message.status === 'delivered' || message.read_at"
                                                class="h-3 w-3"
                                                :class="message.read_at ? theme.accent : ''"
                                            />
                                            <Check v-else class="h-3 w-3" />
                                            <span>{{ statusLabel(message) }}</span>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <footer class="border-t border-[#e6ebee] bg-white p-4">
                            <div
                                class="rounded-lg border border-[#dde5ea] bg-[#f7f8f9] p-2 focus-within:ring-4"
                                :class="isDoctor ? 'focus-within:ring-[#0f6e78]/15' : 'focus-within:ring-teal-500/15'"
                            >
                                <div class="flex items-end gap-2">
                                    <textarea
                                        v-model="newMessage"
                                        rows="1"
                                        maxlength="1000"
                                        :disabled="isSending"
                                        placeholder="Digite uma mensagem..."
                                        class="max-h-28 min-h-10 flex-1 resize-none bg-transparent px-3 py-2 text-sm font-semibold text-gray-900 outline-none placeholder:text-gray-400 disabled:opacity-50"
                                        @keydown.enter.exact.prevent="sendMessage"
                                    />
                                    <Button
                                        type="button"
                                        class="h-10 w-10 shrink-0 p-0"
                                        :class="[theme.accentBg, theme.accentHover, isDoctor ? 'text-white' : 'text-gray-950']"
                                        :disabled="!newMessage.trim() || isSending"
                                        @click="sendMessage"
                                    >
                                        <RefreshCw v-if="isSending" class="h-4 w-4 animate-spin" />
                                        <Send v-else class="h-4 w-4" />
                                    </Button>
                                </div>
                                <div class="mt-1 flex items-center justify-between px-3 text-[11px] font-bold text-gray-400">
                                    <span class="inline-flex items-center gap-1">
                                        <Stethoscope class="h-3 w-3" />
                                        Comunicação vinculada ao cuidado do paciente.
                                    </span>
                                    <span>{{ newMessage.length }}/1000</span>
                                </div>
                            </div>
                        </footer>
                    </template>
                </main>
            </div>
        </div>
    </div>
</template>
