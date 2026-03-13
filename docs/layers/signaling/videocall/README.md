# 📹 Módulo de Videochamadas

Este módulo implementa o sistema de videoconferência em tempo real para consultas médicas.

## 📁 Arquivos

- **[🔧 Implementação de Videochamadas](VideoCallImplementation.md)** - Sistema de vídeo em tempo real
- **[📋 Tarefas de Videochamadas](VideoCallTasks.md)** - Checklist de implementação

## 🎯 Funcionalidades

### Videochamada
- **Conexão P2P** usando WebRTC
- **Sinalização** via WebSockets (Laravel Reverb)
- **Estabelecimento** de conexão segura
- **Controle** de mídia (áudio/vídeo)

### Integração
- **Solicitação** de chamada
- **Aceite/Rejeição** da chamada
- **Status** em tempo real
- **Eventos** de conexão

### Recursos
- **PeerJS** para WebRTC
- **Canais privados** para sinalização
- **Eventos customizados** para controle
- **Interface responsiva** para dispositivos

## 🔗 Relacionamentos

### Dependências
- **[📜 Regras do Sistema](../../architecture-governance/requirements/SystemRules.md)** - Regras de videoconferência
- **[🏗️ Arquitetura](../../architecture-governance/Architecture/Arquitetura.md)** - Padrões de real-time
- **[📊 Matriz de Requisitos](../../../index/MatrizRequisitos.md)** - RF004, RF012

### Implementações
- **[VideoCall Controller](../../../../app/Http/Controllers/VideoCall/VideoCallController.php)** - Controlador principal
- **[VideoCall Events](../../../../app/Events/)** - Eventos de sinalização e sala
- **[VideoCall Models](../../../../app/Models/)** - VideoCallRoom, VideoCallEvent
- **[VideoCall Jobs](../../../../app/Jobs/)** - Limpeza e expiração automática
- **[Broadcasting Config](../../../../config/broadcasting.php)** - Configuração WebSockets
- **[Frontend Components](../../../../resources/js/components/)** - Interface de vídeo

## 🏗️ Arquitetura

### Fluxo de Videochamada
1. **Solicitação** → Usuário solicita chamada
2. **Evento** → RequestVideoCall disparado
3. **Notificação** → Destinatário notificado
4. **Resposta** → Aceite/rejeição
5. **Conexão** → WebRTC P2P estabelecido

### Componentes Técnicos
- **PeerJS** - Wrapper WebRTC
- **Laravel Reverb** - Servidor WebSockets
- **Echo.js** - Cliente WebSocket
- **WebRTC** - Conexão P2P

## 📊 Requisitos Implementados

- **RF004** - Realizar Consultas Online (Videoconferência) ✅
- **RF012** - Videoconferência de Consultas (Tempo Real) ✅

## 🚧 Status de Implementação

### ✅ Implementado
- Eventos de sinalização (RequestVideoCall, RequestVideoCallStatus)
- Eventos de sala (VideoCallRoomCreated, VideoCallRoomExpired, VideoCallUserJoined, VideoCallUserLeft)
- Configuração WebSockets (Laravel Reverb)
- Salas de videoconferência (VideoCallRoom)
- Eventos de videoconferência (VideoCallEvent)
- Jobs automáticos (CleanupOldVideoCallEvents, ExpireVideoCallRooms, UpdateAppointmentFromRoom)
- Integração completa com consultas (Appointments)
- Interface de vídeo P2P (PeerJS)
- Controles de mídia (áudio/vídeo)

### 🔄 Em Desenvolvimento
- Melhorias de UX na interface
- Dashboard de métricas de videoconferência

### 📋 Planejado
- **Migração para MediaSoup (SFU):** Remoção do P2P (PeerJS), WebSocket próprio para sinalização de mídia, recriação do backend e frontend. Ver **[Migração P2P → MediaSoup](../../../videocall/MIGRACAO_P2P_PARA_MEDIASOUP.md)**.
- Gravação de consultas
- Compartilhamento de tela
- Testes de integração completos

## 🧪 Testes

- **VideoCall Tests** - 📋 Planejado
- **Cobertura**: Eventos, conexões, interface
- **Cenários**: Estabelecimento, falhas, diferentes dispositivos

---

*Última atualização: Janeiro 2025*
*Versão: 2.0*
*Localização: `docs/layers/signaling/videocall/` (Camada de Sinalização)*
