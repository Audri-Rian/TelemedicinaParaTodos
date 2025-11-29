# Diagrama de Fluxo de Videoconferência - Telemedicina Para Todos

## Fluxo de Videoconferência WebRTC

Este diagrama mostra como funciona a videoconferência em tempo real usando WebRTC e Laravel Reverb.

```mermaid
sequenceDiagram
    participant D as Doctor
    participant DF as Doctor Frontend
    participant P as Patient
    participant PF as Patient Frontend
    participant C as VideoCallController
    participant E as Events
    participant R as Laravel Reverb
    participant DB as Database
    participant Peer as PeerJS Server

    Note over D,Peer: Início da Consulta
    
    D->>DF: Acessa detalhes da consulta
    DF->>C: GET /doctor/consultations/{id}
    C->>DB: Busca consulta
    DB->>C: Retorna consulta (status: SCHEDULED)
    C->>DF: Retorna dados da consulta
    DF->>D: Exibe botão "Iniciar Consulta"
    
    D->>DF: Clica em "Iniciar Consulta"
    DF->>C: POST /doctor/consultations/{id}/start
    C->>DB: Atualiza status para IN_PROGRESS
    C->>E: Dispara VideoCallRoomCreated
    E->>R: Broadcast evento
    R->>PF: Notifica paciente (WebSocket)
    C->>DF: Retorna sala criada
    
    Note over D,Peer: Configuração WebRTC
    
    DF->>Peer: Conecta ao PeerJS
    Peer->>DF: Retorna Peer ID do Doctor
    PF->>Peer: Conecta ao PeerJS
    Peer->>PF: Retorna Peer ID do Patient
    
    Note over D,Peer: Solicitação de Chamada
    
    DF->>C: POST /video-call/request/{patientId}
    Note right of C: Envia Peer ID do Doctor
    C->>E: Dispara RequestVideoCall
    E->>R: Broadcast RequestVideoCall
    R->>PF: Recebe evento (Laravel Echo)
    PF->>P: Exibe notificação de chamada
    
    P->>PF: Aceita chamada
    PF->>C: POST /video-call/accept
    C->>E: Dispara RequestVideoCallStatus
    E->>R: Broadcast status: accepted
    R->>DF: Notifica Doctor
    
    Note over D,Peer: Conexão WebRTC P2P
    
    DF->>PF: Envia Peer ID via WebSocket
    PF->>DF: Envia Peer ID via WebSocket
    DF->>Peer: Inicia conexão P2P
    PF->>Peer: Aceita conexão P2P
    Peer->>DF: Conexão estabelecida
    Peer->>PF: Conexão estabelecida
    
    DF->>DF: Solicita acesso câmera/microfone
    PF->>PF: Solicita acesso câmera/microfone
    DF->>PF: Transmite vídeo/áudio (P2P)
    PF->>DF: Transmite vídeo/áudio (P2P)
    
    Note over D,Peer: Durante a Videoconferência
    
    DF->>E: Dispara VideoCallUserJoined (Doctor)
    PF->>E: Dispara VideoCallUserJoined (Patient)
    E->>R: Broadcast eventos
    R->>PF: Notifica que Doctor entrou
    R->>DF: Notifica que Patient entrou
    
    loop Durante a consulta
        DF->>PF: Streaming de vídeo/áudio
        PF->>DF: Streaming de vídeo/áudio
        DF->>E: Eventos de controle (mute/unmute)
        E->>R: Broadcast eventos
        R->>PF: Atualiza controles
    end
    
    Note over D,Peer: Finalização
    
    D->>DF: Finaliza consulta
    DF->>C: POST /doctor/consultations/{id}/finalize
    C->>DB: Atualiza status para COMPLETED
    C->>E: Dispara VideoCallRoomExpired
    E->>R: Broadcast expiração
    R->>PF: Notifica expiração
    
    DF->>Peer: Fecha conexão P2P
    PF->>Peer: Fecha conexão P2P
    DF->>E: Dispara VideoCallUserLeft (Doctor)
    PF->>E: Dispara VideoCallUserLeft (Patient)
    E->>R: Broadcast eventos
    R->>PF: Notifica que Doctor saiu
    R->>DF: Notifica que Patient saiu
```

## Componentes da Videoconferência

### Frontend
- **PeerJS**: Biblioteca WebRTC para conexão P2P
- **Laravel Echo**: Cliente para receber eventos WebSocket
- **Vue.js Components**: Interface de vídeo

### Backend
- **VideoCallController**: Gerencia requisições de chamada
- **Events**: Eventos de videoconferência
  - `RequestVideoCall`: Solicitação de chamada
  - `RequestVideoCallStatus`: Status da chamada
  - `VideoCallRoomCreated`: Sala criada
  - `VideoCallRoomExpired`: Sala expirada
  - `VideoCallUserJoined`: Usuário entrou
  - `VideoCallUserLeft`: Usuário saiu

### Infraestrutura
- **Laravel Reverb**: Servidor WebSocket
- **PeerJS Server**: Servidor de sinalização WebRTC

## Fluxo de Conexão WebRTC

1. **Sinalização**: Via Laravel Reverb (WebSocket)
2. **Conexão P2P**: Estabelecida via PeerJS
3. **Streaming**: Vídeo/áudio transmitido diretamente entre clientes
4. **Controles**: Mute/unmute sincronizados via eventos

## Segurança

- **Autorização**: Apenas participantes da consulta podem se conectar
- **Validação**: Verificação de permissões antes de criar sala
- **Expiração**: Salas expiram automaticamente após finalização
- **Auditoria**: Eventos registrados para compliance

---

*Última atualização: Janeiro 2025*


