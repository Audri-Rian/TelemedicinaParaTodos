# Fluxo de Consulta - Sistema Completo

## 📋 Visão Geral

Este documento descreve o fluxo completo de consultas médicas no sistema, desde o agendamento até a finalização com prontuário completo.

## 🔄 Fluxo Completo de Consulta

### 1. Agendamento (Paciente)

**Fluxo:**

1. Paciente acessa `/patient/search-consultations`
2. Busca médico por especialidade, nome ou localização
3. Visualiza disponibilidade do médico através de `/api/doctors/{doctor}/availability/{date}`
4. Sistema valida:
    - Slots disponíveis (recorrentes e específicos)
    - Datas bloqueadas
    - Conflitos de horário
5. Paciente seleciona horário e confirma agendamento
6. Sistema cria `Appointment` com status `scheduled`
7. Gera `access_code` único
8. Notificações enviadas para médico e paciente

**Integração com Agenda:**

- Sistema consulta `AvailabilitySlot` (recorrentes e específicos)
- Verifica `BlockedDate` para datas bloqueadas
- Valida conflitos com outros appointments
- Considera `ServiceLocation` do médico

### 2. Preparação da Consulta (Médico)

**Fluxo:**

1. Médico acessa `/doctor/consultations`
2. Visualiza lista de consultas agendadas
3. Pode acessar `/doctor/consultations/{appointment}` para ver detalhes
4. Sistema exibe dados da consulta; a sala de vídeo é provisionada pelo fluxo `Call`/`Room` quando a janela da teleconsulta abre
5. Prontuário do paciente fica acessível

**Funcionalidades Disponíveis:**

- Visualizar histórico completo do paciente
- Ver consultas anteriores
- Acessar diagnósticos, prescrições e exames anteriores
- Ver sinais vitais históricos

### 3. Início da Consulta

**Fluxo:**

1. Médico clica em "Iniciar Consulta" em `/doctor/consultations/{appointment}/start`
2. Sistema:
    - Atualiza status para `in_progress`
    - Registra `started_at`
    - Mantém a consulta disponível para atendimento
    - Usa a janela de videochamada configurada para provisionar `Call`/`Room`
3. Paciente recebe notificação para entrar na chamada quando a sala está disponível
4. Ambos entram por `/appointments/{appointment}/video/session`
5. Laravel emite token JWT e ambos conectam ao SFU MediaSoup via WebSocket/WebRTC

**Prontuário Durante Consulta:**

- Médico pode acessar prontuário completo durante consulta
- Pode registrar dados em tempo real:
    - Diagnósticos (CID-10)
    - Prescrições
    - Exames
    - Anotações clínicas
    - Sinais vitais
    - Atestados
- Sistema permite salvar rascunho (`/doctor/consultations/{appointment}/save-draft`)

### 4. Durante a Consulta

**Funcionalidades Disponíveis:**

- **Videoconferência**: Transmissão WebRTC roteada pelo SFU MediaSoup
- **Prontuário Integrado**: Acesso completo ao prontuário
- **Registro em Tempo Real**:
    - Diagnósticos com CID-10
    - Prescrições digitais
    - Solicitação de exames
    - Anotações clínicas (públicas ou privadas)
    - Registro de sinais vitais
    - Emissão de atestados
- **Anexos**: Upload de documentos médicos
- **Rascunho**: Salvar progresso sem finalizar

**Eventos Registrados:**

- Todas as ações geram `MedicalRecordAuditLog`
- Eventos de estado da videochamada são enviados por Reverb (`VideoCallAvailable`, `VideoCallRequested`, `VideoCallAccepted`, `VideoCallRejected`, `VideoCallEnded`)
- Logs de consulta em `AppointmentLog`

### 5. Finalização da Consulta

**Fluxo:**

1. Médico clica em "Finalizar Consulta" em `/doctor/consultations/{appointment}/finalize`
2. Sistema:
    - Valida se dados críticos foram preenchidos
    - Atualiza status para `completed`
    - Registra `ended_at`
    - Bloqueia edição de dados críticos (diagnóstico, prescrições)
    - Gera PDF da consulta (opcional)
    - Encerra a chamada/sala quando aplicável
    - Dispara `VideoCallEnded` para limpar o estado dos participantes
3. Paciente recebe notificações:
    - Prescrições emitidas
    - Exames solicitados
    - Atestados emitidos
    - Documentos anexados

**Dados Bloqueados Após Finalização:**

- Diagnósticos e CID-10
- Prescrições emitidas
- Exames solicitados

**Dados Permitidos (Complementos):**

- Comentários adicionais
- Anexos de documentos
- Correções com justificativa e auditoria

### 6. Complementos Pós-Consulta

**Fluxo:**

1. Médico pode adicionar complementos em `/doctor/consultations/{appointment}/complement`
2. Sistema registra ação em `MedicalRecordAuditLog`
3. Paciente é notificado de novos dados

### 7. Exportação e Visualização

**Para Médico:**

- Exportar prontuário completo: `/doctor/patients/{patient}/medical-record/export`
- Gerar PDF de consulta: `/doctor/consultations/{appointment}/pdf`
- Visualizar histórico completo do paciente

**Para Paciente:**

- Visualizar prontuário: `/patient/medical-records`
- Exportar próprio prontuário: `/patient/medical-records/export`
- Ver prescrições, exames, atestados e documentos

## 🏗️ Arquitetura do Fluxo

### Integração com Módulos

**Agenda:**

- Validação de disponibilidade via `AvailabilityService`
- Consulta de slots via `ScheduleService`
- Verificação de datas bloqueadas

**Prontuário:**

- Gestão completa via `MedicalRecordService`
- Registro de todas as entidades (Diagnosis, Prescription, Examination, etc.)
- Auditoria completa via `MedicalRecordAuditLog`

**Videoconferência:**

- Chamadas gerenciadas via `Call`
- Salas de mídia gerenciadas via `Room` no SFU
- Jobs automáticos para provisionamento, encerramento por janela e limpeza de chamadas zumbis

**Timeline:**

- Eventos profissionais do médico visíveis no perfil
- Timeline de formação e certificações

## ✅ Solução Implementada: Layout Integrado

### Opção 1: Sidebar com Formulário (Recomendado)

Durante a videochamada, adicionar um botão "Abrir Prontuário" que abre uma sidebar com o formulário:

```
┌─────────────────────────────────────────────────────────┐
│ HEADER: [Vídeo] [Prontuário] [Finalizar]              │
├─────────────────────────────────────────────────────────┤
│ ┌──────────────┐  ┌──────────────────────────────────┐ │
│ │ VÍDEO        │  │  PRONTUÁRIO (Sidebar)            │ │
│ │              │  │  (Abre ao clicar no botão)        │ │
│ │ [Paciente]   │  │                                   │ │
│ │              │  │  - Queixa Principal              │ │
│ │ [Médico]     │  │  - Anamnese                      │ │
│ │              │  │  - Diagnóstico                   │ │
│ │              │  │  - Prescrição                    │ │
│ │              │  │  - Exames                        │ │
│ │              │  │  - Anotações                      │ │
│ └──────────────┘  └──────────────────────────────────┘ │
└─────────────────────────────────────────────────────────┘
```

### Opção 2: Layout Dividido (Alternativa)

Tela dividida automaticamente quando consulta está em andamento:

```
┌─────────────────────────────────────────────────────────┐
│ HEADER: Informações da Consulta                        │
├─────────────────────────────────────────────────────────┤
│ ┌──────────────┐  ┌──────────────────────────────────┐ │
│ │ VÍDEO        │  │  FORMULÁRIO                      │ │
│ │ (50%)        │  │  (50%)                            │ │
│ │              │  │                                   │ │
│ │ [Paciente]   │  │  - Queixa Principal              │ │
│ │              │  │  - Anamnese                      │ │
│ │ [Médico]     │  │  - Diagnóstico                   │ │
│ │              │  │  - Prescrição                    │ │
│ └──────────────┘  └──────────────────────────────────┘ │
└─────────────────────────────────────────────────────────┘
```

## 🎯 Recomendação: Opção 1 (Sidebar)

**Vantagens:**

- ✅ Médico escolhe quando abrir o formulário
- ✅ Vídeo sempre visível (não perde contexto)
- ✅ Pode minimizar/maximizar sidebar
- ✅ Funciona bem em diferentes tamanhos de tela
- ✅ Não sobrecarrega a interface

**Implementação:**

1. Adicionar botão "Abrir Prontuário" na barra superior da videochamada
2. Ao clicar, abre sidebar deslizante com formulário
3. Formulário carrega dados da consulta atual
4. Auto-save funciona normalmente
5. Pode fechar sidebar e continuar apenas com vídeo

## 📋 Fluxo Ideal

```
1. Médico inicia videochamada
   ↓
2. Durante a consulta, clica "Abrir Prontuário"
   ↓
3. Sidebar abre com formulário
   ↓
4. Médico preenche durante a consulta (vídeo continua visível)
   ↓
5. Auto-save salva automaticamente
   ↓
6. Ao finalizar chamada, pode finalizar consulta também
   ↓
7. Tudo salvo e sincronizado
```

## 🔄 Alternativa: Pós-Consulta

Se o médico preferir:

- Fazer a videochamada completa
- Depois abrir `/doctor/consultations/{id}` para preencher
- Isso também funciona, mas é menos eficiente

## 📊 Status de Implementação

### ✅ Implementado

- Sistema completo de agendamento com validação de disponibilidade
- Videoconferência WebRTC com SFU MediaSoup (`Call` + `Room`)
- Prontuário completo integrado durante consulta
- Registro de diagnósticos, prescrições, exames, anotações, atestados e sinais vitais
- Finalização de consulta com bloqueio de edição
- Sistema de complementos pós-consulta
- Exportação de prontuários em PDF
- Auditoria completa de todas as ações
- Integração completa entre módulos

### 🔄 Em Desenvolvimento

- Melhorias de UX na interface integrada
- Notificações em tempo real
- Dashboard de métricas para médicos

## 💡 Melhorias Futuras

**Recomendações:**

- Dashboard de métricas de consultas
- Relatórios automáticos
- Integração com laboratórios para exames
- Sistema de lembretes automáticos
- Chat integrado durante consulta

---

_Última atualização: Janeiro 2025_
_Versão: 2.0_
