# Diagrama de Fluxo de Videoconferência - Telemedicina Para Todos

## Fluxo de Videoconferência WebRTC com SFU

Este diagrama mostra o fluxo atual de videochamada: Laravel controla negócio, Reverb avisa estado e o SFU MediaSoup transporta áudio/vídeo.

```mermaid
sequenceDiagram
    participant D as Doctor
    participant DF as Doctor Frontend
    participant P as Patient
    participant PF as Patient Frontend
    participant C as Laravel Controllers
    participant S as CallManagerService
    participant G as MediaGateway
    participant SFU as MediaSoup SFU
    participant R as Laravel Reverb
    participant DB as Database

    Note over D,SFU: Chamada agendada

    S->>DB: Busca appointments dentro da janela
    S->>DB: Cria ou reutiliza Call scheduled
    S->>G: createRoom(callId)
    G->>SFU: POST /rooms
    SFU-->>G: room_id, sfu_node, media_ws_url
    G-->>S: MediaRoomData
    S->>DB: Persiste Room
    S->>R: Broadcast VideoCallAvailable
    R-->>DF: Evento em video-call.{doctorUserId}
    R-->>PF: Evento em video-call.{patientUserId}

    D->>DF: Entra na videochamada
    DF->>C: POST /appointments/{appointment}/video/session
    C->>S: provisionAppointmentCall(appointment)
    S-->>C: Call + Room
    C->>C: Gera JWT do SFU
    C-->>DF: token, sfu_ws_url, room_id, role, window

    P->>PF: Entra na videochamada
    PF->>C: POST /appointments/{appointment}/video/session
    C->>S: provisionAppointmentCall(appointment)
    S-->>C: Call + Room existente
    C->>C: Gera JWT do SFU
    C-->>PF: token, sfu_ws_url, room_id, role, window

    Note over DF,SFU: Sinalização técnica de mídia

    DF->>SFU: WebSocket join(token)
    SFU-->>DF: RTP capabilities
    DF->>SFU: createWebRtcTransport(send/recv)
    DF->>SFU: connectWebRtcTransport + produce

    PF->>SFU: WebSocket join(token)
    SFU-->>PF: RTP capabilities
    PF->>SFU: createWebRtcTransport(send/recv)
    PF->>SFU: connectWebRtcTransport + produce

    SFU-->>DF: newProducer / consume / resumeConsumer
    SFU-->>PF: newProducer / consume / resumeConsumer
    SFU-->>DF: Áudio/vídeo remoto
    SFU-->>PF: Áudio/vídeo remoto

    Note over D,SFU: Encerramento

    D->>DF: Encerra chamada
    DF->>C: POST /calls/{call}/end
    C->>S: endCall(call, user)
    S->>G: destroyRoom(roomId)
    G->>SFU: DELETE /rooms/{roomId}
    S->>DB: status ended, ended_at, closed_reason
    S->>R: Broadcast VideoCallEnded
    R-->>DF: Limpar estado local
    R-->>PF: Limpar estado local
    DF->>SFU: Fecha WebSocket/transports
    PF->>SFU: Fecha WebSocket/transports
```

## Chamada ad-hoc

```mermaid
sequenceDiagram
    participant P as Patient
    participant PF as Patient Frontend
    participant D as Doctor
    participant DF as Doctor Frontend
    participant C as CallController
    participant S as CallManagerService
    participant G as MediaGateway
    participant SFU as MediaSoup SFU
    participant R as Laravel Reverb

    P->>PF: Solicita chamada para médico
    PF->>C: POST /calls
    C->>S: createCall(patient, doctor)
    S->>R: Broadcast VideoCallRequested
    R-->>DF: Chamada recebida

    D->>DF: Aceita chamada
    DF->>C: POST /calls/{call}/accept
    C->>S: acceptCall(call, doctorUser)
    S->>G: createRoom(callId)
    G->>SFU: POST /rooms
    SFU-->>G: room_id, media_ws_url
    S->>S: Gera JWT
    S->>R: Broadcast VideoCallAccepted(token, sfu_ws_url)
    R-->>DF: Conectar ao SFU
    R-->>PF: Conectar ao SFU
```

## Componentes

- **Laravel:** `CallController`, `AppointmentVideoSessionController`, `CallManagerService`.
- **Persistência:** `calls` e `rooms`.
- **Eventos Reverb:** `VideoCallAvailable`, `VideoCallRequested`, `VideoCallAccepted`, `VideoCallRejected`, `VideoCallEnded`.
- **Frontend:** `useVideoCall.ts`, `useVideoCallSession.ts`, `useSfu.ts`, `videoCall` store.
- **SFU:** MediaSoup via WebSocket para sinalização técnica e WebRTC para mídia.

## Observações

- Reverb não transporta SDP, ICE, `peerId` nem mídia.
- O `roomId` confiável vem do backend/SFU e fica no JWT ou em resposta autenticada.
- O token do SFU é curto e deve ser renovado pelo backend quando necessário.
