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
- **[📜 Regras do Sistema](../requirements/SystemRules.md)** - Regras de videoconferência
- **[🏗️ Arquitetura](../architecture/Arquitetura.md)** - Padrões de real-time
- **[📊 Matriz de Requisitos](../index/MatrizRequisitos.md)** - RF004, RF012

### Implementações
- **[VideoCall Events](../../../app/Events/)** - Eventos de sinalização
- **[Broadcasting Config](../../../config/broadcasting.php)** - Configuração WebSockets
- **[Frontend Components](../../../resources/js/components/)** - Interface de vídeo

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

- **RF004** - Realizar Consultas Online (Videoconferência) 🔄
- **RF012** - Videoconferência de Consultas (Tempo Real) 🔄

## 🚧 Status de Implementação

### ✅ Implementado
- Eventos de sinalização
- Configuração WebSockets
- Estrutura básica

### 🔄 Em Desenvolvimento
- Interface de vídeo
- Controles de mídia
- Integração com consultas

### 📋 Planejado
- Gravação de consultas
- Compartilhamento de tela
- Testes de integração

## 🧪 Testes

- **VideoCall Tests** - 📋 Planejado
- **Cobertura**: Eventos, conexões, interface
- **Cenários**: Estabelecimento, falhas, diferentes dispositivos

---

*Última atualização: Dezembro 2024*

