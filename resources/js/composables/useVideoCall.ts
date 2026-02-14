import { ref, onMounted, onUnmounted, watch } from 'vue';
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

interface MediaConfig {
    videoResolution: string;
    codec: string;
    stunServer: string;
    turnServer?: string;
}

interface NetworkQuality {
    level: 'excellent' | 'good' | 'fair' | 'poor' | 'none';
    latency: number; // em ms
    bandwidth: number; // em kbps (estimado)
    packetLoss: number; // em porcentagem
}

type CallState = 'idle' | 'ringing_out' | 'ringing_in' | 'connecting' | 'in_call' | 'ending' | 'ended' | 'error';

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
    /**
     * Callback chamado quando a conexão é perdida
     */
    onConnectionLost?: () => void | Promise<void>;
    /**
     * Callback chamado quando a conexão é restaurada
     */
    onConnectionRestored?: () => void | Promise<void>;
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

    // Estados reativos principais
    const peer = ref<Peer | null>(null);
    const peerCall = ref<any>(null);
    const isCalling = ref(false);
    const selectedUser = ref<User | null>(null);
    const hasRemoteStream = ref(false);

    // Estados de conexão avançados
    const isConnected = ref(false);
    const isReceivingCall = ref(false);
    const connectionLost = ref(false);
    const isReconnecting = ref(false);
    const savedRemotePeerId = ref<string>('');
    const showIncomingCallModal = ref(false);
    const incomingCallPeerId = ref<string>('');
    const connectionState = ref<string>('disconnected');
    
    // Estados para melhorar rejeições acidentais
    const lastRejectedPeerId = ref<string>('');
    const rejectionTimestamp = ref<number>(0);
    const canCallBack = ref(false);
    const showRejectionConfirmModal = ref(false);
    const pendingRejectCall = ref<any>(null);
    
    // Estados detalhados da chamada
    const callState = ref<CallState>('idle');
    const callStartTime = ref<number>(0);
    const callDuration = ref<number>(0);
    
    // Qualidade de rede
    const networkQuality = ref<NetworkQuality>({
        level: 'none',
        latency: 0,
        bandwidth: 0,
        packetLoss: 0,
    });
    
    // Timer para duração da chamada
    let callDurationInterval: ReturnType<typeof setInterval> | null = null;
    
    // Timer para monitoramento de qualidade de rede
    let networkQualityInterval: ReturnType<typeof setInterval> | null = null;

    // Refs para elementos de vídeo
    const remoteVideoRef = ref<HTMLVideoElement | null>(null);
    const localVideoRef = ref<HTMLVideoElement | null>(null);
    const localStreamRef = ref<MediaStream | null>(null);

    // Configurações de mídia adaptáveis
    const mediaConfig = ref<MediaConfig>({
        videoResolution: '1280x720',
        codec: 'VP8',
        stunServer: 'stun:stun.l.google.com:19302',
        turnServer: '',
    });

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
     * Detecta a qualidade da conexão e ajusta configurações de mídia
     */
    const detectConnectionQuality = async (): Promise<MediaStreamConstraints> => {
        // Configuração base adaptável
        const baseConstraints: MediaStreamConstraints = {
            video: {
                width: { ideal: parseInt(mediaConfig.value.videoResolution.split('x')[0]) },
                height: { ideal: parseInt(mediaConfig.value.videoResolution.split('x')[1]) },
                frameRate: { ideal: 30, max: 30 },
            },
            audio: {
                echoCancellation: true,
                noiseSuppression: true,
                autoGainControl: true,
            },
        };

        // Tentar detectar qualidade da conexão via navigator.connection (se disponível)
        const connection = (navigator as any).connection;
        if (connection) {
            const effectiveType = connection.effectiveType;
            
            // Ajustar qualidade baseada na conexão
            if (effectiveType === '2g' || effectiveType === 'slow-2g') {
                mediaConfig.value.videoResolution = '640x480';
                (baseConstraints.video as any).width = { ideal: 640 };
                (baseConstraints.video as any).height = { ideal: 480 };
                (baseConstraints.video as any).frameRate = { ideal: 15, max: 15 };
            } else if (effectiveType === '3g') {
                mediaConfig.value.videoResolution = '960x540';
                (baseConstraints.video as any).width = { ideal: 960 };
                (baseConstraints.video as any).height = { ideal: 540 };
                (baseConstraints.video as any).frameRate = { ideal: 24, max: 24 };
            }
        }

        return baseConstraints;
    };

    /**
     * Monitora a qualidade da rede baseado em estatísticas RTC
     */
    const monitorNetworkQuality = async (peerConnection: RTCPeerConnection) => {
        if (!peerConnection) return;

        try {
            const stats = await peerConnection.getStats();
            let totalBytesReceived = 0;
            let totalBytesSent = 0;
            let totalPacketsReceived = 0;
            let totalPacketsLost = 0;
            let rtt = 0;

            stats.forEach((report) => {
                if (report.type === 'inbound-rtp' && report.mediaType === 'video') {
                    totalBytesReceived += report.bytesReceived || 0;
                    totalPacketsReceived += report.packetsReceived || 0;
                    totalPacketsLost += report.packetsLost || 0;
                    rtt = report.roundTripTime ? report.roundTripTime * 1000 : rtt;
                }
                if (report.type === 'outbound-rtp' && report.mediaType === 'video') {
                    totalBytesSent += report.bytesSent || 0;
                }
            });

            // Calcular qualidade
            const packetLoss = totalPacketsReceived > 0 
                ? (totalPacketsLost / (totalPacketsReceived + totalPacketsLost)) * 100 
                : 0;
            
            // Estimar largura de banda (simplificado)
            const bandwidth = totalBytesReceived > 0 ? (totalBytesReceived / 1024) * 8 : 0;

            // Determinar nível de qualidade
            let level: NetworkQuality['level'] = 'none';
            if (rtt > 0) {
                if (rtt < 100 && packetLoss < 1) {
                    level = 'excellent';
                } else if (rtt < 200 && packetLoss < 3) {
                    level = 'good';
                } else if (rtt < 400 && packetLoss < 5) {
                    level = 'fair';
                } else {
                    level = 'poor';
                }
            }

            networkQuality.value = {
                level,
                latency: Math.round(rtt),
                bandwidth: Math.round(bandwidth),
                packetLoss: Math.round(packetLoss * 100) / 100,
            };
        } catch (error) {
            console.error('Erro ao monitorar qualidade de rede:', error);
        }
    };

    /**
     * Inicia monitoramento periódico de qualidade de rede
     */
    const startNetworkQualityMonitoring = (peerConnection: RTCPeerConnection) => {
        if (networkQualityInterval) {
            clearInterval(networkQualityInterval);
        }

        networkQualityInterval = setInterval(() => {
            if (peerConnection && callState.value === 'in_call') {
                monitorNetworkQuality(peerConnection);
            }
        }, 3000); // A cada 3 segundos
    };

    /**
     * Para o monitoramento de qualidade de rede
     */
    const stopNetworkQualityMonitoring = () => {
        if (networkQualityInterval) {
            clearInterval(networkQualityInterval);
            networkQualityInterval = null;
        }
    };

    /**
     * Inicia o timer de duração da chamada
     */
    const startCallTimer = () => {
        callStartTime.value = Date.now();
        if (callDurationInterval) {
            clearInterval(callDurationInterval);
        }
        callDurationInterval = setInterval(() => {
            if (callStartTime.value > 0) {
                callDuration.value = Math.floor((Date.now() - callStartTime.value) / 1000);
            }
        }, 1000);
    };

    /**
     * Para o timer de duração da chamada
     */
    const stopCallTimer = () => {
        if (callDurationInterval) {
            clearInterval(callDurationInterval);
            callDurationInterval = null;
        }
        callDuration.value = 0;
        callStartTime.value = 0;
    };

    /**
     * Formata duração da chamada em formato MM:SS
     */
    const formatCallDuration = (seconds: number): string => {
        const mins = Math.floor(seconds / 60);
        const secs = seconds % 60;
        return `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
    };

    /**
     * Monitora o estado da conexão ICE
     * Baseado na lógica do VideoTest.vue para garantir reconexão correta em ambos os lados
     */
    const monitorIceConnectionState = (peerConnection: RTCPeerConnection) => {
        if (!peerConnection) return;

        const checkState = () => {
            const state = peerConnection.iceConnectionState;

            // ESTE peer caiu - apenas ele deve reconectar
            if (state === 'failed' || state === 'disconnected') {
                if (!connectionLost.value && isCalling.value) {
                    connectionLost.value = true;
                    hasRemoteStream.value = false;
                    callState.value = 'error';
                    stopNetworkQualityMonitoring();
                    
                    if (options.onConnectionLost) {
                        options.onConnectionLost();
                    }
                }
            }

            // ESTE peer está ok - não precisa reconectar
            if (state === 'connected' || state === 'completed') {
                if (connectionLost.value) {
                    connectionLost.value = false;
                    callState.value = 'in_call';
                    startNetworkQualityMonitoring(peerConnection);
                    
                    if (options.onConnectionRestored) {
                        options.onConnectionRestored();
                    }
                }
            }
        };

        // Verificar estado inicial
        checkState();

        // Monitorar mudanças
        peerConnection.addEventListener('iceconnectionstatechange', checkState);
    };

    /**
     * Exibe o vídeo local com configurações adaptáveis
     */
    const displayLocalVideo = async (): Promise<void> => {
        try {
            const constraints = await detectConnectionQuality();
            const stream = await navigator.mediaDevices.getUserMedia(constraints);

            if (localVideoRef.value) {
                localVideoRef.value.srcObject = stream;
            }

            localStreamRef.value = stream;
        } catch (error: any) {
            // Fallback para configurações mais básicas
            try {
                const fallbackStream = await navigator.mediaDevices.getUserMedia({
                    video: { width: 640, height: 480 },
                    audio: true,
                });

                if (localVideoRef.value) {
                    localVideoRef.value.srcObject = fallbackStream;
                }

                localStreamRef.value = fallbackStream;
            } catch (fallbackError: any) {
                console.error('Erro ao acessar dispositivos de mídia:', fallbackError);
                throw fallbackError;
            }
        }
    };

    /**
     * Encerra a chamada e limpa os recursos
     */
    const endCall = async (preservePeerId = false) => {
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
        isReceivingCall.value = false;
        hasRemoteStream.value = false;
        showIncomingCallModal.value = false;
        
        // Parar timers e monitoramentos
        stopCallTimer();
        stopNetworkQualityMonitoring();

        // Se não for perda de conexão, limpar tudo
        if (!preservePeerId) {
            connectionLost.value = false;
            savedRemotePeerId.value = '';
            incomingCallPeerId.value = '';
            callState.value = 'ended';
        } else {
            callState.value = 'error';
        }

        // Chamar callback se fornecido
        if (options.onCallEnd) {
            await options.onCallEnd();
        }
    };

    /**
     * Reconecta automaticamente a chamada
     * Funciona independentemente do lado que iniciou a chamada (baseado em VideoTest.vue)
     */
    const reconnectCall = async () => {
        if (!savedRemotePeerId.value) {
            console.error('Nenhum PeerID salvo para reconexão');
            return;
        }

        if (!peer.value || !isConnected.value) {
            console.error('Peer não está conectado. Reinicie o Peer primeiro.');
            return;
        }

        isReconnecting.value = true;
        connectionLost.value = false;

        try {
            // Limpar chamada anterior
            if (peerCall.value) {
                peerCall.value.close();
                peerCall.value = null;
            }

            // Limpar stream remoto anterior se existir
            if (remoteVideoRef.value?.srcObject) {
                const remoteStream = remoteVideoRef.value.srcObject as MediaStream;
                remoteStream.getTracks().forEach((track) => track.stop());
            }

            if (remoteVideoRef.value) {
                remoteVideoRef.value.srcObject = null;
            }

            // Garantir que temos stream local
            if (!localStreamRef.value) {
                await displayLocalVideo();
            }

            if (!localStreamRef.value) {
                console.error('Não foi possível obter stream local para reconexão');
                isReconnecting.value = false;
                connectionLost.value = true;
                return;
            }

            // Iniciar nova chamada
            const call = peer.value.call(savedRemotePeerId.value, localStreamRef.value);
            peerCall.value = call;
            isCalling.value = true;

            call.on('stream', (stream) => {
                if (remoteVideoRef.value) {
                    remoteVideoRef.value.srcObject = stream;
                }
                hasRemoteStream.value = true;
                isReconnecting.value = false;
                connectionLost.value = false;

                // Garantir que savedRemotePeerId está preservado para futuras reconexões
                if (!savedRemotePeerId.value) {
                    savedRemotePeerId.value = call.peer;
                }

                // Monitorar ICE connection state
                monitorIceConnectionState(call.peerConnection);
            });

            call.on('close', () => {
                // Verificar se connectionLost já foi setado (via ICE ou erro)
                // Se não foi setado, verificar o estado ICE diretamente
                const iceState = call.peerConnection?.iceConnectionState;
                const isConnectionLost = connectionLost.value || iceState === 'disconnected' || iceState === 'failed';
                
                isReconnecting.value = false;
                
                // Preservar estado APENAS se este lado realmente perdeu a conexão
                if (isConnectionLost) {
                    if (!connectionLost.value) {
                        connectionLost.value = true;
                    }
                    // Limpar apenas o stream remoto, manter estado para reconexão
                    if (remoteVideoRef.value) {
                        remoteVideoRef.value.srcObject = null;
                    }
                    hasRemoteStream.value = false;
                    peerCall.value = null;
                    // NÃO chamar endCall() - preservar estado para permitir nova reconexão
                } else {
                    endCall();
                }
            });

            call.on('error', (error: any) => {
                isReconnecting.value = false;
                connectionLost.value = true;
                console.error('Erro na reconexão:', error);
            });
        } catch (error: any) {
            isReconnecting.value = false;
            connectionLost.value = true;
            console.error('Erro ao reconectar:', error);
        }
    };

    /**
     * Inicia uma chamada com um usuário
     * O savedRemotePeerId será salvo quando a conexão for estabelecida (em createConnection)
     */
    const callUser = async (user: User) => {
        if (!user || !peer.value || !peer.value.id) {
            return;
        }

        try {
            callState.value = 'ringing_out';
            
            const payload = {
                peerId: peer.value.id,
            };

            const baseRoute = getRoutePrefix();
            await axios.post(`${baseRoute}/video-call/request/${user.id}`, payload);

            isCalling.value = true;
            selectedUser.value = user;
            connectionLost.value = false;

            // NOTA: Não salvar user.peerId aqui pois pode não estar disponível ainda
            // O savedRemotePeerId será salvo quando a conexão for estabelecida via createConnection()

            // Chamar callback se fornecido
            if (options.onCallStart) {
                await options.onCallStart();
            }

            // Aguardar o stream local estar pronto
            await displayLocalVideo();
            
            // Timeout para ringing (30 segundos)
            setTimeout(() => {
                if (callState.value === 'ringing_out') {
                    callState.value = 'error';
                    endCall();
                }
            }, 30000);
        } catch (error: any) {
            console.error('Erro ao iniciar chamada:', error);
            callState.value = 'error';
        }
    };

    /**
     * Aceita uma chamada recebida
     */
    const acceptCall = async () => {
        if (!peerCall.value) {
            return;
        }

        try {
            callState.value = 'connecting';
            
            // Obter stream local se ainda não tiver
            if (!localStreamRef.value) {
                await displayLocalVideo();
            }

            if (peerCall.value && localStreamRef.value) {
                // Salvar PeerID remoto para reconexão
                savedRemotePeerId.value = peerCall.value.peer;
                
                peerCall.value.answer(localStreamRef.value);
                isReceivingCall.value = false;
                isCalling.value = true;
                connectionLost.value = false;
                showIncomingCallModal.value = false;
                
                // Estado será atualizado quando o stream for recebido
            }
        } catch (error: any) {
            console.error('Erro ao aceitar chamada:', error);
            callState.value = 'error';
        }
    };

    /**
     * Mostra modal de confirmação de rejeição
     */
    const showRejectConfirmation = () => {
        pendingRejectCall.value = peerCall.value;
        showRejectionConfirmModal.value = true;
    };

    /**
     * Confirma a rejeição da chamada
     */
    const confirmRejectCall = () => {
        const rejectedPeerId = incomingCallPeerId.value;
        
        if (pendingRejectCall.value) {
            pendingRejectCall.value.close();
            pendingRejectCall.value = null;
        }
        
        if (peerCall.value) {
            peerCall.value.close();
            peerCall.value = null;
        }
        
        // Salvar informações da rejeição para permitir "chamar de volta"
        lastRejectedPeerId.value = rejectedPeerId;
        rejectionTimestamp.value = Date.now();
        canCallBack.value = true;
        
        isReceivingCall.value = false;
        showIncomingCallModal.value = false;
        showRejectionConfirmModal.value = false;
        incomingCallPeerId.value = '';
        
        // NÃO limpar savedRemotePeerId imediatamente - preservar por 30 segundos
        setTimeout(() => {
            if (savedRemotePeerId.value === rejectedPeerId && !isCalling.value) {
                savedRemotePeerId.value = '';
            }
        }, 30000); // 30 segundos para reconexão
        
        // Limpar possibilidade de "chamar de volta" após 2 minutos
        setTimeout(() => {
            if (lastRejectedPeerId.value === rejectedPeerId) {
                canCallBack.value = false;
                lastRejectedPeerId.value = '';
            }
        }, 120000); // 2 minutos
    };

    /**
     * Cancela a rejeição (volta para o modal de chamada)
     */
    const cancelRejectCall = () => {
        showRejectionConfirmModal.value = false;
        pendingRejectCall.value = null;
        // Modal de chamada continua aberto
    };

    /**
     * Rejeita uma chamada recebida (mantido para compatibilidade)
     */
    const rejectCall = () => {
        confirmRejectCall();
    };

    /**
     * Permite chamar de volta após rejeição acidental
     * Reenvia a solicitação de chamada para o usuário que foi rejeitado
     */
    const callBack = async () => {
        if (!canCallBack.value || !lastRejectedPeerId.value) {
            return;
        }

        try {
            // Encontrar o usuário que foi rejeitado
            const rejectedUser = selectedUser.value;
            if (!rejectedUser) {
                console.error('Usuário não encontrado para reenvio');
                return;
            }

            // Usar o mesmo fluxo de callUser para reenviar a solicitação
            await callUser(rejectedUser);
            
            // Limpar estado de callback após reenvio
            canCallBack.value = false;
            lastRejectedPeerId.value = '';

        } catch (error: any) {
            console.error('Erro ao chamar de volta:', error);
            callState.value = 'error';
        }
    };
    
    /**
     * Reenvia a solicitação de chamada para o usuário atual
     */
    const resendCallRequest = async () => {
        if (!selectedUser.value || !peer.value || !peer.value.id) {
            return;
        }

        try {
            callState.value = 'ringing_out';
            
            const payload = {
                peerId: peer.value.id,
            };

            const baseRoute = getRoutePrefix();
            await axios.post(`${baseRoute}/video-call/request/${selectedUser.value.id}`, payload);

            isCalling.value = true;
            connectionLost.value = false;

            // Timeout para ringing (30 segundos)
            setTimeout(() => {
                if (callState.value === 'ringing_out') {
                    callState.value = 'error';
                    endCall();
                }
            }, 30000);
        } catch (error: any) {
            console.error('Erro ao reenviar solicitação:', error);
            callState.value = 'error';
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
            callState.value = 'connecting';
            
            // Iniciar a chamada com o stream local já obtido
            const call = peer.value.call(receiverId, localStreamRef.value);
            peerCall.value = call;

            // Salvar PeerID para reconexão
            savedRemotePeerId.value = receiverId;

            // Escutar o stream do receptor
            call.on('stream', (remoteStream) => {
                if (remoteVideoRef.value) {
                    remoteVideoRef.value.srcObject = remoteStream;
                }
                hasRemoteStream.value = true;
                connectionLost.value = false;
                callState.value = 'in_call';
                startCallTimer();
                startNetworkQualityMonitoring(call.peerConnection);

                // Monitorar ICE connection state
                monitorIceConnectionState(call.peerConnection);
            });

            // Receptor encerrou a chamada
            call.on('close', () => {
                // Verificar se connectionLost já foi setado (via ICE ou erro)
                // Se não foi setado, verificar o estado ICE diretamente
                const iceState = call.peerConnection?.iceConnectionState;
                const isConnectionLost = connectionLost.value || iceState === 'disconnected' || iceState === 'failed';
                
                // Preservar estado APENAS se este lado realmente perdeu a conexão
                if (isConnectionLost) {
                    if (!connectionLost.value) {
                        connectionLost.value = true;
                    }
                    // Limpar apenas o stream remoto, manter estado para reconexão
                    if (remoteVideoRef.value) {
                        remoteVideoRef.value.srcObject = null;
                    }
                    hasRemoteStream.value = false;
                    peerCall.value = null;
                    // NÃO chamar endCall() - preservar estado para permitir reconexão
                } else {
                    endCall();
                }
            });

            call.on('error', (error: any) => {
                console.error('Erro na conexão:', error);
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

            if (!reverbConfig?.key) {
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
     * Inicializa o PeerJS com configurações otimizadas
     */
    const initializePeer = () => {
        if (peer.value) {
            peer.value.destroy();
        }

        const peerOptions: any = {
            config: {
                iceServers: [
                    { urls: mediaConfig.value.stunServer },
                    ...(mediaConfig.value.turnServer ? [{ urls: mediaConfig.value.turnServer }] : []),
                ],
            },
        };

        peer.value = new Peer(peerOptions);

        peer.value.on('open', (id) => {
            connectionState.value = 'connected';
            isConnected.value = true;
            connectWebSocket();
        });

        peer.value.on('error', (error) => {
            connectionState.value = 'error';
            console.error('Erro no PeerJS:', error);
        });

        peer.value.on('call', async (call) => {
            peerCall.value = call;
            incomingCallPeerId.value = call.peer;
            
            // Verificar se é uma reconexão ANTES de setar savedRemotePeerId:
            // Se já temos savedRemotePeerId e o chamador é o mesmo, é uma reconexão
            const isReconnection = savedRemotePeerId.value && savedRemotePeerId.value === call.peer;
            
            if (isReconnection) {
                // Reconexão - aceitar automaticamente SEM mostrar modal
                isReceivingCall.value = false;
                connectionLost.value = false;
                showIncomingCallModal.value = false;
                
                try {
                    if (!localStreamRef.value) {
                        await displayLocalVideo();
                    }
                    
                    if (localStreamRef.value) {
                        call.answer(localStreamRef.value);
                        isCalling.value = true;
                    }
                } catch (error: any) {
                    console.error('Erro ao aceitar reconexão:', error);
                }
            } else {
                // Nova chamada - mostrar modal e salvar PeerID para futuras reconexões
                savedRemotePeerId.value = call.peer; // Salvar apenas quando for nova chamada
                isReceivingCall.value = true;
                showIncomingCallModal.value = true;
                callState.value = 'ringing_in';
            }

            // Configurar listeners da chamada
            call.on('stream', (stream) => {
                if (remoteVideoRef.value) {
                    remoteVideoRef.value.srcObject = stream;
                }
                hasRemoteStream.value = true;
                connectionLost.value = false;
                callState.value = 'in_call';
                startCallTimer();
                startNetworkQualityMonitoring(call.peerConnection);

                // Monitorar ICE connection state
                monitorIceConnectionState(call.peerConnection);
            });

            call.on('close', () => {
                // Verificar se connectionLost já foi setado (via ICE ou simulação)
                // Se não foi setado, verificar o estado ICE diretamente
                const iceState = call.peerConnection?.iceConnectionState;
                const isConnectionLost = connectionLost.value || iceState === 'disconnected' || iceState === 'failed';
                
                // Preservar estado APENAS se este lado realmente perdeu a conexão
                if (isConnectionLost) {
                    if (!connectionLost.value) {
                        connectionLost.value = true;
                    }
                    // Limpar apenas o stream remoto, manter estado para reconexão
                    if (remoteVideoRef.value) {
                        remoteVideoRef.value.srcObject = null;
                    }
                    hasRemoteStream.value = false;
                    peerCall.value = null;
                    // NÃO chamar endCall() - preservar estado para permitir reconexão
                } else {
                    showIncomingCallModal.value = false;
                    endCall();
                }
            });

            call.on('error', (error: any) => {
                showIncomingCallModal.value = false;
                console.error('Erro na chamada recebida:', error);
            });
        });
    };

    /**
     * Inicializa o sistema de videochamadas
     */
    const initialize = () => {
        initializePeer();
    };

    /**
     * Limpa recursos ao desmontar
     */
    const cleanup = () => {
        stopCallTimer();
        stopNetworkQualityMonitoring();
        
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

    // NOTA: Reconexão manual via botão, seguindo a lógica do VideoTest.vue
    // Não há watch automático para evitar loops de reconexão
    // O usuário deve clicar no botão "Reconectar" quando connectionLost === true

    return {
        // Estados principais
        peer,
        peerCall,
        isCalling,
        selectedUser,
        localStreamRef,
        hasRemoteStream,

        // Estados de conexão avançados
        isConnected,
        isReceivingCall,
        connectionLost,
        isReconnecting,
        showIncomingCallModal,
        incomingCallPeerId,
        connectionState,
        
        // Estados para rejeições acidentais
        canCallBack,
        showRejectionConfirmModal,
        lastRejectedPeerId,
        
        // Novos estados detalhados
        callState,
        callDuration,
        networkQuality,

        // Refs
        remoteVideoRef,
        localVideoRef,

        // Configurações
        mediaConfig,

        // Métodos principais
        callUser,
        endCall,
        acceptCall,
        rejectCall,
        reconnectCall,
        displayLocalVideo,
        connectWebSocket,
        initialize,
        cleanup,
        
        // Novos métodos para rejeições acidentais
        showRejectConfirmation,
        confirmRejectCall,
        cancelRejectCall,
        callBack,
        
        // Novos métodos utilitários
        formatCallDuration,
        resendCallRequest,
    };
}

