# 💬 Sistema de Mensagens - Documentação Completa

*Documento em: `docs/layers/signaling/messages/` (Camada de Sinalização)*

## 📑 Sumário Navegável

- [🎯 Objetivo](#-objetivo)
- [📊 Visão Geral](#-visão-geral)
- [🏗️ Arquitetura](#️-arquitetura)
- [🔧 Backend](#-backend)
- [🎨 Frontend](#-frontend)
- [⚡ Broadcasting em Tempo Real](#-broadcasting-em-tempo-real)
- [⚖️ Regras de Negócio](#️-regras-de-negócio)
- [🔄 Fluxo de Dados](#-fluxo-de-dados)
- [🔐 Segurança](#-segurança)
- [📝 Estrutura de Dados](#-estrutura-de-dados)
- [🚀 Como Usar](#-como-usar)
- [🧪 Testes](#-testes)
- [📚 Referências](#-referências)

---

## 🎯 Objetivo

O sistema de mensagens permite comunicação em tempo real entre médicos e pacientes que possuem relacionamento através de consultas (appointments). O sistema garante que apenas usuários com histórico de consultas possam trocar mensagens, mantendo a privacidade e segurança das comunicações.

### Principais Objetivos:

1. **Comunicação Eficiente**: Permitir troca de mensagens entre médicos e pacientes
2. **Tempo Real**: Atualização instantânea via WebSockets (Laravel Reverb)
3. **Segurança**: Apenas usuários com appointments podem trocar mensagens
4. **Histórico Completo**: Persistência de todas as mensagens no banco de dados
5. **Notificações**: Contador de mensagens não lidas
6. **Interface Intuitiva**: Interface similar a aplicativos de mensagens modernos

---

## 📊 Visão Geral

O sistema de mensagens é composto por:

- **Backend**: Modelos, Services, Controllers, Events e Broadcasting
- **Frontend**: Composables Vue.js e componentes de interface
- **WebSockets**: Laravel Reverb para comunicação em tempo real
- **Validação**: Regras de negócio para garantir segurança

### Fluxo Simplificado:

```
Paciente/Médico → Envia Mensagem → Backend Valida → Salva no BD → 
Broadcast via WebSocket → Destinatário Recebe → Atualiza Interface
```

---

## 🏗️ Arquitetura

### Camadas do Sistema:

```
┌─────────────────────────────────────────┐
│         Frontend (Vue.js)               │
│  - Patient/Messages.vue                 │
│  - Doctor/Messages.vue                  │
│  - useMessages.ts (Composable)          │
└─────────────────────────────────────────┘
                    ↕
┌─────────────────────────────────────────┐
│      WebSockets (Laravel Reverb)        │
│  - Canal Privado: messages.{userId}      │
│  - Event: MessageSent                    │
└─────────────────────────────────────────┘
                    ↕
┌─────────────────────────────────────────┐
│         Backend (Laravel)               │
│  - MessageService                       │
│  - MessageController (API)              │
│  - PatientMessagesController            │
│  - DoctorMessagesController             │
└─────────────────────────────────────────┘
                    ↕
┌─────────────────────────────────────────┐
│         Banco de Dados                  │
│  - messages (tabela)                     │
│  - appointments (validação)              │
└─────────────────────────────────────────┘
```

---

## 🔧 Backend

### Model: `Message`

**Localização**: `app/Models/Message.php`

**Campos**:
- `id` (UUID) - Chave primária
- `sender_id` (UUID) - ID do usuário remetente
- `receiver_id` (UUID) - ID do usuário destinatário
- `content` (TEXT) - Conteúdo da mensagem
- `appointment_id` (UUID, nullable) - ID do appointment relacionado
- `read_at` (TIMESTAMP, nullable) - Data/hora de leitura
- `created_at`, `updated_at`, `deleted_at` (Soft Deletes)

**Relacionamentos**:
- `sender()` - BelongsTo User
- `receiver()` - BelongsTo User
- `appointment()` - BelongsTo Appointments

**Métodos Úteis**:
- `markAsRead()` - Marcar mensagem como lida
- `isRead()` - Verificar se foi lida
- `scopeBetweenUsers()` - Buscar mensagens entre dois usuários
- `scopeUnreadFor()` - Buscar mensagens não lidas

### Service: `MessageService`

**Localização**: `app/Services/MessageService.php`

**Métodos Principais**:

#### `sendMessage(string $receiverId, string $content, ?string $appointmentId = null): Message`
Envia uma mensagem validando se os usuários podem trocar mensagens.

**Validações**:
- Verifica se há appointment entre os usuários
- Valida se são médico e paciente
- Dispara evento de broadcasting

#### `getMessagesBetweenUsers(string $otherUserId, ?int $limit = 50, ?string $beforeMessageId = null)`
Busca mensagens entre o usuário atual e outro usuário.

**Parâmetros**:
- `$otherUserId` - ID do outro usuário
- `$limit` - Limite de mensagens (padrão: 50)
- `$beforeMessageId` - Para paginação (buscar mensagens anteriores)

#### `getConversations()`
Lista todas as conversas do usuário atual baseadas em appointments.

**Retorna**:
- Array com informações de cada conversa
- Última mensagem (se houver)
- Contador de não lidas
- Nome e avatar do outro usuário

#### `markMessagesAsRead(string $otherUserId): int`
Marca todas as mensagens de um usuário como lidas.

#### `getUnreadCount(): int`
Retorna contador total de mensagens não lidas.

### Controllers

#### `Api/MessageController`
**Localização**: `app/Http/Controllers/Api/MessageController.php`

**Endpoints**:
- `GET /api/messages/conversations` - Listar conversas
- `GET /api/messages/{userId}` - Buscar mensagens com usuário
- `POST /api/messages` - Enviar mensagem
- `POST /api/messages/{userId}/read` - Marcar como lidas
- `GET /api/messages/unread/count` - Contar não lidas

#### `PatientMessagesController`
**Localização**: `app/Http/Controllers/Patient/PatientMessagesController.php`

Renderiza a página de mensagens do paciente com conversas do backend.

#### `DoctorMessagesController`
**Localização**: `app/Http/Controllers/Doctor/DoctorMessagesController.php`

Renderiza a página de mensagens do médico com conversas do backend.

### Event: `MessageSent`

**Localização**: `app/Events/MessageSent.php`

**Implementa**: `ShouldBroadcastNow` (broadcast imediato)

**Canais**:
- `messages.{sender_id}` - Canal privado do remetente
- `messages.{receiver_id}` - Canal privado do destinatário

**Dados Broadcastados**:
- Dados completos da mensagem
- Informações do sender e receiver
- Timestamps formatados

### Rotas

**Localização**: `routes/web.php`

```php
Route::prefix('api')->group(function () {
    Route::get('messages/conversations', [MessageController::class, 'conversations']);
    Route::get('messages/{userId}', [MessageController::class, 'messages']);
    Route::post('messages', [MessageController::class, 'store']);
    Route::post('messages/{userId}/read', [MessageController::class, 'markAsRead']);
    Route::get('messages/unread/count', [MessageController::class, 'unreadCount']);
});
```

### Canais de Broadcasting

**Localização**: `routes/channels.php`

```php
Broadcast::channel('messages.{id}', function ($user, $id) {
    return (string) $user->id === (string) $id;
});
```

---

## 🎨 Frontend

### Composable: `useMessages`

**Localização**: `resources/js/composables/useMessages.ts`

**Estado Gerenciado**:
- `conversations` - Lista de conversas
- `messages` - Mensagens da conversa atual
- `selectedConversationId` - ID da conversa selecionada
- `isLoading` - Estado de carregamento
- `isSending` - Estado de envio
- `error` - Mensagens de erro
- `unreadCount` - Contador de não lidas

**Métodos Principais**:

#### `loadConversations()`
Carrega lista de conversas via API.

#### `loadMessages(userId: string)`
Carrega mensagens de uma conversa específica.

#### `sendMessage(receiverId: string, content: string, appointmentId?: string)`
Envia uma mensagem. **Nota**: Não adiciona mensagem localmente - aguarda broadcast para evitar duplicação.

#### `markAsRead(userId: string)`
Marca mensagens como lidas.

#### `setupRealtimeListener()`
Configura escuta de eventos WebSocket via Laravel Echo.

#### `handleNewMessage(data: any)`
Processa nova mensagem recebida via WebSocket:
- Adiciona à lista se for da conversa atual
- Atualiza última mensagem na lista de conversas
- Atualiza contador de não lidas

### Componentes Vue

#### `Patient/Messages.vue`
**Localização**: `resources/js/pages/Patient/Messages.vue`

Interface de mensagens para pacientes:
- Lista de conversas (médicos com appointments)
- Área de mensagens
- Input para enviar mensagens
- Busca de conversas

**Dados**:
- Recebe `conversations` via props do Inertia
- Usa composable `useMessages` para funcionalidades

#### `Doctor/Messages.vue`
**Localização**: `resources/js/pages/Doctor/Messages.vue`

Interface de mensagens para médicos:
- Mesma estrutura do componente de paciente
- Lista de conversas (pacientes com appointments)

---

## ⚡ Broadcasting em Tempo Real

### Configuração

O sistema usa **Laravel Reverb** para WebSockets.

**Configuração no Frontend**:
```typescript
echo = new Echo({
    broadcaster: 'reverb',
    key: reverbConfig.key,
    wsHost: reverbConfig.host,
    wsPort: reverbConfig.port,
    wssPort: reverbConfig.port,
    forceTLS: reverbConfig.scheme === 'https',
    enabledTransports: ['ws', 'wss'],
});
```

**Canal Privado**:
```typescript
echo.private(`messages.${currentUserId}`)
    .listen('.message.sent', (data: any) => {
        handleNewMessage(data);
    });
```

### Fluxo de Broadcasting

1. **Mensagem Enviada**:
   - `MessageService::sendMessage()` cria mensagem
   - Dispara evento `MessageSent`

2. **Event Broadcast**:
   - Event é transmitido para canais privados
   - `messages.{sender_id}` e `messages.{receiver_id}`

3. **Frontend Recebe**:
   - Laravel Echo recebe evento
   - `handleNewMessage()` processa dados
   - Interface atualizada automaticamente

### Evitando Duplicação

O sistema evita duplicação de mensagens:
- Remetente **não** adiciona mensagem localmente ao enviar
- Aguarda broadcast do próprio canal
- Verifica se mensagem já existe antes de adicionar

---

## ⚖️ Regras de Negócio

### 1. Restrição de Conversas

**Regra**: Apenas médicos e pacientes com appointments podem trocar mensagens.

**Validação**:
- Verifica se há pelo menos um appointment entre os usuários
- **Qualquer status** de appointment permite mensagens:
  - `scheduled` (agendada)
  - `in_progress` (em andamento)
  - `completed` (completada)
  - `cancelled` (cancelada)
  - `no_show` (não compareceu)
  - `rescheduled` (reagendada)

**Implementação**: `MessageService::validateUsersCanMessage()`

### 2. Validação de Mensagens

**Regras**:
- Conteúdo obrigatório (1-5000 caracteres)
- Destinatário deve existir
- Appointment (se fornecido) deve existir e estar relacionado

**Implementação**: `StoreMessageRequest`

### 3. Lista de Conversas

**Regra**: Conversas são baseadas em appointments, não apenas em mensagens.

**Comportamento**:
- Mostra todos os médicos/pacientes com appointments
- Se houver mensagens, mostra última mensagem
- Se não houver mensagens, mostra "Nenhuma mensagem ainda"
- Ordena por última mensagem ou appointment (mais recente primeiro)

**Implementação**: `MessageService::getConversations()`

### 4. Marcação de Leitura

**Regra**: Mensagens são marcadas como lidas ao abrir conversa.

**Comportamento**:
- Ao selecionar conversa, marca todas como lidas
- Atualiza contador de não lidas
- Atualiza interface imediatamente

---

## 🔄 Fluxo de Dados

### Envio de Mensagem

```
1. Usuário digita mensagem e clica "Enviar"
   ↓
2. Frontend: sendMessage() chama API
   ↓
3. Backend: MessageService::sendMessage()
   - Valida relação entre usuários
   - Cria mensagem no banco
   - Dispara evento MessageSent
   ↓
4. Event é broadcastado via WebSocket
   ↓
5. Frontend recebe evento
   - handleNewMessage() processa
   - Adiciona à lista de mensagens
   - Atualiza última mensagem na conversa
   ↓
6. Interface atualizada automaticamente
```

### Carregamento de Conversas

```
1. Página carrega
   ↓
2. Controller busca conversas via MessageService
   ↓
3. Service busca:
   - Appointments do usuário
   - Última mensagem de cada conversa (se houver)
   - Contador de não lidas
   ↓
4. Dados passados para Inertia
   ↓
5. Frontend renderiza lista de conversas
```

### Recebimento em Tempo Real

```
1. Outro usuário envia mensagem
   ↓
2. Event MessageSent é broadcastado
   ↓
3. Laravel Echo recebe no canal privado
   ↓
4. handleNewMessage() é chamado
   ↓
5. Se for da conversa atual:
   - Adiciona mensagem à lista
   - Scroll automático
   ↓
6. Se não for da conversa atual:
   - Atualiza última mensagem na lista
   - Incrementa contador de não lidas
```

---

## 🔐 Segurança

### Autenticação

- Todas as rotas protegidas por middleware `auth` e `verified`
- Canais privados verificam ID do usuário

### Autorização

- Validação de appointments antes de permitir mensagens
- Verificação de relacionamento médico-paciente
- Acesso apenas a próprias mensagens

### Validação de Dados

- Form Request valida entrada
- Sanitização de conteúdo
- Limite de caracteres (5000)

### Privacidade

- Mensagens apenas entre usuários com appointments
- Soft deletes para recuperação
- Canais privados por usuário

---

## 📝 Estrutura de Dados

### Tabela: `messages`

```sql
CREATE TABLE messages (
    id UUID PRIMARY KEY,
    sender_id UUID NOT NULL REFERENCES users(id),
    receiver_id UUID NOT NULL REFERENCES users(id),
    content TEXT NOT NULL,
    appointment_id UUID NULL REFERENCES appointments(id),
    read_at TIMESTAMP NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP NULL
);

-- Índices
INDEX (sender_id, receiver_id, created_at)
INDEX (receiver_id, read_at)
INDEX (appointment_id)
```

### Interface TypeScript: `Message`

```typescript
interface Message {
    id: string;
    sender_id: string;
    receiver_id: string;
    content: string;
    read_at: string | null;
    created_at: string;
    sender?: {
        id: string;
        name: string;
        avatar_path: string | null;
    };
    receiver?: {
        id: string;
        name: string;
        avatar_path: string | null;
    };
}
```

### Interface TypeScript: `Conversation`

```typescript
interface Conversation {
    id: string;
    name: string;
    avatar: string | null;
    lastMessage: string;
    lastMessageTime: string | null;
    unread: number;
}
```

---

## 🚀 Como Usar

### Para Desenvolvedores

#### 1. Iniciar Servidor Reverb

```bash
php artisan reverb:start
```

#### 2. Configurar Variáveis de Ambiente

```env
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=app-id
REVERB_APP_KEY=app-key
REVERB_APP_SECRET=app-secret
REVERB_HOST=localhost
REVERB_PORT=8080
REVERB_SCHEME=http

VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

#### 3. Executar Migrations

```bash
php artisan migrate
```

#### 4. Usar no Frontend

```typescript
import { useMessages } from '@/composables/useMessages';

const {
    conversations,
    messages,
    sendMessage,
    loadMessages,
    markAsRead,
} = useMessages();
```

### Para Usuários

#### Paciente

1. Acesse `/patient/messages`
2. Veja lista de médicos com appointments
3. Selecione um médico
4. Digite e envie mensagens
5. Mensagens aparecem em tempo real

#### Médico

1. Acesse `/doctor/messages`
2. Veja lista de pacientes com appointments
3. Selecione um paciente
4. Digite e envie mensagens
5. Mensagens aparecem em tempo real

---

## 🧪 Testes

### Testes Manuais

1. **Teste de Envio**:
   - Envie mensagem de um usuário
   - Verifique se aparece no outro usuário sem refresh

2. **Teste de Conversas**:
   - Verifique se aparecem médicos/pacientes com appointments
   - Mesmo sem mensagens, conversa deve aparecer

3. **Teste de Não Lidas**:
   - Envie mensagem para usuário offline
   - Usuário deve ver contador ao voltar

4. **Teste de Validação**:
   - Tente enviar mensagem para usuário sem appointment
   - Deve retornar erro

### Endpoints para Teste

```bash
# Listar conversas
GET /api/messages/conversations

# Buscar mensagens
GET /api/messages/{userId}

# Enviar mensagem
POST /api/messages
{
  "receiver_id": "uuid",
  "content": "Texto da mensagem"
}

# Marcar como lidas
POST /api/messages/{userId}/read

# Contar não lidas
GET /api/messages/unread/count
```

---

## 📚 Referências

### Arquivos Principais

- **Model**: `app/Models/Message.php`
- **Service**: `app/Services/MessageService.php`
- **Controllers**:
  - `app/Http/Controllers/Api/MessageController.php`
  - `app/Http/Controllers/Patient/PatientMessagesController.php`
  - `app/Http/Controllers/Doctor/DoctorMessagesController.php`
- **Event**: `app/Events/MessageSent.php`
- **Composable**: `resources/js/composables/useMessages.ts`
- **Components**:
  - `resources/js/pages/Patient/Messages.vue`
  - `resources/js/pages/Doctor/Messages.vue`

### Documentação Externa

- [Laravel Broadcasting](https://laravel.com/docs/broadcasting)
- [Laravel Reverb](https://reverb.laravel.com)
- [Laravel Echo](https://laravel.com/docs/broadcasting#client-side-installation)

---

## 🚀 Melhorias Avançadas

O sistema inclui melhorias avançadas para garantia de entrega, performance e escalabilidade:

- ✅ **Delivery Semantics**: Status de mensagens (sending, sent, delivered, failed)
- ✅ **Retry Automático**: Reenvio automático em caso de falha
- ✅ **Reconexão WebSocket**: Reconexão automática e recuperação de mensagens perdidas
- ✅ **Indexação Avançada**: Índices otimizados para performance
- ✅ **Paginação Reversa**: Paginação eficiente estilo Slack
- ⏳ **Notificações Push**: Documentado para implementação futura

**Documentação Completa**: Veja [MELHORIAS_AVANCADAS.md](./MELHORIAS_AVANCADAS.md) para detalhes.

---

**Última Atualização**: Novembro 2025  
**Versão**: 2.0  
**Status**: ✅ Implementado e Funcional com Melhorias Avançadas
