<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import * as patientRoutes from '@/routes/patient';
import { type BreadcrumbItem } from '@/types';
import { Head, router, usePage } from '@inertiajs/vue3';
import { Search, MessageCircle, Send } from 'lucide-vue-next';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { useInitials } from '@/composables/useInitials';
import { useMessages, type Message } from '@/composables/useMessages';
import { ref, onMounted, computed, nextTick } from 'vue';
import { useRouteGuard } from '@/composables/auth';

const { canAccessPatientRoute } = useRouteGuard();

// Verificar acesso ao montar componente
onMounted(() => {
    canAccessPatientRoute();
});

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: patientRoutes.dashboard().url,
    },
    {
        title: 'Mensagens',
        href: patientRoutes.messages().url,
    },
];

// Dados das conversas vindos do backend via Inertia
const page = usePage();
const conversationsData = (page.props.conversations as Array<{
    id: string;
    name: string;
    avatar: string | null;
    lastMessage: string;
    lastMessageTime: string | null;
    unread: number;
}>) || [];

// Usar composable de mensagens para funcionalidades de mensagens
const {
    messages,
    selectedConversationId,
    isLoading,
    isSending,
    error,
    loadMessages,
    sendMessage: sendMessageApi,
    markAsRead,
    isMyMessage,
    formatMessageTime,
} = useMessages();

const currentUserId = (page.props.auth as any)?.user?.id;

// Estado das conversas (vindas do backend)
const conversations = ref(
    conversationsData.map((conv) => ({
        id: conv.id,
        name: conv.name,
        avatar: conv.avatar,
        lastMessage: conv.lastMessage,
        lastMessageTime: conv.lastMessageTime,
        unread: conv.unread,
    }))
);

// Estado da conversa selecionada
const selectedConversation = ref<typeof conversations.value[0] | null>(null);
const searchQuery = ref('');
const newMessage = ref('');

const { getInitials } = useInitials();

// Filtrar conversas baseado na busca
const filteredConversations = computed(() => {
    if (!searchQuery.value.trim()) {
        return conversations.value;
    }
    
    const query = searchQuery.value.toLowerCase();
    return conversations.value.filter(conv => 
        conv.name.toLowerCase().includes(query)
    );
});

// Selecionar conversa
const selectConversation = async (conversation: typeof conversations.value[0]) => {
    selectedConversation.value = conversation;
    await loadMessages(conversation.id);
    await markAsRead(conversation.id);
    
    // Scroll para baixo após carregar mensagens
    await nextTick();
    scrollToBottom();
};

// Enviar mensagem
const sendMessage = async () => {
    if (!newMessage.value.trim() || !selectedConversation.value || isSending.value) return;
    
    const messageText = newMessage.value.trim();
    const receiverId = selectedConversation.value.id;
    
    const success = await sendMessageApi(receiverId, messageText);
    
    if (success) {
        newMessage.value = '';
        // Scroll para baixo após enviar
        await nextTick();
        scrollToBottom();
    }
};

// Scroll para baixo
const scrollToBottom = () => {
    const messagesContainer = document.getElementById('messages-container');
    if (messagesContainer) {
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }
};

// Verificar se mensagem é do usuário atual
const isMyMsg = (message: Message): boolean => {
    return isMyMessage(message, currentUserId);
};

// Navegar para perfil do médico
const goToDoctorProfile = (conversation: typeof conversations.value[0], event?: Event) => {
    if (event) {
        event.stopPropagation();
    }
    const baseUrl = patientRoutes.doctorPerfil().url;
    const url = `${baseUrl}?doctorId=${conversation.id}`;
    router.visit(url);
};
</script>

<template>
    <Head title="Mensagens" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col overflow-hidden">
            <!-- Container principal de mensagens -->
            <div class="flex flex-1 overflow-hidden bg-white">
                <!-- Lista de conversas -->
                <div class="w-1/3 border-r border-gray-200 flex flex-col">
                    <!-- Barra de busca -->
                    <div class="p-4 border-b border-gray-200">
                        <div class="relative">
                            <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-4 h-4" />
                            <input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Buscar conversas..."
                                class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                            />
                        </div>
                    </div>

                    <!-- Lista de conversas -->
                    <div class="flex-1 overflow-y-auto">
                        <div v-if="isLoading && conversations.length === 0" class="p-4 text-center text-gray-500">
                            <p>Carregando conversas...</p>
                        </div>
                        <div v-else-if="conversations.length === 0" class="p-4 text-center text-gray-500">
                            <MessageCircle class="w-12 h-12 mx-auto mb-2 text-gray-300" />
                            <p class="font-medium">Nenhuma conversa disponível</p>
                            <p class="text-sm mt-1">Você precisa ter consultas com médicos para iniciar conversas</p>
                        </div>
                        <div v-else-if="filteredConversations.length === 0" class="p-4 text-center text-gray-500">
                            <p>Nenhuma conversa encontrada</p>
                        </div>
                        <div
                            v-for="conversation in filteredConversations"
                            :key="conversation.id"
                            @click="selectConversation(conversation)"
                            :class="[
                                'flex items-center gap-3 p-4 cursor-pointer border-b border-gray-100 hover:bg-gray-50 transition-colors',
                                selectedConversation?.id === conversation.id ? 'bg-primary/10 border-l-4 border-l-primary' : ''
                            ]"
                        >
                            <Avatar 
                                class="h-12 w-12 flex-shrink-0 cursor-pointer hover:ring-2 hover:ring-primary transition-all"
                                @click.stop="goToDoctorProfile(conversation)"
                            >
                                <AvatarImage v-if="conversation.avatar" :src="conversation.avatar" :alt="conversation.name" />
                                <AvatarFallback class="bg-gray-200 text-gray-600" :delay-ms="600">
                                    {{ getInitials(conversation.name) }}
                                </AvatarFallback>
                            </Avatar>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between mb-1">
                                    <h3 
                                        class="font-semibold text-gray-900 truncate cursor-pointer hover:text-primary transition-colors"
                                        @click.stop="goToDoctorProfile(conversation)"
                                    >
                                        {{ conversation.name }}
                                    </h3>
                                    <span class="text-xs text-gray-500 flex-shrink-0 ml-2">
                                        {{ conversation.lastMessageTime ? formatMessageTime(conversation.lastMessageTime) : '' }}
                                    </span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <p class="text-sm text-gray-600 truncate">
                                        {{ conversation.lastMessage }}
                                    </p>
                                    <span
                                        v-if="conversation.unread > 0"
                                        class="ml-2 flex-shrink-0 bg-primary text-gray-900 text-xs font-semibold rounded-full h-5 w-5 flex items-center justify-center"
                                    >
                                        {{ conversation.unread }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Área de mensagens -->
                <div class="flex-1 flex flex-col">
                    <div v-if="!selectedConversation" class="flex-1 flex items-center justify-center text-gray-500">
                        <div class="text-center">
                            <MessageCircle class="w-16 h-16 mx-auto mb-4 text-gray-300" />
                            <p class="text-lg font-medium">Selecione uma conversa</p>
                            <p class="text-sm">Escolha uma conversa para ver as mensagens</p>
                        </div>
                    </div>

                    <div v-else class="flex flex-col flex-1">
                        <!-- Header da conversa -->
                        <div class="p-4 border-b border-gray-200 flex items-center gap-3">
                            <Avatar 
                                class="h-10 w-10 cursor-pointer hover:ring-2 hover:ring-primary transition-all"
                                @click="goToDoctorProfile(selectedConversation)"
                            >
                                <AvatarImage v-if="selectedConversation.avatar" :src="selectedConversation.avatar" :alt="selectedConversation.name" />
                                <AvatarFallback class="bg-gray-200 text-gray-600" :delay-ms="600">
                                    {{ getInitials(selectedConversation.name) }}
                                </AvatarFallback>
                            </Avatar>
                            <div class="flex-1">
                                <h3 
                                    class="font-semibold text-gray-900 cursor-pointer hover:text-primary transition-colors"
                                    @click="goToDoctorProfile(selectedConversation)"
                                >
                                    {{ selectedConversation.name }}
                                </h3>
                                <p class="text-sm text-gray-500">Online</p>
                            </div>
                        </div>

                        <!-- Área de mensagens -->
                        <div 
                            id="messages-container"
                            class="flex-1 overflow-y-auto p-4 space-y-4 bg-gray-50"
                        >
                            <div v-if="isLoading" class="text-center text-gray-500 py-4">
                                <p>Carregando mensagens...</p>
                            </div>
                            <div v-else-if="error" class="text-center text-red-500 py-4">
                                <p>{{ error }}</p>
                            </div>
                            <div v-else-if="messages.length === 0" class="text-center text-gray-500 py-4">
                                <p>Nenhuma mensagem ainda. Inicie a conversa!</p>
                            </div>
                            <div
                                v-for="message in messages"
                                :key="message.id"
                                :class="[
                                    'flex',
                                    isMyMsg(message) ? 'justify-end' : 'justify-start'
                                ]"
                            >
                                <div
                                    :class="[
                                        'max-w-xs lg:max-w-md px-4 py-2 rounded-lg',
                                        isMyMsg(message)
                                            ? 'bg-primary text-gray-900'
                                            : 'bg-white text-gray-900 border border-gray-200'
                                    ]"
                                >
                                    <p class="text-sm">{{ message.content }}</p>
                                    <div class="flex items-center justify-between mt-1">
                                        <p :class="[
                                            'text-xs',
                                            isMyMsg(message) ? 'text-gray-700' : 'text-gray-500'
                                        ]">
                                            {{ formatMessageTime(message.created_at) }}
                                        </p>
                                        <!-- Indicador de status (apenas para minhas mensagens) -->
                                        <div v-if="isMyMsg(message)" class="ml-2 flex items-center">
                                            <span v-if="message.status === 'sending'" class="text-xs text-gray-400">
                                                Enviando...
                                            </span>
                                            <span v-else-if="message.status === 'sent'" class="text-xs text-gray-400">
                                                ✓
                                            </span>
                                            <span v-else-if="message.status === 'delivered'" class="text-xs text-gray-500">
                                                ✓✓
                                            </span>
                                            <span v-else-if="message.status === 'failed'" class="text-xs text-red-500" title="Falha ao enviar">
                                                ✗
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Input de nova mensagem -->
                        <div class="p-4 border-t border-gray-200 bg-white">
                            <div class="flex items-center gap-2">
                                <input
                                    v-model="newMessage"
                                    @keyup.enter="sendMessage"
                                    type="text"
                                    placeholder="Digite uma mensagem..."
                                    :disabled="isSending"
                                    class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent disabled:opacity-50"
                                />
                                <button
                                    @click="sendMessage"
                                    :disabled="!newMessage.trim() || isSending"
                                    class="p-2 bg-primary text-gray-900 rounded-lg hover:bg-primary/90 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    <Send class="w-5 h-5" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
