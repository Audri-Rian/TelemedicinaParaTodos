<script setup lang="ts">
import { ref, onMounted, onUnmounted, computed, watch } from 'vue';
import { Head } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import Peer from 'peerjs';
import Echo from 'laravel-echo';
import { usePage } from '@inertiajs/vue3';
import {
    Video,
    VideoOff,
    Mic,
    MicOff,
    Phone,
    PhoneOff,
    RefreshCw,
    Settings,
    Wifi,
    WifiOff,
    AlertCircle,
    CheckCircle,
    XCircle,
    Loader2,
} from 'lucide-vue-next';

interface Props {
    reverb: {
        key: string;
        host: string;
        port: number;
        scheme: string;
    } | null;
}

const props = defineProps<Props>();
const page = usePage();

// Estados principais
const peer = ref<Peer | null>(null);
const peerCall = ref<any>(null);
const localStream = ref<MediaStream | null>(null);
const remoteStream = ref<MediaStream | null>(null);
const localVideoRef = ref<HTMLVideoElement | null>(null);
const remoteVideoRef = ref<HTMLVideoElement | null>(null);
const echoInstance = ref<any>(null);

// Estados de conexão
const isConnected = ref(false);
const isCalling = ref(false);
const isReceivingCall = ref(false);
const hasLocalStream = ref(false);
const hasRemoteStream = ref(false);
const connectionState = ref<string>('disconnected');
const reverbConnected = ref(false);
const showIncomingCallModal = ref(false);
const incomingCallPeerId = ref<string>('');
const connectionLost = ref(false);
const isReconnecting = ref(false);
const savedRemotePeerId = ref<string>('');

// IDs
const localPeerId = ref<string>('');
const remotePeerId = ref<string>('');

// Configurações ajustáveis
const config = ref({
    videoResolution: '1280x720',
    codec: 'VP8',
    stunServer: 'stun:stun.l.google.com:19302',
    turnServer: '',
    artificialLatency: 0,
    artificialJitter: 0,
});

// Simulações
const simulations = ref({
    networkLoss: false,
    cameraOff: false,
    micOff: false,
    patientOffline: false,
    doctorOffline: false,
});

// Logs
const logs = ref<Array<{ time: string; type: string; message: string }>>([]);
const iceCandidates = ref<Array<{ candidate: string; type: string }>>([]);
const offerLog = ref<string>('');
const answerLog = ref<string>('');

// Estatísticas WebRTC
const stats = ref({
    bitrate: { inbound: 0, outbound: 0 },
    resolution: { width: 0, height: 0 },
    fps: 0,
    codec: '',
    connectionType: '',
    iceTime: 0,
});

// Painéis visíveis
const showSettings = ref(false);
const showLogs = ref(true);
const showStats = ref(true);

// Adicionar log
const addLog = (type: string, message: string) => {
    const time = new Date().toLocaleTimeString('pt-BR');
    logs.value.unshift({ time, type, message });
    if (logs.value.length > 100) logs.value.pop();
};

// Monitorar estado da conexão ICE
const monitorIceConnectionState = (peerConnection: RTCPeerConnection) => {
    if (!peerConnection) return;

    const checkState = () => {
        const state = peerConnection.iceConnectionState;
        addLog('info', `ICE Connection State: ${state}`);

        if (state === 'failed' || state === 'disconnected') {
            // ESTE peer caiu - apenas ele deve reconectar
            if (!connectionLost.value && isCalling.value) {
                connectionLost.value = true;
                hasRemoteStream.value = false;
                addLog('warning', `Conexão perdida detectada (${state}) - Este peer precisa reconectar`);
            }
        }

        if (state === 'connected' || state === 'completed') {
            // ESTE peer está ok - não precisa reconectar
            if (connectionLost.value) {
                connectionLost.value = false;
                addLog('success', `Conexão restaurada (${state})`);
            }
        }
    };

    // Verificar estado inicial
    checkState();

    // Monitorar mudanças
    peerConnection.addEventListener('iceconnectionstatechange', checkState);
};

// Inicializar PeerJS
const initializePeer = () => {
    if (peer.value) {
        peer.value.destroy();
    }

    const peerOptions: any = {
        config: {
            iceServers: [
                { urls: config.value.stunServer },
                ...(config.value.turnServer ? [{ urls: config.value.turnServer }] : []),
            ],
        },
    };

    peer.value = new Peer(peerOptions);

    peer.value.on('open', (id) => {
        localPeerId.value = id;
        connectionState.value = 'connected';
        isConnected.value = true;
        addLog('success', `PeerJS conectado. ID: ${id}`);
    });

    peer.value.on('error', (error) => {
        addLog('error', `Erro PeerJS: ${error.message}`);
        connectionState.value = 'error';
    });

    peer.value.on('call', async (call) => {
        peerCall.value = call;
        remotePeerId.value = call.peer;
        incomingCallPeerId.value = call.peer;
        
        // Verificar se é uma reconexão ANTES de setar savedRemotePeerId:
        // Se já temos savedRemotePeerId e o chamador é o mesmo, é uma reconexão
        const isReconnection = savedRemotePeerId.value && savedRemotePeerId.value === call.peer;
        
        if (isReconnection) {
            // Reconexão - aceitar automaticamente SEM mostrar modal
            addLog('info', `Reconexão recebida de ${call.peer} - Aceitando automaticamente`);
            isReceivingCall.value = false;
            connectionLost.value = false;
            showIncomingCallModal.value = false; // Garantir que modal não apareça
            
            try {
                if (!localStream.value) {
                    await getLocalStream();
                }
                
                if (localStream.value) {
                    call.answer(localStream.value);
                    isCalling.value = true;
                    addLog('success', 'Reconexão aceita automaticamente - Chamada continua');
                }
            } catch (error: any) {
                addLog('error', `Erro ao aceitar reconexão: ${error.message}`);
            }
        } else {
            // Nova chamada - mostrar modal e salvar PeerID para futuras reconexões
            savedRemotePeerId.value = call.peer; // Salvar apenas quando for nova chamada
            isReceivingCall.value = true;
            addLog('info', `Nova chamada recebida de ${call.peer} - Mostrando modal para aceitar`);
            showIncomingCallModal.value = true;
        }

        // Configurar listeners da chamada
        call.on('stream', (stream) => {
            remoteStream.value = stream;
            connectionLost.value = false;
            if (remoteVideoRef.value) {
                remoteVideoRef.value.srcObject = stream;
            }
            hasRemoteStream.value = true;
            addLog('success', 'Stream remoto recebido');
            updateStats();

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
                addLog('info', `Chamada fechada devido à perda de conexão (ICE: ${iceState || 'N/A'}) - Aguardando reconexão`);
                // Limpar apenas o stream remoto, manter estado para reconexão
                if (remoteStream.value) {
                    remoteStream.value.getTracks().forEach((track) => track.stop());
                    remoteStream.value = null;
                }
                if (remoteVideoRef.value) {
                    remoteVideoRef.value.srcObject = null;
                }
                hasRemoteStream.value = false;
                peerCall.value = null;
            } else {
                addLog('info', 'Chamada encerrada pelo remoto');
                showIncomingCallModal.value = false;
                endCall();
            }
        });

        call.on('error', (error: any) => {
            addLog('error', `Erro na chamada recebida: ${error.message || 'Erro desconhecido'}`);
            showIncomingCallModal.value = false;
        });
    });
};

// Conectar Reverb
const connectReverb = () => {
    if (!props.reverb || !props.reverb.key) {
        addLog('warning', 'Reverb não configurado. Continuando sem WebSocket.');
        return;
    }

    try {
        echoInstance.value = new Echo({
            broadcaster: 'reverb',
            key: props.reverb.key,
            wsHost: props.reverb.host,
            wsPort: props.reverb.port,
            wssPort: props.reverb.port,
            forceTLS: props.reverb.scheme === 'https',
            enabledTransports: ['ws', 'wss'],
        });

        // Aguardar um pouco para o socket estar disponível
        setTimeout(() => {
            if (echoInstance.value?.connector?.socket) {
                echoInstance.value.connector.socket.on('connect', () => {
                    reverbConnected.value = true;
                    addLog('success', 'Reverb conectado');
                });

                echoInstance.value.connector.socket.on('disconnect', () => {
                    reverbConnected.value = false;
                    addLog('warning', 'Reverb desconectado');
                });

                echoInstance.value.connector.socket.on('error', (error: any) => {
                    addLog('error', `Erro Reverb: ${error.message || 'Conexão falhou'}`);
                    reverbConnected.value = false;
                });
            } else {
                addLog('warning', 'Reverb socket não disponível. Verifique se o servidor está rodando.');
            }
        }, 100);
    } catch (error: any) {
        addLog('error', `Erro ao conectar Reverb: ${error.message}`);
        reverbConnected.value = false;
    }
};

// Obter stream local
const getLocalStream = async () => {
    try {
        const constraints: MediaStreamConstraints = {
            video: {
                width: { ideal: parseInt(config.value.videoResolution.split('x')[0]) },
                height: { ideal: parseInt(config.value.videoResolution.split('x')[1]) },
            },
            audio: true,
        };

        const stream = await navigator.mediaDevices.getUserMedia(constraints);
        localStream.value = stream;
        hasLocalStream.value = true;

        if (localVideoRef.value) {
            localVideoRef.value.srcObject = stream;
        }

        // Aplicar simulações
        applySimulations(stream);

        addLog('success', 'Stream local obtido');
        updateStats();
    } catch (error: any) {
        addLog('error', `Erro ao obter stream: ${error.message}`);
    }
};

// Aplicar simulações ao stream
const applySimulations = (stream: MediaStream) => {
    stream.getVideoTracks().forEach((track) => {
        track.enabled = !simulations.value.cameraOff;
    });

    stream.getAudioTracks().forEach((track) => {
        track.enabled = !simulations.value.micOff;
    });
};

// Iniciar chamada
const startCall = async () => {
    if (!peer.value || !remotePeerId.value) {
        addLog('error', 'Peer não inicializado ou PeerID remoto não informado');
        return;
    }

    try {
        await getLocalStream();

        if (!localStream.value) {
            addLog('error', 'Não foi possível obter stream local');
            return;
        }

        // Salvar PeerID remoto para reconexão
        savedRemotePeerId.value = remotePeerId.value;

        const call = peer.value.call(remotePeerId.value, localStream.value);
        peerCall.value = call;
        isCalling.value = true;
        connectionLost.value = false;

        addLog('info', `Iniciando chamada para ${remotePeerId.value}`);

        call.on('stream', (stream) => {
            remoteStream.value = stream;
            connectionLost.value = false;
            if (remoteVideoRef.value) {
                remoteVideoRef.value.srcObject = stream;
            }
            hasRemoteStream.value = true;
            addLog('success', 'Stream remoto recebido');
            updateStats();

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
                addLog('info', `Chamada fechada devido à perda de conexão (ICE: ${iceState || 'N/A'}) - Estado preservado para reconexão`);
                // Limpar apenas o stream remoto, manter estado para reconexão
                if (remoteStream.value) {
                    remoteStream.value.getTracks().forEach((track) => track.stop());
                    remoteStream.value = null;
                }
                if (remoteVideoRef.value) {
                    remoteVideoRef.value.srcObject = null;
                }
                hasRemoteStream.value = false;
                peerCall.value = null;
            } else {
                addLog('info', 'Chamada encerrada pelo remoto');
                endCall();
            }
        });

        call.on('error', (error: any) => {
            addLog('error', `Erro na chamada: ${error.message || 'Erro desconhecido'}`);
        });
    } catch (error: any) {
        addLog('error', `Erro ao iniciar chamada: ${error.message}`);
    }
};

// Aceitar chamada
const acceptCall = async () => {
    if (!peerCall.value) {
        addLog('error', 'Nenhuma chamada pendente para aceitar');
        return;
    }

    try {
        // Obter stream local se ainda não tiver
        if (!localStream.value) {
            await getLocalStream();
        }

        if (peerCall.value && localStream.value) {
            // Salvar PeerID remoto para reconexão
            savedRemotePeerId.value = peerCall.value.peer;
            remotePeerId.value = peerCall.value.peer;
            
            peerCall.value.answer(localStream.value);
            isReceivingCall.value = false;
            isCalling.value = true;
            connectionLost.value = false;
            showIncomingCallModal.value = false;
            addLog('success', 'Chamada aceita');
        } else {
            addLog('error', 'Não foi possível obter stream local para aceitar chamada');
        }
    } catch (error: any) {
        addLog('error', `Erro ao aceitar chamada: ${error.message}`);
    }
};

// Rejeitar chamada
const rejectCall = () => {
    // Salvar incomingCallPeerId antes de limpar para verificação
    const rejectedPeerId = incomingCallPeerId.value;
    
    if (peerCall.value) {
        peerCall.value.close();
        peerCall.value = null;
    }
    isReceivingCall.value = false;
    showIncomingCallModal.value = false;
    remotePeerId.value = '';
    incomingCallPeerId.value = '';
    
    // Limpar savedRemotePeerId apenas se for a mesma chamada que foi rejeitada
    // (não limpar se já tínhamos uma chamada estabelecida anteriormente)
    if (savedRemotePeerId.value === rejectedPeerId && !isCalling.value) {
        savedRemotePeerId.value = '';
    }
    addLog('info', 'Chamada rejeitada');
};

// Encerrar chamada
const endCall = (preservePeerId = false) => {
    if (peerCall.value) {
        peerCall.value.close();
        peerCall.value = null;
    }

    if (remoteStream.value) {
        remoteStream.value.getTracks().forEach((track) => track.stop());
        remoteStream.value = null;
    }

    if (remoteVideoRef.value) {
        remoteVideoRef.value.srcObject = null;
    }

    isCalling.value = false;
    isReceivingCall.value = false;
    hasRemoteStream.value = false;
    showIncomingCallModal.value = false;
    
    // Se não for perda de conexão, limpar tudo
    if (!preservePeerId) {
        connectionLost.value = false;
        remotePeerId.value = '';
        incomingCallPeerId.value = '';
        savedRemotePeerId.value = '';
    }
    
    addLog('info', 'Chamada encerrada');
};

// Reconectar chamada
const reconnectCall = async () => {
    addLog('info', `Tentativa de reconexão - connectionLost: ${connectionLost.value}, savedRemotePeerId: ${savedRemotePeerId.value || 'vazio'}`);
    
    if (!savedRemotePeerId.value) {
        addLog('error', 'Nenhum PeerID salvo para reconexão');
        return;
    }

    if (!peer.value || !isConnected.value) {
        addLog('error', 'Peer não está conectado. Reinicie o Peer primeiro.');
        return;
    }

    isReconnecting.value = true;
    connectionLost.value = false;
    addLog('info', `Tentando reconectar com ${savedRemotePeerId.value}...`);

    try {
        // Limpar chamada anterior
        if (peerCall.value) {
            peerCall.value.close();
            peerCall.value = null;
        }

        if (remoteStream.value) {
            remoteStream.value.getTracks().forEach((track) => track.stop());
            remoteStream.value = null;
        }

        // Garantir que temos stream local
        if (!localStream.value) {
            await getLocalStream();
        }

        if (!localStream.value) {
            addLog('error', 'Não foi possível obter stream local para reconexão');
            isReconnecting.value = false;
            return;
        }

        // Iniciar nova chamada
        remotePeerId.value = savedRemotePeerId.value;
        const call = peer.value.call(savedRemotePeerId.value, localStream.value);
        peerCall.value = call;
        isCalling.value = true;

        call.on('stream', (stream) => {
            remoteStream.value = stream;
            connectionLost.value = false;
            if (remoteVideoRef.value) {
                remoteVideoRef.value.srcObject = stream;
            }
            hasRemoteStream.value = true;
            isReconnecting.value = false;
            
            // Garantir que savedRemotePeerId está preservado para futuras reconexões
            if (!savedRemotePeerId.value) {
                savedRemotePeerId.value = call.peer;
            }
            
            addLog('success', `Reconexão bem-sucedida - Stream remoto recebido (PeerID preservado: ${savedRemotePeerId.value})`);
            updateStats();

            // Monitorar ICE connection state
            monitorIceConnectionState(call.peerConnection);
        });

        call.on('close', () => {
            // Verificar se connectionLost já foi setado (via ICE ou simulação)
            // Se não foi setado, verificar o estado ICE diretamente
            const iceState = call.peerConnection?.iceConnectionState;
            const isConnectionLost = connectionLost.value || iceState === 'disconnected' || iceState === 'failed';
            
            isReconnecting.value = false;
            
            // Preservar estado APENAS se este lado realmente perdeu a conexão
            if (isConnectionLost) {
                if (!connectionLost.value) {
                    connectionLost.value = true;
                }
                addLog('info', `Chamada fechada devido à perda de conexão (ICE: ${iceState || 'N/A'}) - Estado preservado para reconexão`);
                // Limpar apenas o stream remoto, manter estado para reconexão
                if (remoteStream.value) {
                    remoteStream.value.getTracks().forEach((track) => track.stop());
                    remoteStream.value = null;
                }
                if (remoteVideoRef.value) {
                    remoteVideoRef.value.srcObject = null;
                }
                hasRemoteStream.value = false;
                peerCall.value = null;
                // NÃO chamar endCall() - preservar estado para permitir nova reconexão
            } else {
                addLog('info', 'Chamada encerrada pelo remoto');
                endCall();
            }
        });

        call.on('error', (error: any) => {
            isReconnecting.value = false;
            addLog('error', `Erro na reconexão: ${error.message || 'Erro desconhecido'}`);
        });
    } catch (error: any) {
        isReconnecting.value = false;
        connectionLost.value = true;
        addLog('error', `Erro ao reconectar: ${error.message}`);
    }
};

// Limpar tudo
const cleanup = () => {
    endCall();

    if (localStream.value) {
        localStream.value.getTracks().forEach((track) => track.stop());
        localStream.value = null;
    }

    if (localVideoRef.value) {
        localVideoRef.value.srcObject = null;
    }

    hasLocalStream.value = false;
};

// Reiniciar Peer
const restartPeer = () => {
    cleanup();
    initializePeer();
    addLog('info', 'Peer reiniciado');
};

// Gerar nova offer (simulado)
const generateNewOffer = () => {
    if (peer.value && localStream.value) {
        addLog('info', 'Gerando nova offer...');
        offerLog.value = `Offer gerado: ${new Date().toISOString()}`;
    }
};

// Gerar nova answer (simulado)
const generateNewAnswer = () => {
    if (peerCall.value) {
        addLog('info', 'Gerando nova answer...');
        answerLog.value = `Answer gerado: ${new Date().toISOString()}`;
    }
};

// Limpar ICE
const clearICE = () => {
    iceCandidates.value = [];
    addLog('info', 'ICE candidates limpos');
};

// Atualizar estatísticas
const updateStats = async () => {
    if (!peerCall.value) return;

    try {
        const statsReport = await peerCall.value.peerConnection.getStats();
        statsReport.forEach((report: any) => {
            if (report.type === 'inbound-rtp' && report.mediaType === 'video') {
                stats.value.resolution.width = report.frameWidth || 0;
                stats.value.resolution.height = report.frameHeight || 0;
                stats.value.fps = report.framesPerSecond || 0;
            }

            if (report.type === 'candidate-pair' && report.state === 'succeeded') {
                stats.value.connectionType = report.localCandidate?.candidateType || '';
                stats.value.iceTime = report.currentRoundTripTime * 1000 || 0;
            }
        });
    } catch (error) {
        // Ignorar erros de stats
    }
};

// Simular perda de conexão
const simulateNetworkLoss = () => {
    simulations.value.networkLoss = !simulations.value.networkLoss;
    if (simulations.value.networkLoss) {
        if (peerCall.value && peerCall.value.peerConnection && isCalling.value) {
            // Garantir que temos savedRemotePeerId antes de simular perda
            // Priorizar savedRemotePeerId existente, depois remotePeerId, depois peerCall.peer
            if (!savedRemotePeerId.value) {
                if (remotePeerId.value) {
                    savedRemotePeerId.value = remotePeerId.value;
                } else if (peerCall.value.peer) {
                    savedRemotePeerId.value = peerCall.value.peer;
                }
            } else {
                // Se já temos savedRemotePeerId, garantir que está correto
                // Se o peerCall.peer for diferente, atualizar (pode acontecer em reconexões)
                if (peerCall.value.peer && peerCall.value.peer !== savedRemotePeerId.value) {
                    // Manter o savedRemotePeerId original (o peer remoto real)
                    // Não atualizar baseado no peerCall.peer pois pode ser temporário
                }
            }
            
            // Na simulação, sabemos que ESTE lado está perdendo a conexão
            // Então setamos connectionLost diretamente
            connectionLost.value = true;
            hasRemoteStream.value = false;
            
            // IMPORTANTE: Manter isCalling como true para que o botão de reconectar apareça
            // Não setar isCalling = false aqui, pois isso esconderia os controles
            
            // Limpar stream remoto mas preservar estado para reconexão
            if (remoteStream.value) {
                remoteStream.value.getTracks().forEach((track) => track.stop());
                remoteStream.value = null;
            }
            if (remoteVideoRef.value) {
                remoteVideoRef.value.srcObject = null;
            }
            
            // Fechar a conexão
            const callRef = peerCall.value;
            if (callRef.peerConnection) {
                callRef.peerConnection.close();
            }
            
            // Limpar a referência da chamada mas manter o estado
            peerCall.value = null;
            
            addLog('warning', `Simulação: Perda de conexão ativada - PeerID preservado: ${savedRemotePeerId.value}`);
            addLog('info', `Estado: connectionLost=${connectionLost.value}, savedRemotePeerId=${savedRemotePeerId.value}`);
            addLog('info', 'Este lado perdeu a conexão - Use o botão "Reconectar" para restaurar');
        }
    } else {
        addLog('info', 'Simulação: Perda de conexão desativada');
    }
};

// Toggle câmera
const toggleCamera = () => {
    simulations.value.cameraOff = !simulations.value.cameraOff;
    if (localStream.value) {
        localStream.value.getVideoTracks().forEach((track) => {
            track.enabled = !simulations.value.cameraOff;
        });
    }
    addLog('info', `Câmera ${simulations.value.cameraOff ? 'desligada' : 'ligada'}`);
};

// Toggle microfone
const toggleMic = () => {
    simulations.value.micOff = !simulations.value.micOff;
    if (localStream.value) {
        localStream.value.getAudioTracks().forEach((track) => {
            track.enabled = !simulations.value.micOff;
        });
    }
    addLog('info', `Microfone ${simulations.value.micOff ? 'desligado' : 'ligado'}`);
};

// Watch para aplicar simulações quando mudarem
watch(
    () => simulations.value.cameraOff,
    () => {
        if (localStream.value) {
            localStream.value.getVideoTracks().forEach((track) => {
                track.enabled = !simulations.value.cameraOff;
            });
        }
    }
);

watch(
    () => simulations.value.micOff,
    () => {
        if (localStream.value) {
            localStream.value.getAudioTracks().forEach((track) => {
                track.enabled = !simulations.value.micOff;
            });
        }
    }
);

// Inicialização
onMounted(() => {
    addLog('info', 'Página de desenvolvimento carregada');
    initializePeer();
    connectReverb();
});

// Cleanup
onUnmounted(() => {
    cleanup();
    if (peer.value) {
        peer.value.destroy();
    }
    if (echoInstance.value) {
        echoInstance.value.disconnect();
    }
});
</script>

<template>
    <Head title="Dev: Video Test" />

    <div class="min-h-screen bg-slate-50 p-4">
        <div class="max-w-7xl mx-auto space-y-4">
            <!-- Header -->
            <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                <h1 class="text-2xl font-bold text-red-900 flex items-center gap-2">
                    <AlertCircle class="h-6 w-6" />
                    Ambiente de Desenvolvimento - Teste de Vídeo
                </h1>
                <p class="text-sm text-red-700 mt-1">
                    Esta página ignora todas as regras de negócio. Use apenas para desenvolvimento.
                </p>
            </div>

            <!-- Grid Principal -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                <!-- Área de Vídeo -->
                <div class="lg:col-span-2 space-y-4">
                    <!-- Vídeo Local -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <Video class="h-5 w-5" />
                                Vídeo Local
                            </CardTitle>
                            <CardDescription>PeerID: {{ localPeerId || 'Não conectado' }}</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div class="relative bg-black rounded-lg overflow-hidden aspect-video">
                                <video
                                    ref="localVideoRef"
                                    autoplay
                                    muted
                                    playsinline
                                    class="w-full h-full object-cover"
                                ></video>
                                <div
                                    v-if="!hasLocalStream"
                                    class="absolute inset-0 flex items-center justify-center text-white bg-slate-900"
                                >
                                    <div class="text-center">
                                        <VideoOff class="h-12 w-12 mx-auto mb-2 opacity-50" />
                                        <p class="text-sm">Sem stream local</p>
                                    </div>
                                </div>
                                <div
                                    v-if="simulations.cameraOff"
                                    class="absolute top-2 right-2 bg-red-500 text-white px-2 py-1 rounded text-xs"
                                >
                                    Câmera Desligada
                                </div>
                                <div
                                    v-if="simulations.micOff"
                                    class="absolute top-2 left-2 bg-red-500 text-white px-2 py-1 rounded text-xs"
                                >
                                    Mic Desligado
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Vídeo Remoto -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <Video class="h-5 w-5" />
                                Vídeo Remoto
                            </CardTitle>
                            <CardDescription>PeerID: {{ remotePeerId || 'Aguardando conexão' }}</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div class="relative bg-black rounded-lg overflow-hidden aspect-video">
                                <video
                                    ref="remoteVideoRef"
                                    autoplay
                                    playsinline
                                    class="w-full h-full object-cover"
                                ></video>
                                <div
                                    v-if="!hasRemoteStream"
                                    class="absolute inset-0 flex items-center justify-center text-white bg-slate-900"
                                >
                                    <div class="text-center">
                                        <VideoOff class="h-12 w-12 mx-auto mb-2 opacity-50" />
                                        <p class="text-sm">Aguardando stream remoto</p>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Painel de Controle -->
                <div class="space-y-4">
                    <!-- Status -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Status da Conexão</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="text-sm">PeerJS:</span>
                                <span
                                    :class="[
                                        'text-xs px-2 py-1 rounded',
                                        isConnected ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700',
                                    ]"
                                >
                                    {{ connectionState }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm">Reverb:</span>
                                <span
                                    :class="[
                                        'text-xs px-2 py-1 rounded',
                                        reverbConnected ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700',
                                    ]"
                                >
                                    {{ reverbConnected ? 'Conectado' : 'Desconectado' }}
                                </span>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Controles Principais -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Controles</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-2">
                            <div class="space-y-2">
                                <input
                                    v-model="remotePeerId"
                                    type="text"
                                    placeholder="PeerID Remoto"
                                    class="w-full px-3 py-2 border rounded-md text-sm"
                                />
                                <div class="grid grid-cols-2 gap-2">
                                    <Button @click="startCall" :disabled="!isConnected || !remotePeerId" class="w-full">
                                        <Phone class="h-4 w-4 mr-2" />
                                        Iniciar
                                    </Button>
                                    <Button
                                        @click="acceptCall"
                                        :disabled="!isReceivingCall"
                                        variant="outline"
                                        class="w-full"
                                    >
                                        <CheckCircle class="h-4 w-4 mr-2" />
                                        Aceitar
                                    </Button>
                                </div>
                                <Button @click="endCall" :disabled="!isCalling" variant="destructive" class="w-full">
                                    <PhoneOff class="h-4 w-4 mr-2" />
                                    Encerrar
                                </Button>
                                <!-- Debug temporário -->
                                <div v-if="connectionLost || savedRemotePeerId" class="text-xs text-slate-500 p-2 bg-slate-50 rounded border">
                                    <div>connectionLost: {{ connectionLost }}</div>
                                    <div>savedRemotePeerId: {{ savedRemotePeerId || 'vazio' }}</div>
                                    <div>isReconnecting: {{ isReconnecting }}</div>
                                    <div>Botão habilitado: {{ connectionLost && !isReconnecting && savedRemotePeerId ? 'SIM' : 'NÃO' }}</div>
                                </div>
                                <Button
                                    @click="reconnectCall"
                                    :disabled="!connectionLost || isReconnecting || !savedRemotePeerId"
                                    variant="outline"
                                    class="w-full border-orange-300 bg-orange-50 hover:bg-orange-100 text-orange-700"
                                >
                                    <RefreshCw :class="['h-4 w-4 mr-2', isReconnecting ? 'animate-spin' : '']" />
                                    {{ isReconnecting ? 'Reconectando...' : 'Reconectar' }}
                                </Button>
                            </div>

                            <!-- Alerta de conexão perdida -->
                            <div
                                v-if="connectionLost && savedRemotePeerId"
                                class="mt-2 p-2 bg-orange-50 border border-orange-200 rounded-md text-xs text-orange-700"
                            >
                                <div class="flex items-center gap-2">
                                    <AlertCircle class="h-4 w-4" />
                                    <span>Conexão perdida. Clique em "Reconectar" para tentar novamente.</span>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-2 pt-2 border-t">
                                <Button @click="getLocalStream" variant="outline" size="sm" class="w-full">
                                    <Video class="h-4 w-4 mr-1" />
                                    Stream
                                </Button>
                                <Button @click="restartPeer" variant="outline" size="sm" class="w-full">
                                    <RefreshCw class="h-4 w-4 mr-1" />
                                    Reiniciar
                                </Button>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Simulações -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Simulações</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-2">
                            <Button
                                @click="toggleCamera"
                                :variant="simulations.cameraOff ? 'destructive' : 'outline'"
                                size="sm"
                                class="w-full"
                            >
                                <VideoOff v-if="simulations.cameraOff" class="h-4 w-4 mr-2" />
                                <Video v-else class="h-4 w-4 mr-2" />
                                {{ simulations.cameraOff ? 'Ligar Câmera' : 'Desligar Câmera' }}
                            </Button>
                            <Button
                                @click="toggleMic"
                                :variant="simulations.micOff ? 'destructive' : 'outline'"
                                size="sm"
                                class="w-full"
                            >
                                <MicOff v-if="simulations.micOff" class="h-4 w-4 mr-2" />
                                <Mic v-else class="h-4 w-4 mr-2" />
                                {{ simulations.micOff ? 'Ligar Mic' : 'Desligar Mic' }}
                            </Button>
                            <Button
                                @click="simulateNetworkLoss"
                                :variant="simulations.networkLoss ? 'destructive' : 'outline'"
                                size="sm"
                                class="w-full"
                            >
                                <WifiOff v-if="simulations.networkLoss" class="h-4 w-4 mr-2" />
                                <Wifi v-else class="h-4 w-4 mr-2" />
                                {{ simulations.networkLoss ? 'Restaurar Rede' : 'Perder Conexão' }}
                            </Button>
                        </CardContent>
                    </Card>

                    <!-- Ferramentas Avançadas -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Ferramentas</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-2">
                            <Button @click="generateNewOffer" variant="outline" size="sm" class="w-full">
                                Gerar Offer
                            </Button>
                            <Button @click="generateNewAnswer" variant="outline" size="sm" class="w-full">
                                Gerar Answer
                            </Button>
                            <Button @click="clearICE" variant="outline" size="sm" class="w-full">
                                Limpar ICE
                            </Button>
                        </CardContent>
                    </Card>
                </div>
            </div>

            <!-- Configurações -->
            <Card v-if="showSettings">
                <CardHeader>
                    <CardTitle>Configurações</CardTitle>
                </CardHeader>
                <CardContent class="grid grid-cols-2 md:grid-cols-3 gap-4">
                    <div>
                        <label class="text-sm font-medium">Resolução</label>
                        <select v-model="config.videoResolution" class="w-full px-3 py-2 border rounded-md text-sm">
                            <option value="640x480">640x480</option>
                            <option value="1280x720">1280x720</option>
                            <option value="1920x1080">1920x1080</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-sm font-medium">Codec</label>
                        <select v-model="config.codec" class="w-full px-3 py-2 border rounded-md text-sm">
                            <option value="VP8">VP8</option>
                            <option value="VP9">VP9</option>
                            <option value="H264">H264</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-sm font-medium">STUN Server</label>
                        <input
                            v-model="config.stunServer"
                            type="text"
                            class="w-full px-3 py-2 border rounded-md text-sm"
                        />
                    </div>
                </CardContent>
            </Card>

            <!-- Estatísticas WebRTC -->
            <Card v-if="showStats">
                <CardHeader>
                    <CardTitle>Estatísticas WebRTC</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                        <div>
                            <p class="text-slate-500">Resolução</p>
                            <p class="font-semibold">
                                {{ stats.resolution.width }}x{{ stats.resolution.height }}
                            </p>
                        </div>
                        <div>
                            <p class="text-slate-500">FPS</p>
                            <p class="font-semibold">{{ stats.fps }}</p>
                        </div>
                        <div>
                            <p class="text-slate-500">Tipo de Conexão</p>
                            <p class="font-semibold">{{ stats.connectionType || 'N/A' }}</p>
                        </div>
                        <div>
                            <p class="text-slate-500">Tempo ICE</p>
                            <p class="font-semibold">{{ stats.iceTime.toFixed(0) }}ms</p>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Logs -->
            <Card v-if="showLogs">
                <CardHeader>
                    <CardTitle>Logs</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="bg-slate-900 text-green-400 font-mono text-xs p-4 rounded-lg max-h-64 overflow-y-auto">
                        <div v-for="(log, index) in logs" :key="index" class="mb-1">
                            <span class="text-slate-500">[{{ log.time }}]</span>
                            <span
                                :class="{
                                    'text-green-400': log.type === 'success',
                                    'text-red-400': log.type === 'error',
                                    'text-yellow-400': log.type === 'warning',
                                    'text-blue-400': log.type === 'info',
                                }"
                            >
                                {{ log.message }}
                            </span>
                        </div>
                        <div v-if="logs.length === 0" class="text-slate-500">Nenhum log ainda...</div>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Modal de Chamada Recebida -->
        <Dialog :open="showIncomingCallModal" @update:open="(value) => { if (!value) rejectCall() }">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2">
                        <Phone class="h-6 w-6 text-primary" />
                        Chamada Recebida
                    </DialogTitle>
                    <DialogDescription>
                        Você está recebendo uma chamada de vídeo de:
                    </DialogDescription>
                </DialogHeader>

                <div class="py-4">
                    <div class="flex items-center justify-center gap-3 p-4 bg-slate-50 rounded-lg">
                        <div class="flex items-center justify-center w-16 h-16 rounded-full bg-primary/10">
                            <Phone class="h-8 w-8 text-primary" />
                        </div>
                        <div class="flex-1">
                            <p class="font-semibold text-lg text-slate-900">Peer ID</p>
                            <p class="text-sm text-slate-500 font-mono">{{ incomingCallPeerId }}</p>
                        </div>
                    </div>
                </div>

                <DialogFooter class="gap-2">
                    <Button @click="rejectCall" variant="destructive" class="flex-1">
                        <XCircle class="h-4 w-4 mr-2" />
                        Rejeitar
                    </Button>
                    <Button @click="acceptCall" class="flex-1">
                        <CheckCircle class="h-4 w-4 mr-2" />
                        Aceitar
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
