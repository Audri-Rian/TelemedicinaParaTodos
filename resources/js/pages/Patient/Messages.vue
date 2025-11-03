<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import * as patientRoutes from '@/routes/patient';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { Search, MessageCircle, Send } from 'lucide-vue-next';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { useInitials } from '@/composables/useInitials';
import { ref, onMounted } from 'vue';
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

// Dados mock das conversas
const conversations = ref([
    {
        id: 1,
        doctorName: 'Dr. Carlos Mendes',
        doctorAvatar: 'https://images.unsplash.com/photo-1612349317150-e413f6a5b16d?w=150&h=150&fit=crop&crop=face',
        lastMessage: 'Olá! Posso agendar sua consulta para amanhã às 14h?',
        time: '10:30',
        unread: 2,
    },
    {
        id: 2,
        doctorName: 'Dra. Ana Oliveira',
        doctorAvatar: 'https://images.unsplash.com/photo-1594824476969-48dfc0d13b41?w=150&h=150&fit=crop&crop=face',
        lastMessage: 'Obrigado pela consulta! Até a próxima.',
        time: '09:15',
        unread: 0,
    },
    {
        id: 3,
        doctorName: 'Dr. Pedro Santos',
        doctorAvatar: 'https://images.unsplash.com/photo-1582750433449-648ed127bb54?w=150&h=150&fit=crop&crop=face',
        lastMessage: 'Você tem alguma dúvida sobre o tratamento?',
        time: 'Ontem',
        unread: 1,
    },
]);

// Estado da conversa selecionada
const selectedConversation = ref<typeof conversations.value[0] | null>(null);
const searchQuery = ref('');

// Mensagens mock da conversa selecionada
const messages = ref([
    {
        id: 1,
        sender: 'patient',
        text: 'Olá doutor, gostaria de agendar uma consulta.',
        time: '10:25',
    },
    {
        id: 2,
        sender: 'doctor',
        text: 'Olá! Claro, posso agendar. Qual dia e horário prefere?',
        time: '10:28',
    },
    {
        id: 3,
        sender: 'patient',
        text: 'Prefiro na próxima semana, de preferência de manhã.',
        time: '10:30',
    },
]);

const { getInitials } = useInitials();

const newMessage = ref('');

const sendMessage = () => {
    if (!newMessage.value.trim() || !selectedConversation.value) return;
    
    messages.value.push({
        id: messages.value.length + 1,
        sender: 'patient',
        text: newMessage.value,
        time: new Date().toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' }),
    });
    
    newMessage.value = '';
};

const selectConversation = (conversation: typeof conversations.value[0]) => {
    selectedConversation.value = conversation;
    // Resetar mensagens não lidas
    conversation.unread = 0;
};

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
                        <div
                            v-for="conversation in conversations"
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
                                <AvatarImage :src="conversation.doctorAvatar" :alt="conversation.doctorName" />
                                <AvatarFallback class="bg-gray-200 text-gray-600" :delay-ms="600">
                                    {{ getInitials(conversation.doctorName) }}
                                </AvatarFallback>
                            </Avatar>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between mb-1">
                                    <h3 
                                        class="font-semibold text-gray-900 truncate cursor-pointer hover:text-primary transition-colors"
                                        @click.stop="goToDoctorProfile(conversation)"
                                    >
                                        {{ conversation.doctorName }}
                                    </h3>
                                    <span class="text-xs text-gray-500 flex-shrink-0 ml-2">
                                        {{ conversation.time }}
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
                                <AvatarImage :src="selectedConversation.doctorAvatar" :alt="selectedConversation.doctorName" />
                                <AvatarFallback class="bg-gray-200 text-gray-600" :delay-ms="600">
                                    {{ getInitials(selectedConversation.doctorName) }}
                                </AvatarFallback>
                            </Avatar>
                            <div class="flex-1">
                                <h3 
                                    class="font-semibold text-gray-900 cursor-pointer hover:text-primary transition-colors"
                                    @click="goToDoctorProfile(selectedConversation)"
                                >
                                    {{ selectedConversation.doctorName }}
                                </h3>
                                <p class="text-sm text-gray-500">Online</p>
                            </div>
                        </div>

                        <!-- Área de mensagens -->
                        <div class="flex-1 overflow-y-auto p-4 space-y-4 bg-gray-50">
                            <div
                                v-for="message in messages"
                                :key="message.id"
                                :class="[
                                    'flex',
                                    message.sender === 'patient' ? 'justify-end' : 'justify-start'
                                ]"
                            >
                                <div
                                    :class="[
                                        'max-w-xs lg:max-w-md px-4 py-2 rounded-lg',
                                        message.sender === 'patient'
                                            ? 'bg-primary text-gray-900'
                                            : 'bg-white text-gray-900 border border-gray-200'
                                    ]"
                                >
                                    <p class="text-sm">{{ message.text }}</p>
                                    <p :class="[
                                        'text-xs mt-1',
                                        message.sender === 'patient' ? 'text-gray-700' : 'text-gray-500'
                                    ]">
                                        {{ message.time }}
                                    </p>
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
                                    class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                                />
                                <button
                                    @click="sendMessage"
                                    :disabled="!newMessage.trim()"
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

