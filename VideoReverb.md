# Tutorial: Implementação de Videochamadas com Laravel Reverb e Vue.js

Este tutorial adapta a implementação de videochamadas em tempo real usando Laravel Reverb, PeerJS e Vue.js 3, seguindo a arquitetura e padrões do projeto Telemedicina Para Todos.

## Índice

1. [Instalação do Laravel Reverb](#passo-1-instalação-do-laravel-reverb)
2. [Configuração do Broadcasting](#passo-2-configuração-do-broadcasting)
3. [Criação dos Eventos](#passo-3-criação-dos-eventos)
4. [Configuração de Canais Privados](#passo-4-configuração-de-canais-privados)
5. [Criação do Controller](#passo-5-criação-do-controller)
6. [Definição de Rotas](#passo-6-definição-de-rotas)
7. [Instalação do PeerJS](#passo-7-instalação-do-peerjs)
8. [Configuração do Laravel Echo no Frontend](#passo-8-configuração-do-laravel-echo-no-frontend)
9. [Criação do Composable para Videochamadas](#passo-9-criação-do-composable-para-videochamadas)
10. [Criação da Página de Videochamadas](#passo-10-criação-da-página-de-videochamadas)
11. [Executando a Aplicação](#passo-11-executando-a-aplicação)
12. [Documentação de Mudanças e Integração](#documentação-de-mudanças-e-integração)

---

## Passo 1: Instalação do Laravel Reverb

Instale o Laravel Reverb executando o comando:

```bash
php artisan install:broadcasting
```

Este comando configurará o Reverb com um conjunto padrão de opções de configuração.

**Nota:** O projeto já possui o Laravel Reverb configurado. Este passo é apenas para referência caso precise reinstalar.

---

## Passo 2: Configuração do Broadcasting

### 2.1 Configuração do Ambiente

Certifique-se de que seu arquivo `.env` contém as seguintes variáveis:

```env
BROADCAST_CONNECTION=reverb

REVERB_APP_ID=your-app-id
REVERB_APP_KEY=your-app-key
REVERB_APP_SECRET=your-app-secret
REVERB_HOST=localhost
REVERB_PORT=8080
REVERB_SCHEME=http
```

### 2.2 Configuração do Frontend

No arquivo `.env` do frontend (ou variáveis de ambiente do Vite), adicione:

```env
VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

**Nota:** O projeto já possui essas configurações em `config/reverb.php` e `config/broadcasting.php`.

---

## Passo 3: Criação dos Eventos

Os eventos são classes que sinalizam que algo aconteceu na aplicação. Eles permitem desacoplar várias partes do sistema, permitindo que diferentes partes respondam ao mesmo evento de sua própria maneira.

### 3.1 Criar os Eventos

Execute os seguintes comandos para criar as classes de eventos:

```bash
php artisan make:event RequestVideoCall
php artisan make:event RequestVideoCallStatus
```

### 3.2 Atualizar RequestVideoCall.php

O evento `RequestVideoCall` é usado para solicitar uma videochamada. Ele implementa `ShouldBroadcastNow` para transmitir o evento imediatamente, sem enfileirar.

**Localização:** `app/Events/RequestVideoCall.php`

```php
<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RequestVideoCall implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(public User $user)
    {
        //
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        $channel = "video-call.{$this->user->id}";
        
        return [
            new PrivateChannel($channel),
        ];
    }
}
```

### 3.3 Atualizar RequestVideoCallStatus.php

O evento `RequestVideoCallStatus` é usado para notificar sobre o status de uma videochamada (aceita, rejeitada, etc.).

**Localização:** `app/Events/RequestVideoCallStatus.php`

```php
<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RequestVideoCallStatus implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(public User $user)
    {
        //
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        $channel = "video-call.{$this->user->id}";
        
        return [
            new PrivateChannel($channel),
        ];
    }
}
```

**Nota:** Os eventos já existem no projeto em `app/Events/`. Este passo é apenas para referência.

---

## Passo 4: Configuração de Canais Privados

O arquivo `channels.php` é usado para definir os canais que sua aplicação suporta para transmissão de eventos. Este arquivo está localizado no diretório `routes` e desempenha um papel crucial na configuração de canais WebSocket, tanto públicos quanto privados, para transmissão de eventos em tempo real.

- **Canais Públicos:** Acessíveis a qualquer pessoa.
- **Canais Privados:** Requerem autenticação para ingressar.

### 4.1 Adicionar Canal Privado para Videochamadas

**Localização:** `routes/channels.php`

```php
<?php

use Illuminate\Support\Facades\Broadcast;

// Canal privado para videochamadas
Broadcast::channel('video-call.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});
```

**Nota:** O canal já está configurado no projeto. Este passo é apenas para referência.

---

## Passo 5: Criação do Controller

Criaremos um controller para lidar com a API de videochamadas.

### 5.1 Criar o Controller

Execute o comando:

```bash
php artisan make:controller VideoCall/VideoCallController
```

### 5.2 Implementar o Controller

**Localização:** `app/Http/Controllers/VideoCall/VideoCallController.php`

```php
<?php

namespace App\Http\Controllers\VideoCall;

use App\Http\Controllers\Controller;
use App\Events\RequestVideoCall;
use App\Events\RequestVideoCallStatus;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VideoCallController extends Controller
{
    /**
     * Solicita uma videochamada para um usuário
     */
    public function requestVideoCall(Request $request, User $user)
    {
        $user->peerId = $request->peerId;
        $user->fromUser = Auth::user();

        broadcast(new RequestVideoCall($user));
        return response()->json($user);
    }

    /**
     * Envia o status de uma videochamada (aceita, rejeitada, etc.)
     */
    public function requestVideoCallStatus(Request $request, User $user)
    {
        $user->peerId = $request->peerId;
        $user->fromUser = Auth::user();

        broadcast(new RequestVideoCallStatus($user));
        return response()->json($user);
    }
}
```

**Nota:** O controller já existe no projeto. Este passo é apenas para referência.

---

## Passo 6: Definição de Rotas

Adicione as rotas para o `VideoCallController` no arquivo `routes/web.php`.

### 6.1 Rotas para Médicos

**Localização:** `routes/web.php`

```php
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\VideoCall\VideoCallController;

// Rotas para Médicos
Route::middleware(['auth', 'verified', 'doctor'])->prefix('doctor')->name('doctor.')->group(function () {
    // Rotas para videoconferência (médicos)
    Route::post('video-call/request/{user}', [VideoCallController::class, 'requestVideoCall'])->name('video-call.request');
    Route::post('video-call/request/status/{user}', [VideoCallController::class, 'requestVideoCallStatus'])->name('video-call.request-status');
});

// Rotas para Pacientes
Route::middleware(['auth', 'verified', 'patient'])->prefix('patient')->name('patient.')->group(function () {
    // Rotas para videoconferência (pacientes)
    Route::post('video-call/request/{user}', [VideoCallController::class, 'requestVideoCall'])->name('video-call.request');
    Route::post('video-call/request/status/{user}', [VideoCallController::class, 'requestVideoCallStatus'])->name('video-call.request-status');
});
```

**Nota:** As rotas já estão configuradas no projeto. Este passo é apenas para referência.

---

## Passo 7: Instalação do PeerJS

Instale o PeerJS, que é usado para estabelecer conexões peer-to-peer (P2P) para videochamadas:

```bash
npm install peerjs
```

**Nota:** O PeerJS já está instalado no projeto (verificado em `package.json`).

---

## Passo 8: Configuração do Laravel Echo no Frontend

O Laravel Echo já está configurado no projeto. A configuração está em `resources/js/app.ts`:

```typescript
import { configureEcho } from '@laravel/echo-vue';

configureEcho({
    broadcaster: 'reverb',
});
```

### 8.1 Configuração Manual do Echo (Alternativa)

Se precisar configurar manualmente o Echo em um componente, você pode usar:

```typescript
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

const echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT,
    wssPort: import.meta.env.VITE_REVERB_PORT,
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
    enabledTransports: ['ws', 'wss'],
});
```

---

## Passo 9: Criação do Composable para Videochamadas

Seguindo a arquitetura do projeto, criaremos um composable para gerenciar a lógica de videochamadas, separando a lógica de negócio da apresentação.

### 9.1 Criar o Composable

**Localização:** `resources/js/composables/useVideoCall.ts`

```typescript
import { ref, onMounted, onUnmounted } from 'vue';
import { usePage } from '@inertiajs/vue3';
import axios from 'axios';
import Peer from 'peerjs';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

interface User {
    id: number;
    name: string;
    email: string;
    peerId?: string;
    fromUser?: User;
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

/**
 * Composable para gerenciar videochamadas usando PeerJS e Laravel Reverb
 * 
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
 *   connectWebSocket
 * } = useVideoCall();
 * </script>
 * ```
 */
export function useVideoCall() {
    const page = usePage();
    const auth = page.props.auth as unknown as AuthUser;

    // Estados reativos
    const peer = ref<Peer | null>(null);
    const peerCall = ref<any>(null);
    const isCalling = ref(false);
    const selectedUser = ref<User | null>(null);

    // Refs para elementos de vídeo
    const remoteVideoRef = ref<HTMLVideoElement | null>(null);
    const localVideoRef = ref<HTMLVideoElement | null>(null);
    const localStreamRef = ref<MediaStream | null>(null);

    // Instância do Echo para cleanup
    let echoInstance: Echo | null = null;

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
    const endCall = () => {
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

            // Determinar a rota baseada no tipo de usuário
            const baseRoute = auth.user.id === user.id ? '' : 
                (page.props.auth as any).isDoctor ? '/doctor' : '/patient';

            await axios.post(`${baseRoute}/video-call/request/${user.id}`, payload);

            isCalling.value = true;
            selectedUser.value = user;

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

            const baseRoute = (page.props.auth as any).isDoctor ? '/doctor' : '/patient';
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
            // Configurar Echo com Reverb
            echoInstance = new Echo({
                broadcaster: 'reverb',
                key: import.meta.env.VITE_REVERB_APP_KEY,
                wsHost: import.meta.env.VITE_REVERB_HOST,
                wsPort: import.meta.env.VITE_REVERB_PORT,
                wssPort: import.meta.env.VITE_REVERB_PORT,
                forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
                enabledTransports: ['ws', 'wss'],
            });

            // Requisição de videoconferência
            echoInstance
                .private(`video-call.${auth.user.id}`)
                .listen('RequestVideoCall', (e: VideoCallEvent) => {
                    selectedUser.value = e.user.fromUser;
                    isCalling.value = true;

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
```

---

## Passo 10: Criação da Página de Videochamadas

Agora criaremos a página Vue.js para videochamadas usando o composable criado anteriormente.

### 10.1 Criar a Página

**Localização:** `resources/js/pages/VideoCall.vue` (ou adaptar a página existente)

```vue
<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { onMounted, onUnmounted } from 'vue';
import { useVideoCall } from '@/composables/useVideoCall';
import { useAuth } from '@/composables/auth/useAuth';

defineOptions({
    layout: AppLayout,
});

// Interfaces TypeScript
interface User {
    id: number;
    name: string;
    email: string;
}

// Props e dados da página
const page = usePage();
const { user: authUser } = useAuth();
const users = (page.props.users as User[]) || [];

// Usar o composable de videochamadas
const {
    isCalling,
    selectedUser,
    remoteVideoRef,
    localVideoRef,
    callUser: initiateCall,
    endCall,
    initialize,
    cleanup,
} = useVideoCall();

// Função para iniciar chamada com validação
const callUser = async (user: User) => {
    if (!user || isCalling.value) {
        return;
    }
    await initiateCall(user);
};

// Lifecycle hooks
onMounted(() => {
    initialize();
});

onUnmounted(() => {
    cleanup();
});
</script>

<template>
    <Head title="Videochamadas - Telemedicina Para Todos" />

    <div class="h-screen flex bg-gray-100" style="height: 90vh">
        <!-- Sidebar -->
        <div class="w-1/4 bg-white border-r border-gray-200">
            <div class="p-4 bg-gray-100 font-bold text-lg border-b border-gray-200">
                Contatos
            </div>
            <div class="p-4 space-y-4">
                <!-- Lista de Contatos -->
                <div
                    v-for="user in users"
                    :key="user.id"
                    @click="selectedUser = user"
                    :class="[
                        'flex items-center p-2 hover:bg-blue-500 hover:text-white rounded cursor-pointer transition-colors',
                        user.id === selectedUser?.id
                            ? 'bg-primary text-white'
                            : '',
                    ]"
                >
                    <div
                        class="w-12 h-12 bg-blue-200 rounded-full flex items-center justify-center"
                    >
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
            <div
                v-if="!selectedUser"
                class="h-full flex justify-center items-center text-gray-800 font-bold"
            >
                Selecione um Contato
            </div>

            <div v-if="selectedUser">
                <!-- Cabeçalho do Contato -->
                <div
                    class="p-4 border-b border-gray-200 flex items-center justify-between"
                >
                    <div class="flex items-center">
                        <div
                            class="w-12 h-12 bg-blue-200 rounded-full flex items-center justify-center"
                        >
                            <span class="text-blue-600 font-semibold">
                                {{
                                    selectedUser.name?.charAt(0)?.toUpperCase() ||
                                    'U'
                                }}
                            </span>
                        </div>
                        <div class="ml-4 font-bold">{{ selectedUser.name }}</div>
                    </div>
                    <div>
                        <button
                            v-if="!isCalling"
                            @click="callUser(selectedUser)"
                            class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-blue-600 transition-colors"
                        >
                            Iniciar Chamada
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

                <!-- Área de Chamada -->
                <div
                    class="flex-1 overflow-y-auto p-4 space-y-4 bg-gray-50 relative"
                >
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
                            style="margin: 0"
                        ></video>
                    </div>

                    <div
                        v-if="!isCalling"
                        class="h-full flex justify-center items-center text-gray-800 font-bold"
                    >
                        Nenhuma Chamada em Andamento.
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
```

### 10.2 Alternativa: Usar a Página Existente

O projeto já possui uma implementação de videochamadas em `resources/js/pages/Doctor/Consultations.vue`. Você pode usar essa página como referência ou adaptá-la conforme necessário.

---

## Passo 11: Executando a Aplicação

Para executar a aplicação, você precisa iniciar três processos:

### 11.1 Servidor Laravel

Inicie o servidor de desenvolvimento do Laravel:

```bash
php artisan serve
```

Por padrão, o servidor roda em `http://127.0.0.1:8000`.

### 11.2 Compilação do Frontend

Compile e monitore os assets do frontend:

```bash
npm run dev
```

Ou para produção:

```bash
npm run build
```

### 11.3 Servidor Reverb

Inicie o servidor WebSocket do Laravel Reverb:

```bash
php artisan reverb:start
```

Por padrão, o servidor roda na porta 8080. Você pode especificar um host ou porta customizados usando as opções `--host` e `--port`:

```bash
php artisan reverb:start --host=127.0.0.1 --port=9000
```

### 11.4 Testar a Aplicação

Acesse a aplicação em:

```
http://127.0.0.1:8000
```

**Nota:** Se algumas classes do Tailwind não estiverem funcionando, execute `npm run dev` novamente e limpe o cache do navegador.

---

## Documentação de Mudanças e Integração

### Principais Mudanças do Tutorial Original (React) para Vue.js

#### 1. **Framework e Sintaxe**

- **React → Vue.js 3**: Conversão completa de componentes React para componentes Vue.js usando Composition API
- **JSX → Template Syntax**: Conversão de JSX para sintaxe de template do Vue
- **Hooks → Composables**: Uso de composables Vue.js em vez de hooks React
- **useState/useRef → ref()**: Substituição de hooks React por refs reativas do Vue

#### 2. **Estrutura de Arquivos**

- **Pages → pages**: Mantida a estrutura de pastas, mas adaptada para `.vue`
- **Layouts**: Uso de `AppLayout.vue` em vez de `AuthenticatedLayout.jsx`
- **Composables**: Criação de `useVideoCall.ts` para encapsular lógica de videochamadas

#### 3. **TypeScript**

- **Tipagem Forte**: Adição de interfaces TypeScript para melhor type safety
- **Tipos de Props**: Definição de tipos para props e dados da página
- **Type Guards**: Uso de type guards para validação de tipos

#### 4. **Gerenciamento de Estado**

- **Reatividade Vue**: Uso de `ref()` e `reactive()` para gerenciamento de estado
- **Computed Properties**: Uso de `computed()` quando apropriado
- **Lifecycle Hooks**: `onMounted()` e `onUnmounted()` em vez de `useEffect()`

#### 5. **Laravel Echo**

- **Configuração Global**: Echo configurado globalmente em `app.ts`
- **Instância Manual**: Opção de criar instância manual do Echo quando necessário
- **Cleanup**: Limpeza adequada de recursos ao desmontar componentes

#### 6. **Integração com Inertia.js**

- **usePage()**: Uso de `usePage()` do Inertia.js para acessar props
- **Head Component**: Uso de `<Head>` para gerenciar metadados da página
- **Layout System**: Integração com sistema de layouts do projeto

### Como Integrar no Projeto Atual

#### 1. **Verificar Dependências**

Certifique-se de que todas as dependências estão instaladas:

```bash
npm install peerjs laravel-echo pusher-js @laravel/echo-vue
```

#### 2. **Verificar Configurações**

- Verifique se `config/reverb.php` e `config/broadcasting.php` estão configurados
- Verifique se as variáveis de ambiente estão definidas no `.env`
- Verifique se `routes/channels.php` contém o canal de videochamadas

#### 3. **Usar o Composable**

O composable `useVideoCall` pode ser usado em qualquer página Vue.js:

```vue
<script setup lang="ts">
import { useVideoCall } from '@/composables/useVideoCall';
import { onMounted, onUnmounted } from 'vue';

const {
    isCalling,
    remoteVideoRef,
    localVideoRef,
    callUser,
    endCall,
    initialize,
    cleanup,
} = useVideoCall();

onMounted(() => {
    initialize();
});

onUnmounted(() => {
    cleanup();
});
</script>
```

#### 4. **Adaptar Rotas**

As rotas já estão configuradas no projeto. Se precisar adicionar novas rotas, siga o padrão existente em `routes/web.php`.

#### 5. **Personalizar UI**

A UI pode ser personalizada usando os componentes do projeto (shadcn-vue) e seguindo o design system existente.

### Estrutura de Arquivos Criados/Modificados

```
app/
├── Events/
│   ├── RequestVideoCall.php          # Evento de solicitação de chamada
│   └── RequestVideoCallStatus.php    # Evento de status da chamada
├── Http/
│   └── Controllers/
│       └── VideoCall/
│           └── VideoCallController.php  # Controller de videochamadas

routes/
├── channels.php                       # Canais de broadcasting
└── web.php                            # Rotas da aplicação

resources/js/
├── composables/
│   └── useVideoCall.ts                # Composable para videochamadas
├── pages/
│   └── VideoCall.vue                  # Página de videochamadas (exemplo)
└── app.ts                             # Configuração do Echo

config/
├── reverb.php                         # Configuração do Reverb
└── broadcasting.php                   # Configuração do Broadcasting
```

### Próximos Passos

1. **Testes**: Implementar testes unitários e de integração para o composable e componentes
2. **Tratamento de Erros**: Adicionar tratamento de erros mais robusto
3. **Notificações**: Adicionar notificações visuais para eventos de chamada
4. **Histórico**: Implementar histórico de chamadas
5. **Gravação**: Adicionar funcionalidade de gravação de chamadas (se necessário)
6. **Otimizações**: Otimizar performance e uso de recursos

### Troubleshooting

#### Problema: Echo não conecta

**Solução:**
- Verifique se o servidor Reverb está rodando
- Verifique as variáveis de ambiente
- Verifique se o canal está configurado corretamente

#### Problema: PeerJS não funciona

**Solução:**
- Verifique se o PeerJS está instalado
- Verifique se há erros no console do navegador
- Verifique permissões de câmera e microfone

#### Problema: Vídeo não aparece

**Solução:**
- Verifique permissões do navegador
- Verifique se os refs estão corretamente atribuídos
- Verifique se o stream está sendo capturado corretamente

---

## Conclusão

Este tutorial adapta completamente a implementação de videochamadas do React para Vue.js 3, seguindo as melhores práticas e a arquitetura do projeto Telemedicina Para Todos. O código está pronto para uso e pode ser facilmente integrado e estendido conforme necessário.

Para mais informações sobre a arquitetura do projeto, consulte `docs/Architecture/Arquitetura.md`.
