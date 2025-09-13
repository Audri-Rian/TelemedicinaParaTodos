<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted, nextTick } from 'vue';
import axios from 'axios';
import Peer from 'peerjs';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

defineOptions({
    layout: AppLayout,
});

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
    console.log('=== INICIANDO CHAMADA DE VIDEOCONFERÊNCIA ===');
    console.log('Usuário selecionado:', selectedUser.value);
    console.log('PeerJS disponível:', !!peer.value);
    console.log('PeerJS ID:', peer.value?.id);
    
    if (!selectedUser.value || !peer.value || !peer.value.id) {
        console.error('❌ PeerJS não está pronto ou usuário não selecionado');
        console.error('selectedUser:', selectedUser.value);
        console.error('peer:', peer.value);
        console.error('peer.id:', peer.value?.id);
        return;
    }
    
    try {
        const payload = {
            peerId: peer.value.id
        };
        
        console.log('📞 Iniciando chamada para:', selectedUser.value.name);
        console.log('📞 PeerID do chamador:', peer.value.id);
        console.log('📞 Payload enviado:', payload);
        
        const response = await axios.post(`/video-call/request/${selectedUser.value.id}`, payload);
        console.log('✅ Resposta do servidor:', response.data);
        
        isCalling.value = true;
        console.log('🔄 Estado isCalling alterado para:', isCalling.value);
        
        // Aguardar o stream local estar pronto antes de continuar
        console.log('🎥 Obtendo stream local...');
        await displayLocalVideo();
        console.log('✅ Stream local obtido com sucesso');
        
        // Configurar listener para quando o destinatário aceitar
        console.log('👂 Configurando listener para chamadas recebidas...');
        peer.value.on('call', (call) => {
            console.log('📞 Chamada recebida:', call);
            console.log('📞 Peer do chamador:', call.peer);
            peerCall.value = call;
            
            // Responder à chamada com o stream local
            console.log('📞 Respondendo à chamada com stream local...');
            if (localStreamRef.value) {
                call.answer(localStreamRef.value);
            }
            
            // Escutar o stream do destinatário
            call.on('stream', (remoteStream) => {
                console.log('🎥 Stream remoto recebido:', remoteStream);
                if (remoteVideoRef.value) {
                    remoteVideoRef.value.srcObject = remoteStream;
                    console.log('✅ Stream remoto atribuído ao elemento de vídeo');
                } else {
                    console.error('❌ Elemento remoteVideoRef não encontrado');
                }
            });
            
            // Destinatário encerrou a chamada
            call.on('close', () => {
                console.log('📞 Chamada encerrada pelo destinatário');
                endCall();
            });
        });
        
        console.log('=== CHAMADA INICIADA COM SUCESSO ===');
    } catch (error: any) {
        console.error('❌ Erro ao iniciar chamada:', error);
        console.error('❌ Detalhes do erro:', error.response?.data || error.message);
    }
};

// Função para encerrar a chamada
const endCall = () => {
    console.log('=== ENCERRANDO CHAMADA ===');
    
    if (peerCall.value) {
        console.log('📞 Fechando conexão PeerJS...');
        peerCall.value.close();
        peerCall.value = null;
    }
    
    if (localStreamRef.value) {
        console.log('🎥 Parando tracks do stream local...');
        localStreamRef.value.getTracks().forEach(track => {
            console.log('🛑 Parando track:', track.kind);
            track.stop();
        });
        localStreamRef.value = null;
    }
    
    if (localVideoRef.value) {
        console.log('🎥 Limpando vídeo local...');
        localVideoRef.value.srcObject = null;
    }
    
    if (remoteVideoRef.value) {
        console.log('🎥 Limpando vídeo remoto...');
        remoteVideoRef.value.srcObject = null;
    }
    
    isCalling.value = false;
    console.log('🔄 Estado isCalling alterado para:', isCalling.value);
    console.log('=== CHAMADA ENCERRADA ===');
};

// Função para exibir vídeo local
const displayLocalVideo = async (): Promise<void> => {
    console.log('=== OBTENDO STREAM LOCAL ===');
    console.log('🎥 Solicitando acesso aos dispositivos de mídia...');
    
    try {
        const stream = await navigator.mediaDevices.getUserMedia({ 
            video: true, 
            audio: true 
        });
        
        console.log('✅ Stream obtido com sucesso:', stream);
        console.log('📊 Tracks disponíveis:', stream.getTracks().map(track => ({
            kind: track.kind,
            label: track.label,
            enabled: track.enabled,
            readyState: track.readyState
        })));
        
        if (localVideoRef.value) {
            localVideoRef.value.srcObject = stream;
            console.log('✅ Stream atribuído ao elemento de vídeo local');
        } else {
            console.error('❌ Elemento localVideoRef não encontrado');
        }
        
        localStreamRef.value = stream;
        console.log('✅ Stream armazenado em localStreamRef');
        console.log('=== STREAM LOCAL OBTIDO COM SUCESSO ===');
    } catch (error: any) {
        console.error('❌ Erro ao acessar dispositivos de mídia:', error);
        console.error('❌ Nome do erro:', error.name);
        console.error('❌ Mensagem do erro:', error.message);
        throw error;
    }
};

// Função quando o destinatário aceita a chamada
const recipientAcceptCall = async (e: any) => {
    console.log('=== DESTINATÁRIO ACEITANDO CHAMADA ===');
    console.log('📨 Evento recebido:', e);
    console.log('👤 Usuário chamador:', e.user.fromUser);
    console.log('🆔 PeerID do chamador:', e.user.peerId);
    console.log('🆔 Meu PeerID:', peer.value?.id);
    
    if (!peer.value) {
        console.error('❌ PeerJS não está disponível');
        return;
    }
    
    try {
        // Primeiro, obter o stream local
        console.log('🎥 Obtendo stream local do destinatário...');
        await displayLocalVideo();
        
        // Enviar sinal que o destinatário aceitou a chamada
        const statusPayload = { 
            peerId: peer.value.id, 
            status: 'accept' 
        };
        
        console.log('📤 Enviando status de aceitação:', statusPayload);
        const response = await axios.post(`/video-call/request/status/${e.user.fromUser.id}`, statusPayload);
        console.log('✅ Resposta do servidor (status):', response.data);
        
        // Configurar listener para chamadas recebidas
        console.log('👂 Configurando listener para chamadas recebidas...');
        peer.value.on('call', (call) => {
            console.log('📞 Chamada recebida pelo destinatário:', call);
            console.log('📞 Peer do chamador:', call.peer);
            console.log('📞 PeerID esperado:', e.user.peerId);
            
            peerCall.value = call;
            
            // Aceitar chamada se for do usuário correto
            if (e.user.peerId === call.peer) {
                console.log('✅ PeerID confere, aceitando chamada...');
                
                // Responder à chamada com o stream local já obtido
                if (localStreamRef.value) {
                    call.answer(localStreamRef.value);
                }
                console.log('✅ Chamada respondida com stream local');
                
                // Escutar o stream do chamador
                call.on('stream', (remoteStream) => {
                    console.log('🎥 Stream do chamador recebido:', remoteStream);
                    if (remoteVideoRef.value) {
                        remoteVideoRef.value.srcObject = remoteStream;
                        console.log('✅ Stream do chamador atribuído ao vídeo remoto');
                    } else {
                        console.error('❌ Elemento remoteVideoRef não encontrado');
                    }
                });
                
                // Chamador encerrou a chamada
                call.on('close', () => {
                    console.log('📞 Chamada encerrada pelo chamador');
                    endCall();
                });
            } else {
                console.warn('⚠️ PeerID não confere, ignorando chamada');
            }
        });
        
        console.log('=== DESTINATÁRIO CONFIGURADO COM SUCESSO ===');
    } catch (error: any) {
        console.error('❌ Erro ao aceitar chamada:', error);
        console.error('❌ Detalhes do erro:', error.response?.data || error.message);
    }
};

// Função para criar conexão
const createConnection = (e: any) => {
    console.log('=== CRIANDO CONEXÃO PEERJS ===');
    console.log('📨 Evento recebido:', e);
    console.log('👤 Usuário receptor:', e.user);
    console.log('🆔 PeerID do receptor:', e.user.peerId);
    console.log('🆔 Meu PeerID:', peer.value?.id);
    console.log('🎥 Stream local disponível:', !!localStreamRef.value);
    
    if (!peer.value || !localStreamRef.value) {
        console.error('❌ PeerJS ou stream local não disponível');
        console.error('peer.value:', !!peer.value);
        console.error('localStreamRef.value:', !!localStreamRef.value);
        return;
    }
    
    const receiverId = e.user.peerId;
    console.log('📞 Iniciando chamada para PeerID:', receiverId);
    
    try {
        // Iniciar a chamada com o stream local já obtido
        const call = peer.value.call(receiverId, localStreamRef.value);
        console.log('✅ Chamada iniciada:', call);
        peerCall.value = call;
        
        // Escutar o stream do receptor
        call.on('stream', (remoteStream) => {
            console.log('🎥 Stream do receptor recebido:', remoteStream);
            if (remoteVideoRef.value) {
                remoteVideoRef.value.srcObject = remoteStream;
                console.log('✅ Stream do receptor atribuído ao vídeo remoto');
            } else {
                console.error('❌ Elemento remoteVideoRef não encontrado');
            }
        });
        
        // Receptor encerrou a chamada
        call.on('close', () => {
            console.log('📞 Chamada encerrada pelo receptor');
            endCall();
        });
        
        console.log('=== CONEXÃO CRIADA COM SUCESSO ===');
    } catch (error) {
        console.error('❌ Erro ao criar conexão:', error);
    }
};

// Função para conectar WebSocket
const connectWebSocket = () => {
    console.log('=== CONECTANDO WEBSOCKET ===');
    console.log('👤 Usuário autenticado:', auth.user);
    console.log('🆔 ID do usuário:', auth.user.id);
    console.log('📡 Canal privado:', `video-call.${auth.user.id}`);
    
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
        
        console.log('✅ Echo configurado com sucesso');
        console.log('🔧 Configurações do Echo:', {
            broadcaster: 'reverb',
            key: import.meta.env.VITE_REVERB_APP_KEY,
            wsHost: import.meta.env.VITE_REVERB_HOST,
            wsPort: import.meta.env.VITE_REVERB_PORT,
            scheme: import.meta.env.VITE_REVERB_SCHEME
        });
        
        // Requisição de videoconferência
        console.log('👂 Configurando listener para RequestVideoCall...');
        echo.private(`video-call.${auth.user.id}`)
            .listen('RequestVideoCall', (e: any) => {
                console.log('📨 === REQUISIÇÃO DE VIDEOCONFERÊNCIA RECEBIDA ===');
                console.log('📨 Evento completo:', e);
                console.log('👤 Usuário chamador:', e.user.fromUser);
                console.log('🆔 PeerID do chamador:', e.user.peerId);
                
                selectedUser.value = e.user.fromUser;
                isCalling.value = true;
                console.log('🔄 Estados atualizados - selectedUser e isCalling');
                
                recipientAcceptCall(e);
            });
        
        // Status da chamada aceito
        console.log('👂 Configurando listener para RequestVideoCallStatus...');
        echo.private(`video-call.${auth.user.id}`)
            .listen('RequestVideoCallStatus', (e: any) => {
                console.log('📨 === STATUS DE ACEITAÇÃO RECEBIDO ===');
                console.log('📨 Evento completo:', e);
                console.log('👤 Usuário receptor:', e.user);
                console.log('🆔 PeerID do receptor:', e.user.peerId);
                
                createConnection(e);
            });
        
        console.log('✅ WebSocket configurado com sucesso');
        
        // Armazenar instância do Echo para cleanup
        (window as any).echoInstance = echo;
        
    } catch (error) {
        console.error('❌ Erro ao configurar Echo:', error);
    }
    
    console.log('=== WEBSOCKET CONFIGURADO ===');
};

// Lifecycle hooks
onMounted(async () => {
    console.log('=== INICIALIZANDO COMPONENTE ===');
    console.log('👤 Usuário autenticado:', auth.user);
    console.log('👥 Usuários disponíveis:', users);
    
    // Inicializar PeerJS
    console.log('🔧 Inicializando PeerJS...');
    peer.value = new Peer();
    
    peer.value.on('open', (id) => {
        console.log('✅ === PEERJS INICIALIZADO COM SUCESSO ===');
        console.log('🆔 Peer ID gerado:', id);
        console.log('🔧 Conectando WebSocket...');
        
        // Conectar WebSocket após PeerJS estar pronto
        connectWebSocket();
    });
    
    peer.value.on('error', (err) => {
        console.error('❌ === ERRO NO PEERJS ===');
        console.error('❌ Tipo do erro:', err.type);
        console.error('❌ Mensagem do erro:', err.message);
        console.error('❌ Erro completo:', err);
    });
    
    peer.value.on('disconnected', () => {
        console.warn('⚠️ PeerJS desconectado');
    });
    
    peer.value.on('close', () => {
        console.warn('⚠️ PeerJS fechado');
    });
    
    await nextTick();
    console.log('=== COMPONENTE INICIALIZADO ===');
});

onUnmounted(() => {
    console.log('=== DESMONTANDO COMPONENTE ===');
    
    // Limpar recursos
    if (typeof window !== 'undefined' && (window as any).echoInstance) {
        console.log('📡 Desconectando Echo...');
        (window as any).echoInstance.disconnect();
        (window as any).echoInstance = null;
    }
    
    if (localStreamRef.value) {
        console.log('🎥 Parando tracks do stream local...');
        localStreamRef.value.getTracks().forEach(track => {
            console.log('🛑 Parando track:', track.kind);
            track.stop();
        });
    }
    
    if (peerCall.value) {
        console.log('📞 Fechando conexão PeerJS...');
        peerCall.value.close();
    }
    
    console.log('=== COMPONENTE DESMONTADO ===');
});
</script>

<template>
    <Head title="Consultas - Videoconferência Médica" />
    
    <div class="h-screen flex bg-gray-100" style="height: 90vh;">
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
            
            <div v-if="selectedUser">
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
                                class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-blue-600 transition-colors"
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
</template>