import { ref, onMounted, onUnmounted } from 'vue';
import { usePage } from '@inertiajs/vue3';
import axios from 'axios';
import Peer from 'peerjs';
import Echo from 'laravel-echo';

interface User {
    id: number;
    name: string;
    email: string;
    peerId?: string;
    fromUser?: User;
    hasAppointment?: boolean;
    canStartCall?: boolean;
    appointment?: any;
    timeWindowMessage?: string | null;
}

interface AuthUser {
    user: User;
}

interface VideoCallEvent {
    user: {
        id: number;
        peerId: string;
        fromUser: User;
    };
}

interface UseVideoCallOptions {
    /**
     * Prefixo da rota base (ex: '/patient' ou '/doctor')
     * Se não fornecido, será detectado automaticamente baseado no tipo de usuário
     */
    routePrefix?: string;
    /**
     * Callback chamado quando uma chamada é iniciada
     */
    onCallStart?: () => void | Promise<void>;
    /**
     * Callback chamado quando uma chamada é encerrada
     */
    onCallEnd?: () => void | Promise<void>;
    /**
     * Callback chamado quando uma chamada é recebida
     */
    onCallReceived?: (user: User) => void | Promise<void>;
}

/**
 * Composable para gerenciar videochamadas usando PeerJS e Laravel Reverb
 * 
 * @param options Opções de configuração do composable
 * @returns Objeto com funções e estados relacionados a videochamadas
 * 
 * @example
 * ```vue
 * <script setup lang="ts">
 * import { useVideoCall } from '@/composables/useVideoCall';
 * 
 * const {
 *   peer,
 *   isCalling,
 *   localVideoRef,
 *   remoteVideoRef,
 *   callUser,
 *   endCall,
 *   initialize,
 *   cleanup
 * } = useVideoCall();
 * </script>
 * ```
 */
export function useVideoCall(options: UseVideoCallOptions = {}) {
    const page = usePage();
    const auth = page.props.auth as unknown as AuthUser;

    // Estados reativos
    const peer = ref<Peer | null>(null);
    const peerCall = ref<any>(null);
    const isCalling = ref(false);
    const selectedUser = ref<User | null>(null);
    const hasRemoteStream = ref(false);

    // Refs para elementos de vídeo
    const remoteVideoRef = ref<HTMLVideoElement | null>(null);
    const localVideoRef = ref<HTMLVideoElement | null>(null);
    const localStreamRef = ref<MediaStream | null>(null);

    // Instância do Echo para cleanup
    let echoInstance: Echo | null = null;

    /**
     * Determina o prefixo da rota baseado no tipo de usuário
     */
    const getRoutePrefix = (): string => {
        if (options.routePrefix) {
            return options.routePrefix;
        }

        // Detectar automaticamente baseado no tipo de usuário
        const isDoctor = (page.props.auth as any)?.isDoctor ?? false;
        return isDoctor ? '/doctor' : '/patient';
    };

    /**
     * Exibe o vídeo local
     */
    const displayLocalVideo = async (): Promise<void> => {
        try {
            const stream = await navigator.mediaDevices.getUserMedia({
                video: true,
                audio: true,
            });

            if (localVideoRef.value) {
                localVideoRef.value.srcObject = stream;
            }

            localStreamRef.value = stream;
        } catch (error: any) {
            console.error('Erro ao acessar dispositivos de mídia:', error);
            throw error;
        }
    };

    /**
     * Encerra a chamada e limpa os recursos
     */
    const endCall = async () => {
        if (peerCall.value) {
            peerCall.value.close();
            peerCall.value = null;
        }

        if (localStreamRef.value) {
            localStreamRef.value.getTracks().forEach((track) => {
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
        hasRemoteStream.value = false;

        // Chamar callback se fornecido
        if (options.onCallEnd) {
            await options.onCallEnd();
        }
    };

    /**
     * Inicia uma chamada com um usuário
     */
    const callUser = async (user: User) => {
        if (!user || !peer.value || !peer.value.id) {
            return;
        }

        try {
            const payload = {
                peerId: peer.value.id,
            };

            const baseRoute = getRoutePrefix();
            await axios.post(`${baseRoute}/video-call/request/${user.id}`, payload);

            isCalling.value = true;
            selectedUser.value = user;

            // Chamar callback se fornecido
            if (options.onCallStart) {
                await options.onCallStart();
            }

            // Aguardar o stream local estar pronto
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
                        hasRemoteStream.value = true;
                    }
                });

                // Destinatário encerrou a chamada
                call.on('close', () => {
                    endCall();
                });
            });
        } catch (error: any) {
            console.error('Erro ao iniciar chamada:', error);
        }
    };

    /**
     * Quando o destinatário aceita a chamada
     */
    const recipientAcceptCall = async (e: VideoCallEvent) => {
        if (!peer.value) {
            return;
        }

        try {
            // Primeiro, obter o stream local
            await displayLocalVideo();

            // Enviar sinal que o destinatário aceitou a chamada
            const statusPayload = {
                peerId: peer.value.id,
                status: 'accept',
            };

            const baseRoute = getRoutePrefix();
            await axios.post(`${baseRoute}/video-call/request/status/${e.user.fromUser.id}`, statusPayload);

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
                            hasRemoteStream.value = true;
                        }
                    });

                    // Chamador encerrou a chamada
                    call.on('close', () => {
                        endCall();
                    });
                }
            });
        } catch (error: any) {
            console.error('Erro ao aceitar chamada:', error);
        }
    };

    /**
     * Cria conexão quando o status é aceito
     */
    const createConnection = (e: VideoCallEvent) => {
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
                    hasRemoteStream.value = true;
                }
            });

            // Receptor encerrou a chamada
            call.on('close', () => {
                endCall();
            });
        } catch (error) {
            console.error('Erro ao criar conexão:', error);
        }
    };

    /**
     * Conecta ao WebSocket usando Laravel Echo
     */
    const connectWebSocket = () => {
        try {
            const reverbConfig = (page.props as any)?.reverb;

            if (!reverbConfig) {
                console.warn('Reverb não configurado. Adicione os dados no middleware HandleInertiaRequests.');
                return;
            }

            echoInstance = new Echo({
                broadcaster: 'reverb',
                key: reverbConfig.key,
                wsHost: reverbConfig.host,
                wsPort: reverbConfig.port,
                wssPort: reverbConfig.port,
                forceTLS: reverbConfig.scheme === 'https',
                enabledTransports: ['ws', 'wss'],
            });

            // Requisição de videoconferência
            echoInstance
                .private(`video-call.${auth.user.id}`)
                .listen('RequestVideoCall', (e: VideoCallEvent) => {
                    selectedUser.value = e.user.fromUser;
                    isCalling.value = true;

                    // Chamar callback se fornecido
                    if (options.onCallReceived) {
                        options.onCallReceived(e.user.fromUser);
                    }

                    recipientAcceptCall(e);
                });

            // Status da chamada aceito
            echoInstance
                .private(`video-call.${auth.user.id}`)
                .listen('RequestVideoCallStatus', (e: VideoCallEvent) => {
                    createConnection(e);
                });
        } catch (error) {
            console.error('Erro ao conectar WebSocket:', error);
        }
    };

    /**
     * Inicializa o PeerJS e conecta ao WebSocket
     */
    const initialize = () => {
        // Inicializar PeerJS
        peer.value = new Peer();

        peer.value.on('open', () => {
            // Conectar WebSocket após PeerJS estar pronto
            connectWebSocket();
        });

        peer.value.on('error', (error) => {
            console.error('Erro no PeerJS:', error);
        });
    };

    /**
     * Limpa recursos ao desmontar
     */
    const cleanup = () => {
        if (echoInstance) {
            echoInstance.disconnect();
            echoInstance = null;
        }

        if (localStreamRef.value) {
            localStreamRef.value.getTracks().forEach((track) => {
                track.stop();
            });
        }

        if (peerCall.value) {
            peerCall.value.close();
        }

        if (peer.value) {
            peer.value.destroy();
        }
    };

    return {
        // Estados
        peer,
        peerCall,
        isCalling,
        selectedUser,
        localStreamRef,
        hasRemoteStream,

        // Refs
        remoteVideoRef,
        localVideoRef,

        // Métodos
        callUser,
        endCall,
        displayLocalVideo,
        connectWebSocket,
        initialize,
        cleanup,
    };
}

