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

## Melhorias Futuras

1. **Modo Consulta Compacto**: Tela dividida com vídeo + formulário
2. **Templates**: Salvar templates de consultas comuns
3. **Atalhos**: Teclado shortcuts para ações frequentes
4. **Auto-complete**: CID-10, medicamentos, exames
5. **Rascunho Inteligente**: Recuperar rascunhos automaticamente

