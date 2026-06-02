# Fluxo UX do Paciente - Diagrama Visual

## 🎯 Fluxo Principal de Agendamento

```
┌─────────────────────────────────────────────────────────────────────────┐
│                    FLUXO COMPLETO: BUSCA → CONSULTA                     │
└─────────────────────────────────────────────────────────────────────────┘

┌─────────────────┐
│   DASHBOARD     │
│   (Paciente)    │
└────────┬────────┘
         │
         │ Clica "Buscar Médicos"
         ▼
┌─────────────────────────────────────────────────────────────────────┐
│  /patient/search-consultations                                      │
│  ─────────────────────────────────────────────────────────────────── │
│  • Busca por nome, especialidade, sintoma                           │
│  • Filtros: especialidade, convênio, data, telemedicina, "atende agora" │
│  • Lista de médicos disponíveis                                     │
│  • Cada card mostra:                                                 │
│    - Nome, especialidade, avaliação                                 │
│    - Badge "Atende Online" / "Disponível Agora"                     │
│    - Botão "Agendar Consulta"                                      │
└────────┬─────────────────────────────────────────────────────────────┘
         │
         │ Clica "Agendar Consulta" (doctor_id via query param)
         ▼
┌─────────────────────────────────────────────────────────────────────┐
│  /patient/schedule-consultation?doctor_id={id}                      │
│  ─────────────────────────────────────────────────────────────────── │
│  • Painel esquerdo: Info do médico selecionado                     │
│  • Painel direito:                                                 │
│    - Seleção tipo: Online / Presencial                              │
│    - Calendário com datas disponíveis                               │
│    - Horários disponíveis (baseado em availability_schedule)       │
│    - Resumo: médico, data, horário, valor                           │
│  • Botão "Confirmar Agendamento"                                    │
└────────┬─────────────────────────────────────────────────────────────┘
         │
         │ POST /appointments
         │ { doctor_id, patient_id, scheduled_at, type }
         ▼
┌─────────────────────────────────────────────────────────────────────┐
│  Back-End: AppointmentService::create()                             │
│  ─────────────────────────────────────────────────────────────────── │
│  ✓ Valida doctor ativo                                              │
│  ✓ Valida patient completo                                           │
│  ✓ Valida conflito de horário                                       │
│  ✓ Cria appointment (status: 'scheduled')                           │
│  ✓ Observer gera access_code                                        │
│  ✓ Observer cria log de auditoria                                   │
└────────┬─────────────────────────────────────────────────────────────┘
         │
         │ Redirect para /appointments/{id}
         ▼
┌─────────────────────────────────────────────────────────────────────┐
│  /appointments/{id}                                                 │
│  ─────────────────────────────────────────────────────────────────── │
│  • Status Badge: "Agendada" (amarelo)                               │
│  • Informações: médico, data, horário, tipo                        │
│  • Ações disponíveis:                                               │
│    - Cancelar (se dentro da janela permitida)                      │
│    - Reagendar                                                       │
│    - Ver Detalhes                                                    │
└─────────────────────────────────────────────────────────────────────┘
```

---

## ⏱️ Fluxo: Início da Consulta

```
┌─────────────────────────────────────────────────────────────────────┐
│  /patient/dashboard ou /patient/history-consultations              │
│  ─────────────────────────────────────────────────────────────────── │
│  • Lista appointments com status "scheduled"                        │
│  • Badge de status em cada card                                      │
│  • Para appointment próximo (dentro de lead_minutes):                │
│    - Botão "Iniciar Consulta" aparece                              │
└────────┬─────────────────────────────────────────────────────────────┘
         │
         │ Clica "Iniciar Consulta"
         │ (ou aguarda horário e aparece automaticamente)
         ▼
┌─────────────────────────────────────────────────────────────────────┐
│  POST /appointments/{id}/start                                      │
│  ─────────────────────────────────────────────────────────────────── │
│  Back-End:                                                           │
│  • Policy valida: status permitido? janela de tempo?                │
│  • Service atualiza: status → 'in_progress', started_at = now()     │
│  • Cria log de início                                               │
│  • Broadcast WebSocket: AppointmentStatusChanged                    │
└────────┬─────────────────────────────────────────────────────────────┘
         │
         │ WebSocket notifica ambos (médico e paciente)
         │ Redirect para /patient/video-call?appointment={id}
         ▼
┌─────────────────────────────────────────────────────────────────────┐
│  /patient/video-call                                                │
│  ─────────────────────────────────────────────────────────────────── │
│  • Status Badge: "Em Andamento" (azul)                              │
│  • Vídeo chamada ativa (SFU/WebRTC)                                 │
│  • Controles: áudio, vídeo, compartilhar tela                       │
│  • Botão "Finalizar Consulta"                                       │
└────────┬─────────────────────────────────────────────────────────────┘
         │
         │ Clica "Finalizar Consulta"
         ▼
┌─────────────────────────────────────────────────────────────────────┐
│  POST /appointments/{id}/end                                        │
│  ─────────────────────────────────────────────────────────────────── │
│  Back-End:                                                           │
│  • Policy valida: status é 'in_progress'?                           │
│  • Service atualiza: status → 'completed', ended_at = now()         │
│  • Cria log de finalização                                          │
│  • Broadcast WebSocket: AppointmentStatusChanged                    │
└────────┬─────────────────────────────────────────────────────────────┘
         │
         │ Redirect para /patient/consultation-details/{id}
         ▼
┌─────────────────────────────────────────────────────────────────────┐
│  /patient/consultation-details/{id}                                 │
│  ─────────────────────────────────────────────────────────────────── │
│  • Status Badge: "Concluída" (verde)                                │
│  • Resumo clínico / Laudo médico                                    │
│  • Prescrições e receitas                                           │
│  • Anexos / Documentos                                              │
│  • Timeline de eventos (dos logs)                                    │
│  • Feedback e avaliação                                            │
└─────────────────────────────────────────────────────────────────────┘
```

---

## ❌ Fluxo: Cancelamento

```
┌─────────────────────────────────────────────────────────────────────┐
│  /appointments/{id} ou /patient/history-consultations              │
│  ─────────────────────────────────────────────────────────────────── │
│  • Status: "scheduled" ou "rescheduled"                             │
│  • Botão "Cancelar" (se dentro da janela permitida)                │
└────────┬─────────────────────────────────────────────────────────────┘
         │
         │ Clica "Cancelar" → Modal de confirmação
         │ (opcional: campo "Motivo do cancelamento")
         ▼
┌─────────────────────────────────────────────────────────────────────┐
│  POST /appointments/{id}/cancel                                     │
│  { reason?: string }                                                │
│  ─────────────────────────────────────────────────────────────────── │
│  Back-End:                                                           │
│  • Policy valida: status permitido? dentro da janela?              │
│  • Service atualiza: status → 'cancelled'                           │
│  • Cria log de cancelamento                                         │
│  • Broadcast WebSocket: AppointmentStatusChanged                    │
└────────┬─────────────────────────────────────────────────────────────┘
         │
         │ Atualiza página ou redirect
         ▼
┌─────────────────────────────────────────────────────────────────────┐
│  Status atualizado: "Cancelada" (vermelho)                          │
│  • Botão "Cancelar" desaparece                                      │
│  • Apenas "Ver Detalhes" disponível                                │
└─────────────────────────────────────────────────────────────────────┘
```

---

## 🔄 Fluxo: Reagendamento

```
┌─────────────────────────────────────────────────────────────────────┐
│  /appointments/{id}                                                 │
│  ─────────────────────────────────────────────────────────────────── │
│  • Status: "scheduled" ou "rescheduled"                             │
│  • Botão "Reagendar"                                                │
└────────┬─────────────────────────────────────────────────────────────┘
         │
         │ Clica "Reagendar" → Modal ou nova página
         │ Seleciona nova data/horário
         ▼
┌─────────────────────────────────────────────────────────────────────┐
│  POST /appointments/{id}/reschedule                                 │
│  { scheduled_at: "2025-11-15T14:00:00Z" }                           │
│  ─────────────────────────────────────────────────────────────────── │
│  Back-End:                                                           │
│  • Policy valida: status permitido?                                 │
│  • Service valida: novo horário não conflita?                       │
│  • Service atualiza: status → 'rescheduled', scheduled_at = novo    │
│  • Cria log de reagendamento                                        │
│  • Broadcast WebSocket: AppointmentStatusChanged                    │
└────────┬─────────────────────────────────────────────────────────────┘
         │
         │ Atualiza página
         ▼
┌─────────────────────────────────────────────────────────────────────┐
│  Status atualizado: "Reagendada" (roxo)                             │
│  • Nova data/horário exibida                                        │
│  • Ações: Cancelar, Reagendar Novamente, Iniciar (quando disponível)│
└─────────────────────────────────────────────────────────────────────┘
```

---

## 📊 Estados e Transições Visuais

```
┌──────────────┐
│   SCHEDULED  │ ◄─── Criação inicial
│  (Agendada)  │
└──────┬───────┘
       │
       ├───► [Iniciar] ────► ┌──────────────┐
       │                     │ IN_PROGRESS   │
       │                     │ (Em Andamento)│
       │                     └──────┬───────┘
       │                            │
       │                            ├───► [Finalizar] ────► ┌──────────────┐
       │                            │                       │  COMPLETED    │
       │                            │                       │  (Concluída)  │
       │                            │                       └──────────────┘
       │                            │
       ├───► [Cancelar] ────► ┌──────────────┐
       │                      │  CANCELLED    │
       │                      │  (Cancelada)  │
       │                      └──────────────┘
       │
       └───► [Reagendar] ────► ┌──────────────┐
                                │ RESCHEDULED  │
                                │ (Reagendada) │
                                └──────┬───────┘
                                       │
                                       ├───► [Iniciar] ────► IN_PROGRESS
                                       ├───► [Cancelar] ────► CANCELLED
                                       └───► [Reagendar] ────► RESCHEDULED (novo horário)
```

---

## 🎨 Componentes Visuais por Status

### Status: `scheduled` (Agendada)

```
┌─────────────────────────────────────────┐
│  📅 15 de Novembro, 2024 - 14:00       │
│  👨‍⚕️ Dr. João Silva - Cardiologista     │
│  🟡 Agendada                            │
│  ┌──────────┐  ┌──────────┐            │
│  │ Cancelar │  │ Reagendar│            │
│  └──────────┘  └──────────┘            │
└─────────────────────────────────────────┘
```

### Status: `in_progress` (Em Andamento)

```
┌─────────────────────────────────────────┐
│  📹 Consulta em Andamento              │
│  🔵 Em Andamento                        │
│  ┌──────────────────────────┐          │
│  │   [Vídeo Chamada Ativa]   │          │
│  └──────────────────────────┘          │
│  ┌──────────────────────────┐          │
│  │  Finalizar Consulta      │          │
│  └──────────────────────────┘          │
└─────────────────────────────────────────┘
```

### Status: `completed` (Concluída)

```
┌─────────────────────────────────────────┐
│  ✅ Consulta Finalizada                 │
│  🟢 Concluída                           │
│  ┌──────────────────────────┐          │
│  │  Ver Detalhes            │          │
│  └──────────────────────────┘          │
│  ┌──────────────────────────┐          │
│  │  Avaliar Consulta        │          │
│  └──────────────────────────┘          │
└─────────────────────────────────────────┘
```

### Status: `cancelled` (Cancelada)

```
┌─────────────────────────────────────────┐
│  ❌ Consulta Cancelada                  │
│  🔴 Cancelada                           │
│  ┌──────────────────────────┐          │
│  │  Ver Detalhes            │          │
│  └──────────────────────────┘          │
└─────────────────────────────────────────┘
```

---

## 🔔 Notificações e Feedback

### Sucesso

- ✅ "Agendamento criado com sucesso!"
- ✅ "Consulta iniciada com sucesso!"
- ✅ "Consulta finalizada com sucesso!"
- ✅ "Consulta cancelada com sucesso!"
- ✅ "Consulta reagendada com sucesso!"

### Erro

- ❌ "Não foi possível agendar. Horário já ocupado."
- ❌ "Não foi possível iniciar. Aguarde o horário da consulta."
- ❌ "Não foi possível cancelar. Prazo para cancelamento expirado."
- ❌ "Não foi possível reagendar. Novo horário conflita com outro agendamento."

### Validação

- ⚠️ "Selecione uma data e horário."
- ⚠️ "Este horário não está mais disponível."
- ⚠️ "Você só pode cancelar até 2 horas antes da consulta."

---

## 📱 Responsividade e Acessibilidade

### Mobile

- Cards de médicos em coluna única
- Calendário adaptado para touch
- Botões de ação em tamanho adequado para toque
- Vídeo chamada em tela cheia

### Desktop

- Grid de médicos (3 colunas)
- Calendário lado a lado com horários
- Painel lateral com resumo
- Vídeo chamada com controles laterais

---

_Última atualização: Novembro 2025_
