<script setup lang="ts">
import { computed, ref, watch, nextTick, onMounted, onUnmounted } from 'vue';
import { Search, Send, X, MessageCircle } from 'lucide-vue-next';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { useInitials } from '@/composables/useInitials';
import { useAuth } from '@/composables/auth';

interface Conversation {
    id: number;
    name: string;
    avatar: string;
    lastMessage: string;
    time: string;
    unread: number;
}

interface Message {
    id: number;
    sender: 'me' | 'other';
    text: string;
    time: string;
}

interface Props {
    isOpen: boolean;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    'update:isOpen': [value: boolean];
    'close': [];
}>();

const { isDoctor, isPatient } = useAuth();
const { getInitials } = useInitials();

// Dados mock das conversas - adaptar baseado no role
const conversations = ref<Conversation[]>([
    {
        id: 1,
        name: isDoctor.value ? 'Sofia Almeida' : 'Dr. Carlos Mendes',
        avatar: isDoctor.value 
            ? 'https://images.unsplash.com/photo-1494790108755-2616b612b786?w=150&h=150&fit=crop&crop=face'
            : 'https://images.unsplash.com/photo-1612349317150-e413f6a5b16d?w=150&h=150&fit=crop&crop=face',
        lastMessage: isDoctor.value 
            ? 'Olá doutor, gostaria de agendar uma consulta.'
            : 'Olá! Posso agendar sua consulta para amanhã às 14h?',
        time: '10:30',
        unread: 2,
    },
    {
        id: 2,
        name: isDoctor.value ? 'Carlos Pereira' : 'Dra. Ana Oliveira',
        avatar: isDoctor.value 
            ? 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=150&h=150&fit=crop&crop=face'
            : 'https://images.unsplash.com/photo-1594824476969-48dfc0d13b41?w=150&h=150&fit=crop&crop=face',
        lastMessage: isDoctor.value 
            ? 'Obrigado pela consulta de hoje!'
            : 'Obrigado pela consulta! Até a próxima.',
        time: '09:15',
        unread: 0,
    },
    {
        id: 3,
        name: isDoctor.value ? 'Ana Costa' : 'Dr. Pedro Santos',
        avatar: isDoctor.value 
            ? 'https://images.unsplash.com/photo-1438761681033-6461ffad8d80?w=150&h=150&fit=crop&crop=face'
            : 'https://images.unsplash.com/photo-1582750433449-648ed127bb54?w=150&h=150&fit=crop&crop=face',
        lastMessage: isDoctor.value 
            ? 'Posso tirar uma dúvida sobre a receita?'
            : 'Você tem alguma dúvida sobre o tratamento?',
        time: 'Ontem',
        unread: 1,
    },
]);

const selectedConversation = ref<Conversation | null>(null);
const searchQuery = ref('');
const newMessage = ref('');

// Mensagens mock da conversa selecionada
const messages = ref<Message[]>([]);

const filteredConversations = computed(() => {
    if (!searchQuery.value.trim()) {
        return conversations.value;
    }
    const query = searchQuery.value.toLowerCase();
    return conversations.value.filter(conv => 
        conv.name.toLowerCase().includes(query) ||
        conv.lastMessage.toLowerCase().includes(query)
    );
});

const selectConversation = (conversation: Conversation) => {
    selectedConversation.value = conversation;
    conversation.unread = 0;
    
    // Carregar mensagens mock
    messages.value = [
        {
            id: 1,
            sender: 'other',
            text: conversation.lastMessage,
            time: conversation.time,
        },
        {
            id: 2,
            sender: 'me',
            text: isDoctor.value 
                ? 'Olá! Como posso ajudar?'
                : 'Obrigado pela resposta!',
            time: '10:32',
        },
    ];
    
    // Scroll para baixo
    nextTick(() => {
        const messagesContainer = document.getElementById('chat-messages');
        if (messagesContainer) {
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }
    });
};

const sendMessage = () => {
    if (!newMessage.value.trim() || !selectedConversation.value) return;
    
    messages.value.push({
        id: messages.value.length + 1,
        sender: 'me',
        text: newMessage.value,
        time: new Date().toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' }),
    });
    
    newMessage.value = '';
    
    // Scroll para baixo
    nextTick(() => {
        const messagesContainer = document.getElementById('chat-messages');
        if (messagesContainer) {
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }
    });
};

const closeModal = () => {
    emit('update:isOpen', false);
    emit('close');
    selectedConversation.value = null;
    messages.value = [];
    newMessage.value = '';
};

// Fechar ao clicar fora
const handleBackdropClick = (event: MouseEvent) => {
    const target = event.target as HTMLElement;
    if (target.classList.contains('chat-modal-backdrop')) {
        closeModal();
    }
};

watch(() => props.isOpen, (newValue) => {
    if (newValue) {
        document.addEventListener('click', handleBackdropClick);
    } else {
        document.removeEventListener('click', handleBackdropClick);
    }
});

onMounted(() => {
    if (props.isOpen) {
        document.addEventListener('click', handleBackdropClick);
    }
});

onUnmounted(() => {
    document.removeEventListener('click', handleBackdropClick);
});
</script>

<template>
    <Transition
        enter-active-class="transition-all duration-300 ease-out"
        enter-from-class="opacity-0 scale-95 translate-y-2"
        enter-to-class="opacity-100 scale-100 translate-y-0"
        leave-active-class="transition-all duration-200 ease-in"
        leave-from-class="opacity-100 scale-100 translate-y-0"
        leave-to-class="opacity-0 scale-95 translate-y-2"
    >
        <div
            v-if="isOpen"
            class="chat-modal-backdrop fixed inset-0 z-40"
            @click="handleBackdropClick"
        >
            <div
                class="fixed bottom-24 right-6 z-50 w-96 max-w-[calc(100vw-3rem)] h-[600px] max-h-[calc(100vh-8rem)] bg-white rounded-lg shadow-2xl flex flex-col overflow-hidden border border-gray-200 md:w-96"
                @click.stop
            >
                <!-- Header -->
                <div class="flex items-center justify-between p-4 border-b border-gray-200 bg-gray-50">
                    <div class="flex items-center gap-2">
                        <MessageCircle class="h-5 w-5 text-primary" />
                        <h3 class="font-semibold text-gray-900">Mensagens</h3>
                    </div>
                    <button
                        @click="closeModal"
                        class="p-1 rounded-lg hover:bg-gray-200 transition-colors"
                        aria-label="Fechar chat"
                    >
                        <X class="h-5 w-5 text-gray-600" />
                    </button>
                </div>

                <!-- Conteúdo -->
                <div class="flex-1 flex overflow-hidden">
                    <!-- Lista de conversas -->
                    <div v-if="!selectedConversation" class="flex-1 flex flex-col w-full">
                        <!-- Busca -->
                        <div class="p-3 border-b border-gray-200">
                            <div class="relative">
                                <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-4 h-4" />
                                <input
                                    v-model="searchQuery"
                                    type="text"
                                    placeholder="Buscar conversas..."
                                    class="w-full pl-10 pr-4 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                                />
                            </div>
                        </div>

                        <!-- Lista -->
                        <div class="flex-1 overflow-y-auto">
                            <div
                                v-for="conversation in filteredConversations"
                                :key="conversation.id"
                                @click="selectConversation(conversation)"
                                class="flex items-center gap-3 p-3 cursor-pointer hover:bg-gray-50 transition-colors border-b border-gray-100"
                            >
                                <Avatar class="h-10 w-10 flex-shrink-0">
                                    <AvatarImage :src="conversation.avatar" :alt="conversation.name" />
                                    <AvatarFallback class="bg-gray-200 text-gray-600">
                                        {{ getInitials(conversation.name) }}
                                    </AvatarFallback>
                                </Avatar>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between mb-1">
                                        <h4 class="text-sm font-semibold text-gray-900 truncate">
                                            {{ conversation.name }}
                                        </h4>
                                        <span class="text-xs text-gray-500 flex-shrink-0 ml-2">
                                            {{ conversation.time }}
                                        </span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <p class="text-xs text-gray-600 truncate">
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
                    <div v-else class="flex-1 flex flex-col w-full">
                        <!-- Header da conversa -->
                        <div class="p-3 border-b border-gray-200 flex items-center gap-2 bg-gray-50">
                            <button
                                @click="selectedConversation = null"
                                class="p-1 rounded-lg hover:bg-gray-200 transition-colors mr-2"
                            >
                                <X class="h-4 w-4 text-gray-600" />
                            </button>
                            <Avatar class="h-8 w-8">
                                <AvatarImage 
                                    :src="selectedConversation.avatar" 
                                    :alt="selectedConversation.name" 
                                />
                                <AvatarFallback class="bg-gray-200 text-gray-600">
                                    {{ getInitials(selectedConversation.name) }}
                                </AvatarFallback>
                            </Avatar>
                            <div class="flex-1 min-w-0">
                                <h4 class="text-sm font-semibold text-gray-900 truncate">
                                    {{ selectedConversation.name }}
                                </h4>
                                <p class="text-xs text-gray-500">Online</p>
                            </div>
                        </div>

                        <!-- Mensagens -->
                        <div
                            id="chat-messages"
                            class="flex-1 overflow-y-auto p-4 space-y-3 bg-gray-50"
                        >
                            <div
                                v-for="message in messages"
                                :key="message.id"
                                :class="[
                                    'flex',
                                    message.sender === 'me' ? 'justify-end' : 'justify-start'
                                ]"
                            >
                                <div
                                    :class="[
                                        'max-w-[75%] px-3 py-2 rounded-lg text-sm',
                                        message.sender === 'me'
                                            ? 'bg-primary text-gray-900'
                                            : 'bg-white text-gray-900 border border-gray-200'
                                    ]"
                                >
                                    <p>{{ message.text }}</p>
                                    <p
                                        :class="[
                                            'text-xs mt-1',
                                            message.sender === 'me' ? 'text-gray-700' : 'text-gray-500'
                                        ]"
                                    >
                                        {{ message.time }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Input -->
                        <div class="p-3 border-t border-gray-200 bg-white">
                            <div class="flex items-center gap-2">
                                <input
                                    v-model="newMessage"
                                    @keyup.enter="sendMessage"
                                    type="text"
                                    placeholder="Digite uma mensagem..."
                                    class="flex-1 px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                                />
                                <button
                                    @click="sendMessage"
                                    :disabled="!newMessage.trim()"
                                    class="p-2 bg-primary text-gray-900 rounded-lg hover:bg-primary/90 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    <Send class="w-4 h-4" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </Transition>
</template>

<style scoped>
.chat-modal-backdrop {
    background-color: transparent;
}
</style>




