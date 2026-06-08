<script setup lang="ts">
import VideoControls from '@/components/VideoControls.vue';
import VideoGrid from '@/components/VideoGrid.vue';
import { useSfu } from '@/composables/useSfu';
import { Head } from '@inertiajs/vue3';
import axios from 'axios';
import { Activity, LogIn, MonitorUp, RotateCcw, Server, Stethoscope, UserRound } from 'lucide-vue-next';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';

type SimulationRole = 'doctor' | 'patient';
type SimulationStage = 'idle' | 'provisioning' | 'ready' | 'connecting' | 'connected' | 'error';

interface Props {
    simulation: {
        room_id: string;
        sfu_ws_url: string | null;
        sfu_node: string | null;
    };
}

interface SessionResponse {
    data: {
        call_id: string;
        room_id: string;
        role: SimulationRole;
        token: string;
        sfu_ws_url: string | null;
        sfu_node: string | null;
    };
}

interface SimulationStatusResponse {
    data: {
        active: boolean;
        call_id: string;
        room_id: string;
        started_at: string | null;
    };
}

defineProps<Props>();

const sfu = useSfu();
const selectedRole = ref<SimulationRole>('doctor');
const stage = ref<SimulationStage>('idle');
const session = ref<SessionResponse['data'] | null>(null);
const logs = ref<string[]>([]);
const errorMessage = ref<string | null>(null);
let broadcastChannel: BroadcastChannel | null = null;
let statusPollingInterval: ReturnType<typeof setInterval> | null = null;

const roleLabel = computed(() => (selectedRole.value === 'doctor' ? 'Medico' : 'Paciente'));
const canProvision = computed(() => stage.value !== 'provisioning' && stage.value !== 'connecting');
const canConnect = computed(() => !!session.value?.token && ['ready', 'error'].includes(stage.value));
const remoteCount = computed(() => sfu.remoteStreams.value.size);

function addLog(message: string, context?: Record<string, unknown>): void {
    const time = new Intl.DateTimeFormat('pt-BR', {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
    }).format(new Date());
    const suffix = context ? ` ${JSON.stringify(context)}` : '';
    logs.value = [`[${time}] ${message}${suffix}`, ...logs.value].slice(0, 16);
}

async function provisionCall(options: { broadcast?: boolean; source?: 'local' | 'remote' | 'server' } = {}): Promise<void> {
    const shouldBroadcast = options.broadcast ?? true;
    if (!canProvision.value) return;

    stage.value = 'provisioning';
    errorMessage.value = null;
    addLog(options.source && options.source !== 'local' ? 'Sistema iniciou chamada automaticamente' : 'Sistema provisionando sala SFU', {
        role: selectedRole.value,
    });

    try {
        const response = await axios.post<SessionResponse>('/dev/sfu-call-simulator/session', {
            role: selectedRole.value,
        });
        session.value = response.data.data;
        stage.value = 'ready';
        addLog('Sala provisionada pelo Laravel', {
            roomId: session.value.room_id,
            node: session.value.sfu_node,
        });

        if (shouldBroadcast) {
            broadcastChannel?.postMessage({
                type: 'sfu-call-started',
                roomId: session.value.room_id,
            });
        }
    } catch (error) {
        stage.value = 'error';
        const axiosError = error as { response?: { data?: { message?: string } }; message?: string };
        errorMessage.value = axiosError.response?.data?.message ?? axiosError.message ?? 'Falha ao provisionar sala';
        addLog('Falha no provisionamento', { message: errorMessage.value });
    }
}

async function syncStartedCall(): Promise<void> {
    if (session.value || stage.value !== 'idle') return;

    try {
        const response = await axios.get<SimulationStatusResponse>('/dev/sfu-call-simulator/status');
        if (!response.data.data.active) return;

        addLog('Chamada ativa detectada no Laravel', {
            roomId: response.data.data.room_id,
        });
        await provisionCall({ broadcast: false, source: 'server' });
    } catch (error) {
        const err = error as { message?: string };
        addLog('Falha ao consultar estado da chamada', { message: err.message ?? 'erro desconhecido' });
    }
}

async function changeRole(role: SimulationRole): Promise<void> {
    selectedRole.value = role;

    if (!session.value || stage.value === 'connected' || stage.value === 'connecting') return;

    session.value = null;
    addLog('Papel alterado antes de entrar; reprovisionando token', { role });
    await provisionCall({ broadcast: false, source: 'server' });
}

watch(
    () => session.value?.role,
    (role) => {
        if (role && role !== selectedRole.value && stage.value !== 'connected') {
            selectedRole.value = role;
        }
    },
);

async function enterCall(): Promise<void> {
    if (!session.value?.token || !session.value.sfu_ws_url) return;

    stage.value = 'connecting';
    errorMessage.value = null;
    addLog(`${roleLabel.value} entrando na chamada`, { sfuWsUrl: session.value.sfu_ws_url });

    try {
        await sfu.connect(session.value.sfu_ws_url, session.value.token);
        stage.value = 'connected';
        addLog('Conexao SFU estabelecida', {
            state: sfu.connectionState.value,
            role: session.value.role,
        });
        console.info('[VideoCall][Simulator] Conexao SFU estabelecida com sucesso.', {
            role: session.value.role,
            roomId: session.value.room_id,
            connectionState: sfu.connectionState.value,
        });
    } catch (error) {
        stage.value = 'error';
        const err = error as { message?: string };
        errorMessage.value = err.message ?? 'Falha ao conectar no SFU';
        addLog('Falha ao conectar no SFU', { message: errorMessage.value });
    }
}

function leaveCall(): void {
    sfu.disconnect();
    stage.value = session.value ? 'ready' : 'idle';
    addLog(`${roleLabel.value} saiu da chamada`);
}

function resetSimulation(): void {
    sfu.disconnect();
    session.value = null;
    stage.value = 'idle';
    errorMessage.value = null;
    logs.value = [];
    broadcastChannel?.postMessage({ type: 'sfu-call-reset' });
    axios.delete('/dev/sfu-call-simulator/session').catch(() => {
        addLog('Falha ao reiniciar estado no Laravel');
    });
}

function fullscreen(): void {
    document.documentElement.requestFullscreen?.();
}

onMounted(() => {
    syncStartedCall();
    statusPollingInterval = setInterval(syncStartedCall, 2000);

    if (typeof BroadcastChannel === 'undefined') return;

    broadcastChannel = new BroadcastChannel('sfu-call-simulator');
    broadcastChannel.onmessage = (event) => {
        const { type, roomId } = event.data ?? {};

        if (type === 'sfu-call-started' && roomId && !session.value && stage.value === 'idle') {
            addLog('Chamada iniciada pelo sistema detectada', { roomId });
            provisionCall({ broadcast: false, source: 'remote' });
        }

        if (type === 'sfu-call-reset' && stage.value !== 'connected') {
            session.value = null;
            stage.value = 'idle';
            errorMessage.value = null;
            addLog('Simulacao reiniciada em outra aba');
        }
    };
});

onBeforeUnmount(() => {
    if (statusPollingInterval) {
        clearInterval(statusPollingInterval);
    }

    broadcastChannel?.close();
    sfu.disconnect();
});
</script>

<template>
    <Head title="Simulador SFU" />

    <div class="min-h-screen bg-[#eef4f3] text-slate-900">
        <main class="mx-auto flex min-h-screen w-full max-w-7xl flex-col gap-4 px-4 py-4 lg:px-6">
            <section class="grid gap-4 lg:grid-cols-[360px_minmax(0,1fr)]">
                <aside class="flex flex-col gap-4">
                    <div class="border border-slate-200 bg-white p-4 shadow-sm">
                        <div class="mb-4 flex items-center justify-between gap-3">
                            <div>
                                <h1 class="text-lg font-semibold text-slate-950">Simulador SFU</h1>
                                <p class="text-sm text-slate-500">Chamada automatica de desenvolvimento</p>
                            </div>
                            <span class="inline-flex h-9 w-9 items-center justify-center bg-emerald-100 text-emerald-700">
                                <Activity class="h-5 w-5" />
                            </span>
                        </div>

                        <div class="grid grid-cols-2 gap-2">
                            <button
                                type="button"
                                class="flex h-20 flex-col items-center justify-center gap-2 border text-sm font-medium transition"
                                :class="
                                    selectedRole === 'doctor'
                                        ? 'border-emerald-600 bg-emerald-50 text-emerald-800'
                                        : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50'
                                "
                                @click="changeRole('doctor')"
                            >
                                <Stethoscope class="h-5 w-5" />
                                Medico
                            </button>
                            <button
                                type="button"
                                class="flex h-20 flex-col items-center justify-center gap-2 border text-sm font-medium transition"
                                :class="
                                    selectedRole === 'patient'
                                        ? 'border-emerald-600 bg-emerald-50 text-emerald-800'
                                        : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50'
                                "
                                @click="changeRole('patient')"
                            >
                                <UserRound class="h-5 w-5" />
                                Paciente
                            </button>
                        </div>
                    </div>

                    <div class="border border-slate-200 bg-white p-4 shadow-sm">
                        <div class="mb-3 flex items-center gap-2 text-sm font-semibold text-slate-800">
                            <Server class="h-4 w-4 text-emerald-700" />
                            Provisionamento
                        </div>

                        <dl class="mb-4 grid gap-2 text-sm">
                            <div class="flex justify-between gap-3">
                                <dt class="text-slate-500">Sala</dt>
                                <dd class="font-mono text-xs text-slate-800">{{ session?.room_id ?? simulation.room_id }}</dd>
                            </div>
                            <div class="flex justify-between gap-3">
                                <dt class="text-slate-500">No SFU</dt>
                                <dd class="font-mono text-xs text-slate-800">{{ session?.sfu_node ?? simulation.sfu_node ?? '-' }}</dd>
                            </div>
                            <div class="flex justify-between gap-3">
                                <dt class="text-slate-500">Estado</dt>
                                <dd class="font-mono text-xs text-slate-800">{{ stage }}</dd>
                            </div>
                        </dl>

                        <div class="grid gap-2">
                            <button
                                type="button"
                                class="inline-flex h-11 items-center justify-center gap-2 bg-slate-950 px-4 text-sm font-semibold text-white transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-50"
                                :disabled="!canProvision"
                                @click="provisionCall"
                            >
                                <MonitorUp class="h-4 w-4" />
                                Sistema iniciar chamada
                            </button>
                            <button
                                type="button"
                                class="inline-flex h-11 items-center justify-center gap-2 bg-emerald-600 px-4 text-sm font-semibold text-white transition hover:bg-emerald-500 disabled:cursor-not-allowed disabled:opacity-50"
                                :disabled="!canConnect"
                                @click="enterCall"
                            >
                                <LogIn class="h-4 w-4" />
                                Entrar como {{ roleLabel }}
                            </button>
                            <button
                                type="button"
                                class="inline-flex h-10 items-center justify-center gap-2 border border-slate-200 px-4 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                                @click="resetSimulation"
                            >
                                <RotateCcw class="h-4 w-4" />
                                Reiniciar teste
                            </button>
                        </div>

                        <p v-if="errorMessage" class="mt-3 border border-red-200 bg-red-50 p-3 text-sm text-red-700">
                            {{ errorMessage }}
                        </p>
                    </div>

                    <div class="min-h-48 border border-slate-200 bg-white p-4 shadow-sm">
                        <div class="mb-3 flex items-center justify-between">
                            <h2 class="text-sm font-semibold text-slate-800">Eventos</h2>
                            <span class="font-mono text-xs text-slate-500">remotos: {{ remoteCount }}</span>
                        </div>
                        <div class="space-y-2">
                            <p v-if="logs.length === 0" class="text-sm text-slate-500">Sem eventos.</p>
                            <p v-for="line in logs" :key="line" class="font-mono text-xs leading-relaxed break-all text-slate-600">
                                {{ line }}
                            </p>
                        </div>
                    </div>
                </aside>

                <section class="min-h-[calc(100vh-2rem)] overflow-hidden border border-slate-900 bg-[#0b2030] shadow-sm">
                    <div v-if="stage === 'connected'" class="flex h-full min-h-[620px] flex-col">
                        <VideoGrid
                            :local-stream="sfu.localStream.value"
                            :remote-streams="sfu.remoteStreams.value"
                            :is-mic-enabled="sfu.isMicEnabled.value"
                            :is-camera-enabled="sfu.isCameraEnabled.value"
                        />
                        <VideoControls
                            :is-mic-enabled="sfu.isMicEnabled.value"
                            :is-camera-enabled="sfu.isCameraEnabled.value"
                            @toggle-mic="sfu.toggleMic()"
                            @toggle-camera="sfu.toggleCamera()"
                            @end="leaveCall"
                            @fullscreen="fullscreen"
                        />
                    </div>

                    <div v-else class="flex h-full min-h-[620px] items-center justify-center p-8 text-center text-white">
                        <div class="max-w-sm">
                            <div class="mx-auto mb-5 flex h-16 w-16 items-center justify-center bg-white/10">
                                <MonitorUp class="h-8 w-8 text-emerald-300" />
                            </div>
                            <h2 class="text-xl font-semibold">Sala aguardando entrada</h2>
                            <p class="mt-2 text-sm text-white/60">
                                {{
                                    stage === 'ready'
                                        ? 'Sala provisionada. Entre como medico ou paciente.'
                                        : 'Acione o sistema para provisionar a sala.'
                                }}
                            </p>
                        </div>
                    </div>
                </section>
            </section>
        </main>
    </div>
</template>
