# Arquitetura UX - Sistema de Prontuários Médicos

## Estrutura de Páginas

### 1. `/doctor/consultations` - Lista de Consultas
**Propósito**: Visualizar todas as consultas (agendadas, em andamento, finalizadas)

**Componentes**:
- Lista de consultas do dia/semana
- Filtros: Data, Status, Paciente
- Ações rápidas: Iniciar, Abrir, Finalizar

**Ações**:
- Clicar em consulta → Abre `/doctor/consultations/{id}`

---

### 2. `/doctor/consultations/{appointment_id}` - Página de Consulta ⭐
**Propósito**: Interface principal para DURANTE e PÓS-consulta

**Layout**:
```
┌─────────────────────────────────────────────────────────┐
│ HEADER: Informações da Consulta                        │
│ - Paciente, Data/Hora, Status, Tempo decorrido          │
│ - [Finalizar] [Salvar Rascunho] [Gerar PDF]            │
├─────────────────────────────────────────────────────────┤
│ ┌──────────────┐  ┌──────────────────────────────────┐ │
│ │ SIDEBAR      │  │  ÁREA PRINCIPAL                   │ │
│ │ Prontuário   │  │  Formulário da Consulta           │ │
│ │ Resumido     │  │                                    │ │
│ │              │  │  - Queixa Principal               │ │
│ │ - Alergias   │  │  - Anamnese                       │ │
│ │ - Medicações │  │  - Exame Físico                   │ │
│ │ - Histórico  │  │  - Diagnóstico                    │ │
│ │              │  │  - Prescrição                     │ │
│ │ [Ver         │  │  - Exames                         │ │
│ │  Completo]   │  │  - Anotações                      │ │
│ └──────────────┘  │  - Sinais Vitais                  │ │
│                   └──────────────────────────────────┘ │
└─────────────────────────────────────────────────────────┘
```

**Estados**:
- **Em Andamento**: Todos os campos editáveis
- **Finalizada**: Campos críticos bloqueados, apenas complementos permitidos
- **Rascunho**: Salvo automaticamente, pode continuar depois

**Funcionalidades**:
- Auto-save a cada 30 segundos
- Validação em tempo real
- Alertas de alergias/interações
- Preview de prescrição antes de emitir

---

### 3. `/doctor/patients` - Lista de Pacientes
**Propósito**: Encontrar pacientes rapidamente

**Componentes**:
- Busca rápida (nome, CPF, diagnóstico)
- Filtros: Última consulta, Status, Diagnóstico
- Cards de pacientes com resumo

**Ações**:
- Clicar em paciente → Abre `/doctor/patients/{id}/medical-record`
- Botão "Nova Consulta" → Cria consulta e abre página de consulta

---

### 4. `/doctor/patients/{patient}/medical-record` - Prontuário Completo
**Propósito**: Visualização completa do histórico do paciente

**Layout**:
- Header com dados do paciente
- Tabs: Histórico, Consultas, Diagnósticos, Prescrições, Exames, Documentos, Anotações, Atestados, Evolução
- **SEM formulários de registro** (apenas visualização)

**Ações**:
- Botão "Nova Consulta" → Cria consulta e abre `/doctor/consultations/{id}`
- Botão "Registrar [Ação]" → Abre modal/sidebar para registro rápido
- Links para consultas específicas → Abre `/doctor/consultations/{id}`

---

## Fluxos de Uso

### Fluxo A: Consulta Agendada → Durante → Finalização

```
1. /doctor/consultations
   ↓ (clica em consulta)
2. /doctor/consultations/{id}
   ↓ (preenche durante consulta)
3. Clica "Finalizar"
   ↓
4. /doctor/consultations/{id} (status: completed)
   ↓ (pode complementar)
5. Pronto!
```

### Fluxo B: Acesso via Lista de Pacientes

```
1. /doctor/patients
   ↓ (busca/seleciona paciente)
2. /doctor/patients/{id}/medical-record
   ↓ (visualiza histórico)
3. Clica "Nova Consulta" ou "Registrar Diagnóstico"
   ↓
4. /doctor/consultations/{id} (nova ou existente)
   ↓
5. Preenche e salva
```

### Fluxo C: Complementar Consulta Finalizada

```
1. /doctor/consultations
   ↓ (filtra por "Finalizadas")
2. Clica em consulta finalizada
   ↓
3. /doctor/consultations/{id}
   ↓ (status: completed, campos críticos bloqueados)
4. Adiciona anotações, documentos, atestado
   ↓
5. Salva complementos
```

---

## Decisões de Design

### Por que separar "Consulta" de "Prontuário"?

1. **Contexto Claro**: Médico sabe que está "em uma consulta"
2. **Foco**: Formulário dedicado, sem distrações
3. **Performance**: Carrega apenas dados da consulta atual
4. **Workflow**: Fluxo natural de trabalho

### Por que manter formulários no Prontuário também?

- **Registro Rápido**: Médico pode registrar ação sem abrir consulta
- **Flexibilidade**: Nem tudo precisa estar vinculado a uma consulta
- **Acesso Rápido**: Modal/sidebar para ações rápidas

### Quando usar cada página?

| Ação | Página Recomendada |
|------|-------------------|
| Durante consulta | `/consultations/{id}` |
| Pós-consulta | `/consultations/{id}` |
| Visualizar histórico | `/patients/{id}/medical-record` |
| Registrar ação rápida | Modal no prontuário |
| Nova consulta | Criar → `/consultations/{id}` |

---

## Componentes Reutilizáveis

### 1. `ConsultationForm.vue`
Formulário principal da consulta (usado em `/consultations/{id}`)

### 2. `MedicalRecordSidebar.vue`
Sidebar com resumo do prontuário (usado em `/consultations/{id}`)

### 3. `QuickActionModal.vue`
Modal para ações rápidas (usado em `/patients/{id}/medical-record`)

### 4. `DiagnosisForm.vue`, `PrescriptionForm.vue`, etc.
Formulários específicos (reutilizados em ambas as páginas)

---

### 5. `/doctor/schedule` - Gestão de Agenda
**Propósito**: Configurar disponibilidade e locais de atendimento

**Componentes**:
- Lista de locais de atendimento (teleconsulta, consultório, hospital, clínica)
- Slots de disponibilidade recorrentes (semanal)
- Slots de disponibilidade específicos (datas específicas)
- Datas bloqueadas
- Calendário visual de disponibilidade

**Ações**:
- Criar/editar/excluir locais de atendimento
- Criar/editar/excluir slots recorrentes
- Criar/editar/excluir slots específicos
- Bloquear/desbloquear datas
- Visualizar disponibilidade em calendário

**Rotas**:
- `GET /doctor/schedule` - Visualizar agenda
- `GET /doctor/doctors/{doctor}/schedule` - Visualizar agenda de médico
- `POST /doctor/doctors/{doctor}/schedule/save` - Salvar configuração completa
- `POST /doctor/doctors/{doctor}/locations` - Criar local
- `PUT /doctor/doctors/{doctor}/locations/{location}` - Atualizar local
- `DELETE /doctor/doctors/{doctor}/locations/{location}` - Excluir local
- `POST /doctor/doctors/{doctor}/availability` - Criar slot
- `PUT /doctor/doctors/{doctor}/availability/{slot}` - Atualizar slot
- `DELETE /doctor/doctors/{doctor}/availability/{slot}` - Excluir slot
- `POST /doctor/doctors/{doctor}/blocked-dates` - Bloquear data
- `DELETE /doctor/doctors/{doctor}/blocked-dates/{blockedDate}` - Desbloquear data

---

### 6. `/patient/schedule-consultation` - Agendamento de Consulta
**Propósito**: Paciente agenda consulta com médico

**Componentes**:
- Busca de médicos
- Seleção de médico
- Visualização de disponibilidade
- Seleção de data e horário
- Confirmação de agendamento

**Fluxo**:
1. Buscar médico
2. Visualizar perfil e disponibilidade
3. Selecionar data e horário disponível
4. Confirmar agendamento
5. Receber confirmação

---

### 7. `/patient/medical-records` - Prontuário do Paciente
**Propósito**: Paciente visualiza seu próprio prontuário

**Componentes**:
- Tabs: Consultas, Diagnósticos, Prescrições, Exames, Documentos, Atestados, Sinais Vitais
- Filtros e busca
- Exportação em PDF
- Upload de documentos próprios

**Ações**:
- Visualizar histórico completo
- Exportar prontuário em PDF
- Anexar documentos próprios
- Visualizar prescrições ativas e expiradas

---

### 8. Timeline Profissional (Médico)
**Propósito**: Gerenciar timeline de formação e experiência

**Componentes**:
- Lista de eventos (educação, cursos, certificados, projetos)
- Formulário de criação/edição
- Controle de visibilidade (público/privado)
- Ordenação por prioridade

**Rotas**:
- `GET /api/timeline-events` - Listar eventos
- `POST /api/timeline-events` - Criar evento
- `PUT /api/timeline-events/{timelineEvent}` - Atualizar evento
- `DELETE /api/timeline-events/{timelineEvent}` - Excluir evento

---

## Fluxos de Uso Atualizados

### Fluxo D: Configuração de Agenda (Médico)

```
1. /doctor/schedule
   ↓ (configura locais e disponibilidade)
2. Cria locais de atendimento
   ↓
3. Configura slots recorrentes (ex: toda segunda 8h-12h)
   ↓
4. Adiciona slots específicos (ex: 15/01/2025 14h-18h)
   ↓
5. Bloqueia datas (ex: feriados, férias)
   ↓
6. Salva configuração completa
   ↓
7. Pacientes podem agendar nas disponibilidades configuradas
```

### Fluxo E: Agendamento (Paciente)

```
1. /patient/search-consultations
   ↓ (busca médico)
2. Seleciona médico
   ↓
3. Visualiza disponibilidade via /api/doctors/{doctor}/availability/{date}
   ↓
4. Seleciona data e horário disponível
   ↓
5. Confirma agendamento
   ↓
6. Sistema cria Appointment com status scheduled
   ↓
7. Recebe notificação de confirmação
```

### Fluxo F: Consulta com Prontuário Integrado

```
1. /doctor/consultations/{appointment}
   ↓ (inicia consulta)
2. Status muda para in_progress
   ↓
3. Sala de videoconferência é criada
   ↓
4. Durante consulta:
   - Registra diagnóstico (CID-10)
   - Emite prescrição
   - Solicita exames
   - Registra sinais vitais
   - Cria anotações clínicas
   - Emite atestado (se necessário)
   ↓
5. Salva rascunho (opcional)
   ↓
6. Finaliza consulta
   ↓
7. Status muda para completed
   ↓
8. Dados críticos são bloqueados
   ↓
9. Paciente recebe notificações
```

---

## Componentes Reutilizáveis Atualizados

### 1. `ConsultationForm.vue`
Formulário principal da consulta (usado em `/consultations/{id}`)
- Integrado com prontuário
- Suporta rascunho
- Validação em tempo real

### 2. `MedicalRecordSidebar.vue`
Sidebar com resumo do prontuário (usado em `/consultations/{id}`)
- Histórico resumido
- Alertas (alergias, medicações)
- Links para prontuário completo

### 3. `ScheduleManagement.vue`
Interface de gestão de agenda (usado em `/doctor/schedule`)
- Calendário visual
- Gestão de slots
- Gestão de locais
- Gestão de datas bloqueadas

### 4. `AvailabilityCalendar.vue`
Calendário de disponibilidade (usado em agendamento)
- Visualização de slots disponíveis
- Seleção de data e horário
- Validação de conflitos

### 5. `TimelineEventForm.vue`
Formulário de eventos de timeline
- Tipos: educação, curso, certificado, projeto
- Controle de visibilidade
- Upload de mídia

### 6. `DiagnosisForm.vue`, `PrescriptionForm.vue`, `ExaminationForm.vue`, etc.
Formulários específicos (reutilizados em ambas as páginas)
- Validação de CID-10
- Busca de medicamentos
- Catálogo de exames

---

## Melhorias Futuras

1. **Modo Consulta Compacto**: Tela dividida com vídeo + formulário
2. **Templates**: Salvar templates de consultas comuns
3. **Atalhos**: Teclado shortcuts para ações frequentes
4. **Auto-complete**: CID-10, medicamentos, exames
5. **Rascunho Inteligente**: Recuperar rascunhos automaticamente
6. **Dashboard de Métricas**: Estatísticas de consultas para médicos
7. **Notificações em Tempo Real**: Push notifications para ações importantes
8. **Integração com Laboratórios**: Status automático de exames

---

*Última atualização: Janeiro 2025*
*Versão: 2.0*

