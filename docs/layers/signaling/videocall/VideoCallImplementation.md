# 📹 Implementação de Videoconferência Médica com PeerJS

## 🎯 Visão Geral

Este sistema implementa consultas médicas por videoconferência em tempo real usando:
- **Backend**: Laravel com Broadcasting (WebSockets)
- **Frontend**: Vue.js com PeerJS
- **Arquitetura**: P2P (Peer-to-Peer) para eficiência
- **Contexto**: Telemedicina e consultas médicas online

## 🏗️ Arquitetura do Sistema

### **Fluxo de Funcionamento:**

1. **Iniciação da Chamada:**
   - Usuário A seleciona Usuário B na lista de contatos
   - Frontend envia requisição para `/video-call/request/{user_id}`
   - Laravel armazena `peerId` e dispara evento WebSocket
   - Usuário B recebe notificação em tempo real

2. **Aceitação da Chamada:**
   - Usuário B aceita a chamada
   - Sistema envia status via `/video-call/request/status/{user_id}`
   - Conexão P2P é estabelecida via PeerJS
   - Vídeo/áudio trafegam diretamente entre usuários

## 📁 Estrutura de Arquivos

### **Backend (Laravel):**
```
app/
├── Http/Controllers/VideoCall/
│   └── VideoCallController.php          # Controlador principal
├── Http/Controllers/Settings/
│   └── ProfileController.php            # Controlador de perfil
├── Events/
│   ├── RequestVideoCall.php            # Evento de solicitação
│   ├── RequestVideoCallStatus.php      # Evento de status
│   ├── VideoCallRoomCreated.php        # Evento de criação de sala
│   ├── VideoCallRoomExpired.php         # Evento de expiração de sala
│   ├── VideoCallUserJoined.php          # Evento de entrada na sala
│   └── VideoCallUserLeft.php            # Evento de saída da sala
├── Jobs/
│   ├── CleanupOldVideoCallEvents.php    # Limpeza de eventos antigos
│   ├── ExpireVideoCallRooms.php         # Expiração automática de salas
│   └── UpdateAppointmentFromRoom.php    # Atualização de consulta
├── Models/
│   ├── User.php                        # Modelo de usuário
│   ├── VideoCallRoom.php               # Modelo de sala de videoconferência
│   └── VideoCallEvent.php              # Modelo de evento de videoconferência

routes/
├── web.php                             # Rotas da aplicação
└── channels.php                        # Canais de broadcasting

database/migrations/
├── 2025_11_21_193554_create_video_call_rooms_table.php
└── 2025_11_21_193603_create_video_call_events_table.php
```

### **Frontend (Vue.js):**
```
resources/js/pages/
└── Doctor/
    └── Consultations.vue               # Página de consultas médicas com videoconferência
```

## 🛣️ Rotas Disponíveis

### **Rotas de Videoconferência:**
```php
// Páginas principais
GET  /consultations     # Página de consultas médicas com videoconferência

// APIs de videoconferência
POST /video-call/request/{user}           # Solicitar videoconferência
POST /video-call/request/status/{user}    # Responder solicitação

// Perfil do usuário
GET    /profile         # Editar perfil
PATCH  /profile         # Atualizar perfil
DELETE /profile         # Excluir conta
```

### **Middleware Aplicado:**
- `auth`: Requer autenticação
- `verified`: Requer email verificado

## 🔧 Configuração Necessária

### **1. Dependências Frontend:**
```bash
npm install peerjs
npm install @types/peerjs  # Para TypeScript
```

### **2. Broadcasting (WebSocket):**
Configure no `.env`:
```env
BROADCAST_DRIVER=pusher  # ou redis, log
PUSHER_APP_ID=your_app_id
PUSHER_APP_KEY=your_app_key
PUSHER_APP_SECRET=your_app_secret
PUSHER_HOST=127.0.0.1
PUSHER_PORT=6001
PUSHER_SCHEME=http
```

### **3. Inicialização do Echo (Frontend):**
```javascript
// Em app.js ou bootstrap.js
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;
window.Echo = new Echo({
    broadcaster: 'pusher',
    key: process.env.MIX_PUSHER_APP_KEY,
    cluster: process.env.MIX_PUSHER_APP_CLUSTER,
    forceTLS: true
});
```

## 🔐 Segurança

### **Canais Privados:**
- Cada usuário tem seu canal privado: `video-call.{user_id}`
- Apenas usuários autorizados podem escutar
- Validação automática de permissões

### **Autenticação:**
- Todas as rotas requerem autenticação
- Middleware `auth` e `verified` aplicados
- Validação de usuário em cada requisição

## 🎨 Interface do Usuário

### **Layout:**
- **Sidebar (25%)**: Lista de contatos com avatars
- **Área Principal (75%)**: Interface de chamada
- **Responsivo**: Adapta-se a diferentes tamanhos de tela

### **Estados da Interface:**
1. **Sem Seleção**: Mensagem "Selecione uma Conversa"
2. **Usuário Selecionado**: Botão "Ligar" disponível
3. **Chamada Ativa**: Vídeos local e remoto + botão "Encerrar"

## 🔧 Funcionalidades Implementadas

### **✅ Funcionalidades Principais:**
- ✅ Lista de usuários disponíveis
- ✅ Iniciação de chamadas
- ✅ Aceitação/recusa de chamadas
- ✅ Transmissão de vídeo/áudio P2P
- ✅ Interface responsiva
- ✅ Gerenciamento de estado da chamada
- ✅ Limpeza automática de recursos
- ✅ Salas de videoconferência (VideoCallRoom)
- ✅ Eventos de videoconferência (VideoCallEvent)
- ✅ Expiração automática de salas
- ✅ Integração com appointments

### **🔄 Estados de Chamada:**
- `isCalling: false` - Nenhuma chamada ativa
- `isCalling: true` - Chamada em andamento
- Transições automáticas entre estados

## 🏠 Salas de Videoconferência (VideoCallRoom)

### Funcionalidade
- **Criação Automática**: Salas são criadas automaticamente para consultas
- **Expiração**: Salas expiram automaticamente após período configurado
- **Integração**: Relacionadas com Appointments para rastreamento

### Jobs Relacionados
- **ExpireVideoCallRooms**: Executa periodicamente para expirar salas antigas
- **UpdateAppointmentFromRoom**: Atualiza status de consulta a partir da sala
- **CleanupOldVideoCallEvents**: Remove eventos antigos para manter performance

## 📊 Eventos de Videoconferência (VideoCallEvent)

### Rastreamento
- **Entrada na Sala**: Registra quando usuário entra na videoconferência
- **Saída da Sala**: Registra quando usuário sai da videoconferência
- **Ações**: Registra ações importantes durante a chamada
- **Auditoria**: Todos os eventos são registrados para auditoria e análise

### Limpeza Automática
- **Job Automático**: CleanupOldVideoCallEvents remove eventos antigos
- **Performance**: Mantém banco de dados otimizado
- **Retenção**: Configurável por período (ex: 90 dias)

## 🔗 Integração com Consultas

### Fluxo Integrado
1. **Consulta Agendada**: Sistema prepara sala de videoconferência
2. **Início da Consulta**: Sala é ativada quando consulta inicia
3. **Durante Consulta**: Eventos são registrados em tempo real
4. **Finalização**: Sala expira e consulta é atualizada automaticamente
5. **Limpeza**: Jobs automáticos limpam recursos antigos

### Eventos Relacionados
- **VideoCallRoomCreated**: Disparado quando sala é criada
- **VideoCallRoomExpired**: Disparado quando sala expira
- **VideoCallUserJoined**: Disparado quando usuário entra
- **VideoCallUserLeft**: Disparado quando usuário sai
- **AppointmentStatusChanged**: Disparado quando consulta muda de status

## 🐛 Troubleshooting

### **Problemas Comuns:**

1. **"Cannot access media devices":**
   - Verifique permissões do navegador
   - Certifique-se de usar HTTPS em produção
   - Teste em navegador compatível

2. **"WebSocket connection failed":**
   - Verifique configuração do Pusher
   - Confirme se o servidor de broadcasting está rodando
   - Verifique logs do Laravel

3. **"Peer connection failed":**
   - Verifique conectividade de rede
   - Teste com diferentes usuários
   - Verifique configurações de firewall

### **Logs Úteis:**
```javascript
// No console do navegador
console.log('Peer ID:', peer.id);
console.log('Call status:', isCalling);
console.log('Selected user:', selectedUser);
```

---

**Desenvolvido para Telemedicina Para Todos** 🏥✨
*Documento em: `docs/layers/signaling/videocall/` (Camada de Sinalização)*
