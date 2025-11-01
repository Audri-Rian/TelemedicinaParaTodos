<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { type BreadcrumbItem } from '@/types';
import { ref, onMounted, onUnmounted } from 'vue';
import axios from 'axios';
import Peer from 'peerjs';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
import * as patientRoutes from '@/routes/patient';
import { useRouteGuard } from '@/composables/auth';

const { canAccessPatientRoute } = useRouteGuard();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: patientRoutes.dashboard().url,
    },
    {
        title: 'Videoconferência',
        href: patientRoutes.videoCall().url,
    },
];

// Interfaces TypeScript
interface User {
    id: number;
    name: string;
    email: string;
}

interface AuthUser {
    user: User;
}

// Props e dados da página
const page = usePage();
const auth = page.props.auth as AuthUser;
const users = page.props.users as User[] || [];

// Estados reativos
const selectedUser = ref<User | null>(null);
const peer = ref<Peer | null>(null);
const peerCall = ref<any>(null);
const isCalling = ref(false);

// Refs para elementos de vídeo
const remoteVideoRef = ref<HTMLVideoElement | null>(null);
const localVideoRef = ref<HTMLVideoElement | null>(null);
const localStreamRef = ref<MediaStream | null>(null);

// Função para iniciar uma chamada
const callUser = async () => {
    if (!selectedUser.value || !peer.value || !peer.value.id) {
        return;
    }
    
    try {
        const payload = {
            peerId: peer.value.id
        };
        
        await axios.post(`/patient/video-call/request/${selectedUser.value.id}`, payload);
        
        isCalling.value = true;
        
        // Aguardar o stream local estar pronto antes de continuar
        await displayLocalVideo();
        
        // Configurar listener para quando o destinatário aceitar
        peer.value.on('call', (call) => {
            peerCall.value = call;
            
            // Responder à chamada com o stream local
            if (localStreamRef.value) {
                call.answer(localStreamRef.value);
            }
            
            // Escutar o stream do destinatário
            call.on('stream', (remoteStream) => {
                if (remoteVideoRef.value) {
                    remoteVideoRef.value.srcObject = remoteStream;
                }
            });
            
            // Destinatário encerrou a chamada
            call.on('close', () => {
                endCall();
            });
        });
    } catch (error: any) {
        // silencioso
    }
};

// Função para encerrar a chamada
const endCall = () => {
    if (peerCall.value) {
        peerCall.value.close();
        peerCall.value = null;
    }
    
    if (localStreamRef.value) {
        localStreamRef.value.getTracks().forEach(track => {
            track.stop();
        });
        localStreamRef.value = null;
    }
    
    if (localVideoRef.value) {
        localVideoRef.value.srcObject = null;
    }
    
    if (remoteVideoRef.value) {
        remoteVideoRef.value.srcObject = null;
    }
    
    isCalling.value = false;
};

// Função para exibir vídeo local
const displayLocalVideo = async (): Promise<void> => {
    try {
        const stream = await navigator.mediaDevices.getUserMedia({ 
            video: true, 
            audio: true 
        });
        
        if (localVideoRef.value) {
            localVideoRef.value.srcObject = stream;
        }
        
        localStreamRef.value = stream;
    } catch (error: any) {
        throw error;
    }
};

// Função quando o destinatário aceita a chamada
const recipientAcceptCall = async (e: any) => {
    if (!peer.value) {
        return;
    }
    
    try {
        // Primeiro, obter o stream local
        await displayLocalVideo();
        
        // Enviar sinal que o destinatário aceitou a chamada
        const statusPayload = { 
            peerId: peer.value.id, 
            status: 'accept' 
        };
        
        await axios.post(`/patient/video-call/request/status/${e.user.fromUser.id}`, statusPayload);
        
        // Configurar listener para chamadas recebidas
        peer.value.on('call', (call) => {
            peerCall.value = call;
            
            // Aceitar chamada se for do usuário correto
            if (e.user.peerId === call.peer) {
                // Responder à chamada com o stream local já obtido
                if (localStreamRef.value) {
                    call.answer(localStreamRef.value);
                }
                
                // Escutar o stream do chamador
                call.on('stream', (remoteStream) => {
                    if (remoteVideoRef.value) {
                        remoteVideoRef.value.srcObject = remoteStream;
                    }
                });
                
                // Chamador encerrou a chamada
                call.on('close', () => {
                    endCall();
                });
            }
        });
    } catch (error: any) {
        // silencioso
    }
};

// Função para criar conexão
const createConnection = (e: any) => {
    if (!peer.value || !localStreamRef.value) {
        return;
    }
    
    const receiverId = e.user.peerId;
    
    try {
        // Iniciar a chamada com o stream local já obtido
        const call = peer.value.call(receiverId, localStreamRef.value);
        peerCall.value = call;
        
        // Escutar o stream do receptor
        call.on('stream', (remoteStream) => {
            if (remoteVideoRef.value) {
                remoteVideoRef.value.srcObject = remoteStream;
            }
        });
        
        // Receptor encerrou a chamada
        call.on('close', () => {
            endCall();
        });
    } catch (error) {
        // silencioso
    }
};

// Função para conectar WebSocket
const connectWebSocket = () => {
    try {
        // Configurar Echo com Reverb
        const echo = new Echo({
            broadcaster: 'reverb',
            key: import.meta.env.VITE_REVERB_APP_KEY,
            wsHost: import.meta.env.VITE_REVERB_HOST,
            wsPort: import.meta.env.VITE_REVERB_PORT,
            wssPort: import.meta.env.VITE_REVERB_PORT,
            forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
            enabledTransports: ['ws', 'wss'],
        });
        
        // Requisição de videoconferência
        echo.private(`video-call.${auth.user.id}`)
            .listen('RequestVideoCall', (e: any) => {
                selectedUser.value = e.user.fromUser;
                isCalling.value = true;
                
                recipientAcceptCall(e);
            });
        
        // Status da chamada aceito
        echo.private(`video-call.${auth.user.id}`)
            .listen('RequestVideoCallStatus', (e: any) => {
                createConnection(e);
            });
        
        // Armazenar instância do Echo para cleanup
        (window as any).echoInstance = echo;
        
    } catch (error) {
        // silencioso
    }
};

// Lifecycle hooks
onMounted(async () => {
    // Verificar acesso ao montar componente
    canAccessPatientRoute();
    
    // Inicializar PeerJS
    peer.value = new Peer();
    
    peer.value.on('open', () => {
        // Conectar WebSocket após PeerJS estar pronto
        connectWebSocket();
    });
});

onUnmounted(() => {
    // Limpar recursos
    if (typeof window !== 'undefined' && (window as any).echoInstance) {
        (window as any).echoInstance.disconnect();
        (window as any).echoInstance = null;
    }
    
    if (localStreamRef.value) {
        localStreamRef.value.getTracks().forEach(track => {
            track.stop();
        });
    }
    
    if (peerCall.value) {
        peerCall.value.close();
    }
});
</script>

<template>
    <Head title="Videoconferência Médica" />
    
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 overflow-hidden rounded-xl p-6 bg-gray-50" style="height: 90vh;">
            <!-- Header -->
            <div class="flex flex-col gap-1">
                <h1 class="text-3xl font-bold text-gray-900">Videoconferência</h1>
                <p class="text-gray-600">Conecte-se com seus médicos</p>
            </div>

            <div class="flex-1 overflow-hidden bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="h-full flex bg-gray-100">
                    <!-- Sidebar -->
                    <div class="w-1/4 bg-white border-r border-gray-200">
                        <div class="p-4 bg-gray-100 font-bold text-lg border-b border-gray-200">
                            Médicos Disponíveis
                        </div>
                        <div class="p-4 space-y-4">
                            <!-- Lista de Contatos -->
                            <div
                                v-for="user in users"
                                :key="user.id"
                                @click="selectedUser = user"
                                :class="[
                                    'flex items-center p-2 hover:bg-blue-500 hover:text-white rounded cursor-pointer transition-colors',
                                    user.id === selectedUser?.id ? 'bg-primary text-white' : ''
                                ]"
                            >
                                <div class="w-12 h-12 bg-blue-200 rounded-full flex items-center justify-center">
                                    <span class="text-blue-600 font-semibold">
                                        {{ user.name?.charAt(0)?.toUpperCase() || 'U' }}
                                    </span>
                                </div>
                                <div class="ml-4">
                                    <div class="font-semibold">{{ user.name }}</div>
                                    <div class="text-sm text-gray-500">{{ user.email }}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Área de Chamadas -->
                    <div class="flex flex-col w-3/4">
                        <div v-if="!selectedUser" class="h-full flex justify-center items-center text-gray-800 font-bold">
                            Selecione um Médico para Consulta
                        </div>
                        
                        <div v-if="selectedUser" class="h-full flex flex-col">
                            <!-- Cabeçalho do Contato -->
                            <div class="p-4 border-b border-gray-200 flex items-center">
                                <div class="w-12 h-12 bg-blue-200 rounded-full flex items-center justify-center">
                                    <span class="text-blue-600 font-semibold">
                                        {{ selectedUser.name?.charAt(0)?.toUpperCase() || 'U' }}
                                    </span>
                                </div>
                                <div class="ml-4 flex items-center justify-between w-full">
                                    <div class="font-bold">{{ selectedUser.name }}</div>
                                    <div>
                                        <button
                                            v-if="!isCalling"
                                            @click="callUser"
                                            class="px-4 py-2 bg-primary text-gray-900 rounded-lg hover:bg-primary/90 transition-colors"
                                        >
                                            Iniciar Consulta
                                        </button>
                                        <button
                                            v-if="isCalling"
                                            @click="endCall"
                                            class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors"
                                        >
                                            Encerrar Chamada
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Área de Chamada -->
                            <div class="flex-1 overflow-y-auto p-4 space-y-4 bg-gray-50 relative">
                                <div v-if="isCalling" class="relative">
                                    <video 
                                        id="remoteVideo" 
                                        ref="remoteVideoRef" 
                                        autoplay 
                                        playsinline 
                                        muted 
                                        class="border-2 border-gray-800 w-full rounded-lg"
                                    ></video>
                                    <video 
                                        id="localVideo" 
                                        ref="localVideoRef" 
                                        autoplay 
                                        playsinline 
                                        muted 
                                        class="border-2 border-gray-800 absolute top-6 right-6 w-4/12 rounded-lg"
                                        style="margin: 0;"
                                    ></video>
                                </div>
                                
                                <div v-if="!isCalling" class="h-full flex justify-center items-center text-gray-800 font-bold">
                                    Nenhuma Consulta em Andamento.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

