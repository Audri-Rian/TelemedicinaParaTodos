# 📖 Documento de Análise do Sistema de Videochamada P2P com PeerJS + Laravel Echo

## 1. Visão Geral

O sistema implementa chamadas de vídeo peer-to-peer (P2P) utilizando **PeerJS** (WebRTC simplificado) e **Laravel Echo/Reverb** (WebSocket para sinalização).

- O **servidor Laravel** atua apenas como **canal de sinalização** (início/aceite/estado da chamada).
- O **PeerJS** cuida da **conexão direta** de áudio/vídeo entre navegadores.
- O **Laravel Echo** entrega eventos em tempo real de **pedido de chamada** e **aceite**.

---

## 2. Fluxo Geral de Comunicação

### Chamador (Usuário A)

1. Inicia a chamada (`callUser`):
    - Captura vídeo local (`displayLocalVideo`).
    - Envia `peerId` ao servidor (`/video-call/request/{user}`).

2. Aguarda resposta do receptor:
    - Escuta evento **RequestVideoCallStatus**.
    - Ao receber `peerId` do destinatário, cria a conexão P2P (`createConnection`).

3. Conexão estabelecida:
    - Recebe stream remoto.
    - Exibe vídeo do outro usuário.

4. Encerramento:
    - Fecha conexão (`endCall`), para tracks de mídia e limpa UI.

### Receptor (Usuário B)

1. Recebe evento **RequestVideoCall** do servidor:
    - Identifica chamador (`fromUser`).
    - Captura vídeo local (`displayLocalVideo`).
    - Notifica servidor que aceitou (`/video-call/request/status/{caller}`).

2. Aguarda ligação P2P do chamador (`peer.on('call')`):
    - Responde com `call.answer(localStream)`.
    - Exibe vídeo remoto do chamador.

3. Encerramento:
    - Fecha chamada e limpa recursos.

---

## 3. Backend (Laravel)

### `VideoCallController`

- **requestVideoCall**: recebe peerId do chamador, emite evento `RequestVideoCall` para o receptor.
- **requestVideoCallStatus**: recebe peerId do receptor, emite evento `RequestVideoCallStatus` para o chamador.

### Eventos

- **RequestVideoCall** → Enviado para canal privado do receptor (`video-call.{id}`), contendo `peerId` do chamador e `fromUser`.
- **RequestVideoCallStatus** → Enviado para canal privado do chamador (`video-call.{id}`), contendo `peerId` do receptor e `fromUser` (quem aceitou).

### Autorização de Canal

```php
Broadcast::channel('video-call.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});
```

Garante que apenas o usuário alvo possa escutar eventos da sua chamada.

---

## 4. Ciclo de Vida no Vue

- **onMounted**: inicializa PeerJS e conecta Echo.
- **onUnmounted**: desconecta Echo, encerra PeerJS e libera dispositivos.
- **displayLocalVideo**: abre câmera/microfone.
- **endCall**: fecha a conexão e limpa recursos.

---

## 5. Checklist de Melhorias

1. Domínio & Banco de Dados (Fonte da Verdade)
   1.1 Amarração de Chamada ao Agendamento

Toda chamada pertence a um appointment: nenhum fluxo de sinalização ou mídia ocorre sem appointment_id.

Gerar callId (UUID) no “request” inicial e manter em todo o ciclo (request/accept/started/end).
Critério de aceite: logs/eventos e front sempre carregam {appointmentId, callId}; eventos sem correspondência são ignorados.

1.2 Campos de Lifecycle no appointments

Preencher started_at ao primeiro started() (quando o media realmente conecta).

Preencher ended_at ao finalizar a chamada (botão encerrar ou timeout).

Atualizar status conforme regras:

scheduled → in_progress (primeiro join real),

in_progress → completed (terminou com started_at),

scheduled → no_show (estourou janela, ninguém entrou),

cancelled / rescheduled via ações externas.
Critério de aceite: históricos coerentes, relatórios por status funcionam.

1.3 Metadados e Auditoria

Usar appointments.metadata (JSON) para:
{"callId":"...", "doctorPeerId":"...", "patientPeerId":"...", "turn":"...", "constraints":{"video":{...}}}

(Opcional) Tabela appointment_call_events para trilha fina:

type: "request"|"accept"|"join"|"leave"|"error"|"mute"|"recording_started"...",

user_id, at, payload (json).
Critério de aceite: é possível reconstituir a sessão (quem, quando, por quanto tempo).

1.4 Índices & Performance

Garantir índices já existentes (ok na sua migration).

(Opcional) Índices em colunas de auditoria se forem muito consultadas.

2. Policies & Autorização (Implementação do 1º pedido)
   2.1 AppointmentPolicy (Obrigatória)

Instalar AppointmentPolicy e registrar em AuthServiceProvider.

Aplicar authorize() nos endpoints:

requestCall → $this->authorize('requestCall', $appointment)

acceptCall → $this->authorize('acceptCall', $appointment)

started → $this->authorize('started', $appointment)

end → $this->authorize('end', $appointment)

Regras mínimas da Policy (já fornecidas):

O usuário deve ser médico ou paciente do appointment (ligado por doctor.user_id/patient.user_id).

Janela de acesso: lead, duration, grace (via config/telemedicine.php).

Status permitido: scheduled/in_progress.

Médico elegível: doctors.status='active' e (opcional) licença válida.

Sem sessão concorrente: lock por appointment_id.
Critério de aceite: chamadas fora de contexto/horário/status são 403.

2.2 Rate Limiting & Anti-spam

Aplicar Throttle (ex.: ->middleware('throttle:video-call')) nas rotas request/accept.

Tratar “ocupado” (busy) se já houver sala ativa (ver item Lock/Concorrência).
Critério de aceite: spam de request não causa flood no outro usuário.

2.3 Locks/Concorrência

Implementar Redis lock por appointment:{id}:active_call na criação da sala.

Liberar lock no end/timeout/erro.
Critério de aceite: só uma sessão ativa por appointment.

3. Sinalização & Back-end (Echo/Reverb + Endpoints)
   3.1 Canais de Broadcast

Canal por consulta: private("appointments.{id}").

(Opcional) Canal por usuário para notificações globais: private("users.{id}").
Critério de aceite: nenhum evento é emitido em canal de usuário errado.

3.2 Eventos com broadcastWith() (payload mínimo)

AppointmentCallRequested {appointmentId, callId, from:{id,name,role}, peerId, at}

AppointmentCallAccepted {appointmentId, callId, by:{id,role}, peerId}

AppointmentCallStarted {appointmentId, callId, who:{id}, at}

AppointmentCallEnded {appointmentId, callId, reason?, duration?, at}

(Opcional) AppointmentBusy, AppointmentCallCancelled, AppointmentNoShow
Critério de aceite: não enviar modelos Eloquent inteiros; apenas DTOs/arrays.

3.3 Endpoints REST (exemplo)

POST /appointments/{id}/call/request → cria callId, checa Policy, emite Requested.

POST /appointments/{id}/call/accept → checa Policy, emite Accepted.

POST /appointments/{id}/call/started → Policy; se 1º, seta started_at, status='in_progress'.

POST /appointments/{id}/call/end → Policy; seta ended_at e status final.
Critério de aceite: fluxos cobrem todas as transições de estado do appointment.

3.4 Regras de Janela & Timezone

Salvar scheduled_at em UTC.

Validar janela no back; converter para fuso do usuário no front para exibição.

Parametrizar lead/duration/grace via config/telemedicine.php.
Critério de aceite: ninguém entra cedo demais/tarde demais.

3.5 Cancelamento & Timeout

Endpoint cancel (opcional) para abortar ringing (emite AppointmentCallCancelled).

Timeout servidor-side (job) para marcar no_show quando aplicável.
Critério de aceite: chamadas não atendidas são encerradas e refletidas no DB.

4. Front-end (Vue) — Estado, PeerJS, Echo
   4.1 Máquina de Estados (única fonte de verdade)

Definir enum:
idle → ringing_out → ringing_in → connecting → in_call → ending → ended | error

Transições acionadas exclusivamente por:

Ações do usuário (request/accept/cancel/end),

Respostas HTTP,

Eventos Echo (Requested/Accepted/Started/Ended),

Eventos Peer (call, stream, close, error).
Critério de aceite: nenhum estado “fantasma”; UI sempre coerente.

4.2 Listeners Únicos & Contexto

Um peer.on('call') por tela; usar off() no onUnmounted.

Filtrar todos os eventos por {appointmentId, callId}; ignorar o resto.

Guardar peerCall atual; impedir iniciar outra enquanto existir ativa.
Critério de aceite: sem listener leak e sem “sobreposição” de ligações.

4.3 Timeouts & Cancel

Timeout de ringing_out (25–30s) → mostra “sem resposta” + emite cancel.

Botão Cancelar enquanto ringing_out.

Se receptor está in_call, recebedor emite busy.
Critério de aceite: usuário nunca fica “preso” no ringing.

4.4 Integração com Echo

Inscrever-se em private("appointments.{id}") na montagem da tela.

stopListening/leave no unmount (não derrubar Echo global).

Reagir a:

Requested (se você for o destinatário) → mostrar ringing_in.

Accepted (se você for o chamador) → fazer peer.call.

Started → exibir “conectado”.

Ended → acionar endCall() local.
Critério de aceite: sincronismo imediato entre os dois lados.

5. Mídia (WebRTC/PeerJS) & UX
   5.1 Captura e Permissões

getUserMedia com permissões tratadas (erros exibidos em toast/modal).

Vídeos com autoplay + playsinline (mobile) e muted para local.

Botões: mute/unmute, toggle camera, switch camera (mobile), screen share com replaceTrack.
Critério de aceite: usuário controla sua mídia de forma clara.

5.2 Dispositivos & Preferências

enumerateDevices() e persistência das preferências por usuário (localStorage/DB).

Constraints configuráveis (resolução, fps, prioridade de largura x altura).
Critério de aceite: seleção de câmera/mic/alto-falante funciona e é lembrada.

5.3 Conectividade & TURN

Configurar PeerJS com iceServers (STUN + TURN confiável).

Tratar peer.on('disconnected') com peer.reconnect() se apropriado.

Exibir indicador de “reconectando” na UI.
Critério de aceite: chamadas funcionam em CGNAT/rede corporativa.

5.4 Eventos de Chamada

call.on('stream') → setar remoteVideoRef.srcObject.

call.on('close') → endCall() + emitir Ended se você encerrou.

call.on('error') → mensagem clara + transição para error.
Critério de aceite: falhas não travam a UI e deixam rastro.

6. Segurança & Compliance
   6.1 Policies em Tudo

Todas as rotas de chamada com authorize() (ver seção 2).

403 com mensagens utilitárias (fora da janela, não é participante, etc.).
Critério de aceite: pentest simples não “vaza” chamadas.

6.2 Privacidade & Gravação

Consentimento antes de gravar; exibir badge “gravando”.

video_recording_url salvo via webhook; controle de acesso para download.

Política de retenção e remoção.
Critério de aceite: aderência mínima a boas práticas de privacidade.

6.3 HTTPS & Cookies

Produção sempre HTTPS (getUserMedia exige).

Cookies de sessão “secure”/“httponly”.
Critério de aceite: sem problemas de permissão/miçangas em produção.

7. Observabilidade, Logs & Qualidade
   7.1 Logs Estruturados

Registrar appointmentId, callId, userId, event, ts, latency.

Logs de erro com pilha e payloads mínimos.
Critério de aceite: é possível diagnosticar falhas em 1–2 minutos.

7.2 Métricas & KPIs

Taxa de sucesso da conexão (%),

Tempo médio até conectar (s),

% timeouts, % busy,

Duração média da consulta.
Critério de aceite: dashboard com números essenciais.

7.3 Testes

Caminhos felizes: desktop↔desktop, desktop↔mobile, Wi-Fi/4G.

Erros: negar câmera, sem mic, queda de rede, TURN offline.

Estados: cancel durante ringing, end durante connecting, refresh da página.
Critério de aceite: todos cenários críticos cobertos.

8. Operação & Resiliência
   8.1 Jobs/Cron

Marcar no_show quando expirar janela sem started_at.

Fechar chamadas zumbis (sem mídia) após X minutos.
Critério de aceite: banco não fica com pendências inconsistentes.

8.2 Degradação Elegante

Se TURN indisponível, avisar “rede pode impedir a conexão”.

Permitir fallback (reagendar/telefone).
Critério de aceite: o usuário sempre sabe o que fazer.

8.3 Playbooks

“TURN down”: como verificar e restaurar.

“Ninguém conecta”: checklist (STUN/TURN, firewall, DNS).

“Echo sem eventos”: checar auth do canal, chaves Reverb, SSL WS.
Critério de aceite: tempo médio de recuperação minimizado.

9. DevEx (DX) & Organização
   9.1 Tipagem & DTOs

TS forte no front (interfaces de eventos).

FormRequest no Laravel validando payloads (peerId, role, etc.).
Critério de aceite: zero any/mixed em paths críticos.

9.2 Feature Flags & Config

lead/duration/grace configuráveis (“sem deploy”).

Flag para “aceite automático” (dev) vs “Atender/Recusar” (prod).
Critério de aceite: ajustes finos sem reimplantar.

9.3 Código Limpo & Reuso

Hook/composable Vue para PeerJS (in/outbound handlers).

Serviço EchoService que normaliza inscrição/saída de canais.
Critério de aceite: módulos coesos, baixo acoplamento.

---

## 6. Evolução do Módulo de VideoCall

1. Situação Antes da Checklist

O módulo de videochamada inicial foi desenvolvido com foco em estabelecer a comunicação P2P básica entre dois usuários (via PeerJS) e em propagar eventos de request/accept pelo backend (via Laravel Echo/Reverb).

Ele permitia que um usuário ligasse para outro, que por sua vez podia aceitar a chamada. A mídia (áudio e vídeo) fluía diretamente entre os navegadores, enquanto o servidor atuava apenas como canal de sinalização.

Limitações da versão inicial:

Não havia integração com o banco de dados → chamadas não estavam vinculadas a um agendamento (appointments).

Qualquer usuário podia chamar outro a qualquer momento, sem validação de horário ou vínculo médico/paciente.

Sem controle de estado no domínio: campos started_at, ended_at e status da tabela appointments nunca eram atualizados.

Sem lógica de janela de acesso (ex.: 10 min antes ou 15 min depois do horário).

Payloads inseguros: os eventos de broadcast transmitiam o modelo inteiro do usuário, com campos que não precisariam sair do backend.

Sem controle de concorrência: era possível abrir múltiplas salas paralelas para a mesma consulta.

UX limitada: não havia Atender/Recusar, nem timeout/cancelamento, nem indicadores de estado (“ocupado”, “aguardando paciente/médico”).

Ausência de logs/auditoria: nenhuma trilha sobre quem entrou/saiu, nem métricas de sucesso/falha.

Sem suporte robusto de rede: dependência apenas de STUN padrão do PeerJS, sem TURN configurado.

2. Situação Após a Checklist

Com a aplicação da Checklist, o sistema de videochamadas deixa de ser um recurso “solto” e passa a ser um módulo integrado ao domínio de consultas médicas, com regras de negócio e governança.

Novas capacidades:

🗄️ Integração com o banco

Toda chamada está vinculada a um appointment.

Campos started_at, ended_at e status são atualizados conforme a evolução da chamada (scheduled → in_progress → completed/no_show).

Possibilidade de salvar video_recording_url e metadados técnicos (peerIds, ICE, device info).

Auditoria opcional em tabela appointment_call_events.

🛡️ Segurança & Políticas

Implantada a AppointmentPolicy, que garante:

Apenas médico/paciente relacionados podem participar.

Apenas em janela de tempo válida (lead/duration/grace).

Apenas em status scheduled ou in_progress.

Apenas médicos ativos/elegíveis podem iniciar.

Locks de concorrência: apenas uma sala ativa por appointment.

Payloads de eventos agora são mínimos e explícitos, usando broadcastWith().

📡 Fluxo de sinalização

Eventos padronizados: AppointmentCallRequested, Accepted, Started, Ended, Cancelled, Busy.

Todos os eventos carregam appointmentId e callId.

Timeouts e cancelamentos tratados, evitando chamadas “penduradas”.

🎥 Experiência de uso

Tela de toque com Atender/Recusar e som de ring.

Cancelamento durante ringing e retorno de status “ocupado” se outra chamada estiver em andamento.

Indicadores: aguardando médico/paciente, reconectando, gravando.

Controles completos de mídia: mute/unmute, troca de câmera, compartilhamento de tela.

🌐 Rede & Infra

Configuração de STUN/TURN confiáveis, garantindo funcionamento mesmo em redes restritas (corporativas/3G/CGNAT).

Reconexão automática (peer.reconnect) em casos de perda temporária.

📊 Observabilidade

Logs estruturados de cada etapa (request, accept, started, ended) com appointmentId, callId, userId.

Métricas de sucesso/falha, tempo médio até conectar, duração média de consultas.

Jobs automáticos para marcar no_show ou encerrar chamadas zumbis.

📌 **Conclusão:**  
O sistema já cumpre a função principal de estabelecer chamadas P2P, mas pode ser refinado em **fluxo, UX, segurança e robustez** para lidar com casos de erro, redes adversas e múltiplas chamadas simultâneas.
